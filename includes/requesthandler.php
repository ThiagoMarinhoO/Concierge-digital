<?php
add_action('wp_ajax_concierge_chat', 'concierge_chat');
add_action('wp_ajax_nopriv_concierge_chat', 'concierge_chat');

function concierge_chat()
{
    $userMensagem = isset($_POST['mensagem']) ? $_POST['mensagem'] : null;
    $chatbotId = isset($_POST['assistantId']) ? $_POST['assistantId'] : null;
    $user_id = get_current_user_id();

    // error_log('---- assistantId ---');
    // error_log($chatbotId);

    $chatbot = new Chatbot();

    $result = $chatbot->enviarMensagem($userMensagem, $chatbotId, $user_id);
    // error_log('---- Resposta do sistema -----');
    // error_log(print_r($result, true));

    wp_send_json_success($result);

}

add_action('wp_ajax_create_chatbot', 'create_chatbot');
add_action('wp_ajax_nopriv_create_chatbot', 'create_chatbot');

function create_chatbot()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $chatbot_options = $_POST['chatbot_options'] ?? '';
        $chatbot_name = $_POST['chatbot_name'] ?? '';
        $chatbot_welcome_message = $_POST['chatbot_welcome_message'] ?? '';

        if (isset($_FILES['chatbot_image']) && $_FILES['chatbot_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['chatbot_image'];

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024;

            if (!in_array($file['type'], $allowed_types)) {
                wp_send_json_error(['message' => 'Tipo de arquivo não permitido: ' . $file['type']]);
                return;
            }

            if ($file['size'] > $max_size) {
                wp_send_json_error(['message' => 'Arquivo excede o tamanho máximo permitido.']);
                return;
            }

            $upload_dir = wp_upload_dir();
            $target_path = $upload_dir['path'] . '/' . basename($file['name']);

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $chatbot_image = $upload_dir['url'] . '/' . basename($file['name']);
            } else {
                wp_send_json_error(['message' => 'Falha ao salvar o arquivo.']);
                return;
            }
        } else {
            $chatbot_image = null;
        }

        if ($chatbot_options) {
            $chatbot_options = json_decode(stripslashes($chatbot_options), true);

            foreach ($chatbot_options as &$option) {
                if (isset($_FILES[$option['field_name']]) && $_FILES[$option['field_name']]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$option['field_name']];

                    $allowed_types = ['text/csv', 'text/plain', 'application/pdf'];
                    $max_size = 5 * 1024 * 1024;

                    if (!in_array($file['type'], $allowed_types)) {
                        wp_send_json_error(['message' => 'Tipo de arquivo não permitido: ' . $file['type']]);
                        return;
                    }

                    if ($file['size'] > $max_size) {
                        wp_send_json_error(['message' => 'Arquivo excede o tamanho máximo permitido.']);
                        return;
                    }

                    // Mover o arquivo para o diretório de uploads
                    $upload_dir = wp_upload_dir();
                    $target_path = $upload_dir['path'] . '/' . basename($file['name']);

                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        $option['file_url'] = $upload_dir['url'] . '/' . basename($file['name']);
                    } else {
                        wp_send_json_error(['message' => 'Falha ao salvar o arquivo.']);
                        return;
                    }
                }
            }

            // error_log(print_r($chatbot_options, true));
        }

        $chatbot = new Chatbot();
        $chatbot->createChatbot($chatbot_name, $chatbot_options, $chatbot_image, $chatbot_welcome_message);

        wp_send_json_success(['chatbotName' => $chatbot_name]);
    } else {
        wp_send_json_error(['message' => 'Método inválido']);
    }
}

add_action('wp_ajax_delete_chatbot', 'delete_chatbot');
add_action('wp_ajax_nopriv_delete_chatbot', 'delete_chatbot');

function delete_chatbot()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $chatbot_id = $_POST['chatbot_id'] ?? '';

        $chatbot = new Chatbot();
        $chatbot->deleteChatbot($chatbot_id);

        wp_send_json_success([$chatbot_id => "$chatbot_id deletado"]);
    } else {
        wp_send_json_error(['message' => 'Método inválido']);
    }
}

add_action('wp_ajax_delete_question', 'delete_question');
add_action('wp_ajax_nopriv_delete_question', 'delete_question');

