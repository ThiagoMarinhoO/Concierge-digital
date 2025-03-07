<?php

use Smalot\PdfParser\Parser;

add_action('wp_ajax_create_assistant', 'create_assistant');
function create_assistant()
{
    $chatbot_options = isset($_POST['chatbot_options']) ? json_decode(stripslashes($_POST['chatbot_options']), true) : [];
    $chatbot_name = $_POST['chatbot_name'] ?? '';

    $api_url = "https://api.openai.com/v1/assistants";
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;

    $assistant_dto = generate_instructions($chatbot_options, $chatbot_name);

    $data = [
        "instructions" => $assistant_dto['assistant_instructions'],
        "name" => $assistant_dto['assistant_name'],
        "tools" => [["type" => "file_search"]],
        "model" => "gpt-3.5-turbo",
        "metadata" => (object) [
            "assistant_image" => $assistant_dto['assistant_image']
        ]
    ];

    $data = [
        "instructions" => $assistant_dto['assistant_instructions'],
        "name" => $assistant_dto['assistant_name'],
        "tools" => [["type" => "file_search"]],
        "model" => "gpt-3.5-turbo",
        "metadata" => !empty($assistant_dto['assistant_image']) ? (object) [
            "assistant_image" => $assistant_dto['assistant_image']
        ] : (object) []
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
        throw new Exception('Erro na criação do Assistente' . curl_error($ch));
    }

    curl_close($ch);

    $response = json_decode($response, true);

    $new_assistant = new Chatbot();

    $new_assistant->setId($response['id']);
    $new_assistant->setInstructions($response['instructions']);
    $new_assistant->setImage($response['metadata']['assistant_image']);

    $new_assistant->save();

    wp_send_json_success([
        "assistant" => $response,
    ]);
}

// add_action('wp_ajax_generate_instructions', 'generate_instructions');
function generate_instructions($chatbot_options, $chatbot_name)
{
    if (isset($_FILES['chatbot_image']) && $_FILES['chatbot_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['chatbot_image'];

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;

        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(['message' => 'Tipo de arquivo não permitido: ' . $file['type']]);
            exit;
        }

        if ($file['size'] > $max_size) {
            wp_send_json_error(['message' => 'Arquivo excede o tamanho máximo permitido.']);
            exit;
        }

        $upload_dir = wp_upload_dir();
        $target_path = $upload_dir['path'] . '/' . basename($file['name']);

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $chatbot_image = $upload_dir['url'] . '/' . basename($file['name']);
        } else {
            wp_send_json_error(['message' => 'Falha ao salvar o arquivo.']);
            exit;
        }
    } else {
        $chatbot_image = null;
    }

    $chatbot_trainning = [];

    foreach ($chatbot_options as $option) {
        $training_phrase = $option['training_phrase'];
        $resposta = $option['resposta'];

        if ($option['field_type'] == 'file') {
            $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $resposta);

            if (file_exists($file_path)) {
                $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

                if ($file_extension == 'pdf') {
                    $parser = new Parser();
                    $pdf = $parser->parseFile($file_path);
                    $file_content = $pdf->getText();
                    // plugin_log(print_r($file_content , true));
                } elseif (in_array($file_extension, ['mp3', 'wav', 'm4a', 'ogg'])) {
                    $file_content = transcribe_audio_with_whisper($file_path);
                    // plugin_log(print_r($file_content , true));
                } else {
                    $file_content = file_get_contents($file_path);
                }

                if (!empty($file_content)) {
                    $file_content = mb_convert_encoding($file_content, 'UTF-8', 'UTF-8');
                    $file_content = preg_replace('/[^\x20-\x7E\n\r\t]/u', '', $file_content);
                }

                $sanitized_file_content = substr($file_content, 0, 5000);
                $chatbot_trainning[] = $training_phrase . ' ' . $sanitized_file_content;
            }
        } else {
            $chatbot_trainning[] = $training_phrase . ' ' . $resposta;
        }
    }

    // foreach ($chatbotFixedQuestions as $question) {
    //     $chatbot_trainning[] = $question['response'];
    // }

    $training_context = implode("\n", $chatbot_trainning);

    plugin_log('-------- TRAINING CONTEXT --------');
    plugin_log(print_r($training_context, true));

    return ([
        "assistant_name" => $chatbot_name,
        "assistant_instructions" => $training_context,
        "assistant_image" => $chatbot_image,
    ]);
}

add_action('wp_ajax_upload_image', 'upload_image');
function upload_image()
{
    if (!isset($_FILES['file'])) {
        wp_send_json_error(['message' => 'Nenhum arquivo enviado.']);
        return;
    }

    $file = $_FILES['file'];

    // Validação do arquivo (opcional)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        wp_send_json_error(['message' => 'Formato de imagem inválido.']);
        return;
    }

    // Salvar a imagem na biblioteca de mídia do WordPress
    $upload = wp_handle_upload($file, ['test_form' => false]);

    if (isset($upload['error'])) {
        wp_send_json_error(['message' => 'Erro ao enviar a imagem.', 'error' => $upload['error']]);
        return;
    }

    // Criar anexo no WordPress
    $attachment = [
        'post_mime_type' => $upload['type'],
        'post_title' => sanitize_file_name($file['name']),
        'post_content' => '',
        'post_status' => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    $image_url = wp_get_attachment_url($attach_id);

    wp_send_json_success(['url' => $image_url]);
}


