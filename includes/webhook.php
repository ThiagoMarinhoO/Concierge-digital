<?php

// add_action('wp_ajax_gerar_script_chatbot', 'gerar_script_chatbot');
// function gerar_script_chatbot()
// {
//     if (!is_user_logged_in()) {
//         wp_send_json_error('Usuário não autenticado', 403);
//     }
//     $chatbot_id = isset($_GET['chatbotID']) ? $_GET['chatbotID'] : 0;
//     $user_id = get_current_user_id();

//     $chatbot = new Chatbot();
//     $currentChatbot = $chatbot->getChatbotById($chatbot_id, $user_id);

//     $assistant = json_decode($currentChatbot['assistant'], true);

//     // $chatbot_image = isset($currentChatbot['chatbot_image']) ? (string)$currentChatbot['chatbot_image'] : '';
//     // $chatbot_welcome_message = isset($currentChatbot['chatbot_welcome_message']) ? (string)$currentChatbot['chatbot_welcome_message'] : '';

//     $token = get_user_meta($user_id, 'chatbot_api_token', true);
//     if (!$token) {
//         $token = generate_chatbot_api_token($user_id);
//     }

//     $endpoint = esc_url(site_url('/wp-json/custom/v1/chatbot'));

//     $script = "
//         <script>
//         (function(d, s) {
//             var xhr = new XMLHttpRequest();
//             xhr.open('GET', '$endpoint?token=$token', true);

//             xhr.onload = function() {
//                 if (xhr.status === 200) {
//                     var cleanResponseText = xhr.responseText.replace(/\\\\|\\s+|\"/g, '').trim();
//                     localStorage.setItem('chatbot_user_id', ".$user_id.");
//                     localStorage.setItem('chatbot_id' , '".$chatbot_id."');
//                     localStorage.setItem('assistant' , '".$currentChatbot['assistant']."');
//                     var script = document.createElement('script');
//                     script.async = false;
//                     script.defer = true;
//                     script.src = cleanResponseText;
//                     document.head.appendChild(script);
//                 } else {
//                     console.error('Erro ao carregar o chatbot: ', xhr.status, xhr.statusText);
//                 }
//             };

//             xhr.onerror = function() {
//                 console.error('Erro na conexão com o servidor do chatbot.');
//             };

//             xhr.send();
//         })(document, 'script');
//         </script>
//     ";
//     wp_send_json_success(['script' => htmlspecialchars_decode($script)]);
// }
add_action('wp_ajax_gerar_script_chatbot', 'gerar_script_chatbot');
function gerar_script_chatbot()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado', 403);
    }

    $chatbot_id = isset($_GET['chatbotID']) ? $_GET['chatbotID'] : 0;
    $user_id = get_current_user_id();

    $chatbot = new Chatbot();
    $currentChatbot = $chatbot->getChatbotById($chatbot_id, $user_id);

    $assistant = json_decode($currentChatbot['assistant'], true);
    $assistant_json = wp_json_encode($assistant, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $token = get_user_meta($user_id, 'chatbot_api_token', true);
    if (!$token) {
        $token = generate_chatbot_api_token($user_id);
    }

    $endpoint = esc_url(site_url('/wp-json/custom/v1/chatbot'));

    $script = "
        <script>
        (function(d, s) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '$endpoint?token=$token', true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    var cleanResponseText = xhr.responseText.replace(/\\\\|\\s+|\"/g, '').trim();
                    localStorage.setItem('chatbot_user_id', $user_id);
                    localStorage.setItem('chatbot_id', '$chatbot_id');
                    
                    var script = document.createElement('script');
                    script.async = false;
                    script.defer = true;
                    script.src = cleanResponseText;
                    document.head.appendChild(script);
                } else {
                    console.error('Erro ao carregar o chatbot: ', xhr.status, xhr.statusText);
                }
            };

            xhr.onerror = function() {
                console.error('Erro na conexão com o servidor do chatbot.');
            };

            xhr.send();
        })(document, 'script');
        </script>
    ";

    wp_send_json_success(['script' => $script]);
}



