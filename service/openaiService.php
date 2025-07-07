<?php

class OpenaiService
{
    private static function request($method, $endpoint, $headers = [], $data = [], $isMultipart = false)
    {
        $ch = curl_init();

        $url = "https://api.openai.com/v1/$endpoint";

        $defaultHeaders = [
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ];

        if (!$isMultipart) {
            $defaultHeaders[] = 'Content-Type: application/json';
            $data = json_encode($data);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
            CURLOPT_POSTFIELDS => $data,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception("Erro na requisição cURL: $error");
        }

        return json_decode($response, true);
    }

    // 
    //  ASSISTANTS API
    // 

    public static function processMessageFromWhatsapp(WhatsappMessage $whatsappMessage)
    {
        // Mensagem, ThreadId e Assistente ID
        $assistantId = WhatsappInstance::findByInstanceName($whatsappMessage->getInstanceName())->getAssistant();

        //  Cria a Thread se não existir
        if (empty($whatsappMessage->getThreadId())) {
            $threadObject = self::createThread();
            $whatsappMessage->setThreadId($threadObject['id']);
            $whatsappMessage->save();
        }

        //  Adiciona a mensagem a Thread
        self::addMessageToThread($whatsappMessage->getMessage(), $whatsappMessage->getThreadId(), $assistantId);

        //  Executa(RUN) a Thread e recebe a mensagem e o uso
        return self::runStream();

        //  Retorna a mensagem e o uso
    }

    public static function createThread() {}

    public static function addMessageToThread(string $message, string $threadId, string $assistantId) {}

    public static function runStream() {}

    // 
    //  TOOLS
    // 

    // 
    //  END TOOLS
    // 

    // 
    //  END ASSISTANTS API
    // 


    // 
    //  SPECIALIZED MODELS
    // 

    public static function speechToText($arq)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . OPENAI_API_KEY,
            ],
            CURLOPT_POSTFIELDS => [
                'file' => new CURLFile($arq),
                'model' => 'whisper-1',
            ],
        ]);

        $response = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($response, true);

        error_log('Response OpenaiService::speechToText($arq): ' . print_r($response, true));

        if (!empty($response['error'])) {
            error_log('Erro OpenaiService::speechToText($arq): ' . print_r($response, true));
        }

        return $response;
    }
    public static function textToSpeech(string $text, string $voice = 'alloy', string $outputPath = 'output.mp3')
    {
        $url = 'https://api.openai.com/v1/audio/speech';

        $headers = [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'Content-Type: application/json'
        ];

        $postData = json_encode([
            'model' => 'gpt-4o-mini-tts',
            'input' => $text,
            'voice' => $voice, // opções: alloy, echo, fable, onyx, nova, shimmer
            'response_format' => 'mp3',
            'instructions' => 'Fale com um tom alegre e positivo.'
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $postData
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || $httpCode !== 200) {
            error_log("Erro na conversão texto-para-áudio: $err, HTTP Code: $httpCode");
            return null;
        }

        if (!empty($response['error'])) {
            error_log("Erro na conversão texto-para-áudio: " . print_r($response));
        }

        return base64_encode($response);

        // file_put_contents($outputPath, $response);

        // return $outputPath;
    }

    // 
    //  END SPECIALIZED MODELS
    // 

    //
    //  FILES
    // 

    public static function uploadFiles($files)
    {
        $filePaths = [];

        if (is_string($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (filter_var($file, FILTER_VALIDATE_URL)) {
                $tempFile = self::downloadFileToTemp($file);
                if ($tempFile) {
                    $filePaths[] = $tempFile;
                } else {
                    error_log("Erro ao baixar arquivo da URL: $file");
                }
            } elseif (file_exists($file)) {
                $filePaths[] = $file;
            } else {
                error_log("Arquivo não encontrado: $file");
            }
        }

        $responses = [];

        foreach ($filePaths as $filePath) {
            $fileData = [
                'purpose' => 'assistants',
                'file' => new CURLFile($filePath),
            ];

            $responses[] = self::request('POST', 'files', [], $fileData, true);

            // Se o arquivo for temporário, apaga depois do upload
            if (str_starts_with($filePath, sys_get_temp_dir())) {
                @unlink($filePath);
            }
        }

        return $responses;
    }

    public static function listFiles()
    {
        return self::request('GET', 'files');
    }

    public static function retrieveFile($file_id)
    {
        return self::request('GET', "files/{$file_id}");
    }

    private static function downloadFileToTemp(string $url): ?string
    {
        $basename = basename(parse_url($url, PHP_URL_PATH));
        $basename = preg_replace('/[^A-Za-z0-9._-]/', '_', $basename);
        $tempFile = tempnam(sys_get_temp_dir(), 'openai_');
        $finalPath = $tempFile . '_' . $basename;

        $contents = @file_get_contents($url);
        if (!$contents) {
            return null;
        }

        file_put_contents($finalPath, $contents);
        return $finalPath;
    }

    //
    //  END FILES
    //  
}
