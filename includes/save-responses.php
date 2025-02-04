<?php

add_action('wp_ajax_save_responses', 'save_responses');

function save_responses()
{

    $chatbot_id = $_POST['chatbot_id'];
    $chatbot_name = $_POST['chatbot_name'];
    $chatbot_image = $_POST['chatbot_image'];
    $chatbot_welcome_message = $_POST['chatbot_welcome_message'];
    $user_id = get_current_user_id();

    // Decodificar as opções enviadas
    $chatbot_options = !empty($_POST['chatbot_options'])
        ? json_decode(stripslashes($_POST['chatbot_options']), true)
        : null;

    // Obter os dados atuais do chatbot no banco
    $chatbot_instance = new Chatbot();
    $current_chatbot = $chatbot_instance->getChatbotById($chatbot_id, $user_id);

    if ($current_chatbot) {
        $current_options = $current_chatbot['chatbot_options'];
        $current_image = $current_chatbot['chatbot_image'];

        // Manter valores antigos se chatbot_options for nulo ou vazio
        if (is_null($chatbot_options)) {
            $chatbot_options = $current_options;
        } else {
            // Iterar pelos chatbot_options e manter valores antigos para campos do tipo "file" vazios
            foreach ($chatbot_options as &$option) {
                if ($option['field_type'] === 'file' && empty($option['value'])) {
                    // Verificar se o valor atual existe no banco
                    foreach ($current_options as $current_option) {
                        if ($current_option['name'] === $option['name']) {
                            $option['value'] = $current_option['value'];
                            break;
                        }
                    }
                }
            }
        }

        if (empty($chatbot_image)) {
            $chatbot_image = $current_image;
        }
    }

    // Atualizar o chatbot
    $update_success = $chatbot_instance->updateChatbot(
        $chatbot_id,
        $chatbot_name,
        $chatbot_options,
        $chatbot_image,
        $chatbot_welcome_message,
        $user_id
    );

    if ($update_success) {
        wp_send_json_success([
            'message' => 'Chatbot atualizado com sucesso!',
            'chatbot_id' => $chatbot_id,
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Erro ao atualizar o chatbot. Tente novamente.',
        ]);
    }

    wp_die();
}