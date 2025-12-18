<?php

use Smalot\PdfParser\Parser;

add_action('wp_ajax_create_assistant', 'create_assistant');
function create_assistant()
{
    $chatbot_options = isset($_POST['chatbot_options']) ? json_decode(stripslashes($_POST['chatbot_options']), true) : [];
    $chatbot_name = $_POST['chatbot_name'] ?? '';
    $chatbot_welcome_message = $_POST['chatbot_welcome_message'] ?? '';
    $user_id = get_current_user_id();

    $user_policy = user_can($user_id, 'edit_assistants');
    if (empty($user_policy)) {
        wp_send_json_error(
            [
                'message' => 'Você não está autorizado a realizar esta ação.'
            ],
            401
        );
    }
    global $wpdb;

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
        // error_log(print_r('entrou', true));
        // error_log(print_r($vector_store, true));
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

require_once plugin_dir_path(__FILE__) . 'PromptBuilder.php';

function generate_instructions($chatbot_options, $chatbot_name)
{
    global $wpdb;

    $builder = new PromptBuilder($chatbot_name);
    $upload_results = []; // Track upload results for status feedback

    // 1. Regras Gerais (Perguntas Fixas)
    $question = new Question();
    $chatbotFixedQuestions = $question->getQuestionsByCategory('Regras Gerais');
    foreach ($chatbotFixedQuestions as $fixedQuestion) {
        // Sanitização: Remove tags antigas (como <identidade>) que possam ter ficado no banco
        // Assim garantimos que apenas o texto puro entre no PromptBuilder
        $cleanResponse = strip_tags($fixedQuestion['response']);
        $builder->addInstruction($cleanResponse);
    }

    // 2. Imagem do Chatbot
    $chatbot_image = null;
    if (isset($_FILES['chatbot_image']) && $_FILES['chatbot_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['chatbot_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $upload_dir = wp_upload_dir();
            $target_path = $upload_dir['path'] . '/' . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $chatbot_image = $upload_dir['url'] . '/' . basename($file['name']);
            }
        }
    }

    // 3. Processar Opções do Chatbot
    foreach ($chatbot_options as $categoria => $perguntas) {
        foreach ($perguntas as $option) {
            $training_phrase = $option['training_phrase'] ?? '';
            $resposta = $option['resposta'] ?? '';

            if (empty($resposta) || (is_array($resposta) && count(array_filter($resposta)) === 0)) {
                continue;
            }

            // Personalidade
            if ($option['pergunta'] === 'Qual o estilo da comunicação?') {
                $builder->setPersonality(PersonalitiesHelper::getPersonality($resposta));
                continue;
            }

            // Função Principal
            if (stripos($option['pergunta'], 'função principal') !== false) {
                $builder->setMainFunction($resposta);
                continue;
            }

            // Função Secundária
            if (stripos($option['pergunta'], 'função secundária') !== false || stripos($option['pergunta'], 'funcao secundaria') !== false) {
                $builder->setSecondaryFunction($resposta);
                continue;
            }

            // Nível de Interatividade
            if (stripos($option['pergunta'], 'nível de interatividade') !== false || stripos($option['pergunta'], 'nivel de interatividade') !== false) {
                $builder->setInteractivityLevel($resposta);
                continue;
            }

            // Tamanho das Respostas
            if (stripos($option['pergunta'], 'tamanho das respostas') !== false) {
                $builder->setResponseSize($resposta);
                continue;
            }

            // Fonte de Conhecimento
            if (stripos($option['pergunta'], 'fonte de informação') !== false || stripos($option['pergunta'], 'fonte de conhecimento') !== false) {
                $builder->setKnowledgeSource($resposta);
                continue;
            }

            // Tratamento especial para injeção dinâmica (Legacy preserved)
            if ($option['pergunta'] === 'Documentos anexos') {
                $info = is_array($resposta) ? $resposta[0] : $resposta;
                $info = strip_tags($info); // Vacina: Sanitiza o input
                $builder->addInstruction($training_phrase . ' ' . $info);
                continue;
            }

            // Documentos Anexos (Vector Store)
            if (($option['field_type'] ?? '') == 'file') {
                $respostas = is_array($resposta) ? $resposta : [$resposta];
                foreach ($respostas as $respostaItem) {
                    $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $respostaItem);
                    if (file_exists($file_path)) {
                        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
                        $file_content = '';

                        $vector_store_label = "Vector Store para {$chatbot_name}";
                        $vector_store = $wpdb->get_row($wpdb->prepare('SELECT * FROM wp_vector_stores WHERE name = %s', $vector_store_label));

                        if (empty($vector_store)) {
                            if ($file_extension == 'pdf') {
                                $parser = new Parser();
                                $pdf = $parser->parseFile($file_path);
                                $file_content = $pdf->getText();
                            } elseif (in_array($file_extension, ['mp3', 'wav', 'm4a', 'ogg'])) {
                                $file_content = transcribe_audio_with_whisper($file_path);
                            } else {
                                $file_content = file_get_contents($file_path);
                            }
                        }

                        if (!empty($file_content)) {
                            $file_content = mb_convert_encoding($file_content, 'UTF-8', 'UTF-8');
                            $file_content = preg_replace('/[\x00-\x1F\x7F]/u', '', $file_content);
                            $builder->addKnowledge("Conteúdo do arquivo {$respostaItem}: " . $file_content);
                        }
                        
                        // Track RAG document for listing
                        $filename = basename($respostaItem);
                        $builder->addRagDocument($filename, $file_extension);
                    }
                }
            }
            // Crawler (Links de Conhecimento) - COM ROTEAMENTO INTELIGENTE
            elseif (($option['pergunta'] ?? '') == "Links para Aprendizado") {
                $url = $resposta;
                if (!empty($url)) {
                    // 🧠 DETECÇÃO DE INTENÇÃO
                    $intention = $builder->detectIntent($training_phrase);

                    if ($intention === 'STUDY') {
                        // 📚 MODO ESTUDO: Gerar arquivo .txt e enviar para Vector Store
                        error_log("🔍 INTENTION:STUDY detected for URL: $url");
                        
                        // PRIMEIRO: Obter Vector Store ID para verificar se URL já foi processada
                        $vector_store_label = "Vector Store para {$chatbot_name}";
                        $table_stores = $wpdb->prefix . 'vector_stores';
                        $vector_store = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $table_stores . ' WHERE name = %s', $vector_store_label));
                        $vector_store_id = $vector_store ? $vector_store->vector_store_id : null;
                        
                        if ($vector_store_id) {
                            // VERIFICAR SE URL PRECISA SER RE-SCRAPED
                            $scrape_decision = should_rescrape_url($url, $vector_store_id);
                            error_log("📋 Decisão de scraping: {$scrape_decision['action']} - {$scrape_decision['reason']}");
                            
                            // SKIP: URL já foi processada, não fazer nada
                            if ($scrape_decision['action'] === 'skip') {
                                error_log("⏭️ SKIP: URL já processada, mantendo arquivo existente");
                                $builder->addScrapedUrl($url);
                                $builder->addKnowledge("Base de conhecimento do site $url já anexada ao Vector Store.");
                                continue;
                            }
                            
                            // REPLACE: Deletar arquivo antigo antes de criar novo
                            if ($scrape_decision['action'] === 'replace') {
                                error_log("🔄 REPLACE: Deletando arquivo antigo file_id={$scrape_decision['old_file_id']}");
                                StorageController::deleteVectorStoreFile($vector_store_id, $scrape_decision['old_file_id']);
                                $table_files = $wpdb->prefix . 'vector_files';
                                $wpdb->delete($table_files, ['id' => $scrape_decision['old_record_id']]);
                            }
                        }
                        
                        // SCRAPE: URL nova ou alterada - fazer scraping
                        $file_path = generate_site_content_file($url, $chatbot_name);

                        if ($file_path && file_exists($file_path)) {
                            // 📁 SALVAR CÓPIA LOCAL PARA AUDITORIA
                            $upload_dir = wp_upload_dir();
                            $scraped_folder = $upload_dir['basedir'] . '/scraped_content';

                            // Criar pasta se não existir
                            if (!file_exists($scraped_folder)) {
                                wp_mkdir_p($scraped_folder);
                            }

                            // Nome do arquivo: chatbot_name + domain + timestamp
                            $url_domain = parse_url($url, PHP_URL_HOST);
                            $safe_domain = preg_replace('/[^a-zA-Z0-9_-]/', '_', $url_domain);
                            $safe_chatbot = preg_replace('/[^a-zA-Z0-9_-]/', '_', $chatbot_name);
                            $local_filename = "{$safe_chatbot}_{$safe_domain}_" . date('Y-m-d_H-i-s') . ".txt";
                            $local_path = $scraped_folder . '/' . $local_filename;

                            // Copiar arquivo para pasta permanente
                            if (copy($file_path, $local_path)) {
                                error_log("📁 Cópia local salva em: {$upload_dir['baseurl']}/scraped_content/{$local_filename}");
                            }

                            if ($vector_store_id) {
                                // Upload para OpenAI
                                $fileResponse = StorageController::uploadFile($file_path);

                                if ($fileResponse && !empty($fileResponse['id'])) {
                                    $file_id = $fileResponse['id'];

                                    // Associar ao Vector Store
                                    StorageController::createVectorStoreFile($vector_store_id, $file_id);

                                    // Registrar no banco
                                    $table_files = $wpdb->prefix . 'vector_files';
                                    $wpdb->insert($table_files, [
                                        'file_id' => $file_id,
                                        'vector_store_id' => $vector_store_id,
                                        'file_url' => $url // URL de referência
                                    ]);

                                    error_log("✅ Site content uploaded to Vector Store: file_id=$file_id");
                                    
                                    // Track scraped URL for listing
                                    $builder->addScrapedUrl($url);
                                    

                                    // NO XML: Apenas referência
                                    $builder->addKnowledge("Base de conhecimento do site $url processada e anexada ao Vector Store.");
                                } else {
                                    error_log("❌ Falha ao fazer upload do arquivo para OpenAI após 3 tentativas");
                                    $upload_results[] = ['url' => $url, 'status' => 'failed', 'error' => 'Upload falhou após 3 tentativas'];
                                }
                            } else {
                                error_log("⚠️ Vector Store não encontrado. Fallback para método antigo.");
                                $text = crawl_page($url, 2);
                                if (!empty($text)) {
                                    $builder->addKnowledge("Conteúdo extraído do site:\n" . $text);
                                }
                            }

                            // Limpar arquivo temporário (cópia local já foi salva)
                            @unlink($file_path);
                        } else {
                            error_log("❌ Falha ao gerar arquivo de conteúdo do site");
                        }
                    } else {
                        // 📤 MODO DISPLAY: Adicionar link ao XML (para IA enviar ao cliente)
                        error_log("🔗 INTENTION:DISPLAY detected for URL: $url");
                        $builder->addInstruction("{$training_phrase}: {$url}");
                    }
                }
            }
            // Outras perguntas
            else {
                if (stripos($training_phrase, 'seu nome é') !== false) {
                    continue;
                }
                $cleanResposta = strip_tags($resposta); // Vacina: Sanitiza o input genérico
                $builder->addInstruction($training_phrase . ' ' . $cleanResposta);
            }
        }
    }

    // Determinar status geral do upload
    $upload_success = empty($upload_results) || !in_array('failed', array_column($upload_results, 'status'));
    $upload_errors = array_filter($upload_results, fn($r) => $r['status'] === 'failed');

    return ([
        "assistant_name" => $chatbot_name,
        "assistant_instructions" => $builder->build(),
        "assistant_image" => $chatbot_image,
        "upload_status" => $upload_success ? 'success' : 'partial_failure',
        "upload_results" => $upload_results,
        "upload_errors" => array_values($upload_errors)
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

    /**
     * FALTA O ISWHATSAPP PARA WHATSAPP
     */
    if (!check_user_message_quota($assistant_id)) {
        wp_send_json_error([
            'ai_response' => 'Desculpe. Não consigo ajudar no momento. Por favor tente mais tarde.',
            'message' => 'Limite de mensagens atingido'
        ]);
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
    $runInstruction = "";

    // ActiveCampaign funções
    $activeCampaignSettings = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}active_campaign_variables WHERE assistant_id = %s",
            $assistant_id
        )
    );
    if ($activeCampaignSettings) {
        $runInstruction .= "\n Função ActiveCampaign coleta de leads:\n";
        $runInstruction .= AssistantHelpers::createLeadsFunctionPrompt();
    }

    $runInstruction .= "\n {$assistant['chatbot_options']}\n";

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
    $is_connected = GoogleCalendarController::get_valid_access_token($assistant['user_id']);
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
        // Removed forced tool_choice - AI will automatically use file_search from Vector Store when needed
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

    // error_log('--- Resposta completa da OpenAI ---');
    // error_log(print_r($response, true));

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
                                    $output = "❌ Não foi possível criar o evento. Tente novamente mais tarde.";
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

                                    if (!empty($file)) {
                                        $localFile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vector_files WHERE file_id = %s", $file['id']));
                                        // error_log('Local file: ' . print_r($localFile, true));

                                        if (empty($localFile)) {
                                            return;
                                        }

                                        $output = "Aqui está o documento: " . $localFile->file_url;
                                    } else {
                                        $output = "Desculpe. Não encontrei o documento.";
                                    }
                                }
                            } elseif ($function_name === 'create_leads') {
                                // error_log('entrou create_leads');
                                $output = "Me desculpe. Não consegui criar o lead."; // Output padrão (de falha)

                                $activeCampaignSettings = $wpdb->get_row(
                                    $wpdb->prepare(
                                        "SELECT * FROM {$wpdb->prefix}active_campaign_variables WHERE assistant_id = %s",
                                        $assistant_id
                                    )
                                );


                                $name = $arguments['name'] ?? null;
                                $email = $arguments['email'] ?? null;
                                $phone = $arguments['phone'] ?? null;

                                // error_log('Nome: ' . print_r($name, true));
                                // error_log('Email: ' . print_r($email, true));
                                // error_log('Telefone: ' . print_r($phone, true));

                                if ($activeCampaignSettings) {
                                    $api_url = $activeCampaignSettings->api_url;
                                    $api_key = $activeCampaignSettings->api_key;

                                    if (!empty($name) && !empty($email) && !empty($phone)) {
                                        $service = new ActiveCampaignService($api_url, $api_key);
                                        $firstName = explode(' ', $name)[0] ?? $name;

                                        $contactId = $service->createOrUpdateContact($firstName, $email, $phone);

                                        if ($contactId) {
                                            // Tenta criar o Deal SOMENTE se o contato foi criado/encontrado
                                            $dealName = "Lead - CharlieApp - " . $name;
                                            $dealId = $service->createDealForContact($contactId, $dealName);

                                            if ($dealId) {
                                                $output = "✅ Obrigado. Suas informações foram salvas. Como posso ajudar agora?";
                                            } else {
                                                error_log('Não foi possível criar ou atualizar o deal.');
                                                // $output = "Houve um problema ao criar o registro de atendimento no sistema. Por favor, tente novamente mais tarde.";
                                                $output = "✅ Obrigado. Suas informações foram salvas. Como posso ajudar agora?";
                                            }
                                        } else {
                                            error_log('Não foi possível criar ou atualizar o contato.');
                                            // $output = "Me desculpe. Não consegui salvar suas informações de contato. Verifique o email ou telefone e tente novamente.";
                                            $output = "✅ Obrigado. Suas informações foram salvas. Como posso ajudar agora?";
                                        }
                                    }
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

    $pattern = '/【\d+:\d+(?:-\d+)?†[^】]+】/';
    $assistant_message = preg_replace($pattern, '', $assistant_message);

    $assistant_message = trim($assistant_message);

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

    // $usageObj = manage_usage($usage);
    $limit = get_user_message_limit($assistant['user_id']);
    $used = get_user_total_messages_current_cycle($assistant['user_id']);
    $percent = ($used / ($limit ?? 1)) * 100;

    $usageObj = [
        "total" => $used,
        "limit" => $limit,
        "percentage" => $percent
    ];

    if (empty($assistant_message)) {
        $assistant_message = "Desculpe. Não consigo ajudar no momento. Por favor tente mais tarde.";
    }

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


/**
 * Busca conteúdo de uma URL usando cURL com headers apropriados
 * @param string $url URL para buscar
 * @param int $timeout Timeout em segundos
 * @return string|false Conteúdo HTML ou false em caso de erro
 */
function fetch_url_content($url, $timeout = 15)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; CharlieBot/1.0; +https://projetocharlie.humans.land)',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
            'Cache-Control: no-cache'
        ]
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("fetch_url_content: cURL error - $error - URL: $url");
        return false;
    }

    if ($http_code >= 400) {
        error_log("fetch_url_content: HTTP $http_code - URL: $url");
        return false;
    }

    return $response;
}

