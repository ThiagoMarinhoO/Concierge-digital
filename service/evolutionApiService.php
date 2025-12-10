<?php

class EvolutionApiService
{

    public static function sendPlainText(WhatsappMessage $whatsappMessage, string $text): array
    {

        EvolutionApiService::markMessageAsRead($whatsappMessage);

        $encodedInstanceName = rawurlencode($whatsappMessage->getInstanceName());
        $number = strstr($whatsappMessage->getRemoteJid(), '@', true);

        $apiKey = EVOAPI_API_KEY;
        $apiUrl = EVOAPI_API_URL;

        $data = [
            "number" => $number,
            "text" => $text,
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

        $response = json_decode($response, true);

        if ($err) {
            error_log("cURL Error #:" . $err);
        }

        if (!empty($response['error'])) {
            error_log("cURL Error # EvolutionApiService::sendPlainText: " . print_r($response['error'], true));
        }

        error_log("cURL ResponseEvolutionApiService::sendPlainText : " . print_r($response, true));

        return $response;
    }

    public static function sendPlainTextV2(string $instanceName, string $remoteJid, string $text): array
    {
        $encodedInstanceName = rawurlencode($instanceName);
        $number = strstr($remoteJid, '@', true);

        $apiKey = EVOAPI_API_KEY;
        $apiUrl = EVOAPI_API_URL;

        $data = [
            "number" => $number,
            "text" => $text,
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

        $response = json_decode($response, true);

        if ($err) {
            error_log("cURL Error #:" . $err);
        }

        if (!empty($response['error'])) {
            error_log("cURL Error # EvolutionApiService::sendPlainText: " . print_r($response['error'], true));
        }

        error_log("cURL ResponseEvolutionApiService::sendPlainText : " . print_r($response, true));

        return $response;
    }


    public static function sendWhatsappAudio(WhatsappMessage $whatsappMessage, string $audio)
    {
        $encodedInstanceName = rawurlencode($whatsappMessage->getInstanceName());
        $endpoint = "/message/sendWhatsAppAudio/{$encodedInstanceName}";

        $number = strstr($whatsappMessage->getRemoteJid(), '@', true);

        $data = [
            "number" => $number,
            "audio" => $audio,
            "delay" => 3000,
            "linkPreview" => true
        ];

        EvolutionApiService::markMessageAsRead($whatsappMessage);

        return ClientEvolutionApi::postRequest($endpoint, $data);
    }

    public static function sendMedia(WhatsappMessage $whatsappMessage, string $fileUrl, string $caption = '')
    {
        $encodedInstanceName = rawurlencode($whatsappMessage->getInstanceName());
        $endpoint = "/message/sendMedia/{$encodedInstanceName}";

        $number = strstr($whatsappMessage->getRemoteJid(), '@', true);
        $fileName = basename(parse_url($fileUrl, PHP_URL_PATH));
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Detecta o tipo de mídia
        $mimeTypes = [
            'pdf'  => ['application/pdf', 'document'],
            'txt'  => ['text/plain', 'document'],
            'doc'  => ['application/msword', 'document'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'document'],
            'xls'  => ['application/vnd.ms-excel', 'document'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'document'],
            'png'  => ['image/png', 'image'],
            'jpg'  => ['image/jpeg', 'image'],
            'jpeg' => ['image/jpeg', 'image'],
            'mp4'  => ['video/mp4', 'video'],
            'mp3'  => ['audio/mpeg', 'audio'],
            'ogg'  => ['audio/ogg', 'audio'],
        ];

        // Fallbacks
        $mimeType = 'application/octet-stream';
        $mediaType = 'document';

        if (isset($mimeTypes[$extension])) {
            [$mimeType, $mediaType] = $mimeTypes[$extension];
        }


        $data = [
            "number" => $number,
            "mediatype" => $mediaType,
            "mimetype" => $mimeType,
            "caption" => $caption,
            "media" => $fileUrl,
            "fileName" => $fileName,
            "delay" => 3000,
            "linkPreview" => true
        ];

        // EvolutionApiService::markMessageAsRead($whatsappMessage);

        return ClientEvolutionApi::postRequest($endpoint, $data);
    }


    public static function getBase64($whatsappMessage)
    {
        $encodedInstanceName = rawurlencode($whatsappMessage['instance']);

        $apiKey = EVOAPI_API_KEY;
        $apiUrl = EVOAPI_API_URL;

        $data = [
            "message" => [
                "key" => [
                    "id" => $whatsappMessage['data']['key']['id']
                ]
            ],
            "convertToMp4" => true
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$apiUrl}/chat/getBase64FromMediaMessage/{$encodedInstanceName}",
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

        $response = json_decode($response, true);

        error_log('Resposta EvolutionApiService::getbase64($whatsappMessage): ' . print_r($response, true));

        if ($err) {
            error_log("cURL Error #: " . $err);
            return false;
        }

        if (!empty($response['erro'])) {
            error_log('Erro EvolutionApiService::getbase64($whatsappMessage): ' . print_r($response, true));
        }

        return $response;
    }

    public static function markMessageAsRead($whatsappMessage)
    {
        $encodedInstanceName = rawurlencode($whatsappMessage->getInstanceName());

        $endpoint = "/chat/markMessageAsRead/{$encodedInstanceName}";

        $readMessages = [
            "readMessages" => [
                [
                    "id" => $whatsappMessage->getMessageId(),
                    "fromMe" => (bool)$whatsappMessage->getFromMe(),
                    "remoteJid" => $whatsappMessage->getRemoteJid()
                ]
            ]

        ];


        ClientEvolutionApi::postRequest($endpoint, $readMessages);
    }
}
