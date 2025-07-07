<?php
// File: controllers/WhatsappController.php

class WhatsappController
{
    // public static function handle_message_upsert(WP_REST_Request $request)
    // {
    //     // Recebe os dados enviados pela Evolution API
    //     $data = $request->get_body();
    //     $data = json_decode($data, true);

    //     $whatsappMessage = $data;
    //     $messageKey = $data['data']['key'];

    //     error_log('Dados recebidos do webhook WhatsApp: ' . print_r($data, true));

    //     WhatsappInstanceService::markMessageAsRead($data['instance'], $messageKey);



    //     if (
    //         isset($data['data']['key']['fromMe']) &&
    //         $data['data']['key']['fromMe'] === true
    //     ) {
    //         // Mensagem enviada por mim, não processar
    //         error_log('Mensagem enviada por mim, não processar: ' . print_r($data, true));
    //         return;
    //     }

    //     $mensagemTexto = $data['data']['message']['conversation'] ?? '';
    //     $remoteJid = $data['data']['key']['remoteJid'];

    //     // Checar se é uma mensagem com o código base64 do thread
    //     $threadIdFromMessage = null;

    //     if (stripos($mensagemTexto, 'gostaria de continuar nosso atendimento') !== false) {
    //         // Tenta extrair entre crases: `base64`
    //         if (preg_match('/`([^`]+)`/', $mensagemTexto, $matches)) {
    //             $decoded = base64_decode($matches[1], true);
    //             if ($decoded && str_starts_with($decoded, 'thread_')) {
    //                 $threadIdFromMessage = $decoded;
    //                 error_log('Thread ID recuperado da mensagem: ' . $threadIdFromMessage);
    //             }
    //         }
    //     }

    //     // Caso não tenha vindo da mensagem, tenta buscar o último da conversa
    //     $lastThreadId = $threadIdFromMessage;

    //     if (!$lastThreadId) {
    //         $lastMessages = WhatsappMessage::findByRemoteJid($remoteJid);
    //         if (!empty($lastMessages) && is_array($lastMessages)) {
    //             $firstMessage = reset($lastMessages);
    //             $lastThreadId = $firstMessage->getThreadId();
    //         }
    //     }

    //     if ( $data['data']['message']['audioMessage'] ) {

    //     }

    //     // $newWhatsappMessage = new WhatsappMessage();
    //     // $newWhatsappMessage->setMessageId($data['data']['key']['id']);
    //     // $newWhatsappMessage->setRemoteJid($remoteJid);
    //     // $newWhatsappMessage->setInstanceName($data['instance']);
    //     // $newWhatsappMessage->setMessage($mensagemTexto);
    //     // $newWhatsappMessage->setPushName($data['data']['pushName']);
    //     // $newWhatsappMessage->setThreadId($lastThreadId);
    //     // $newWhatsappMessage->setDateTime($data['date_time']);

    //     // $newWhatsappMessage->save();

    //     // error_log('messageData WhatsApp : ' . print_r($newWhatsappMessage, true));

    //     // $assistantData = handle_assistant_message(true, $newWhatsappMessage, $lastThreadId);

    //     // error_log('Assistant data: ' . print_r($assistantData, true));

    //     // if ($assistantData['thread_id']) {
    //     //     $newWhatsappMessage->setThreadId($assistantData['thread_id']);
    //     //     $newWhatsappMessage->save();
    //     // }

    //     // self::send_message(
    //     //     $newWhatsappMessage->getInstanceName(),
    //     //     $assistantData['ai_response'],
    //     //     $newWhatsappMessage->getRemoteJid()
    //     // );

    //     return;
    // }

