<?php

class PromptBuilder
{
    private $chatbot_name;
    private $company_name = '';
    private $main_function = 'Atendimento ao Cliente';
    private $personality_text = '';
    
    // Categorized Storage
    private $instructions = []; // General Rules
    private $negative_constraints = [];
    private $business_hours = [];
    private $service_links = [];
    private $knowledge_base = [];

    public function __construct($chatbot_name)
    {
        $this->chatbot_name = $chatbot_name;
    }

    public function setCompanyName($name) {
        $this->company_name = $name;
    }

    public function setMainFunction($function) {
        $this->main_function = $function;
    }

    public function setPersonality($personality_text)
    {
        $this->personality_text = $personality_text;
    }

    public function addInstruction($instruction)
    {
        // 1. Sanitization
        $clean_instruction = trim(stripslashes(strip_tags($instruction)));
        
        if (empty($clean_instruction)) {
            return;
        }

        // 2. Semantic Routing (Logic Switch)
        $lower_input = mb_strtolower($clean_instruction, 'UTF-8');

        // Case A: Negative Constraints
        if ($this->containsAny($lower_input, ['proibido', 'nunca', 'evite', 'não pode', 'jamais'])) {
            $this->negative_constraints[] = $clean_instruction;
            return;
        }

        // Case B: Business Hours / Operations
        if ($this->containsAny($lower_input, ['horário', 'aberto', 'funcionamento', 'atendimento', 'expediente', 'fecha', 'abre'])) {
            $this->business_hours[] = $clean_instruction;
            return;
        }

        // Case C: Links / Services (Simple Heuristic: contains http or www)
        if ($this->containsAny($lower_input, ['http', 'www.', '.com', 'link', 'acesse'])) {
            $this->service_links[] = $clean_instruction;
            return;
        }

        // Case D: General Rules (Default)
        $this->instructions[] = $clean_instruction;
    }

    private function containsAny($haystack, array $needles) {
        foreach ($needles as $needle) {
            if (mb_strpos($haystack, $needle, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detecta a intenção do training_phrase para routing inteligente
     * @param string $training_phrase Frase de treinamento do painel
     * @return string 'STUDY' (enviar para Vector Store) ou 'DISPLAY' (mostrar link no XML)
     */
    public function detectIntent($training_phrase) {
        $lower_phrase = mb_strtolower($training_phrase, 'UTF-8');
        
        // Palavras-chave que indicam conteúdo para ESTUDO (RAG)
        $study_keywords = ['estude', 'aprenda', 'base de conhecimento', 'interprete', 'consulte o site', 'leia'];
        
        // Palavras-chave que indicam conteúdo para EXIBIÇÃO (Link)
        $display_keywords = ['envie', 'mostre', 'quando pedir', 'compartilhe', 'disponibilize'];
        
        if ($this->containsAny($lower_phrase, $study_keywords)) {
            return 'STUDY';
        }
        
        if ($this->containsAny($lower_phrase, $display_keywords)) {
            return 'DISPLAY';
        }
        
        // Default: Se não detectar nenhuma intenção clara, assume STUDY (mais conservador)
        return 'STUDY';
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

    public function build()
    {
        // Fallback se o nome da empresa não for definido
        $company = !empty($this->company_name) ? $this->company_name : $this->chatbot_name;

        $xml = "<system_instructions>\n";
        
        // 1. SYSTEM IDENTITY
        $xml .= "    <system_identity>\n";
        $xml .= "        ### PERFIL DO ASSISTENTE\n";
        $xml .= "        - Nome: {$this->chatbot_name}\n";
        $xml .= "        - Função: {$this->main_function}\n";
        $xml .= "        - Empresa: {$company}\n";
        $xml .= "    </system_identity>\n\n";

        // 2. INTERACTION RULES
        $xml .= "    <interaction_rules>\n";
        $xml .= "        ### PROTOCOLOS DO SISTEMA (Fixo)\n";
        $xml .= "        1. O documento vence o conhecimento geral.\n";
        $xml .= "        2. Zero Alucinação.\n\n";
        
        $xml .= "        ### REGRAS DE NEGÓCIO (Do Painel)\n";
        if (!empty($this->instructions)) {
            foreach ($this->instructions as $rule) {
                $xml .= "        - {$rule}" . PHP_EOL;
            }
        } else {
             $xml .= "        - Nenhuma regra específica definida.\n";
        }
        $xml .= "    </interaction_rules>\n\n";

        // 3. BEHAVIOR PROFILE
        $xml .= "    <behavior_profile>\n";
        if (!empty($this->personality_text)) {
            $xml .= "        ### TOM DE VOZ\n";
            $xml .= "        {$this->personality_text}\n";
        }
        $xml .= "    </behavior_profile>\n\n";

        // 4. NEGATIVE CONSTRAINTS
        if (!empty($this->negative_constraints)) {
            $xml .= "    <negative_constraints>\n";
            $xml .= "        ### O QUE NÃO FAZER\n";
            foreach ($this->negative_constraints as $constraint) {
                $xml .= "        - {$constraint}" . PHP_EOL;
            }
            $xml .= "    </negative_constraints>\n\n";
        }

        // 5. CONTEXT DATA
        $xml .= "    <context_data>\n";
        
        // 5.1 Business Hours
        if (!empty($this->business_hours)) {
             $xml .= "        ### HORÁRIOS E FUNCIONAMENTO\n";
             foreach ($this->business_hours as $hours) {
                 $xml .= "        - {$hours}" . PHP_EOL;
             }
             $xml .= "\n";
        }

        // 5.2 Service Links
        if (!empty($this->service_links)) {
             $xml .= "        ### CATÁLOGO DE SERVIÇOS/LINKS\n";
             foreach ($this->service_links as $link) {
                 $xml .= "        - {$link}" . PHP_EOL;
             }
             $xml .= "\n";
        }
        
        // 5.3 Knowledge Base / Scraped Content
        if (!empty($this->knowledge_base)) {
            $xml .= "        ### CONTEÚDO DO SITE (Scraping/Base de Conhecimento)\n";
            foreach ($this->knowledge_base as $kb) {
                $xml .= "        {$kb}" . PHP_EOL . PHP_EOL;
            }
        }
        
        $xml .= "    </context_data>\n\n";

        $xml .= "</system_instructions>";

        return $xml;
    }
}
