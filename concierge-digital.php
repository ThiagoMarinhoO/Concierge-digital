<?php

/**
 * Plugin Name: Concierge Digital Chatbot
 * Description: Plugin para criar e testar chatbots no front-end usando a API OpenAI.
 * Version: 2.0
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
require_once CONCIERGE_DIGITAL_PATH . 'model/chatbot.php';
require_once CONCIERGE_DIGITAL_PATH . 'model/question.php';
require_once CONCIERGE_DIGITAL_PATH . 'model/questionCategory.php';

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
        'Perguntas Chatbot',             // Título do menu
        'manage_options',                // Capacidade necessária
        'question-manager',              // Slug do menu
        'render_question_manager_page',  // Função de callback para renderizar a página
        'dashicons-format-chat',         // Ícone do menu
        50                               // Posição no menu
    );
});

// function render_question_manager_page()
// {
//     $questionManager = new Question();
//     $categoryManager = new QuestionCategory();

//     // Adicionar uma pergunta
//     // if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
//     //     $title = sanitize_text_field($_POST['question_title']);
//     //     $options = !empty($_POST['question_options']) ? explode(',', sanitize_text_field($_POST['question_options'])) : [];
//     //     $training_phrase = sanitize_text_field($_POST['training_phrase']);
//     //     $categories = !empty($_POST['question_categories']) ? array_map('intval', $_POST['question_categories']) : [];
//     //     $field_type = sanitize_text_field($_POST['field_type']) ?? 'text';

//     //     $questionManager->addQuestion($title, $training_phrase, $options, $categories);

//     //     echo "<div class='updated'><p>Pergunta adicionada com sucesso!</p></div>";
        
//     // }

//     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
//         $title = sanitize_text_field($_POST['question_title']);
//         $field_type = sanitize_text_field($_POST['field_type']); // Captura o tipo de campo do input radio
//         $options = [];
    
//         // Caso o tipo seja "selection", capturar as opções
//         if ($field_type === 'selection') {
//             $options_input = sanitize_text_field($_POST['selection_options_input']);
//             $options = !empty($options_input) ? explode(',', $options_input) : [];
//         }
    
//         $training_phrase = sanitize_text_field($_POST['training_phrase']);
//         $categories = !empty($_POST['question_categories']) ? array_map('intval', $_POST['question_categories']) : [];
    
//         $questionManager->addQuestion($title, $training_phrase, $options, $categories, $field_type);
    
//         echo "<div class='updated'><p>Pergunta adicionada com sucesso!</p></div>";
//     }

//     // Adicionar uma categoria
//     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
//         $categoryTitle = sanitize_text_field($_POST['category_title']);
//         $categoryManager->addCategory($categoryTitle);

//         echo "<div class='updated'><p>Categoria adicionada com sucesso!</p></div>";
//     }

//     // Obter dados
//     $questions = $questionManager->getAllQuestions();
//     $categories = $categoryManager->getAllCategories();

//     echo '<h1>Gerenciador de Perguntas</h1>';

//     // Formulário para adicionar perguntas
//     echo '<h2>Adicionar Pergunta</h2>';
//     echo '<form method="post">';
//     echo '<label for="question_title">Título:</label><br>';
//     echo '<input type="text" id="question_title" name="question_title" required><br>';

//     echo '<label>Tipo de Campo:</label><br>';
//     echo '<input type="radio" id="option_file" name="field_type" value="file" onclick="document.getElementById(\'selection_options\').style.display=\'none\'" required>';
//     echo '<label for="option_file">Arquivo</label><br>';
//     echo '<input type="radio" id="option_text" name="field_type" value="text" onclick="document.getElementById(\'selection_options\').style.display=\'none\'" required>';
//     echo '<label for="option_text">Texto</label><br>';
//     echo '<input type="radio" id="option_selection" name="field_type" value="selection" onclick="document.getElementById(\'selection_options\').style.display=\'block\'" required>';
//     echo '<label for="option_selection">Seleção</label><br>';

//     echo '<div id="selection_options" style="display:none;">';
//     echo '<label for="selection_options_input">Opções (separadas por vírgulas):</label><br>';
//     echo '<input type="text" id="selection_options_input" name="selection_options_input"><br>';
//     echo '</div>';

//     echo '<label for="training_phrase">Frase de Treinamento:</label><br>';
//     echo '<input type="text" id="training_phrase" name="training_phrase" required><br>';
//     echo '<label for="question_categories">Categorias:</label><br>';
//     echo '<select id="question_categories" name="question_categories[]" multiple>';
//     foreach ($categories as $category) {
//         echo '<option value="' . esc_attr($category['id']) . '">' . esc_html($category['title']) . '</option>';
//     }
//     echo '</select><br>';
//     echo '<button type="submit" name="add_question">Adicionar Pergunta</button>';
//     echo '</form>';

//     // Formulário para adicionar categorias
//     echo '<h2>Adicionar Categoria</h2>';
//     echo '<form method="post">';
//     echo '<label for="category_title">Título da Categoria:</label><br>';
//     echo '<input type="text" id="category_title" name="category_title" required><br>';
//     echo '<button type="submit" name="add_category">Adicionar Categoria</button>';
//     echo '</form>';

//     // Lista de perguntas
//     echo '<h2>Perguntas Existentes</h2>';
//     echo '<ul>';
//     foreach ($questions as $question) {
//         echo '<li>' . esc_html($question['title']) . ' <span class="" data-question-id="' . $question['id'] . '">Excluir</span></li>';
//     }
//     echo '</ul>';

//     // Script para alternar visibilidade do campo de opções
//     echo "
//     <script>
//         document.getElementById('toggle_options').addEventListener('click', function() {
//             var optionsContainer = document.getElementById('options_container');
//             if (optionsContainer.style.display === 'none' || optionsContainer.style.display === '') {
//                 optionsContainer.style.display = 'block';
//                 this.textContent = 'Esconder Opções';
//             } else {
//                 optionsContainer.style.display = 'none';
//                 this.textContent = 'Mostrar Opções';
//             }
//         });

//     </script>";
// }

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