    public static function handle_message_upsert(WP_REST_Request $request)
    {
        $whatsappMessage = json_decode($request->get_body(), true);
        // Log da mensagem
        // error_log(print_r($whatsappMessage, true));

        // Processar a mensagem recebida
        $processedMessage = WhatsappMessageService::processMessage($whatsappMessage);

        // Se a mensagem for minha (ou seja, enviada por mim), encerrar o fluxo
        if ($processedMessage->getFromMe()) {
            return;
        }

        ## error_log('Mensagem salva e processada' . print_r($processedMessage, true));

        // Processar a mensagem com o assistente na openAI
        // $assistantResponse = OpenaiService::processMessageFromWhatsapp();
        $assistantData = handle_assistant_message(true, $processedMessage, null);

        // error_log('Assistant data: ' . print_r($assistantData, true));

        //  Atualizar a mensagem e seu threadID
        $processedMessage->setThreadId($assistantData['thread_id']);
        $processedMessage->save();

        // error_log('with threadId: ' . print_r($processedMessage, true));

        ##
        ## ENVIAR A MENSAGEM PARA O CLIENTE
        ##
        // ENVIAR MENSAGEM DE TEXTO

        $sentMessage = WhatsappMessageService::processSendMessage($processedMessage, $assistantData['ai_response']);

        // $sentMessage = EvolutionApiService::sendPlainText($processedMessage, $assistantData['ai_response']);
        // ENVIAR MENSAGEM DE AUDIO
        // TRANSFORMAR MENSAGEM DO ASSISTENTE EM ÁUDIO E APÓS ENVIAR PARA O CLIENTE
        // $audioMessage = OpenaiService::TextToSpeech($processedMessage->getMessage());
        // error_log('Audio return: ' . print_r($audioMessage, true));
        // $sentMessage = EvolutionApiService::sendWhatsappAudio($processedMessage, $audioMessage);

        //  Salvar a mensagem enviada pelo cliente
        WhatsappMessageService::processCreateFromAssistant($processedMessage, $sentMessage, $assistantData['ai_response']);
        
        return new WP_REST_Response(['status' => 'ok'], 200);
    }

    public static function create_whatsapp_instance()
    {
        // $instanceName = isset($_POST['instanceName']) ? $_POST['instanceName'] : null;
        $assistant_id = isset($_POST['assistant_id']) ? $_POST['assistant_id'] : null;

        $integration = isset($_POST['integration']) ? $_POST['integration'] : 'WHATSAPP-BAILEYS';
        $wabaNumber = isset($_POST['wabaNumber']) ? $_POST['wabaNumber'] : null;
        $wabaBusinessId = isset($_POST['wabaBusinessId']) ? $_POST['wabaBusinessId'] : null;


        $current_user = wp_get_current_user();
        $username = $current_user->user_login ?? 'user';
        $newInstanceName = $assistant_id . '_' . $username;

        $apiKey = EVOAPI_API_KEY;
        $apiUrl = EVOAPI_API_URL;

        $curl = curl_init();

        $data = [
            'instanceName' => $newInstanceName,
            'qrcode' => true,
            'integration' => $integration,
            'groupsIgnore' => true,
            'webhook' => array(
                'url' => WEBHOOK_UPSERTMESSAGE_URL,
                'base64' => true,
                'events' => [
                    'MESSAGES_UPSERT'
                ],
            ),
        ];

        if ($integration !== 'WHATSAPP-BAILEYS') {
            $data['number'] = $wabaNumber;
            $data['businessId'] = $wabaBusinessId;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$apiUrl}/instance/create",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "apikey: {$apiKey}",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            error_log("cURL Error #:" . $err);
            wp_send_json_error(['message' => 'Failed to create WhatsApp instance.', 'error' => $err]);
        }

        error_log("cURL Response: " . $response);

        $response = json_decode($response, true);

        if ($response['error']) {
            error_log("Error creating WhatsApp instance: " . $response['error']);
            wp_send_json_error(['message' => 'Failed to create WhatsApp instance.', 'error' => $response['error']]);
            return;
        }

