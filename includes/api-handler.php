<?php

// Função principal para processar o formulário e enviar os dados para a OpenAI
function concierge_process_form()
{
    // Verificar o nonce para garantir segurança
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'concierge_nonce')) {
        wp_send_json_error(['message' => 'Falha na verificação de segurança.']);
    }


    // Obter a chave da API
    $api_key = concierge_get_api_key();
    if (!$api_key) {
        wp_send_json_error(['message' => 'A chave da API não está configurada.']);
    }

    // Coletar e sanitizar os dados enviados pelo formulário
    $jsonl_data = isset($_POST['jsonl_data']) ? sanitize_textarea_field(wp_unslash($_POST['jsonl_data'])) : '';

    if (empty($jsonl_data)) {
        wp_send_json_error(['message' => 'Nenhum dado JSONL foi enviado.']);
    }

    // Registrar os dados processados após sanitização
    error_log('Dados após sanitização:');
    error_log(print_r($form_data, true));

    // Validar campos obrigatórios
    $missing_fields = [];
    foreach ($form_data as $field_name => $value) {
        if (empty($value)) {
            $missing_fields[] = $field_name;
        }
    }

    if (!empty($missing_fields)) {
        error_log('Erro: Campos obrigatórios não preenchidos: ' . implode(', ', $missing_fields));
        wp_send_json_error([
            'message' => 'Por favor, preencha todos os campos obrigatórios.',
            'missing_fields' => $missing_fields,
        ]);
    }

    // Dados para a API da OpenAI
    $data = [
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'user', 'content' => "Processar o seguinte JSONL:\n" . $jsonl_data],
        ],
        'temperature' => 0.7,
    ];

    // Enviar os dados para a API da OpenAI usando cURL
    $response = concierge_call_openai_api($data, $api_key);

    // Validar a resposta da API
    if (isset($response['error'])) {
        $error_message = isset($response['error']['message']) ? $response['error']['message'] : 'Erro desconhecido na API.';
        wp_send_json_error(['message' => $error_message]);
    }

    // Processar a resposta da OpenAI
    if (!isset($response['chatbot_url']) || !isset($response['chatbot_file'])) {
        wp_send_json_error(['message' => 'A resposta da API não contém as informações esperadas.']);
    }

    $chatbot_test_url = $response['chatbot_url']; // URL para testar o chatbot
    $chatbot_file = $response['chatbot_file'];   // Arquivo gerado pelo chatbot

    // Retornar o URL para teste do chatbot e o arquivo gerado
    wp_send_json_success([
        'chatbot_url' => $chatbot_test_url,
        'chatbot_file' => $chatbot_file,
    ]);
}
add_action('wp_ajax_concierge_process_form', 'concierge_process_form');
add_action('wp_ajax_nopriv_concierge_process_form', 'concierge_process_form');

// Função para chamada à API da OpenAI usando cURL
function concierge_call_openai_api($data, $api_key)
{
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => true, 'message' => $error];
    }

    return json_decode($response, true);
}
