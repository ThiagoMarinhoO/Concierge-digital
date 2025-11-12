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
    $orgRepo = new OrganizationRepository();

    $user_id = get_current_user_id();
    $organization_id = 0;
    $resource_user_id = 0;

    if (!empty($user_id)) {
        $orgData = $orgRepo->findByUserId($user_id);
        $organization_id = $orgData ? (int) $orgData->id : 0;

        $resource_user_id = $organization_id > 0 && isset($orgData->owner_user_id)
            ? (int) $orgData->owner_user_id
            : $user_id;
    }

    $assistant_answers = get_user_meta($resource_user_id, "assistant_answers", true);

    $assistant_answers = json_decode($assistant_answers, true);

    if ($assistant_answers !== null) {
        wp_send_json_success(['answers' => $assistant_answers]);
    } else {
        wp_send_json_error(['message' => 'Nenhuma resposta recebida.']);
    }
}
