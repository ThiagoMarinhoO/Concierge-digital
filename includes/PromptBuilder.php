<?php

class PromptBuilder
{
    // Identity
    private $chatbot_name;
    private $main_function = 'Atendimento ao Cliente';
    private $secondary_function = '';
    private $personality_text = '';
    
    // Client Configuration
    private $knowledge_source = 'Plataforma'; // Plataforma, Internet, ou Ambos
    private $interactivity_level = '';
    private $response_size = '';
    
    // Categorized Storage
    private $instructions = []; // General Rules (Admin)
    private $negative_constraints = [];
    private $business_hours = [];
    private $service_links = [];
    private $knowledge_base = [];
    
    // RAG Documents Tracking
    private $rag_documents = [];
    private $scraped_urls = [];

    public function __construct($chatbot_name)
    {
        $this->chatbot_name = $chatbot_name;
    }

    // === SETTERS ===

    public function setMainFunction($function) {
        $this->main_function = $function;
    }

    public function setSecondaryFunction($function) {
        $this->secondary_function = $function;
    }

    public function setPersonality($personality_text)
    {
        $this->personality_text = $personality_text;
    }

    public function setKnowledgeSource($source) {
        $this->knowledge_source = $source;
    }

    public function setInteractivityLevel($level) {
        $this->interactivity_level = $level;
    }

    public function setResponseSize($size) {
        $this->response_size = $size;
    }

    public function addRagDocument($name, $type = 'file') {
        $this->rag_documents[] = ['name' => $name, 'type' => $type];
    }

    public function addScrapedUrl($url, $filename = null) {
        $this->scraped_urls[] = ['url' => $url, 'filename' => $filename];
    }