add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/chatbot', [
        'methods' => 'GET',
        'callback' => 'custom_chatbot_script',
        'permission_callback' => 'validate_chatbot_token',
    ]);
});

function custom_chatbot_script()
{
    // $file_path = esc_url(site_url('/wp-content/plugins/Concierge-digital/assets/chatbot.js'));
    // $cleaned_path = str_replace(['\\', ' '], '', $file_path);

    $timestamp = time(); // força o browser a pegar sempre a última versão
    $file_path = esc_url(site_url("/wp-content/plugins/Concierge-digital/assets/chatbot.js?v=$timestamp"));
    $cleaned_path = str_replace(['\\', ' '], '', $file_path);


    return new WP_REST_Response($cleaned_path, 200, [
        'Content-Type' => 'application/javascript',
    ]);
}

function validate_chatbot_token()
{
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        error_log('Token ausente.');
        return new WP_Error('invalid_token', 'Token ausente.', array('status' => 403));
    }

    $user_query = new WP_User_Query([
        'meta_key' => 'chatbot_api_token',
        'meta_value' => $token,
        'number' => 1,
    ]);

    $users = $user_query->get_results();
    if (empty($users)) {
        error_log('Token inválido.');
        return new WP_Error('invalid_token', 'Token inválido.', array('status' => 403));
    }

    $user = $users[0];
    wp_set_current_user($user->ID);

    return true;
}

function chatbot_rest_api_init()
{
    register_rest_route('chatbot/v1', '/send_message', array(
        'methods' => 'POST',
        'callback' => 'handle_chatbot_message',
        'permission_callback' => '__return_true',
    ));
}

add_action('rest_api_init', 'chatbot_rest_api_init');

function handle_chatbot_message(WP_REST_Request $request)
{
    plugin_log('--- HANDLE FUUUUNCTION ---');

    $message = sanitize_text_field($request->get_param('message'));
    $user_id = intval($request->get_param('user_id'));
    $chatbot_id = sanitize_text_field($request->get_param('chatbot_id'));
    $thread_id = sanitize_text_field($request->get_param('thread_id'));

    if (empty($thread_id)) {
        $thread_id = create_thread();
    }

    add_message_to_thread($thread_id, $message);

    plugin_log('--- RUNNNN FUUUUNCTION ---');
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;
    $api_url = "https://api.openai.com/v1/threads/$thread_id/runs";

    $data = json_encode([
        "assistant_id" => $chatbot_id,
        "stream" => true
    ]);

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key",
        "OpenAI-Beta: assistants=v2"
    ];

    $assistant_message = "";

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    // Captura toda a resposta da API
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('Erro no cURL: ' . curl_error($ch));
        // plugin_log('Erro no cURL: ' . curl_error($ch));
    }

    curl_close($ch);

    // $response = json_decode($response, true);

    plugin_log('--- Resposta completa da OpenAI ---');
    plugin_log(print_r($response, true));

    // Divide a resposta por linha
    $lines = explode("\n", $response);

    foreach ($lines as $line) {
        $line = trim($line);

        // Log para verificar cada linha recebida
        plugin_log("Linha recebida: " . $line);

        if (strpos($line, 'data:') === 0) {
            $jsonData = trim(substr($line, 5));

            // Verifica se o JSON é válido antes de tentar decodificar
            if (!empty($jsonData) && $jsonData !== "[DONE]") {
                $decodedData = json_decode($jsonData, true);

                plugin_log('--- JSON Decodificado ---');
                plugin_log(print_r($decodedData, true));

                if (isset($decodedData['delta']['content'])) {
                    foreach ($decodedData['delta']['content'] as $chunkPart) {
                        if (isset($chunkPart['type']) && $chunkPart['type'] === 'text') {
                            $assistant_message .= $chunkPart['text']['value'];
                        }
                    }
                }
            }
        }
    }

    plugin_log('--- Mensagem final gerada ---');
    plugin_log(print_r($assistant_message, true));

    return new WP_REST_Response([
        'status' => 'success',
        'response' => $assistant_message,
        'thread_id' => $thread_id
    ], 200);
}

