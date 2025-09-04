<?php

class WhatsappMessageController
{
    public static function listConversations()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['mensagem' => 'Usuário não autenticado'], 401);
        }

        $userId = get_current_user_id();
        $instance = WhatsappInstance::findByUserId($userId);

        if (!$instance) {
            wp_send_json_error(['mensagem' => 'Instância não encontrada'], 404);
        }

        $messages = WhatsappMessage::findByInstanceName($instance->getInstanceName());

        $conversations = self::groupByRemoteJid($messages);

        $conversations = self::checkActiveSession($conversations, $instance->getInstanceName());

        // error_log('conversations: ' . print_r($conversations, true));

        // wp_send_json_success();
        wp_send_json_success(['conversations' => array_values($conversations)]);
    }

    public static function sendMessage()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['mensagem' => 'Usuário não autenticado'], 401);
        }

        $remoteJid = isset($_POST['remoteJid']) ? sanitize_text_field($_POST['remoteJid']) : null;
        $instanceName = isset($_POST['instanceName']) ? sanitize_text_field($_POST['instanceName']) : null;
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : null;

        if (!$instanceName) {
            wp_send_json_error(['mensagem' => 'Instância não encontrada'], 404);
        }

        if (empty($message) || empty($remoteJid)) {
            wp_send_json_error(['mensagem' => 'Dados inválidos'], 400);
        }

        $sentMessage = EvolutionApiService::sendPlainTextV2($instanceName, $remoteJid, $message);

        if ($sentMessage) {
            $newWhatsappMessage = new WhatsappMessage();
            $newWhatsappMessage->setFromMe($sentMessage['key']['fromMe']);

            $newWhatsappMessage->setMessageId($sentMessage['key']['id']);
            $newWhatsappMessage->setRemoteJid($sentMessage['key']['remoteJid']);
            $newWhatsappMessage->setMessage($sentMessage['message']['conversation']);
            $newWhatsappMessage->setDateTime((new DateTime())->setTimestamp($sentMessage['messageTimestamp']));

            $thread_id = WhatsappMessageService::resolveThreadId($sentMessage);
            
            $newWhatsappMessage->setThreadId($thread_id);
            $newWhatsappMessage->setInstanceName($instanceName);

            $newWhatsappMessage->save();

            wp_send_json_success(['mensagem' => 'Mensagem enviada com sucesso']);
        } else {
            wp_send_json_error(['mensagem' => 'Erro ao enviar mensagem'], 500);
        }
    }


    public static function groupByRemoteJid(array $messages): array
    {
        $conversations = [];

        foreach ($messages as $msg) {
            if (is_object($msg)) {
                $msg = (array)$msg;
            }

            $remoteJid = $msg['remoteJid'];

            // Se a conversa ainda não existir, cria a estrutura inicial.
            if (!isset($conversations[$remoteJid])) {
                $conversations[$remoteJid] = [
                    'id' => $remoteJid,
                    'lastMessage' => '',
                    'messages' => [],
                    'name' => $msg['pushName'] ?? null,
                    // 'paused' => (bool)$paused,
                ];
            }

            // Adiciona a mensagem atual ao array de mensagens da conversa.
            $conversations[$remoteJid]['messages'][] = [
                'message_id' => $msg['messageId'],
                'message' => $msg['message'],
                'from_me' => (bool)$msg['fromMe'],
                'date_time' => $msg['dateTime']->format('Y-m-d H:i:s'),
            ];

            // Atualiza a última mensagem.
            $conversations[$remoteJid]['lastMessage'] = $msg['message'];

            // Se o nome ainda não foi definido, usa o push_name da mensagem.
            if (empty($conversations[$remoteJid]['name']) && !empty($msg['pushName'])) {
                $conversations[$remoteJid]['name'] = $msg['pushName'];
            }
        }
        return $conversations;
    }

    private static function checkActiveSession(array $conversations, string $instanceName): array
    {
        global $wpdb;

        // Extrai os remote_jids únicos
        $remoteJids = array_keys($conversations);

        if (empty($remoteJids)) {
            return $conversations;
        }

        // Prepara os valores para o IN (...) de forma segura
        $placeholders = implode(',', array_fill(0, count($remoteJids), '%s'));
        $table = 'human_sessions';

        // Cria a query com wpdb::prepare
        $query = $wpdb->prepare(
            "
        SELECT remote_jid
        FROM $table
        WHERE instance_name = %s
          AND ended_at IS NULL
          AND remote_jid IN ($placeholders)
        ",
            array_merge([$instanceName], $remoteJids)
        );

        // Executa a query
        $activeJids = $wpdb->get_col($query);

        // Marca paused como true ou false
        foreach ($conversations as $remoteJid => &$conv) {
            $conv['paused'] = (bool) in_array($remoteJid, $activeJids, true);
        }

        return $conversations;
    }
}

add_action('wp_ajax_list_conversations', ['WhatsappMessageController', 'listConversations']);

add_action('wp_ajax_send_whatsapp_message', ['WhatsappMessageController', 'sendMessage']);
