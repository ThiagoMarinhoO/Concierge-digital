<?php

use Smalot\PdfParser\Parser;

class WhatsappMessageService
{
    public static function processMessage($whatsappMessage)
    {
        // Se for uma mensagem de texto, processar e retornar.
        if (!empty($whatsappMessage['data']['message']['conversation'])) {
            return self::processTextMessage($whatsappMessage);
        }

        // Se for uma mensagem de áudio, processar e retornar.
        if (!empty($whatsappMessage['data']['message']['audioMessage'])) {
            return self::processAudioMessage($whatsappMessage);
        }

        if ($whatsappMessage['data']['messageType'] === 'documentMessage') {
            return self::processDocumentMessage($whatsappMessage);
        }
    }

    private static function processTextMessage($whatsappMessage)
    {
        error_log("Processing text message: " . $whatsappMessage['data']['message']['conversation']);

        //  Resolver ThreadID
        $threadId = self::resolveThreadId($whatsappMessage);

        return self::create($whatsappMessage, $threadId, null);
    }

    private static function processAudioMessage($whatsappMessage)
    {
        // Transcrever o áudio para que que tenha mensagem para salvar
        $base64 = $whatsappMessage['data']['message']['base64'];
        $decoded = base64_decode($base64);
        $tmpFile = tempnam(sys_get_temp_dir(), 'audio_') . '.ogg';
        file_put_contents($tmpFile, $decoded);

        $textMessage = OpenaiService::speechToText($tmpFile);

        //  Resolver Thread Id
        $threadId = self::resolveThreadId($whatsappMessage);

        //  Salvar a mensagem
        return self::create($whatsappMessage, $threadId, $textMessage['text']);
    }

    private static function processDocumentMessage($whatsappMessage)
    {

        $textMessage = self::resolveDocument($whatsappMessage);
        error_log('PDF formatado: ' . print_r($textMessage, true));

        if (!empty($whatsappMessage['data']['message']['documentMessage']['caption'])) {
            $caption = $whatsappMessage['data']['message']['documentMessage']['caption'];
            $textMessage .= "\n" . $caption;
        }

        $threadId = self::resolveThreadId($whatsappMessage);

        return self::create($whatsappMessage, $threadId, $textMessage);
    }

    public static function processSendMessage($processedMessage, $aiResponse)
    {
        // Tenta extrair um link de mídia do texto
        $mediaInfo = self::extractMediaInfo($aiResponse);

        if ($mediaInfo !== null) {
            return EvolutionApiService::sendMedia(
                $processedMessage,
                $mediaInfo['url'],
                $mediaInfo['caption'] // mensagem sem o link
            );
        }

        return EvolutionApiService::sendPlainText($processedMessage, $aiResponse);
    }

    public static function processCreateFromAssistant($processedMessage, $sentMessage, $assistantResponse = null)
    {
        if (!empty($sentMessage['message']['conversation'])) {
            return self::createSendTextFromAssistant($processedMessage, $sentMessage);
        }

        if ($sentMessage['messageType'] === 'documentMessage') {
            return self::createSendMediaFromAssistant($processedMessage, $sentMessage, $assistantResponse);
        }
    }

    public static function create($whatsappMessage, $threadId = null, $textMessage = null)
    {
        $newWhatsappMessage = new WhatsappMessage();

        $newWhatsappMessage->setMessageId($whatsappMessage['data']['key']['id']);
        $newWhatsappMessage->setRemoteJid($whatsappMessage['data']['key']['remoteJid']);
        $newWhatsappMessage->setInstanceName($whatsappMessage['instance']);
        $newWhatsappMessage->setMessage($textMessage ?? $whatsappMessage['data']['message']['conversation']);
        $newWhatsappMessage->setPushName($whatsappMessage['data']['pushName']);
        $newWhatsappMessage->setFromMe($whatsappMessage['data']['key']['fromMe'] ?? 0);
        $newWhatsappMessage->setThreadId($threadId);
        $newWhatsappMessage->setDateTime($whatsappMessage['date_time']);

        $newWhatsappMessage->save();

        return $newWhatsappMessage;
    }

    public static function createSendTextFromAssistant(WhatsappMessage $whatsappMessage, $sentMessage)
    {
        $newWhatsappMessage = new WhatsappMessage();
        $newWhatsappMessage->setFromMe($sentMessage['key']['fromMe']);

        $newWhatsappMessage->setMessageId($sentMessage['key']['id']);
        $newWhatsappMessage->setRemoteJid($sentMessage['key']['remoteJid']);
        $newWhatsappMessage->setMessage($sentMessage['message']['conversation']);
        $newWhatsappMessage->setDateTime((new DateTime())->setTimestamp($sentMessage['messageTimestamp']));

        $newWhatsappMessage->setThreadId($whatsappMessage->getThreadId());
        $newWhatsappMessage->setInstanceName($whatsappMessage->getInstanceName());

        $newWhatsappMessage->save();

        return $newWhatsappMessage;
    }