function create_thread()
{
    plugin_log('--- CRIAR THREAAD FUUUUNCTION ---');

    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;
    $api_url = "https://api.openai.com/v1/threads";

    plugin_log('--- Create thread funcccc ---');

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key",
        "OpenAI-Beta: assistants=v2"
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    curl_close($ch);

    $response = json_decode($response, true);

    plugin_log('--- Create thread ---');
    plugin_log(print_r($response, true));

    return $response['id'];
}

function add_message_to_thread($thread_id, $message)
{
    plugin_log('--- ADD MESSAGE  funcccc ---');

    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;
    $api_url = "https://api.openai.com/v1/threads/$thread_id/messages";


    $data = [
        "role" => "user",
        "content" => $message
    ];

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
        throw new Exception(curl_error($ch));
    }

    curl_close($ch);

    $response = json_decode($response, true);

    plugin_log('--- Add Message to thread ---');
    plugin_log(print_r($response, true));

    return $response;
}

function get_assistant_rest_api()
{
    register_rest_route('chatbot/v1', '/get_assistant', array(
        'methods' => ['POST', 'OPTIONS'],
        'callback' => 'get_assistant_handler',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'get_assistant_rest_api');

function get_assistant_handler(WP_REST_Request $request) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");

    $assistant_id = $request->get_param('assistant_id');

    if (empty($assistant_id)) {
        return new WP_REST_Response(['message' => 'Nenhum ID de assistente fornecido.'], 400);
    }

    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;
    $url = "https://api.openai.com/v1/assistants/" . urlencode($assistant_id);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
            'OpenAI-Beta: assistants=v2'
        ],
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return new WP_REST_Response(['message' => "Erro ao conectar à API: $error_msg"], 500);
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if ($http_code >= 400 || isset($data['error'])) {
        $message = $data['error']['message'] ?? 'Erro desconhecido na API.';
        return new WP_REST_Response(['message' => $message], $http_code);
    }

    return new WP_REST_Response([
        'status' => true,
        'assistant' => $data
    ], 200);
}




// function run_thread($thread_id, $assistant_id) {

//     $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;
//     $api_url = "https://api.openai.com/v1/threads/". $thread_id . "/messages";

//     $data = [
//         "assistant_id" => $assistant_id,
//         "stream" => true
//     ];

//     $headers = [
//         "Content-Type: application/json",
//         "Authorization: Bearer $api_key",
//         "OpenAI-Beta: assistants=v2"
//     ];

//     $ch = curl_init($api_url);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//     $response = curl_exec($ch);
//     $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//     if (curl_errno($ch)) {
//         throw new Exception(curl_error($ch));
//     }

//     curl_close($ch);

//     $response = json_decode($response, true);

//     return $response;
// }

// ----------- MOCK DAS REQUISIÇÕES -----------
// function handle_chatbot_message(WP_REST_Request $request)
// {
//     plugin_log('--- HANDLE FUUUUNCTION (Mock) ---');

//     $message = sanitize_text_field($request->get_param('message'));
//     $user_id = intval($request->get_param('user_id'));
//     $chatbot_id = sanitize_text_field($request->get_param('chatbot_id'));
//     $thread_id = sanitize_text_field($request->get_param('thread_id'));

//     if (empty($thread_id)) {
//         $thread_id = create_thread();
//     }

//     add_message_to_thread($thread_id, $message);

//     plugin_log('--- RUNNNN FUUUUNCTION (Mock) ---');

//     // Simulando uma resposta de execução de thread
//     $mock_response = <<<EOT
//         event: thread.run.created
//         data: {"id":"run_123","object":"thread.run","created_at":1710330640,"assistant_id":"asst_123","thread_id":"thread_123","status":"queued","started_at":null,"expires_at":1710331240,"cancelled_at":null,"failed_at":null,"completed_at":null,"required_action":null,"last_error":null,"model":"gpt-4o","instructions":null,"tools":[],"metadata":{},"temperature":1.0,"top_p":1.0,"max_completion_tokens":null,"max_prompt_tokens":null,"truncation_strategy":{"type":"auto","last_messages":null},"incomplete_details":null,"usage":null,"response_format":"auto","tool_choice":"auto","parallel_tool_calls":true}}