add_action('wp_ajax_manage_usage', 'manage_usage');
function manage_usage(){
    $usage = $_POST['usage'] ?? null;

    plugin_log("----Usage----");
    plugin_log(print_r($usage, true));

    UsageService::updateUsage($usage);

    $updatedUsagePercentages = UsageService::usagePercentages();
    
    wp_send_json_success([
        "usage" => $updatedUsagePercentages,
    ]);

}

// add_action('wp_ajax_create_thread', 'create_thread');
// function create_thread()
// {
//     $api_url = "https://api.openai.com/v1/threads";
//     $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;;

//     $data = [];

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
//         echo 'Erro: ' . curl_error($ch);
//     } 
//     // else {
//     //     echo "Código HTTP: $http_code\n";
//     //     echo "Resposta: $response";
//     // }

//     curl_close($ch);

//     $response = json_decode($response, true);

//     wp_send_json_success( [
//             "thread_id" => $response['id']
//         ] );
//     // var_dump($response);
//     // return $response['id'];

// }

// add_action('wp_ajax_add_message_to_thread', 'add_message_to_thread');
// function add_message_to_thread()
// {
//     if (!UsageService::usageControl()) {
//         wp_send_json_error(['message' => 'Limite de tokens atingido.']);
//         return;
//     }


//     $message = $_POST['mensagem'] ?? null;
//     $thread_id = $_POST['sessionId'] ?? null;
//     $assistant_id = $_POST['assistantId'] ?? null;

//     // plugin_log(print_r($message, true));
//     // plugin_log('-------- FRONT END THREAD ID --------');
//     // plugin_log(print_r($thread_id, true));
//     // plugin_log('-------- FRONT END ASSISTANT ID --------');
//     // plugin_log(print_r($assistant_id, true));

//     // if ( empty($thread_id) ) {
//     //     $thread_id = create_thread();
//     //     plugin_log('-------- PASSEI AQUII --------');

//     // }

//     // plugin_log('-------- CURRENT THREAD ID --------');
//     // plugin_log(print_r($thread_id, true));

//     $api_url = "https://api.openai.com/v1/threads/". $thread_id . "/messages";
//     $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;;

//     $data = [
//         "role" => "user",
//         "content" => $message
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

//     $response = json_decode($response, true);

//     if (curl_errno($ch)) {
//         echo 'Erro: ' . curl_error($ch);
//     }
//     // else {
//     //     echo "Código HTTP: $http_code\n";
//     //     echo "Resposta: $response";
//     // }

//     // plugin_log('-------- MESSAGE ADDED TO THREAD --------');
//     // plugin_log(print_r($response, true));

//     curl_close($ch);

//     // $run_id = create_run($thread_id, $assistant_id);

//     // plugin_log('-------- CURRENT Run ID --------');
//     // plugin_log(print_r($run_id, true));

//     wp_send_json_success([
//         'msg' => $response['id'],
//     ]);
// }

// add_action('wp_ajax_create_run', 'create_run');
// function create_run()
// {
//     $thread_id = $_POST['sessionId'] ?? null;
//     $assistant_id = $_POST['assistantId'] ?? null;

//     $user_id = get_current_user_id();

//     $ass = new Chatbot();
//     $assistant = $ass->getChatbotById($assistant_id, $user_id);

//     $instructions = null;

//     $messages = get_messages($thread_id);

//     if (count($messages) < 2) {
//         $instructions = treat_assistant_instructions($assistant);
//     }
//     // se a thread tiver mais de uma mensagem (ou não enviar as instruções novamente);

//     plugin_log('------- Assistente instructions ------');
//     plugin_log(print_r($instructions, true));

//     $api_url = "https://api.openai.com/v1/threads/". $thread_id . "/runs";
//     $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;;

//     $data = [
//         "assistant_id" => $assistant_id,
//         "instructions" => $instructions,
//         // "max_prompt_tokens" => 350,
//         // "max_completion_tokens" => 300
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
//         wp_send_json_error(['message' => 'Erro na requisição: ' . curl_error($ch)]);
//         return;
//     }

//     $response = json_decode($response, true);

//     // plugin_log('------- Prompt tokens ------');
//     // plugin_log(print_r($response, true));

//     if (!$response || !isset($response['id'])) {
//         wp_send_json_error(['message' => 'Erro ao criar run', 'response' => $response]);
//         return;
//     }

//     wp_send_json_success(['run_id' => $response['id']]);
// }

// add_action('wp_ajax_retrieve_run', 'retrieve_run');

// function retrieve_run()
// {

//     $thread_id = $_POST['sessionId'] ?? null;
//     $run_id = $_POST['runId'] ?? null;

//     $api_url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs/" . $run_id . "";
//     $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;;