/**
 * Faz crawling recursivo de uma página e suas subpáginas
 * @param string $url URL inicial
 * @param int $depth Profundidade de recursão
 * @param bool $reset_cache Se true, limpa o cache de URLs visitadas
 * @return string Conteúdo extraído
 */
function crawl_page($url, $depth = 2, $reset_cache = false)
{
    static $seen = array();

    // Reset cache quando iniciar novo crawl (evita problema de persistência)
    if ($reset_cache) {
        $seen = array();
    }

    // Evita loop infinito e respeita profundidade
    if ($depth === 0 || isset($seen[$url])) {
        return "";
    }

    $seen[$url] = true;

    // 1. Obter conteúdo da página usando cURL
    $html = fetch_url_content($url);
    if ($html === false || empty($html)) {
        error_log("crawl_page: Falha ao acessar URL: $url");
        return "";
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    // 2. Extrair Texto Limpo (Usando a lógica do PromptBuilder)
    // Precisamos instanciar o builder ou usar uma função estática?
    // Como PromptBuilder está disponível, vamos usar uma versão simplificada da limpeza aqui
    // ou apenas extrair o texto principal.

    $text = extract_text($dom);
    $final_content = "URL: {$url}\nCONTENT: {$text}\n\n";

    // 3. Recursão (Se depth > 0)
    if ($depth > 1) {
        $xpath = new DOMXPath($dom);
        $links = $xpath->query('//a/@href');

        $base_url_parts = parse_url($url);
        $base_host = $base_url_parts['host'] ?? '';
        $scheme = $base_url_parts['scheme'] ?? 'http';

        foreach ($links as $link) {
            $href = $link->nodeValue;

            // Normalização de URL básica
            if (strpos($href, 'http') === 0) {
                // URL absoluta
                $href_parts = parse_url($href);
                if (($href_parts['host'] ?? '') !== $base_host) continue; // Só links internos
            } else {
                // URL relativa
                $href = $scheme . '://' . $base_host . '/' . ltrim($href, '/');
            }

            // Ignorar âncoras e arquivos não-html
            if (strpos($href, '#') !== false) continue;
            if (preg_match('/\.(jpg|jpeg|png|gif|pdf|zip)$/i', $href)) continue;

            // 🔒 BLACKLIST: Ignorar URLs de paginação e taxonomia (evita duplicação)
            if (preg_match('/\/(tag|category|page|author|feed)\//i', $href)) continue;

            $final_content .= crawl_page($href, $depth - 1);
        }
    }

    return $final_content;
}

function extract_text($dom)
{
    $xpath = new DOMXPath($dom);

    // Remover scripts e estilos
    $scripts = $xpath->query('//script | //style | //noscript | //header | //footer | //nav');
    foreach ($scripts as $script) {
        $script->parentNode->removeChild($script);
    }

    // Tentar pegar apenas o conteúdo principal
    $main = $xpath->query('//main | //article | //div[@id="content"] | //div[@class="content"]');
    if ($main->length > 0) {
        $content_node = $main->item(0);
    } else {
        $content_node = $dom->getElementsByTagName('body')->item(0);
    }

    if (!$content_node) return '';

    // Extrair texto
    $textContent = trim($content_node->textContent);

    // Limpar espaços múltiplos
    return preg_replace('/\s+/', ' ', $textContent);
}

// function extract_text($dom)
// {
//     $xpath = new DOMXPath($dom);

//     // 1. **Sanitização: Remover tags <script>** (Mantida da sugestão anterior)
//     $scripts = $xpath->query('//script');
//     foreach ($scripts as $script) {
//         $script->parentNode->removeChild($script);
//     }
    
//     // 2. **Remover tags de estilo/CSS e comentários, se houver necessidade de limpeza adicional**
//     $styles = $xpath->query('//style|//comment()');
//     foreach ($styles as $style) {
//         $style->parentNode->removeChild($style);
//     }

//     // 3. **Selecionar elementos principais do corpo para estruturar a transcrição**
//     // Selecionamos todos os descendentes diretos do body que são tipicamente estruturais:
//     $nodes = $xpath->query('//body//*[self::h1 or self::h2 or self::h3 or self::h4 or self::p or self::li or self::ul or self::ol]');

//     $markdownContent = [];

//     foreach ($nodes as $node) {
//         $tag = strtolower($node->nodeName);
//         $text = trim($node->nodeValue);

//         // Ignorar textos vazios ou irrelevantes que sobraram (como espaço em branco de listas <ul>/<li>)
//         if (empty($text) || preg_match('/^(@|document|[^a-zA-Z0-9])/', $text)) {
//              continue;
//         }

//         switch ($tag) {
//             case 'h1':
//                 $markdownContent[] = "\n# " . $text . "\n";
//                 break;
//             case 'h2':
//                 $markdownContent[] = "\n## " . $text . "\n";
//                 break;
//             case 'h3':
//                 $markdownContent[] = "\n### " . $text . "\n";
//                 break;
//             case 'h4':
//                 $markdownContent[] = "\n#### " . $text . "\n";
//                 break;
//             case 'li':
//                 // Verifica se o pai é uma lista ordenada (<ol>) ou não ordenada (<ul>)
//                 $parentTag = strtolower($node->parentNode->nodeName);
//                 if ($parentTag === 'ol') {
//                     // Usar o número do índice para lista ordenada (1. item)
//                     $position = 1;
//                     if ($node->parentNode->hasChildNodes()) {
//                         $count = 0;
//                         foreach ($node->parentNode->childNodes as $child) {
//                             if (strtolower($child->nodeName) === 'li') {
//                                 $count++;
//                             }
//                             if ($child === $node) {
//                                 $position = $count;
//                                 break;
//                             }
//                         }
//                     }
//                     $markdownContent[] = $position . ". " . $text;
//                 } else {
//                     // Lista não ordenada (* item ou - item)
//                     $markdownContent[] = "* " . $text;
//                 }
//                 break;
//             case 'p':
//                 // Parágrafos são separados por linhas duplas no Markdown
//                 $markdownContent[] = $text . "\n";
//                 break;
//             case 'ul':
//             case 'ol':
//                 // Ignora as tags pai <ul> e <ol> para evitar duplicidade, pois o <li> já foi tratado.
//                 break;
//             default:
//                 $markdownContent[] = $text;
//         }
//     }

//     // Unir o array e limpar espaços em branco redundantes no início/fim
//     return trim(implode("\n", $markdownContent));
// }


/**
 * Gera um arquivo .txt temporário com o conteúdo do site (para Vector Store)
 * @param string $url URL do site para fazer scraping
 * @param string $assistant_name Nome do assistente (para identificação do arquivo)
 * @return string|false Caminho do arquivo gerado ou false em caso de erro
 */
function generate_site_content_file($url, $assistant_name)
{
    // Reset cache para garantir crawl limpo a cada chamada
    $text = crawl_page($url, 2, true);

    if (empty($text)) {
        error_log("generate_site_content_file: Nenhum conteúdo extraído de $url");
        return false;
    }

    $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $assistant_name);
    $filename = "site_content_{$safe_name}_" . time() . ".txt";
    $filepath = sys_get_temp_dir() . "/" . $filename;

    $result = file_put_contents($filepath, $text);

    if ($result === false) {
        error_log("generate_site_content_file: Erro ao escrever arquivo $filepath");
        return false;
    }

    error_log("generate_site_content_file: Arquivo gerado com sucesso: $filepath");
    return $filepath;
}

