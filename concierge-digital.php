<?php
/**
 * Plugin Name: Concierge Digital Chatbot
 * Description: Plugin para criar e testar chatbots no front-end usando a API OpenAI.
 * Version: 1.3
 * Author: Seu Nome
 */

if (!defined('ABSPATH')) {
    exit; // Evitar acesso direto
}

// Definir constantes
define('CONCIERGE_DIGITAL_PATH', plugin_dir_path(__FILE__));
define('CONCIERGE_DIGITAL_URL', plugin_dir_url(__FILE__));

// Incluir arquivos necessários
require_once CONCIERGE_DIGITAL_PATH . 'includes/api-handler.php';
require_once CONCIERGE_DIGITAL_PATH . 'includes/helper-functions.php';

// Carregar scripts e estilos
function concierge_enqueue_assets() {
    wp_enqueue_style('concierge-style', CONCIERGE_DIGITAL_URL . 'assets/style.css');
    wp_enqueue_script('concierge-script', CONCIERGE_DIGITAL_URL . 'assets/script.js', ['jquery'], null, true);

    // Passar a URL do AJAX e o nonce para o JavaScript
    wp_localize_script('concierge-script', 'conciergeAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('concierge_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'concierge_enqueue_assets');

// Criar pasta para uploads, se não existir
function concierge_create_upload_directory() {
    $upload_dir = wp_upload_dir();
    $concierge_dir = $upload_dir['basedir'] . '/concierge_uploads';
    if (!file_exists($concierge_dir)) {
        wp_mkdir_p($concierge_dir);
    }
}
add_action('init', 'concierge_create_upload_directory');

// Função para processar upload de arquivos
function concierge_handle_file_upload($file, $is_developer = false) {
    $upload_dir = wp_upload_dir();
    $concierge_dir = $upload_dir['basedir'] . '/concierge_uploads';

    $filename = sanitize_file_name($file['name']);
    $destination = $concierge_dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $upload_dir['baseurl'] . '/concierge_uploads/' . $filename;
    } else {
        return false;
    }
}

// AJAX para upload de arquivos do formulário
function concierge_process_file_upload() {
    check_ajax_referer('concierge_nonce', 'nonce');

    if (!empty($_FILES['file'])) {
        $file_url = concierge_handle_file_upload($_FILES['file']);
        if ($file_url) {
            wp_send_json_success(['url' => $file_url]);
        } else {
            wp_send_json_error('Erro ao fazer upload do arquivo.');
        }
    } else {
        wp_send_json_error('Nenhum arquivo enviado.');
    }
}
add_action('wp_ajax_concierge_upload_file', 'concierge_process_file_upload');
add_action('wp_ajax_nopriv_concierge_upload_file', 'concierge_process_file_upload');

