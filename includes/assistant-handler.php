<?php

use Smalot\PdfParser\Parser;

add_action('wp_ajax_create_assistant', 'create_assistant');
function create_assistant()
{
    $chatbot_options = isset($_POST['chatbot_options']) ? json_decode(stripslashes($_POST['chatbot_options']), true) : [];
    $chatbot_name = $_POST['chatbot_name'] ?? '';
    $chatbot_welcome_message = $_POST['chatbot_welcome_message'] ?? '';
    $user_id = get_current_user_id();

    $api_url = "https://api.openai.com/v1/assistants";
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;

    $assistant_dto = generate_instructions($chatbot_options, $chatbot_name);

    // tools
    $tools = [
        ["type" => "file_search"]
    ];

    $is_connected = GoogleCalendarController::get_valid_access_token($user_id);
    if ($is_connected) {
        $tools[] = [
            "type" => "function",
            "function" => AssistantHelpers::assistant_tool_get_calendar_slots()
        ];
        $tools[] = [
            "type" => "function",
            "function" => AssistantHelpers::assistant_tool_create_calendar_event()
        ];
        $tools[] = [
            "type" => "function",
            "function" => AssistantHelpers::assistant_tool_delete_calendar_event()
        ];
    }

    // $tools[] = [
    //     "type" => "function",
    //     "function" => AssistantHelpers::assistant_tool_get_calendar_slots()
    // ];
    // $tools[] = [
    //     "type" => "function",
    //     "function" => AssistantHelpers::assistant_tool_create_calendar_event()
    // ];
    // $tools[] = [
    //     "type" => "function",
    //     "function" => AssistantHelpers::assistant_tool_delete_calendar_event()
    // ];

    $data = [
        "instructions" => $assistant_dto['assistant_instructions'],
        "name" => $assistant_dto['assistant_name'],
        "tools" => $tools,
        "model" => "gpt-4.1-mini",
        "temperature" => 0.6,
        "metadata" => !empty($assistant_dto['assistant_image']) ? (object) [
            "assistant_image" => $assistant_dto['assistant_image']
        ] : (object) []
    ];

    /**
     * Verificar se o assistente já tem um vector store e associar ao file Search
     */
    $vector_store_label = "Vector Store para {$assistant_dto['assistant_name']}";
    global $wpdb;

    // 1️⃣ Buscar vector store existente
    $table_stores = $wpdb->prefix . 'vector_stores';
    $vector_store = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_stores WHERE name = %s",
        $vector_store_label
    ));

    if ($vector_store) {
        error_log(print_r('entrou', true));
        error_log(print_r($vector_store, true));
        $data['tool_resources'] = [
            "file_search" => [
                "vector_store_ids" => [$vector_store->vector_store_id]
            ]
        ];
    }


    if (!empty($chatbot_welcome_message)) {
        $data['metadata']->welcome_message = $chatbot_welcome_message;
    }

    $tools[] = [
        "type" => "function",
        "function" => AssistantHelpers::assistant_tool_send_to_whatsapp()
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

    if ($vector_store) {
        $wpdb->update(
            $table_stores,
            ['assistant_id' => json_decode($response, true)['id']],
            ['vector_store_id' => $vector_store->vector_store_id]
        );
    }

    $new_assistant = new Chatbot();

    $new_assistant->setAssistant($response);

    $response = json_decode($response, true);

    $new_assistant->setId($response['id']);
    $new_assistant->setInstructions($assistant_dto['assistant_instructions']);
    $new_assistant->setImage($response['metadata']['assistant_image']);

    $new_assistant->save();

    wp_send_json_success([
        "assistant" => $response,
    ]);
}