/**
 * Verifica se uma URL precisa ser re-scraped
 * @param string $url URL a verificar
 * @param string $vector_store_id ID do Vector Store
 * @return array ['action' => 'skip'|'scrape'|'replace', 'reason' => string, 'old_file_id' => string|null]
 */
function should_rescrape_url($url, $vector_store_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'vector_files';
    
    // Buscar último arquivo da URL para este vector_store
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id, file_id, file_url FROM {$table} 
         WHERE vector_store_id = %s ORDER BY created_at DESC LIMIT 1",
        $vector_store_id
    ));
    
    // Caso 1: Nenhum arquivo existe ainda → fazer scraping
    if (!$existing) {
        return ['action' => 'scrape', 'reason' => 'Novo URL - primeiro scraping'];
    }
    
    // Caso 2: URL igual à existente → pular (não fazer nada)
    if ($existing->file_url === $url) {
        return ['action' => 'skip', 'reason' => 'URL já processada anteriormente', 'existing_file_id' => $existing->file_id];
    }
    
    // Caso 3: URL diferente → deletar antigo e fazer novo scraping
    return [
        'action' => 'replace', 
        'reason' => 'URL alterada - substituindo arquivo antigo',
        'old_file_id' => $existing->file_id,
        'old_record_id' => $existing->id
    ];
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