    public static function createSendMediaFromAssistant(WhatsappMessage $whatsappMessage, $sentMessage, $assistantResponse)
    {
        $newWhatsappMessage = new WhatsappMessage();
        $newWhatsappMessage->setFromMe($sentMessage['key']['fromMe']);

        $newWhatsappMessage->setMessageId($sentMessage['key']['id']);
        $newWhatsappMessage->setRemoteJid($sentMessage['key']['remoteJid']);
        $newWhatsappMessage->setMessage($assistantResponse);
        $newWhatsappMessage->setDateTime((new DateTime())->setTimestamp($sentMessage['messageTimestamp']));

        $newWhatsappMessage->setThreadId($whatsappMessage->getThreadId());
        $newWhatsappMessage->setInstanceName($whatsappMessage->getInstanceName());

        $newWhatsappMessage->save();

        return $newWhatsappMessage;
    }


    private static function extractMediaInfo(string $text): ?array
    {
        // Procura por link com extensão de documento
        $pattern = '/(https?:\/\/[^\s"]+\.(pdf|docx?|xlsx?|pptx?|txt|csv))/i';

        if (preg_match($pattern, $text, $matches)) {
            $url = $matches[1];

            // Remove o link da mensagem original para usar como caption
            $caption = trim(str_replace($url, '', $text));

            return [
                'url' => $url,
                'caption' => $caption,
            ];
        }

        return null;
    }


    private static function containsMediaLink(string $text): bool
    {
        // Regex para encontrar links que terminam com extensões de documentos
        $pattern = '/https?:\/\/[^\s"]+\.(pdf|docx?|xlsx?|pptx?|txt|csv)/i';
        return preg_match($pattern, $text) === 1;
    }


    private static function resolveThreadId($whatsappMessage)
    {
        //  Baseado nas últimas mensagens
        $lastThreadId = null;

        //  Mensagem para registrar atendimento vindo do site
        if (!empty($whatsappMessage['data']['message']['conversation'])) {
            if (stripos($whatsappMessage['data']['message']['conversation'], 'gostaria de continuar nosso atendimento') !== false) {
                if (preg_match('/`([^`]+)`/', $whatsappMessage['data']['message']['conversation'], $matches)) {
                    $decoded = base64_decode($matches[1], true);
                    if ($decoded && str_starts_with($decoded, 'thread_')) {
                        $lastThreadId =  $decoded;
                    }
                }
            }
        }

        //  Mensagem de texto whatsapp
        if (empty($lastThreadId)) {
            $lastMessages = WhatsappMessage::findByRemoteJid($whatsappMessage['data']['key']['remoteJid']);

            if ($lastMessages) {
                $lastMessage = reset($lastMessages);
                $lastThreadIdCandidate = $lastMessage->getThreadId();
                $lastMessageDateTime = $lastMessage->getDateTime();

                // Verifica se a última threadId tem menos de 24 horas
                if ($lastThreadIdCandidate && $lastMessageDateTime) {
                    $lastMessageTimestamp = strtotime($lastMessageDateTime->format('Y-m-d H:i:s'));
                    if ($lastMessageTimestamp !== false && (time() - $lastMessageTimestamp) < 86400) {
                        $lastThreadId = $lastThreadIdCandidate;
                    }
                }
            }
        }

        return $lastThreadId;
    }

    private static function resolveDocument($whatsappMessage)
    {
        $documentMessage = $whatsappMessage['data']['message']['documentMessage'];
        $base64 = $whatsappMessage['data']['message']['base64'];

        if (empty($base64)) {
            error_log("Base64 não disponível para o documento.");
            return null;
        }

        // Nome original ou nome padrão
        $fileName = sanitize_file_name($documentMessage['fileName'] ?? 'documento.pdf');

        // Obter ano e mês atuais
        $year = date('Y');
        $month = date('m');

        // Caminho de destino completo
        $uploadDir = wp_upload_dir();
        $targetDir = "{$uploadDir['basedir']}/concierge/whatsapp/{$year}/{$month}";

        // Cria a pasta se não existir
        if (!file_exists($targetDir)) {
            wp_mkdir_p($targetDir);
        }

        // Caminho final do arquivo
        $filePath = "{$targetDir}/{$fileName}";

        // Salvar o arquivo decodificado
        file_put_contents($filePath, base64_decode($base64));

        // Verificar extensão
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (strtolower($fileExtension) === 'pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $fileContent = $pdf->getText();

                // Limpeza do texto
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'UTF-8');
                $fileContent = preg_replace('/[\x00-\x1F\x7F]/u', '', $fileContent);
                return $fileContent;
            } catch (Exception $e) {
                error_log("Erro ao processar PDF: " . $e->getMessage());
                return null;
            }
        }

        // Para outros formatos
        $fileContent = file_get_contents($filePath);
        $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'UTF-8');
        $fileContent = preg_replace('/[\x00-\x1F\x7F]/u', '', $fileContent);
        return $fileContent;
    }
}