add_action('wp_ajax_delete_assistant', 'delete_assistant');
function delete_assistant()
{
    $assistant_id = $_POST['assistant_id'] ?? '';

    $existing_assistant = new Chatbot();
    $existing_assistant = $existing_assistant->getChatbotByIdII($assistant_id);

    if (empty($existing_assistant)) {
        wp_send_json_error([
            "message" => "Assistente não encontrado"
        ]);
    }

    $api_url = "https://api.openai.com/v1/assistants";
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key",
        "OpenAI-Beta: assistants=v2"
    ];

    $ch = curl_init($api_url . '/' . $assistant_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('Erro na criação do Assistente' . curl_error($ch));
    }

    curl_close($ch);

    $response = json_decode($response, true);
    // plugin_log(print_r($response, true));

    $deleted_status = isset($response['deleted']) && $response['deleted'] ? $response['deleted'] : 'Assistente não deletado na API';
    // plugin_log(print_r($deleted_status, true));

    $deleted_assistant = new Chatbot();
    $deleted_db_status = $deleted_assistant->adminDeleteChatbot($assistant_id);
    // plugin_log(print_r($deleted_db_status, true));

    wp_send_json_success([
        // "assistant" => $response,
        "deleted" => $deleted_status,
        "deletion_info" => [
            "API" => $deleted_status,
            "DB" => $deleted_db_status
        ]
    ]);
}