// Shortcode para exibir o formulário no front-end
function concierge_display_chatbot_form() {
    ob_start();
    ?>
    <div id="concierge-container">
        <form id="concierge-form">
            <h2>Configuração do Chatbot</h2>
            
            <!-- Nome do(a) Concierge -->
            <label for="concierge-name">Qual nome será dado a concierge?</label>
            <textarea id="concierge-name" name="concierge_name"></textarea>

            <!-- Objetivo do assistente -->
            <label for="concierge-objective">Qual o principal objetivo do seu assistente virtual?</label>
            <select id="concierge-objective" name="concierge_objective" onchange="toggleAdditionalField('concierge-objective', 'other-objective-container')">
                <option value="Atendimento ao cliente">Atendimento ao cliente</option>
                <option value="Suporte técnico">Suporte técnico</option>
                <option value="Conversão de vendas">Conversão de vendas</option>
                <option value="Guia de produtos">Guia de produtos</option>
                <option value="Orientação de serviços">Orientação de serviços</option>
                <option value="Automação de respostas frequentes">Automação de respostas frequentes</option>
                <option value="Captura de leads qualificação de leads">Captura de leads qualificação de leads</option>
                <option value="Agendamento de serviços">Agendamento de serviços</option>
                <option value="Rastreamento de pedidos">Rastreamento de pedidos</option>
                <option value="Resolução de problemas">Resolução de problemas</option>
                <option value="Acompanhamento pós-venda">Acompanhamento pós-venda</option>
                <option value="Onboarding de novos clientes">Onboarding de novos clientes</option>
                <option value="Suporte de autosserviço">Suporte de autosserviço</option>
                <option value="Monitoramento de dúvidas frequentes">Monitoramento de dúvidas frequentes</option>
                <option value="Melhoria da experiência do usuário">Melhoria da experiência do usuário</option>
                <option value="Educação">Educação</option>
                <option value="Outros">Outros</option>
            </select>
            <div id="other-objective-container" style="display: none; margin-top: 10px;">
                <label for="other-objective">Por favor, descreva:</label>
                <input type="text" id="other-objective" name="other_objective" placeholder="Digite o objetivo">
            </div>

            <!-- Tarefas do assistente -->
            <label for="concierge-tasks">Quais tarefas o assistente deve desempenhar?</label>
            <select id="concierge-tasks" name="concierge-tasks" onchange="toggleAdditionalField('concierge-tasks', 'other-tone-container')">
                <option value="Responder dúvidas frequentes">Responder dúvidas frequentes</option>
                <option value="Guiar o cliente em compras">Guiar o cliente em compras</option>
                <option value="Resolver problemas">Resolver problemas</option>
                <option value=" Direcionar para atendimento humano"> Direcionar para atendimento humano</option>
                <option value="Outros">Outros</option>
            </select>
            <div id="other-tone-container" style="display: none; margin-top: 10px;">
                <label for="other-tasks">Por favor, descreva:</label>
                <input type="text" id="other-tasks" name="other_tasks" placeholder="Digite outras tarefas">
            </div>

            <!-- Tom e personalidade -->
            <label for="concierge-tone">Como você gostaria que o assistente se posicionasse em relação ao tom e à personalidade?</label>
            <select id="concierge-tone" name="concierge_tone" onchange="toggleAdditionalField('concierge-tone', 'other-tone-container')">
                <option value="Amigável">Amigável</option>
                <option value="Formal">Formal</option>
                <option value="Direto">Direto</option>
                <option value="Engraçado">Engraçado</option>
                <option value="Sarcástico">Sarcástico</option>
                <option value="Técnico">Técnico</option>
                <option value="Descontraído">Descontraído</option>
                <option value="Outros">Outros</option>
            </select>
            <div id="other-tone-container" style="display: none; margin-top: 10px;">
                <label for="other-tone">Por favor, descreva:</label>
                <input type="text" id="other-tone" name="other_tone" placeholder="Digite o tom desejado">
            </div>

            <!-- Início da interação -->
            <label for="concierge-initiation">Você prefere que o assistente inicie a interação via chat:</label>
            <select id="concierge-initiation" name="concierge_initiation">
                <option value="Automaticamente">Automaticamente</option>
                <option value="Apenas quando solicitado">Apenas quando solicitado</option>
            </select>

            <!-- Tipo de abordagem -->
            <label for="concierge-approach">Qual tipo de abordagem prefere?</label>
            <select id="concierge-approach" name="concierge_approach">
                <option value="Mais direta">Mais direta</option>
                <option value="Com contexto adicional">Com contexto adicional: oferecendo mais detalhes e explicações para situar melhor o cliente</option>
            </select>
            
            <!-- Nível de formalidade -->
            <label for="formal-level">Se você escolheu um tom formal, qual o nível de formalidade que deseja?</label>
                <select id="formal-level" name="formal_level">
                    <option value="Alta formalidade">Alta formalidade: uma linguagem mais polida e conservadora.</option>
                    <option value="Moderada">Moderada: formal, mas sem excesso de rigidez.</option>
                    <option value="Leve formalidade">Leve formalidade: formal, mas acessível e amigável.</option>
                </select>

            <!-- Prioridade de conteúdo -->
            <label for="concierge-content-priority">Em termos de conteúdo, qual aspecto você considera de maior importância?</label>
            <select id="concierge-content-priority" name="concierge_content_priority">
                <option value="Clareza">Clareza</option>
                <option value="Precisão">Precisão</option>
                <option value="Simpatia">Simpatia</option>
            </select>
            
             <!-- Característica da marca -->
            <label for="brand-characteristic">Qual característica da marca você gostaria de refletir na comunicação?</label>
            <select id="brand-characteristic" name="brand_characteristic" onchange="toggleBrandDetails()">
                <option value="Inovação">Inovação: uma abordagem moderna e vanguardista.</option>
                <option value="Acessibilidade">Acessibilidade: linguagem inclusiva e fácil de entender.</option>
                <option value="Exclusividade">Exclusividade: tom sofisticado, destacando a unicidade da marca.</option>
                <option value="Tradição/Experiência">Tradição/Experiência: ênfase na história e confiança da marca.</option>
            </select>
            
            <!-- Termos e expressões -->
            <label for="concierge-custom-terms">Há termos, expressões ou palavras que você gostaria de incluir ou evitar?</label>
            <textarea id="concierge-custom-terms" name="concierge_custom_terms"></textarea>

            <!-- Público principal -->
            <label for="concierge-audience">Quem é o público principal dessa comunicação?</label>
            <select id="concierge-audience" name="concierge_audience" onchange="toggleAdditionalField('concierge-audience', 'other-audience-container')">
                <option value="Jovens adultos">Jovens adultos</option>
                <option value="Adultos">Adultos</option>
                <option value="Terceira idade">Terceira idade</option>
                <option value="Estudantes">Estudantes</option>
                <option value="Profissionais de Tecnologia">Profissionais de Tecnologia</option>
                <option value="Executivos">Executivos</option>
                <option value="Outros">Outros</option>
            </select>
            <div id="other-audience-container" style="display: none; margin-top: 10px;">
                <label for="other-audience">Por favor, descreva:</label>
                <input type="text" id="other-audience" name="other_audience" placeholder="Descreva seu público">
            </div>
            
            <!-- Nível de conhecimento -->
            <label for="concierge-knowledge-level">Qual é o nível de conhecimento desse público sobre os produtos ou serviços oferecidos?</label>
            <select id="concierge-knowledge-level" name="concierge_knowledge_level">
                <option value="Básico">Básico: pouco conhecimento; requer explicações mais detalhadas.</option>
                <option value="Intermediário">Intermediário: familiaridade básica, mas pode precisar de alguns esclarecimentos.</option>
                <option value="Avançado">Avançado: público que já conhece bem o produto ou serviço.</option>
            </select>

            <!-- Campo de upload de arquivos -->
            <label for="concierge-upload">Envie arquivos para personalização:</label>
            <input type="file" id="concierge-upload" name="file">

            <!-- Botão de envio -->
            <button type="button" id="concierge-test-chatbot">Testar Chatbot</button>
        </form>

        <div id="concierge-test-result"></div>
    </div>

    <!-- Scripts inline para controle dinâmico -->
    <script>
        function toggleAdditionalField(selectId, containerId) {
            const select = document.getElementById(selectId);
            const container = document.getElementById(containerId);
            container.style.display = select.value === 'Outros' ? 'block' : 'none';
        }
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('concierge_chatbot', 'concierge_display_chatbot_form');