//         event: thread.run.queued
//         data: {"id":"run_123","object":"thread.run","created_at":1710330640,"assistant_id":"asst_123","thread_id":"thread_123","status":"queued","started_at":null,"expires_at":1710331240,"cancelled_at":null,"failed_at":null,"completed_at":null,"required_action":null,"last_error":null,"model":"gpt-4o","instructions":null,"tools":[],"metadata":{},"temperature":1.0,"top_p":1.0,"max_completion_tokens":null,"max_prompt_tokens":null,"truncation_strategy":{"type":"auto","last_messages":null},"incomplete_details":null,"usage":null,"response_format":"auto","tool_choice":"auto","parallel_tool_calls":true}}

//         event: thread.run.in_progress
//         data: {"id":"run_123","object":"thread.run","created_at":1710330640,"assistant_id":"asst_123","thread_id":"thread_123","status":"in_progress","started_at":1710330641,"expires_at":1710331240,"cancelled_at":null,"failed_at":null,"completed_at":null,"required_action":null,"last_error":null,"model":"gpt-4o","instructions":null,"tools":[],"metadata":{},"temperature":1.0,"top_p":1.0,"max_completion_tokens":null,"max_prompt_tokens":null,"truncation_strategy":{"type":"auto","last_messages":null},"incomplete_details":null,"usage":null,"response_format":"auto","tool_choice":"auto","parallel_tool_calls":true}}

//         event: thread.run.step.created
//         data: {"id":"step_001","object":"thread.run.step","created_at":1710330641,"run_id":"run_123","assistant_id":"asst_123","thread_id":"thread_123","type":"message_creation","status":"in_progress","cancelled_at":null,"completed_at":null,"expires_at":1710331240,"failed_at":null,"last_error":null,"step_details":{"type":"message_creation","message_creation":{"message_id":"msg_001"}},"usage":null}

//         event: thread.run.step.in_progress
//         data: {"id":"step_001","object":"thread.run.step","created_at":1710330641,"run_id":"run_123","assistant_id":"asst_123","thread_id":"thread_123","type":"message_creation","status":"in_progress","cancelled_at":null,"completed_at":null,"expires_at":1710331240,"failed_at":null,"last_error":null,"step_details":{"type":"message_creation","message_creation":{"message_id":"msg_001"}},"usage":null}

//         event: thread.message.created
//         data: {"id":"msg_001","object":"thread.message","created_at":1710330641,"assistant_id":"asst_123","thread_id":"thread_123","run_id":"run_123","status":"in_progress","incomplete_details":null,"incomplete_at":null,"completed_at":null,"role":"assistant","content":[],"metadata":{}}

//         event: thread.message.in_progress
//         data: {"id":"msg_001","object":"thread.message","created_at":1710330641,"assistant_id":"asst_123","thread_id":"thread_123","run_id":"run_123","status":"in_progress","incomplete_details":null,"incomplete_at":null,"completed_at":null,"role":"assistant","content":[],"metadata":{}}

//         event: thread.message.delta
//         data: {"id":"msg_001","object":"thread.message.delta","delta":{"content":[{"index":0,"type":"text","text":{"value":"Hello","annotations":[]}}]}}

//         event: thread.message.delta
//         data: {"id":"msg_001","object":"thread.message.delta","delta":{"content":[{"index":0,"type":"text","text":{"value":" today"}}]}}

//         event: thread.message.delta
//         data: {"id":"msg_001","object":"thread.message.delta","delta":{"content":[{"index":0,"type":"text","text":{"value":"?"}}]}}