    // === INSTRUCTION ROUTING ===

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
        // Nota: 'atendimento' removido para não capturar 'função principal é: Atendimento'
        if ($this->containsAny($lower_input, ['horário', 'aberto', 'funcionamento', 'expediente', 'fecha', 'abre'])) {
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

    // === BUILD METHOD ===

    public function build()
    {
        $xml = "<system_instructions>\n";
        
        // 1. SYSTEM IDENTITY (Quem é o assistente)
        $xml .= "    <system_identity>\n";
        $xml .= "        ### PERFIL DO ASSISTENTE\n";
        $xml .= "        - Nome: {$this->chatbot_name}\n";
        $xml .= "        - Função Principal: {$this->main_function}\n";
        if (!empty($this->secondary_function)) {
            $xml .= "        - Função Secundária: {$this->secondary_function}\n";
        }
        $xml .= "    </system_identity>\n\n";

        // 2. SYSTEM PROTOCOLS (Guardrails fixos - Nós controlamos)
        $xml .= "    <system_protocols>\n";
        $xml .= "        ### ÂNCORAS DE SEGURANÇA (Imutáveis)\n";
        $xml .= "        - O documento/RAG vence o conhecimento geral\n";
        $xml .= "        - Zero Alucinação: se não souber, admita de forma natural usando seu tom de voz\n";
        $xml .= "        - Mantenha-se no escopo das funções definidas acima\n";
        
        // Hierarquia de fonte de conhecimento:
        // 1. RAG (sempre prioridade máxima)
        // 2. Conhecimento geral da LLM
        // 3. Internet (só se configurado)
        $xml .= "        ### HIERARQUIA DE CONHECIMENTO\n";
        $xml .= "        - Prioridade 1: Documentos/RAG (sempre vence, NUNCA invente)\n";
        $xml .= "        - Prioridade 2: Conhecimento geral (complementa o RAG)\n";
        
        if ($this->knowledge_source === 'Internet') {
            $xml .= "        - Prioridade 3: Pesquisa na internet (para informações não encontradas no RAG nem no conhecimento geral)\n";
        }
        $xml .= "    </system_protocols>\n\n";

        // 3. BEHAVIOR PROFILE (Personalidade)
        $xml .= "    <behavior_profile>\n";
        if (!empty($this->personality_text)) {
            $xml .= "        ### TOM DE VOZ\n";
            $xml .= "        {$this->personality_text}\n";
        }
        $xml .= "    </behavior_profile>\n\n";

        // 4. CLIENT CONFIGURATION (Preferências do dashboard)
        $has_client_config = !empty($this->interactivity_level) || !empty($this->response_size) || !empty($this->knowledge_source);
        if ($has_client_config) {
            $xml .= "    <client_configuration>\n";
            $xml .= "        ### PREFERÊNCIAS DO PAINEL\n";
            if (!empty($this->interactivity_level)) {
                $xml .= "        - Nível de interatividade: {$this->interactivity_level}\n";
            }
            if (!empty($this->response_size)) {
                // Instruções expandidas para tamanho de resposta
                $size_instruction = match($this->response_size) {
                    'Curta' => "Respostas curtas: máximo 2-3 frases, direto ao ponto",
                    'Média' => "Respostas médias: 4-6 frases, com contexto quando necessário",
                    'Longa' => "Respostas detalhadas: parágrafos completos, explicações aprofundadas",
                    default => "Tamanho das respostas: {$this->response_size}"
                };
                $xml .= "        - {$size_instruction}\n";
            }
            if (!empty($this->knowledge_source)) {
                $xml .= "        - Fonte de conhecimento: {$this->knowledge_source}\n";
            }
            $xml .= "    </client_configuration>\n\n";
        }

        // 5. BUSINESS RULES (Regras Gerais do Admin)
        if (!empty($this->instructions)) {
            $xml .= "    <business_rules>\n";
            $xml .= "        ### REGRAS GERAIS\n";
            foreach ($this->instructions as $rule) {
                $xml .= "        - {$rule}" . PHP_EOL;
            }
            $xml .= "    </business_rules>\n\n";
        }

        // 6. NEGATIVE CONSTRAINTS (O que NÃO fazer)
        if (!empty($this->negative_constraints)) {
            $xml .= "    <negative_constraints>\n";
            $xml .= "        ### O QUE NÃO FAZER\n";
            foreach ($this->negative_constraints as $constraint) {
                $xml .= "        - {$constraint}" . PHP_EOL;
            }
            $xml .= "    </negative_constraints>\n\n";
        }

        // 7. AVAILABLE KNOWLEDGE (Lista de documentos RAG + Links)
        $xml .= "    <available_knowledge>\n";
        
        // 7.1 RAG Documents
        if (!empty($this->rag_documents)) {
            $xml .= "        📁 Documentos disponíveis para consulta:\n";
            foreach ($this->rag_documents as $doc) {
                $xml .= "        - {$doc['name']} ({$doc['type']})\n";
            }
            $xml .= "\n";
        }

        // 7.2 Scraped URLs (apenas para consciência do agente sobre o Vector Store)
        if (!empty($this->scraped_urls)) {
            $xml .= "        🌐 Fontes indexadas no Vector Store (NÃO compartilhe estas URLs com o usuário):\n";
            foreach ($this->scraped_urls as $scraped) {
                $display = is_array($scraped) ? $scraped['url'] : $scraped;
                if (is_array($scraped) && !empty($scraped['filename'])) {
                    $display .= " → arquivo: {$scraped['filename']}";
                }
                $xml .= "        - {$display}\n";
            }
            $xml .= "        ⚠️ Estes sites já foram processados e estão disponíveis via file_search.\n";
            $xml .= "\n";
        }

        // 7.3 Business Hours
        if (!empty($this->business_hours)) {
             $xml .= "        ⏰ Horários de funcionamento:\n";
             foreach ($this->business_hours as $hours) {
                 $xml .= "        - {$hours}" . PHP_EOL;
             }
             $xml .= "\n";
        }

        // 7.4 Service Links (para compartilhar com usuário)
        if (!empty($this->service_links)) {
             $xml .= "        🔗 Links para compartilhar quando solicitado:\n";
             foreach ($this->service_links as $link) {
                 // URL já validada no frontend - passa direto
                 $xml .= "        - {$link}" . PHP_EOL;
             }
             $xml .= "\n";
        }
        
        $xml .= "    </available_knowledge>\n\n";

        $xml .= "</system_instructions>";

        return $xml;
    }
}