function generate_instructions($chatbot_options, $chatbot_name)
{
    // plugin_log('-------- entrou no generate_instructions --------');
    // plugin_log(print_r($chatbot_options, true));

    global $wpdb;

    $behavior_instructions = [];
    $knowledge_base = [];
    $chatbot_image = null;

    $question = new Question();
    $chatbotFixedQuestions = $question->getQuestionsByCategory('Regras Gerais');
    foreach ($chatbotFixedQuestions as $fixedQuestion) {
        $behavior_instructions[] = $fixedQuestion['response'];
    }

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
    }

    foreach ($chatbot_options as $categoria => $perguntas) {
        // Normaliza o nome da categoria para comparação
        $categoria_normalizada = mb_strtolower(trim($categoria));
        if (in_array($categoria_normalizada, ['configuracao', 'comportamento'])) {
            $destino = &$behavior_instructions;
        } else {
            $destino = &$knowledge_base;
        }

        foreach ($perguntas as $option) {
            $training_phrase = $option['training_phrase'] ?? '';
            $resposta = $option['resposta'] ?? '';

            if (empty($resposta) || (is_array($resposta) && count(array_filter($resposta)) === 0)) {
                continue;
            }

            if ($option['pergunta'] === 'Documentos anexos') {
                $destino[] = $training_phrase . ' ' . $resposta[0];
                continue;
            }

            if (($option['field_type'] ?? '') == 'file') {
                $respostas = is_array($resposta) ? $resposta : [$resposta];
                foreach ($respostas as $respostaItem) {
                    $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $respostaItem);
                    if (file_exists($file_path)) {
                        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
                        if ($file_extension == 'pdf') {
                            $vector_store_label = "Vector Store para {$chatbot_name}";

                            $vector_store = $wpdb->get_row(
                                $wpdb->prepare(
                                    'SELECT * FROM wp_vector_stores WHERE name = %s',
                                    $vector_store_label
                                )
                            );

                            error_log("Encontrei vector store: " . print_r($vector_store, true));

                            if (empty($vector_store)) {
                                $parser = new Parser();
                                $pdf = $parser->parseFile($file_path);
                                $file_content = $pdf->getText();
                            }
                        } elseif (in_array($file_extension, ['mp3', 'wav', 'm4a', 'ogg'])) {
                            $file_content = transcribe_audio_with_whisper($file_path);
                        } else {
                            $vector_store_label = "Vector Store para {$chatbot_name}";

                            $vector_store = $wpdb->get_row(
                                $wpdb->prepare(
                                    'SELECT * FROM wp_vector_stores WHERE name = %s',
                                    $vector_store_label
                                )
                            );

                            if (empty($vector_store)) {
                                $file_content = file_get_contents($file_path);
                            }
                        }
                        if (!empty($file_content)) {
                            $file_content = mb_convert_encoding($file_content, 'UTF-8', 'UTF-8');
                            $file_content = mb_convert_encoding($file_content, 'UTF-8', 'UTF-8'); // garante encoding
                            $file_content = preg_replace('/[\x00-\x1F\x7F]/u', '', $file_content); // remove apenas caracteres de controle (invisíveis)
                            $sanitized_file_content = substr($file_content, 0);
                            $destino[] = $training_phrase . ' ' . $sanitized_file_content;
                        }
                    }
                }
            } elseif (($option['pergunta'] ?? '') == "Adicione Links de conhecimento:") {
                // plugin_log('-------- entrou no elseif do generate_instructions --------');
                $url = $resposta;
                $depth = 2;
                if (!empty($url)) {
                    $text = crawl_page($url, $depth);
                    $destino[] = $training_phrase . ' ' . $text;
                }
            } else {
                if (stripos($training_phrase, 'seu nome é') !== false) {
                    continue;
                }

                $destino[] = $training_phrase . ' ' . $resposta;
            }
        }
    }
    // Junta instruções de comportamento e base de conhecimento
    $training_context = implode("\n", array_merge($behavior_instructions, $knowledge_base));

    $formatted_output = "Seu nome é {$chatbot_name}.\n\n";

    $formatted_output .= "Instruções de comportamento:\n";
    foreach ($behavior_instructions as $instruction) {
        $instruction = trim($instruction);
        if ($instruction !== '') {
            $formatted_output .= "- {$instruction}\n";
        }
    }

    $formatted_output .= "\nBase de conhecimento:\n";
    foreach ($knowledge_base as $knowledge) {
        $knowledge = trim($knowledge);
        if ($knowledge !== '') {
            $formatted_output .= "- {$knowledge}\n";
        }
    }


    plugin_log('-------- OUTPUT FORMATADO FINAL --------');
    plugin_log($formatted_output);

    return ([
        "assistant_name" => $chatbot_name,
        "assistant_instructions" => $formatted_output,
        "assistant_image" => $chatbot_image,
        // "files_id" => $files_id
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

// add_action('wp_ajax_manage_usage', 'manage_usage');
function manage_usage($usage = null)
{

    if (empty($usage)) {
        $usage = $_POST['usage'] ?? null;
    }

    // plugin_log("----Usage----");
    // plugin_log(print_r($usage, true));

    UsageService::updateUsage($usage);
    // $updatedUsagePercentages = UsageService::usagePercentages();

    $usage_check = UsageService::usageControl();

    // plugin_log('-------- USAGE CHECK --------');
    // plugin_log(print_r($usage_check, true));

    $warning_message = null;
    if (is_array($usage_check) && isset($usage_check['message'])) {
        $warning_message = $usage_check['message'];
    }

    $updatedUsagePercentages = UsageService::usagePercentages();

    // wp_send_json_success([
    //     "usage" => $updatedUsagePercentages,
    //     "warning" => $warning_message
    // ]);

    return [
        "usage" => $updatedUsagePercentages,
        "warning" => $warning_message
    ];
}


// 
// 
//  HANDLE MESSAGES
// 
// 

add_action('wp_ajax_handle_assistant_message', 'handle_assistant_message');
function handle_assistant_message($isWhatsapp = false, $whatsappMessage = null, $thread_id = null)
{
    // plugin_log('--- HANDLE ASSISTANT FUNCTION ---');
    global $wpdb;

    $message = $_POST['message'] ?? null;
    $thread_id = $_POST['session_id'] ?? $thread_id;
    $assistant_id = $_POST['assistant_id'] ?? null;

    if ($isWhatsapp && $whatsappMessage) {
        $message = $whatsappMessage->getMessage() ?? null;
        // desenvolver thread_id

        $assistant_id = WhatsappInstance::findByInstanceName($whatsappMessage->getInstanceName())->getAssistant();

        // error_log("Assistant id WhatsApp instance: " . print_r($assistant_id, true));

        $thread_id = $whatsappMessage->getThreadId() ?? null;
    }

    if(!check_user_message_quota($assistant_id)){
        wp_send_json_error(['message' => 'Limite de mensagens atingido']);
        return;
    }

    $assistant = new Chatbot();
    $assistant = $assistant->getChatbotByIdII($assistant_id);

    $instance = WhatsappInstance::findByAssistant($assistant_id);

    $usage = null;

    if (empty($assistant_id)) {
        wp_send_json_error(['message' => 'Nenhum assistente encontrado.']);
        return;
    }

    /*
    *   CRIAR THREAD
    */
    if (empty($thread_id)) {
        $thread_id = create_thread();
    }

    /*
    *
    *   SALVAR MENSAGEM DO USUÁRIO NO BANCO
    *    
    */
    if (empty($isWhatsapp) && empty($whatsappMessage)) {
        $message_obj = [
            "message" => $message,
            "thread_id" => $thread_id,
            "from_me" => 0,
            "assistant_id" => $assistant_id,
            // "date" => new DateTime('now')
        ];

        MessageService::processMessage($message_obj);
    }

    add_message_to_thread($thread_id, $message);

    /**
     *  Início da RUN
     */
    $runInstruction = $assistant['chatbot_options'];

    if ($isWhatsapp) {
        $runInstruction .= "\n Funções:\n";
        $runInstruction .= AssistantHelpers::whatsappFunctionsPrompt();
    }
    if (!$isWhatsapp) {
        $webFunctions = AssistantHelpers::webFunctionsPrompt();
        if (!empty(trim($webFunctions)) || $instance) {
            $runInstruction .= "\n Funções:\n";
        }
        if (!empty(trim($webFunctions))) {
            $runInstruction .= $webFunctions;
        }
        if ($instance) {
            $runInstruction .= AssistantHelpers::webAndWhatsappPrompt();
        }
    }

    // Google client e funções
    $is_connected = GoogleCalendarController::get_valid_access_token($user_id);
    if ($is_connected) {
        $runInstruction .= "\n Funções Calendar:\n";
        $runInstruction .= AssistantHelpers::calendarFunctionPrompt();
    }

    // APAGAR ASSISTENTE EXPO
    if ($assistant_id == "asst_x6lc89gAv4hNlWdeuWGxNANn") {
        $runInstruction .= "\n Funções EXPO:\n";
        $runInstruction .= AssistantHelpers::sendFileToUser();
    }

    // error_log('--- Run instruction ---');
    // error_log(print_r($runInstruction, true));


    // plugin_log('--- RUNNNN FUUUUNCTION ---');
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;
    $api_url = "https://api.openai.com/v1/threads/$thread_id/runs";

    $data = [
        "assistant_id" => $assistant_id,
        "stream" => true,
        "instructions" => $runInstruction
    ];

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key",
        "OpenAI-Beta: assistants=v2"
    ];

    $assistant_message = "";

    // error_log("Enviando mensagem para o assistente: $message");

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Captura toda a resposta da API
    $response = curl_exec($ch);
    // error_log(print_r($response, true));

    if (curl_errno($ch)) {
        throw new Exception('Erro no cURL: ' . curl_error($ch));
        // plugin_log('Erro no cURL: ' . curl_error($ch));
    }

    curl_close($ch);

    // $response = json_decode($response, true);

    error_log('--- Resposta completa da OpenAI ---');
    error_log(print_r($response, true));

    $run_id = null;

    // Divide a resposta por linha
    $lines = explode("\n", $response);

    foreach ($lines as $line) {
        $line = trim($line);

        // Log para verificar cada linha recebida
        // plugin_log("Linha recebida: " . $line);

        if (strpos($line, 'data:') === 0) {
            $jsonData = trim(substr($line, 5));

            // Verifica se o JSON é válido antes de tentar decodificar
            if (!empty($jsonData) && $jsonData !== "[DONE]") {
                $decodedData = json_decode($jsonData, true);

                // error_log('--- JSON Decodificado ---');
                // error_log(print_r($decodedData, true));

                if (!$run_id && isset($decodedData['id'])) {
                    $run_id = $decodedData['id'];
                    // plugin_log(">> RUN_ID detectado: $run_id");
                }

                if (isset($decodedData['delta']['content'])) {
                    foreach ($decodedData['delta']['content'] as $chunkPart) {
                        if (isset($chunkPart['type']) && $chunkPart['type'] === 'text') {
                            $assistant_message .= $chunkPart['text']['value'];
                        }
                    }
                }

                if (isset($decodedData['usage'])) {
                    $usage = $decodedData['usage'];
                }

                if (isset($decodedData['required_action'])) {
                    $required_action = $decodedData['required_action'];

                    if ($required_action['type'] === 'submit_tool_outputs') {
                        foreach ($required_action['submit_tool_outputs']['tool_calls'] as $tool_call) {
                            $tool_call_id = $tool_call['id'];
                            $function_name = $tool_call['function']['name'];
                            $arguments = json_decode($tool_call['function']['arguments'], true);

                            $output = null;

                            if ($function_name === 'get_calendar_slots') {
                                // error_log('entrou calendar slots');

                                // error_log('Assistant ID');
                                // error_log(print_r($assistant_id, true));

                                $instance = new Chatbot();
                                $assistant = $instance->getChatbotByIdII($assistant_id);

                                // error_log('assist');
                                // error_log(print_r($assistant, true));

                                $user_id = $assistant['user_id'];
                                // error_log(print_r($user_id, true));

                                $access_token = GoogleCalendarController::get_valid_access_token($user_id);

                                $output = "Desculpe, não fazemos agendamento.";

                                if (!empty($access_token)) {
                                    $slots = GoogleCalendarService::getAvailableTimeSlots($access_token, 7, $user_id);

                                    $targetDate = $arguments['target_date'] ?? null;
                                    $periodOfDay = $arguments['period_of_day'] ?? null;

                                    if ($targetDate) {
                                        // Exibir os horários detalhados do dia escolhido
                                        $readable = GoogleCalendarService::formatSlotsForDay($slots, $targetDate, $periodOfDay);

                                        if (empty($readable)) {
                                            $output = "Não encontrei horários disponíveis em {$targetDate}. Deseja escolher outro dia?";
                                        } else {
                                            $output = "Claro! Vou te enviar as datas disponíveis:\n\n" .
                                                implode("\n", array_map(
                                                    fn($i, $slot) => ($i + 1) . ". " . $slot,
                                                    array_keys($readable),
                                                    $readable
                                                ));
                                        }
                                    } else {
                                        // Exibir apenas dias + períodos
                                        $readable = GoogleCalendarService::formatDayPeriods($slots);

                                        if (empty($readable)) {
                                            $output = "No momento não há disponibilidade nos próximos dias.";
                                        } else {
                                            $output = "Tenho disponibilidade para agendar sua reunião nos seguintes dias e períodos:\n\n" .
                                                implode("\n", $readable) .
                                                "\n\nQual dia e período você prefere?";
                                        }
                                    }
                                }
                            } elseif ($function_name === 'create_calendar_event') {
                                $instance = new Chatbot();
                                $assistant = $instance->getChatbotByIdII($assistant_id);
                                $user_id = $assistant['user_id'];
                                $access_token = GoogleCalendarController::get_valid_access_token($user_id);

                                $start = $arguments['start'] ?? null;
                                $end = $arguments['end'] ?? null;
                                $name = $arguments['name'] ?? null;
                                $email = $arguments['email'] ?? null;
                                $title = $arguments['name'] ? "Reunião com {$name}" : "Reunião agendada";
                                $extra_attendees = $arguments['extra_attendees'] ?? [];

                                $organizer_email = GoogleCalendarService::getUserEmail($access_token);
                                $attendees = [];

                                if (!empty($email)) {
                                    $attendees[] = [
                                        'email' => $email,
                                        'displayName' => $name ?? ''
                                    ];
                                }

                                if (!empty($organizer_email)) {
                                    $attendees[] = [
                                        'email' => $organizer_email,
                                        'displayName' => 'Organizador'
                                    ];
                                }

                                if (!empty($extra_attendees)) {
                                    foreach ($extra_attendees as $attendee) {
                                        $attendees[] = [
                                            'email' => $attendee['email'],
                                            'displayName' => $attendee['name'] ?? ''
                                        ];
                                    }
                                }

                                // $event = GoogleCalendarService::createEvent($access_token, $title, $start, $end, $email, $name, '', [], true);
                                $event = GoogleCalendarService::createEventWithClient(
                                    $access_token,
                                    $title,
                                    $start,
                                    $end,
                                    $attendees,
                                    '',
                                    true // com Meet
                                );

                                // if (!empty($event)) {
                                //     $newMeet = new Meet();
                                //     $newMeet->setTitle($title);
                                //     $newMeet->setStartTime((new DateTime($start, new DateTimeZone('UTC'))));
                                //     $newMeet->setAssistantId($assistant_id);
                                //     $newMeet->save();

                                //     error_log(print_r($event, true));

                                //     /**
                                //      * Disparar email para organizador
                                //      */
                                //     $emailBody = "Seu evento foi criado no Google Agenda:\n\n" .
                                //                     "Título: {$title}\n" .
                                //                     "Início: {$start}\n" .
                                //                     "Fim: {$end}\n";
                                //     wp_mail($organizer_email, $title, $emailBody);

                                //     $output = "✅ Evento criado: \"$title\" em " . (new DateTime($start))->format('d/m/Y H:i');
                                // } 
                                if (!empty($event)) {
                                    $newMeet = new Meet();
                                    $newMeet->setTitle($title);
                                    $newMeet->setStartTime((new DateTime($start, new DateTimeZone('UTC'))));
                                    $newMeet->setAssistantId($assistant_id);
                                    $newMeet->save();

                                    error_log(print_r($event, true));

                                    // Preparar infos formatadas
                                    $startDate = (new DateTime($start))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i');
                                    $endDate   = (new DateTime($end))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i');

                                    $attendeesList = "";
                                    if (!empty($event['attendees'])) {
                                        foreach ($event['attendees'] as $a) {
                                            $attendeesList .= "- " . $a['email'] . "\n";
                                        }
                                    }

                                    $meetLink = $event['hangoutLink'] ?? '';
                                    $calendarLink = $event['htmlLink'] ?? '';

                                    /**
                                     * Disparar email para organizador
                                     */
                                    $emailBody  = "✅ Seu evento foi criado no Google Agenda!\n\n";
                                    $emailBody .= "📌 Título: {$title}\n";
                                    $emailBody .= "🗓️ Início: {$startDate}\n";
                                    $emailBody .= "⏰ Fim: {$endDate}\n\n";

                                    if ($attendeesList) {
                                        $emailBody .= "👥 Convidados:\n{$attendeesList}\n";
                                    }

                                    if ($meetLink) {
                                        $emailBody .= "🔗 Link do Google Meet: {$meetLink}\n";
                                    }

                                    if ($calendarLink) {
                                        $emailBody .= "📅 Ver no Google Calendar: {$calendarLink}\n";
                                    }

                                    wp_mail($organizer_email, "Evento confirmado: {$title}", $emailBody);

                                    $output = "✅ Evento criado: \"$title\" em {$startDate}";
                                } else {
                                    // Adicione um log ou uma mensagem de erro caso o evento não seja criado
                                    $output = "❌ Não foi possível criar o evento. Por favor, tente novamente.";
                                }

                                // $output = "Confirme novamente o horário, por favor !";
                            } elseif ($function_name === 'solicitar_conversacao_whatsapp') {
                                $instance = WhatsappInstance::findByAssistant($assistant_id);
                                $holeInstance = WhatsappController::fetch_instance_by_name($instance->getInstanceName());
                                $whatsappInstanceNumber = $holeInstance[0]['ownerJid'];

                                $output = "Claro! É só clicar aqui para conversar com a gente no WhatsApp: " . AssistantHelpers::tool_handler_send_to_whatsapp($whatsappInstanceNumber, $thread_id);
                            } elseif ($function_name === 'delete_calendar_event') {
                                // error_log(print_r('Entrou no delete', true));

                                $email = $arguments['email'] ?? null;
                                $name = $arguments['name'] ?? null;
                                $confirm = $arguments['confirm'] ?? false;

                                // error_log(print_r('confirm', true));
                                // error_log(print_r($confirm, true));


                                $instance = new Chatbot();
                                $assistant = $instance->getChatbotByIdII($assistant_id);
                                $user_id = $assistant['user_id'];
                                $access_token = GoogleCalendarController::get_valid_access_token($user_id);

                                $event = GoogleCalendarService::findEventByAttendee($access_token, $email, $name);

                                // error_log(print_r('Eventooo', true));
                                // error_log(print_r($event, true));

                                if (!$event) {
                                    $output = "❌ Nenhum evento encontrado com esse e-mail.";
                                } elseif (!$confirm) {
                                    $dt = new DateTime($event['start']);
                                    $formatter = new IntlDateFormatter(
                                        'pt_BR',
                                        IntlDateFormatter::LONG,
                                        IntlDateFormatter::SHORT,
                                        $dt->getTimezone(),
                                        IntlDateFormatter::GREGORIAN,
                                        "d 'de' MMMM 'às' HH:mm"
                                    );
                                    $formatted = $formatter->format($dt);
                                    $output = "Encontrei a reunião \"{$event['summary']}\" marcada para {$formatted}. Deseja cancelar?";
                                } else {
                                    if ($event['id'] && GoogleCalendarService::deleteEvent($access_token, $event['id'])) {
                                        $output = "✅ Evento cancelado com sucesso.";
                                    } else {
                                        $output = "❌ Não foi possível cancelar o evento. Verifique as informações.";
                                    }
                                }
                            } elseif ($function_name === 'create_human_flag') {
                                // error_log('entrou create_human_flag');
                                $output = "Desculpe. Não transferimos para humanos.";

                                $instanceName = isset($whatsappMessage) && method_exists($whatsappMessage, 'getInstanceName') ? $whatsappMessage->getInstanceName() : null;
                                $remoteJid = isset($whatsappMessage) && method_exists($whatsappMessage, 'getRemoteJid') ? $whatsappMessage->getRemoteJid() : null;
                                // error_log('Instance: ' . print_r($instanceName, true));
                                // error_log('Remote: ' . print_r($remoteJid, true));

                                if (!empty($instanceName) && !empty($remoteJid)) {
                                    $flag = new HumanSessionFlag($remoteJid, $instanceName);
                                    $flag->createOrUpdate();

                                    $output = "Um atendente entrará em contato em instantes";
                                }
                                error_log('Flag: ' . print_r($flag, true));
                            } elseif ($function_name === 'send_file_to_user') {
                                // error_log('entrou send_file_to_user');
                                $output = "Desculpe. Não encontrei o documento.";

                                $fileId = $arguments['file_id'] ?? null;
                                $fileName = $arguments['file_name'] ?? null;
                                // error_log('Instance: ' . print_r($fileId, true));
                                // error_log('Remote: ' . print_r($fileName, true));

                                if (!empty($fileId)) {
                                    $file = StorageController::getFileContent($fileId);
                                    // error_log('File: ' . print_r($file, true));

                                    $localFile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vector_files WHERE file_id = %s", $file['id']));
                                    // error_log('Local file: ' . print_r($localFile, true));

                                    if (empty($localFile)) {
                                        return;
                                    }

                                    $output = "Aqui está o documento: " . $localFile->file_url;
                                }
                            }

                            $assistant_message = $output;

                            // Submete o output para a OpenAI
                            AssistantService::submit_tool_outputs(
                                [[
                                    "tool_call_id" => $tool_call_id,
                                    "output" => $output
                                ]],
                                $thread_id,
                                $run_id
                            );
                        }
                    }
                }
            }
        }
    }

    // error_log('--- Mensagem final gerada ---');
    // error_log(print_r($assistant_message, true));

    /*
    *
    *   SALVAR MENSAGEM DO USUÁRIO NO BANCO
    *    
    */
    if (empty($isWhatsapp) && empty($whatsappMessage)) {
        $assistant_message_obj = [
            "message" => $assistant_message,
            "thread_id" => $thread_id,
            "from_me" => 1,
            "name" => $assistant_id,
            "assistant_id" => $assistant_id,
            // "date" => new DateTime('now')
        ];

        MessageService::processMessage($assistant_message_obj);
    }

    // plugin_log('--- USAGE FINAL da mensagem ---');
    // plugin_log(print_r($usage, true));

    $usageObj = manage_usage($usage);

    if ($isWhatsapp && $whatsappMessage) {

        return [
            'ai_response' => $assistant_message,
            'thread_id' => $thread_id,
            'usage' => $usageObj,
        ];
    }

    wp_send_json_success([
        'ai_response' => $assistant_message,
        'thread_id' => $thread_id,
        'usage' => $usageObj
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

// 
// 
//  END HANDLE MESSAGES
// 
// 

// add_action('wp_ajax_add_message_to_thread', 'add_message_to_thread');


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


// function treat_assistant_instructions($assistant)
// {

//     $as = new Chatbot();

//     // $question = new Question();
//     // $chatbotFixedQuestions = $question->getQuestionsByCategory('Regras Gerais');

//     $chatbot_trainning = [];

//     foreach ($assistant['chatbot_options'] as $option) {
//         $training_phrase = $option['training_phrase'];
//         $resposta = $option['resposta'];

//         if ($option['field_type'] == 'file') {
//             $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $resposta);

//             if (file_exists($file_path)) {
//                 $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

//                 if ($file_extension == 'pdf') {
//                     $parser = new Parser();
//                     $pdf = $parser->parseFile($file_path);
//                     $file_content = $pdf->getText();
//                     // plugin_log(print_r($file_content , true));
//                 } elseif (in_array($file_extension, ['mp3', 'wav', 'm4a', 'ogg'])) {
//                     $file_content = $as->transcribe_audio_with_whisper($file_path);
//                     // plugin_log(print_r($file_content , true));
//                 } else {
//                     $file_content = file_get_contents($file_path);
//                 }

//                 if (!empty($file_content)) {
//                     $file_content = mb_convert_encoding($file_content, 'UTF-8', 'UTF-8');
//                     $file_content = preg_replace('/[^\x20-\x7E\n\r\t]/u', '', $file_content);
//                 }

//                 $sanitized_file_content = substr($file_content, 0, 5000);
//                 $chatbot_trainning[] = $training_phrase . ' ' . $sanitized_file_content;
//             }
//         } else {
//             $chatbot_trainning[] = $training_phrase . ' ' . $resposta;
//         }
//     }

//     // foreach ($chatbotFixedQuestions as $question) {
//     //     $chatbot_trainning[] = $question['response'];
//     // }

//     $chatbot_trainning[] = 'seu nome é ' . $assistant['chatbot_name'];

//     $training_context = implode("\n", $chatbot_trainning);

//     return $training_context;
// }

function transcribe_audio_with_whisper($file_path)
{
    plugin_log('Transcrevendo com Whisper...');
    $file_path = str_replace('\\', '/', $file_path); // Normaliza caminho no Windows

    if (!file_exists($file_path)) {
        plugin_log('Arquivo não encontrado: ' . $file_path);
        return '';
    }

    $ch = curl_init();

    $post_fields = [
        'file' => new CURLFile($file_path),
        'model' => 'whisper-1'
    ];

    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        plugin_log('Erro cURL: ' . curl_error($ch));
    }

    curl_close($ch);

    if ($http_status !== 200) {
        plugin_log("Erro HTTP: $http_status");
        plugin_log("Resposta: " . $response);
        return '';
    }

    $result = json_decode($response, true);
    return $result['text'] ?? '';
}


function crawl_page($url, $depth = 5)
{
    static $seen = array();
    if (isset($seen[$url]) || $depth === 0) {
        return;
    }

    $seen[$url] = true;

    $dom = new DOMDocument('1.0', 'UTF-8');
    @$dom->loadHTMLFile($url);

    // Extrair texto puro
    $text = extract_text($dom);

    // echo "URL:", $url, PHP_EOL, "CONTENT:", PHP_EOL, $text, PHP_EOL, PHP_EOL;
    return $text;
}

function extract_text($dom)
{
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//body//text()'); // Seleciona somente o texto dentro do <body>

    $textContent = [];
    foreach ($nodes as $node) {
        $trimmedText = trim($node->nodeValue);
        if (!empty($trimmedText) && !preg_match('/^(@|document|[^a-zA-Z0-9])/', $trimmedText)) {
            $textContent[] = $trimmedText;
        }
    }

    return implode("\n", $textContent);
}


add_action('wp_ajax_get_assistant_by_id', 'get_assistant_by_id');
function get_assistant_by_id()
{
    $assistant_id = $_POST['assistant_id'] ?? null;

    if (empty($assistant_id)) {
        wp_send_json_error(['message' => 'Nenhum ID de assistente fornecido.']);
        return;
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
        wp_send_json_error(['message' => "Erro ao conectar à API: $error_msg"]);
        return;
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if ($http_code >= 400 || isset($data['error'])) {
        $message = $data['error']['message'] ?? 'Erro desconhecido na API.';
        wp_send_json_error(['message' => $message]);
        return;
    }

    wp_send_json_success(['assistant' => $data]);
}