//         event: thread.message.completed
//         data: {"id":"msg_001","object":"thread.message","created_at":1710330641,"assistant_id":"asst_123","thread_id":"thread_123","run_id":"run_123","status":"completed","incomplete_details":null,"incomplete_at":null,"completed_at":1710330642,"role":"assistant","content":[{"type":"text","text":{"value":"Hello! How can I assist you today?","annotations":[]}}],"metadata":{}}

//         event: thread.run.step.completed
//         data: {"id":"step_001","object":"thread.run.step","created_at":1710330641,"run_id":"run_123","assistant_id":"asst_123","thread_id":"thread_123","type":"message_creation","status":"completed","cancelled_at":null,"completed_at":1710330642,"expires_at":1710331240,"failed_at":null,"last_error":null,"step_details":{"type":"message_creation","message_creation":{"message_id":"msg_001"}},"usage":{"prompt_tokens":20,"completion_tokens":11,"total_tokens":31}}

//         event: thread.run.completed
//         data: {"id":"run_123","object":"thread.run","created_at":1710330640,"assistant_id":"asst_123","thread_id":"thread_123","status":"completed","started_at":1710330641,"expires_at":null,"cancelled_at":null,"failed_at":null,"completed_at":1710330642,"required_action":null,"last_error":null,"model":"gpt-4o","instructions":null,"tools":[],"metadata":{},"temperature":1.0,"top_p":1.0,"max_completion_tokens":null,"max_prompt_tokens":null,"truncation_strategy":{"type":"auto","last_messages":null},"incomplete_details":null,"usage":{"prompt_tokens":20,"completion_tokens":11,"total_tokens":31},"response_format":"auto","tool_choice":"auto","parallel_tool_calls":true}}

//         event: done
//         data: [DONE]
//     EOT;

//     plugin_log('--- Resposta completa MOCK ---');
//     plugin_log(print_r($mock_response, true));

//     $assistant_message = "";

//     $lines = explode("\n", $mock_response);
//     foreach ($lines as $line) {
//         $line = trim($line);

//         if (strpos($line, 'data:') === 0) {
//             $jsonData = trim(substr($line, 5));

//             if (!empty($jsonData) && $jsonData !== "[DONE]") {
//                 $decodedData = json_decode($jsonData, true);

//                 if (isset($decodedData['delta']['content'])) {
//                     foreach ($decodedData['delta']['content'] as $chunkPart) {
//                         if (isset($chunkPart['type']) && $chunkPart['type'] === 'text') {
//                             $assistant_message .= $chunkPart['text']['value'];
//                         }
//                     }
//                 }
//             }
//         }
//     }

//     plugin_log('--- Mensagem final gerada MOCK ---');
//     plugin_log(print_r($assistant_message, true));

//     return new WP_REST_Response([
//         'status' => 'success',
//         'response' => $assistant_message,
//         'thread_id' => $thread_id
//     ], 200);
// }

// function add_message_to_thread($thread_id, $message)
// {
//     plugin_log('--- ADD MESSAGE (Mock) ---');

//     // Resposta mockada
//     $response = [
//         "id" => "msg_abc123",
//         "object" => "thread.message",
//         "created_at" => time(),
//         "assistant_id" => null,
//         "thread_id" => $thread_id,
//         "run_id" => null,
//         "role" => "user",
//         "content" => [
//             [
//                 "type" => "text",
//                 "text" => [
//                     "value" => $message,
//                     "annotations" => []
//                 ]
//             ]
//         ],
//         "attachments" => [],
//         "metadata" => []
//     ];

//     plugin_log('--- Add Message to thread MOCK ---');
//     plugin_log(print_r($response, true));

//     return $response;
// }

// function create_thread()
// {
//     plugin_log('--- CRIAR THREAAD FUUUUNCTION (Mock) ---');

//     // Resposta mockada da OpenAI
//     $response = [
//         "id" => "thread_abc123",
//         "object" => "thread",
//         "created_at" => time(),
//         "metadata" => [],
//         "tool_resources" => []
//     ];

//     plugin_log('--- Create thread MOCK ---');
//     plugin_log(print_r($response, true));

//     return $response['id'];
// }