//     $headers = [
//         "Authorization: Bearer $api_key",
//         "OpenAI-Beta: assistants=v2"
//     ];

//     $ch = curl_init($api_url);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//     $response = curl_exec($ch);
//     $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//     if (curl_errno($ch)) {
//         echo 'Erro: ' . curl_error($ch);
//     }
//     // else {
//     //     echo "Código HTTP: $http_code\n";
//     //     echo "Resposta: $response";
//     // }

//     curl_close($ch);

//     $response_data = json_decode($response, true);

//     UsageService::updateUsage($response_data);

//     plugin_log('------- Retrive runnn ------');
//     plugin_log(print_r($response_data, true));
//     // var_dump($response_data);

//     // var_dump(print_r($response_data['usage'], true));

//     wp_send_json_success(['run' => $response_data]);

// }

// function get_messages($thread_id) {
//     $api_url = "https://api.openai.com/v1/threads/" . $thread_id . "/messages";
//     $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;

//     $headers = [
//         "Content-Type: application/json",
//         "Authorization: Bearer $api_key",
//         "OpenAI-Beta: assistants=v2"
//     ];

//     $ch = curl_init($api_url);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//     $response = curl_exec($ch);
//     $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     curl_close($ch);

//     if (curl_errno($ch)) {
//         return ['error' => 'Erro: ' . curl_error($ch)];
//     }

//     $data = json_decode($response, true);

//     if (json_last_error() !== JSON_ERROR_NONE) {
//         return ['error' => 'Erro ao decodificar JSON: ' . json_last_error_msg()];
//     }

//     return $data['data'] ?? [];
// }

// add_action('wp_ajax_list_messages', 'list_messages');
// function list_messages()
// {
//     $thread_id = $_POST['sessionId'] ?? null;

//     if (!$thread_id) {
//         wp_send_json_error('Thread ID não fornecido');
//         return;
//     }

//     $messages = get_messages($thread_id);

//     if (isset($messages['error'])) {
//         wp_send_json_error($messages['error']);
//         return;
//     }

//     wp_send_json_success($messages);
// }


function treat_assistant_instructions($assistant)
{

    $as = new Chatbot();

    // $question = new Question();
    // $chatbotFixedQuestions = $question->getQuestionsByCategory('Regras Gerais');

    $chatbot_trainning = [];

    foreach ($assistant['chatbot_options'] as $option) {
        $training_phrase = $option['training_phrase'];
        $resposta = $option['resposta'];

        if ($option['field_type'] == 'file') {
            $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $resposta);

            if (file_exists($file_path)) {
                $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

                if ($file_extension == 'pdf') {
                    $parser = new Parser();
                    $pdf = $parser->parseFile($file_path);
                    $file_content = $pdf->getText();
                    // plugin_log(print_r($file_content , true));
                } elseif (in_array($file_extension, ['mp3', 'wav', 'm4a', 'ogg'])) {
                    $file_content = $as->transcribe_audio_with_whisper($file_path);
                    // plugin_log(print_r($file_content , true));
                } else {
                    $file_content = file_get_contents($file_path);
                }

                if (!empty($file_content)) {
                    $file_content = mb_convert_encoding($file_content, 'UTF-8', 'UTF-8');
                    $file_content = preg_replace('/[^\x20-\x7E\n\r\t]/u', '', $file_content);
                }

                $sanitized_file_content = substr($file_content, 0, 5000);
                $chatbot_trainning[] = $training_phrase . ' ' . $sanitized_file_content;
            }
        } else {
            $chatbot_trainning[] = $training_phrase . ' ' . $resposta;
        }
    }

    // foreach ($chatbotFixedQuestions as $question) {
    //     $chatbot_trainning[] = $question['response'];
    // }

    $chatbot_trainning[] = 'seu nome é ' . $assistant['chatbot_name'];

    $training_context = implode("\n", $chatbot_trainning);

    return $training_context;
}

function transcribe_audio_with_whisper($file_path)
{
    $url = 'https://api.openai.com/v1/audio/transcriptions';
    $boundary = uniqid();
    $delimiter = '--------------------------' . $boundary;

    $file_content = file_get_contents($file_path);
    $file_name = basename($file_path);
    $file_mime = mime_content_type($file_path); // Detecta o tipo correto do arquivo

    $file_data = "--$delimiter\r\n" .
        "Content-Disposition: form-data; name=\"file\"; filename=\"$file_name\"\r\n" .
        "Content-Type: $file_mime\r\n\r\n" .
        $file_content . "\r\n" .
        "--$delimiter\r\n" .
        "Content-Disposition: form-data; name=\"model\"\r\n\r\n" .
        "whisper-1\r\n" .
        "--$delimiter--\r\n";

    $headers = [
        "Authorization: Bearer " . OPENAI_API_KEY,
        "Content-Type: multipart/form-data; boundary=$delimiter",
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $file_data,
        ],
    ]);

    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        plugin_log('Erro na solicitação ao Whisper API');
    }

    $result = json_decode($response, true);


    return $result['text'] ?? '';
}
