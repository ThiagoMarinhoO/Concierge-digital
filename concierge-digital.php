<?php

/**
 * Plugin Name: Concierge Digital Chatbot
 * Description: Plugin para criar e testar chatbots no front-end usando a API OpenAI.
 * Version: 2.5
 * Author: DevHouse
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
require_once CONCIERGE_DIGITAL_PATH . 'includes/requesthandler.php';
require_once CONCIERGE_DIGITAL_PATH . 'includes/formhandler.php';
require_once CONCIERGE_DIGITAL_PATH . 'includes/webhook.php';
require_once CONCIERGE_DIGITAL_PATH . 'includes/generate-token.php';
require_once CONCIERGE_DIGITAL_PATH . 'includes/fileHandler.php';
require_once CONCIERGE_DIGITAL_PATH . 'includes/save-responses.php';
require_once CONCIERGE_DIGITAL_PATH . 'includes/log-to-file.php';
require_once CONCIERGE_DIGITAL_PATH . 'model/chatbot.php';
require_once CONCIERGE_DIGITAL_PATH . 'model/question.php';
require_once CONCIERGE_DIGITAL_PATH . 'model/questionCategory.php';
require_once CONCIERGE_DIGITAL_PATH . 'helpers/update-plugin.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Carregar scripts e estilos
function concierge_enqueue_assets()
{
    // Enqueue Tailwind CSS (via CDN ou arquivo local)
    wp_enqueue_script('tailwind', 'https://cdn.tailwindcss.com');

    // Enqueue Alpine.js (via CDN ou arquivo local)
    wp_enqueue_script('alpine-js', 'https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js', [], '3.12.0', true);

    // Enqueue o estilo e script customizados do plugin
    wp_enqueue_style('concierge-style', CONCIERGE_DIGITAL_URL . 'assets/style.css');
    wp_enqueue_script('concierge-script', CONCIERGE_DIGITAL_URL . 'assets/script.js', ['jquery'], null, true);
    // wp_enqueue_script('chatbot-script', CONCIERGE_DIGITAL_URL . 'assets/chatbot.js', ['jquery'], null, true);
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], '11', true);

    // Passar a URL do AJAX e o nonce para o JavaScript
    wp_localize_script('concierge-script', 'conciergeAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('concierge_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'concierge_enqueue_assets');

add_action('admin_enqueue_scripts', 'concierge_enqueue_admin_assets');
function concierge_enqueue_admin_assets()
{
    // wp_enqueue_script('tailwind', 'https://cdn.tailwindcss.com');
    wp_enqueue_script('alpine-js', 'https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js', [], '3.12.0', true);
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], '11', true);

}

// session_start();

// Criar pasta para uploads, se não existir
function concierge_create_upload_directory()
{
    $upload_dir = wp_upload_dir();
    $concierge_dir = $upload_dir['basedir'] . '/concierge_uploads';
    if (!file_exists($concierge_dir)) {
        wp_mkdir_p($concierge_dir);
    }
}
add_action('init', 'concierge_create_upload_directory');

// Função para processar upload de arquivos
function concierge_handle_file_upload($file, $is_developer = false)
{
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
function concierge_process_file_upload()
{
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
function concierge_display_chatbot_form()
{
    ob_start();
    // Inclui o conteúdo do arquivo /views/index.php
    include plugin_dir_path(__FILE__) . 'views/index.php';
    return ob_get_clean();
}

add_shortcode('concierge_chatbot', 'concierge_display_chatbot_form');

add_action('admin_menu', function () {
    add_menu_page(
        'Gerenciar Perguntas',           // Título da página
        'Perguntas Assistente Virtual',  // Título do menu
        'manage_options',                // Capacidade necessária
        'question-manager',              // Slug do menu
        'render_question_manager_page',  // Função de callback para renderizar a página
        'dashicons-format-chat',         // Ícone do menu
        50                               // Posição no menu
    );
});

function render_question_manager_page()
{
    // Cria instâncias dos gerenciadores
    $questionManager = new Question();
    $categoryManager = new QuestionCategory();

    // Adicionar uma pergunta
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
        $title = sanitize_text_field($_POST['question_title']);
        $field_type = sanitize_text_field($_POST['field_type']); // Captura o tipo de campo do input radio
        $options = [];
    
        if ($field_type === 'selection') {
            $options_input = sanitize_text_field($_POST['selection_options_input']);
            $options = !empty($options_input) ? explode(',', $options_input) : [];
        }
    
        $training_phrase = sanitize_text_field($_POST['training_phrase']);
        $categories = !empty($_POST['question_categories']) ? array_map('intval', $_POST['question_categories']) : [];
    
        $questionManager->addQuestion($title, $training_phrase, $options, $categories, $field_type);
        echo "<div class='updated'><p>Pergunta adicionada com sucesso!</p></div>";
    }

    // Adicionar uma categoria
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
        $categoryTitle = sanitize_text_field($_POST['category_title']);
        $categoryManager->addCategory($categoryTitle);
        echo "<div class='updated'><p>Categoria adicionada com sucesso!</p></div>";
    }

    // Obter dados
    $questions = $questionManager->getAllQuestions();
    $categories = $categoryManager->getAllCategories();

    // Incluindo o arquivo de visualização
    include(plugin_dir_path(__FILE__) . 'views/admin/questions.php');
}




register_activation_hook(__FILE__, function () {
    $manager = new Question();
    $manager->createTable();

    $initialChatbot = new Chatbot();
    $initialChatbot->createTable();

    $initialQuestionCategory = new QuestionCategory();
    $initialQuestionCategory->createTable();

    $initialQuestionCategoryRelationships = new QuestionCategoryRelationships();
    $initialQuestionCategoryRelationships->createTable();
});
