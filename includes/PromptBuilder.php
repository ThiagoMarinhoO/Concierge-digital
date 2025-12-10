<?php

class PromptBuilder
{
    private $chatbot_name;
    private $instructions = [];
    private $knowledge_base = [];
    private $personality_text = '';

    public function __construct($chatbot_name)
    {
        $this->chatbot_name = $chatbot_name;
    }

    public function setPersonality($personality_text)
    {
        $this->personality_text = $personality_text;
    }

    public function addInstruction($instruction)
    {
        if (!empty(trim($instruction))) {
            $this->instructions[] = trim($instruction);
        }
    }

    public function addKnowledge($content)
    {
        if (!empty(trim($content))) {
            $this->knowledge_base[] = trim($content);
        }
    }

    public function addScrapedContent($html)
    {
        $clean_text = $this->clean_crawler_text($html);
        if (!empty($clean_text)) {
            $this->knowledge_base[] = $clean_text;
        }
    }

    private function clean_crawler_text($html)
    {
        if (empty($html)) return '';

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Remover scripts, styles, e elementos irrelevantes
        $nodes = $xpath->query('//script | //style | //noscript | //iframe | //svg | //header | //footer | //nav');
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }

        // Tentar pegar apenas o conteúdo principal
        $main = $xpath->query('//main | //article | //div[@id="content"] | //div[@class="content"]');
        if ($main->length > 0) {
            $content_node = $main->item(0);
        } else {
            $content_node = $dom->getElementsByTagName('body')->item(0);
        }

        if (!$content_node) return '';

        // Extrair texto e limpar espaços múltiplos
        $text = $content_node->textContent;
        $text = preg_replace('/\s+/', ' ', $text); // Remove quebras de linha e tabs excessivos
        $text = trim($text);

        return $text;
    }

    private $company_name = '';
    private $main_function = 'Atendimento ao Cliente';

    public function setCompanyName($name) {
        $this->company_name = $name;
    }

    public function setMainFunction($function) {
        $this->main_function = $function;
    }

    public function build()
    {
        // Fallback se o nome da empresa não for definido
        $company = !empty($this->company_name) ? $this->company_name : $this->chatbot_name;

        $xml = "<system_identity>\n";
        $xml .= "  Você é o assistente virtual da {$company}.\n";
        $xml .= "  Seu nome é {$this->chatbot_name}.\n";
        $xml .= "  Sua função principal é: {$this->main_function}.\n";
        $xml .= "</system_identity>\n\n";

        $xml .= "<behavior_profile>\n";
        if (!empty($this->personality_text)) {
            $xml .= "  ### PERSONALIDADE ATIVA\n";
            $xml .= "  {$this->personality_text}\n";
        }
        $xml .= "</behavior_profile>\n\n";

        $xml .= "<interaction_rules>\n";
        // As regras agora vêm 100% do painel (Regras Gerais + Opções)
        if (!empty($this->instructions)) {
             foreach ($this->instructions as $rule) {
                 $xml .= "  - {$rule}\n";
             }
        }
        $xml .= "</interaction_rules>\n\n";

        $xml .= "<context_data>\n";
        $xml .= "  ### INFORMAÇÕES DO SITE (RAG TEXT)\n";
        if (!empty($this->knowledge_base)) {
            foreach ($this->knowledge_base as $kb) {
                $xml .= "  {$kb}\n";
            }
        }
        $xml .= "</context_data>\n\n";

        $xml .= "<response_format>\n";
        $xml .= "  Responda de forma concisa e direta.\n";
        $xml .= "</response_format>";

        return $xml;
    }
}
