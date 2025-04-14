<?php

add_action('wp_ajax_handle_questions_answers', 'handle_questions_answers');
function handle_questions_answers()
{

    plugin_log('Entrou na função handle_questions_answers', 'handle_questions_answers');

    $chatbotRespostas = isset($_POST['saved_data']) ? $_POST['saved_data'] : null;

    if ($chatbotRespostas !== null) {
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, "assistant_answers", $chatbotRespostas);
            wp_send_json_success(['message' => 'Respostas salvas com sucesso.']);
        } else {
            wp_send_json_error(['message' => 'Usuário não autenticado.']);
        }
    } else {
        wp_send_json_error(['message' => 'Nenhuma resposta recebida.']);
    }
}

add_action('wp_ajax_get_questions_answers', 'get_questions_answers');
function get_questions_answers()
{
    // $assistant_name = isset($_POST['assistant_name']) ? $_POST['assistant_name'] : "1";

    $assistant_answers = get_user_meta(get_current_user_id(), "assistant_answers", true);

    $assistant_answers = json_decode($assistant_answers, true);

    if ($assistant_answers !== null) {
        wp_send_json_success(['answers' => $assistant_answers]);
    } else {
        wp_send_json_error(['message' => 'Nenhuma resposta recebida.']);
    }
}
