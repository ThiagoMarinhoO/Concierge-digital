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
    $chatbot_options = isset($_POST['chatbot_options']) ? json_decode(stripslashes($_POST['chatbot_options']), true) : [];


    // Obter os dados atuais do chatbot no banco
    $chatbot_instance = new Chatbot();
    $current_chatbot = $chatbot_instance->getChatbotById($chatbot_id, $user_id);

    $assistant_dto = generate_instructions($chatbot_options, $chatbot_name);

    plugin_log('Assistant DTO', $assistant_dto['assistant_instructions']);

    $tools = [
        ["type" => "file_search"]
    ];

    $assistant_whatsapp_instance = WhatsappInstance::findByAssistant($chatbot_id);
    if (!empty($assistant_whatsapp_instance)) {
        $tools[] = [
            "type" => "function",
            "function" => AssistantHelpers::assistant_tool_send_to_whatsapp()
        ];
    }

    $data = [
        "instructions" => $assistant_dto['assistant_instructions'],
        "name" => $assistant_dto['assistant_name'],
        "tools" => $tools,
        "model" => "gpt-4.1-mini",
        "metadata" => !empty($assistant_dto['assistant_image']) ? (object) [
            "assistant_image" => $assistant_dto['assistant_image']
        ] : (object) []
    ];

    if (!empty($chatbot_welcome_message)) {
        $data['metadata']->welcome_message = $chatbot_welcome_message;
    }

    $api_url = "https://api.openai.com/v1/assistants/" . $chatbot_id;
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key",
        "OpenAI-Beta: assistants=v2"
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('Erro na criação do Assistente' . curl_error($ch));
    }

    curl_close($ch);


    // Atualizar o chatbot
    $update_success = $chatbot_instance->updateChatbot(
        $chatbot_id,
        $response,
        $user_id,
        $chatbot_name,
        $assistant_dto['assistant_instructions'],
        $chatbot_image,
        $chatbot_welcome_message,
    );

    $response = json_decode($response, true);


    if ($update_success) {
        wp_send_json_success([
            'message' => 'Chatbot atualizado com sucesso!',
            'chatbot_id' => $response['id'],
            'assistant' => $response
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Erro ao atualizar o chatbot. Tente novamente.',
        ]);
    }

    wp_die();
}