        $whatsappInstance = new WhatsappInstance();
        $whatsappInstance->setInstanceId($response['instance']['instance_id'] ?? '');
        $whatsappInstance->setInstanceName($response['instance']['instance_name'] ?? $newInstanceName);
        $whatsappInstance->setUserId(get_current_user_id());
        $whatsappInstance->setAssistant($assistant_id ?? '');

        $whatsappInstance->save();

        wp_send_json_success(($response));
    }

    public static function delete_whatsapp_instance()
    {
        $instanceName = isset($_POST['instanceName']) ? $_POST['instanceName'] : '';

        if (empty($instanceName)) {
            wp_send_json_error(['message' => 'Instance name is required.']);
            return;
        }

        $encodedInstanceName = rawurlencode($instanceName);
        $apiKey = EVOAPI_API_KEY;
        $apiUrl = EVOAPI_API_URL;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$apiUrl}/instance/delete/{$encodedInstanceName}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => [
                "apikey: {$apiKey}",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            error_log("cURL Error #:" . $err);
        } else {
            error_log("cURL Response: " . $response);
        }

        $whatsappInstance = WhatsappInstance::findByInstanceName($instanceName);
        if ($whatsappInstance) $whatsappInstance->delete();

        wp_send_json_success(json_decode($response, true));
    }

    public static function send_message($instance, $message, $number)
    {

        $encodedInstanceName = rawurlencode($instance);

        $apiKey = EVOAPI_API_KEY;
        $apiUrl = EVOAPI_API_URL;

        $data = [
            "number" => $number,
            "text" => $message,
            "delay" => 3000,
            "linkPreview" => true,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$apiUrl}/message/sendText/{$encodedInstanceName}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "apikey: {$apiKey}"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            error_log("cURL Error #:" . $err);
        }

        error_log("cURL Response: " . $response);
    }

    public static function fetch_instance_by_name($instanceName)
    {
        $encodedInstanceName = rawurlencode($instanceName);

        $apiKey = EVOAPI_API_KEY;
        $apiUrl = EVOAPI_API_URL;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$apiUrl}/instance/fetchInstances?instanceName={$encodedInstanceName}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "apikey: {$apiKey}"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        if (!empty($response['error'])) {
            error_log("Error fetching WhatsApp instance: " . $response['error']);
            error_log("cURL Error #:" . $err);
        } else {
            error_log(print_r($response, true));
        }

        return $response;
    }

    public static function connection_state($instanceName)
    {
        $encodedInstanceName = rawurlencode($instanceName);

        $apiKey =   EVOAPI_API_KEY;
        $apiUrl = EVOAPI_API_URL;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$apiUrl}/instance/connectionState/{$encodedInstanceName}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "apikey: {$apiKey}"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        if ($response['error']) {
            error_log("Error fetching WhatsApp instance connection state: " . $response['error']);
            error_log("cURL Error #:" . $err);
        }

        error_log("cURL Response: " . $response);

        wp_send_json_success($response['instance']);
    }
}


//ROTAS REST
add_action('rest_api_init', function () {
    register_rest_route('/v1', '/whatsapp-webhook/message_upsert', [
        'methods'  => 'POST',
        'callback' => ['WhatsappController', 'handle_message_upsert'],
        'permission_callback' => '__return_true',
    ]);
});

//ROTAS AJAX
add_action('wp_ajax_create_whatsapp_instance', ['WhatsappController', 'create_whatsapp_instance']);
add_action('wp_ajax_nopriv_create_whatsapp_instance', ['WhatsappController', 'create_whatsapp_instance']);

add_action('wp_ajax_delete_whatsapp_instance', ['WhatsappController', 'delete_whatsapp_instance']);
add_action('wp_ajax_nopriv_delete_whatsapp_instance', ['WhatsappController', 'delete_whatsapp_instance']);

add_action('wp_ajax_connection_state', ['WhatsappController', 'connection_state']);
add_action('wp_ajax_nopriv_connection_state', ['WhatsappController', 'connection_state']);
