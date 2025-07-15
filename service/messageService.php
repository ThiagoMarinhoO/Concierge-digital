<?php

class MessageService
{
    public static function processMessage($messageObj)
    {
        // FromMe ?? se sim simplesmente salvar no banco. (Adicionar o nome como nome do assistente)
        if (!empty($message['from_me'])) {
            return self::saveAssistantMessage($messageObj);
        }

        // Mandar para a IA de captura de Leads.

        // Tratar resultado da captura de Leads.

        // Salvar mensagem do usuário
        return self::saveUserMessage($messageObj);
    }

    private static function saveAssistantMessage($messageObj)
    {

        $assitant = new Chatbot();
        $assitant = $assitant->getChatbotByIdII($messageObj['assistant_id']);

        $new_message = new Message();

        $new_message->setMessage($messageObj['message']);
        $new_message->setName($assitant->chatbot_name);
        // $new_message->setPhone($messageObj['phone']);
        $new_message->setThreadId($messageObj['thread_id']);
        $new_message->setFromMe($messageObj['from_me']);
        $new_message->setAssistantId($messageObj['assistant_id']);
        $new_message->setDateTime(new DateTime('now', new DateTimeZone('America/Sao_Paulo')));

        $saved_message = $new_message->save();

        return $saved_message;
    }

    private static function saveUserMessage($messageObj)
    {
        $isLead = self::isLead($messageObj['message']);

        $extractedData = $isLead ? self::refineLeadDataWithAI($messageObj['message']) : [];

        $new_message = new Message();

        $new_message->setMessage($messageObj['message']);

        $new_message->setName($extractedData['name'] ?? null);
        $new_message->setPhone($extractedData['phone'] ?? null);

        $new_message->setThreadId($messageObj['thread_id']);
        $new_message->setFromMe($messageObj['from_me']);
        $new_message->setAssistantId($messageObj['assistant_id']);
        $new_message->setDateTime(new DateTime('now', new DateTimeZone('America/Sao_Paulo')));

        $saved_message = $new_message->save();

        return $saved_message;
    }

    private static function refineLeadDataWithAI(string $userMessage): ?array
    {
        $apiKey = OPENAI_API_KEY;
        $assistantId = 'asst_rsYUBQNXToRiQNy7w1RdJyqU';

        // plugin_log('--- HANDLE ASSISTANT FUNCTION ---');

        if (empty($assistantId)) {
            wp_send_json_error(['message' => 'Nenhum assistente encontrado.']);
            exit;
        }

        $thread_id = create_thread();

        add_message_to_thread($thread_id, $userMessage);

        $api_url = "https://api.openai.com/v1/threads/$thread_id/runs";

        $data = json_encode([
            "assistant_id" => $assistantId,
            "stream" => true
        ]);

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey",
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
        }

        curl_close($ch);

        // plugin_log('--- Resposta completa da OpenAI ---');
        // plugin_log(print_r($response, true));

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

                    // plugin_log('--- JSON Decodificado ---');
                    // plugin_log(print_r($decodedData, true));

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
                }
            }
        }

        plugin_log('--- Mensagem final gerada ---');
        plugin_log(print_r($assistant_message, true));

        // Limpar blocos markdown como ```json ... ```
        $clean = trim($assistant_message);
        $clean = preg_replace('/^```json\s*|\s*```$/', '', $clean);

        // Tentar decodificar JSON
        $json = json_decode($clean, true);

        if (!is_array($json)) {
            error_log('Resposta inválida da IA: ' . $assistant_message);
            return ['name' => null, 'phone' => null, 'email' => null];
        }

        return [
            'name'  => $json['name'] ?? null,
            'phone' => $json['phone'] ?? null,
            'email' => $json['email'] ?? null,
        ];

    }

    private static function isLead(string $message): bool
    {

        $hasPhone = preg_match('/\b\d{8,13}\b/', $message);
        $hasEmail = preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $message);
        $hasName = preg_match('/\b(meu nome é|sou o|sou a|chamo me|me chamo)\b/i', $message);

        return $hasPhone || $hasEmail || $hasName;
    }
}
