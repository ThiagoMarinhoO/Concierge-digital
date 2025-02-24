<?php

use Smalot\PdfParser\Parser;

add_action('wp_ajax_create_assistant', 'create_assistant');
function create_assistant()
{
    $api_url = "https://api.openai.com/v1/assistants";
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;;

    $chatbot_options = $_POST['chatbot_options'] ?? '';
    $chatbot_name = $_POST['chatbot_name'] ?? '';
    $chatbot_welcome_message = $_POST['chatbot_welcome_message'] ?? '';

    $question = new Question();
    $chatbotFixedQuestions = $question->getQuestionsByCategory('Regras Gerais');

    $chatbot_trainning = [];

    foreach ($chatbotFixedQuestions as $question) {
        $chatbot_trainning[] = $question['response'];
    }

    $training_context = implode("\n", $chatbot_trainning);

    if (isset($_FILES['chatbot_image']) && $_FILES['chatbot_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['chatbot_image'];

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;

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

    $data = [
        // "instructions" => $training_context,
        // 
        // NOTAS:
        //  PARAR DE PASSAR INFORMAÇÕES QUE VÃO COMPROMETER O INTELECTO DO ASSISTENTE. PARAR DE DITAR COMO VAI SE COMPORTAR, COMO FALAR, QUE TRABALHA PRA PLATAFORMA TAL. INFORMAÇÕES DESNECESSSÁRIAS
        //   
        "instructions" => "Você é o oncobot. Um assistente da plataforma https://clinicaoncologica.com.br.br. Você deve auxiliar os usuários e retirar suas dúvidas acerca do Câncer",
        "name" => $chatbot_name,
        "tools" => [["type" => "code_interpreter"]],
        "model" => "gpt-3.5-turbo"
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
        echo 'Erro: ' . curl_error($ch);
    }

    curl_close($ch);

    $responseData = json_decode($response, true);

    // plugin_log(print_r($responseData, true));

    error_log($responseData['id']);

    $new_assistant = new Chatbot();

    $new_assistant->setId($responseData['id']);
    $new_assistant->setName($chatbot_name);
    $new_assistant->setWelcomeMessage($chatbot_welcome_message);
    $new_assistant->setInstructions(json_encode($chatbot_options));
    $new_assistant->setImage($chatbot_image);

    $new_assistant->save();

    wp_send_json_success([
        'assistant' => [
            'id' => $new_assistant->getId(),
            'name' => $new_assistant->getName(),
            'welcomeMessage' => $new_assistant->getWelcomeMessage(),
            'instructions' => $new_assistant->getInstructions(),
            'image' => $new_assistant->getImage(),
        ]
    ]);
}

add_action('wp_ajax_create_thread', 'create_thread');
function create_thread()
{
    $api_url = "https://api.openai.com/v1/threads";
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;;

    $data = [];

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
        echo 'Erro: ' . curl_error($ch);
    } 
    // else {
    //     echo "Código HTTP: $http_code\n";
    //     echo "Resposta: $response";
    // }

    curl_close($ch);

    $response = json_decode($response, true);

    wp_send_json_success( [
            "thread_id" => $response['id']
        ] );
    // var_dump($response);
    // return $response['id'];

}

add_action('wp_ajax_add_message_to_thread', 'add_message_to_thread');
function add_message_to_thread()
{
    if (!UsageService::usageControl()) {
        wp_send_json_error(['message' => 'Limite de tokens atingido.']);
        return;
    }


    $message = $_POST['mensagem'] ?? null;
    $thread_id = $_POST['sessionId'] ?? null;
    $assistant_id = $_POST['assistantId'] ?? null;

    // plugin_log(print_r($message, true));
    // plugin_log('-------- FRONT END THREAD ID --------');
    // plugin_log(print_r($thread_id, true));
    // plugin_log('-------- FRONT END ASSISTANT ID --------');
    // plugin_log(print_r($assistant_id, true));

    // if ( empty($thread_id) ) {
    //     $thread_id = create_thread();
    //     plugin_log('-------- PASSEI AQUII --------');

    // }

    // plugin_log('-------- CURRENT THREAD ID --------');
    // plugin_log(print_r($thread_id, true));

    $api_url = "https://api.openai.com/v1/threads/". $thread_id . "/messages";
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;;

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

    $response = json_decode($response, true);

    if (curl_errno($ch)) {
        echo 'Erro: ' . curl_error($ch);
    }
    // else {
    //     echo "Código HTTP: $http_code\n";
    //     echo "Resposta: $response";
    // }

    // plugin_log('-------- MESSAGE ADDED TO THREAD --------');
    // plugin_log(print_r($response, true));

    curl_close($ch);

    // $run_id = create_run($thread_id, $assistant_id);

    // plugin_log('-------- CURRENT Run ID --------');
    // plugin_log(print_r($run_id, true));

    wp_send_json_success([
        'msg' => $response['id'],
    ]);
}

add_action('wp_ajax_create_run', 'create_run');
function create_run()
{
    $thread_id = $_POST['sessionId'] ?? null;
    $assistant_id = $_POST['assistantId'] ?? null;

    $user_id = get_current_user_id();

    $ass = new Chatbot();
    $assistant = $ass->getChatbotById($assistant_id, $user_id);

    $instructions = null;

    $messages = get_messages($thread_id);

    if (count($messages) < 2) {
        $instructions = treat_assistant_instructions($assistant);
    }
    // se a thread tiver mais de uma mensagem (ou não enviar as instruções novamente);

    plugin_log('------- Assistente instructions ------');
    plugin_log(print_r($instructions, true));

    $api_url = "https://api.openai.com/v1/threads/". $thread_id . "/runs";
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;;

    $data = [
        "assistant_id" => $assistant_id,
        "instructions" => $instructions,
        // "max_prompt_tokens" => 350,
        // "max_completion_tokens" => 300
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
        wp_send_json_error(['message' => 'Erro na requisição: ' . curl_error($ch)]);
        return;
    }

    $response = json_decode($response, true);

    // plugin_log('------- Prompt tokens ------');
    // plugin_log(print_r($response, true));

    if (!$response || !isset($response['id'])) {
        wp_send_json_error(['message' => 'Erro ao criar run', 'response' => $response]);
        return;
    }

    wp_send_json_success(['run_id' => $response['id']]);
}

add_action('wp_ajax_retrieve_run', 'retrieve_run');

function retrieve_run()
{

    $thread_id = $_POST['sessionId'] ?? null;
    $run_id = $_POST['runId'] ?? null;

    $api_url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs/" . $run_id . "";
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;;

    $headers = [
        "Authorization: Bearer $api_key",
        "OpenAI-Beta: assistants=v2"
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo 'Erro: ' . curl_error($ch);
    }
    // else {
    //     echo "Código HTTP: $http_code\n";
    //     echo "Resposta: $response";
    // }

    curl_close($ch);

    $response_data = json_decode($response, true);
    
    UsageService::updateUsage($response_data);

    plugin_log('------- Retrive runnn ------');
    plugin_log(print_r($response_data, true));
    // var_dump($response_data);

    // var_dump(print_r($response_data['usage'], true));

    wp_send_json_success(['run' => $response_data]);

}

function get_messages($thread_id) {
    $api_url = "https://api.openai.com/v1/threads/" . $thread_id . "/messages";
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key",
        "OpenAI-Beta: assistants=v2"
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (curl_errno($ch)) {
        return ['error' => 'Erro: ' . curl_error($ch)];
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Erro ao decodificar JSON: ' . json_last_error_msg()];
    }

    return $data['data'] ?? [];
}

add_action('wp_ajax_list_messages', 'list_messages');
function list_messages()
{
    $thread_id = $_POST['sessionId'] ?? null;

    if (!$thread_id) {
        wp_send_json_error('Thread ID não fornecido');
        return;
    }

    $messages = get_messages($thread_id);

    if (isset($messages['error'])) {
        wp_send_json_error($messages['error']);
        return;
    }

    wp_send_json_success($messages);
}


function treat_assistant_instructions($assistant) {

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