function delete_question()
{
    $question_id = isset($_POST['question_id']) ? $_POST['question_id'] : null;

    $question = new Question();
    $question->deleteQuestion($question_id);

    wp_send_json_success(['message' => "pergunta deletada com sucesso"]);
}

add_action('wp_ajax_delete_category', 'delete_category');
add_action('wp_ajax_nopriv_delete_category', 'delete_category');

function edit_question()
{
    $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : null;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $training_phrase = isset($_POST['training_phrase']) ? sanitize_text_field($_POST['training_phrase']) : '';
    $categories = isset($_POST['categories']) ? sanitize_text_field($_POST['categories']) : '';
    $responseQuestion = isset($_POST['responseQuestion']) ? sanitize_text_field($_POST['responseQuestion']) : '';
    $required_field = isset($_POST['requiredField']) ? sanitize_text_field($_POST['requiredField']) : '';
    $priority_field = isset($_POST['priorityField']) ? sanitize_text_field($_POST['priorityField']) : '';
    $field_type = isset($_POST['field_type']) ? sanitize_text_field($_POST['field_type']) : '';

    $options = isset($_POST['options']) ? json_decode(stripslashes($_POST['options']), true) : [];

    // Sanitizar cada opção individual
    $options = array_map('sanitize_text_field', $options);

    if (empty($question_id)) {
        wp_send_json_error(['message' => 'ID da pergunta inválido']);
    }

    $question = new Question();

    if ($categories == 'Regras Gerais') {
        $updated = $question->updateQuestion($question_id, '', '', $options, 'Regras Gerais', '', $responseQuestion, $priority_field, null);

        if ($updated) {
            wp_send_json_success(['message' => 'Resposta da pergunta atualizada com sucesso!']);
        } else {
            wp_send_json_error(['message' => 'Erro ao atualizar a resposta da pergunta']);
        }
    } else {
        $updated = $question->updateQuestion($question_id, $title, $training_phrase, $options, $categories, $field_type, $responseQuestion, $required_field, $priority_field);

        if ($updated) {
            wp_send_json_success(['message' => 'Pergunta atualizada com sucesso!']);
        } else {
            wp_send_json_error(['message' => 'Erro ao atualizar a pergunta']);
        }
    }
}

add_action('wp_ajax_edit_question', 'edit_question');
add_action('wp_ajax_nopriv_edit_question', 'edit_question');


function edit_cat()
{
    $cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : null;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $position = isset($_POST['position']) ? sanitize_text_field($_POST['position']) : '';

    if (empty($cat_id)) {
        wp_send_json_error(['message' => 'ID da pergunta inválido']);
    }

    $categories = new QuestionCategory();

    $updated = $categories->updateCategory($cat_id, $title, $position);

    if ($updated) {
        wp_send_json_success(['message' => 'Categoria atualizada com sucesso!']);
    } else {
        wp_send_json_error(['message' => 'Erro ao atualizar a categoria']);
    }
}

add_action('wp_ajax_edit_cat', 'edit_cat');
add_action('wp_ajax_nopriv_edit_cat', 'edit_cat');

function delete_category()
{
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;

    $category = new QuestionCategory();
    $category->deleteCategory($category_id);

    wp_send_json_success(['message' => "pergunta deletada com sucesso"]);
}

add_action('wp_ajax_get_questions_by_category', 'get_questions_by_category');
add_action('wp_ajax_nopriv_get_questions_by_category', 'get_questions_by_category');

function get_questions_by_category()
{
    $category_title = isset($_POST['category_title']) ? $_POST['category_title'] : null;

    $question = new Question();
    $questions = $question->getQuestionsByCategory($category_title);

    error_log(print_r($questions, true));

    wp_send_json_success($questions);
}

add_action('wp_ajax_add_fixed_question', 'add_fixed_question');
add_action('wp_ajax_nopriv_add_fixed_question', 'add_fixed_question');

function add_fixed_question()
{
    try {
        $response = sanitize_text_field($_POST['response']);

        if (empty($response)) {
            wp_send_json_error(['message' => 'O campo de resposta é obrigatório.']);
        }

        $question = new Question();
        $question_id = $question->addFixedQuestion($response);

        if ($question_id) {
            wp_send_json_success(['message' => 'Pergunta adicionada com sucesso!']);
        } else {
            wp_send_json_error(['message' => 'Erro ao adicionar a pergunta. Tente novamente.']);
        }
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
?>