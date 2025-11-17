<?php

class WhatsappMessageController
{
    /**
     * Deprecated
     */
    // public static function listConversations()
    // {
    //     if (!is_user_logged_in()) {
    //         wp_send_json_error(['mensagem' => 'Usuário não autenticado'], 401);
    //     }

    //     $userId = get_current_user_id();
    //     $instance = WhatsappInstance::findByUserId($userId);

    //     if (!$instance) {
    //         wp_send_json_error(['mensagem' => 'Instância não encontrada'], 404);
    //     }

    //     $messages = WhatsappMessage::findByInstanceName($instance->getInstanceName());

    //     $conversations = self::groupByRemoteJid($messages);

    //     $conversations = self::checkActiveSession($conversations, $instance->getInstanceName());

    //     // error_log('conversations: ' . print_r($conversations, true));

    //     // wp_send_json_success();
    //     wp_send_json_success(['conversations' => array_values($conversations)]);
    // }
    public static function listConversations()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['mensagem' => 'Usuário não autenticado'], 401);
        }

        $orgRepo = new OrganizationRepository();

        $userId = get_current_user_id();
        $currentUser = wp_get_current_user();
        $organization_id = 0;
        $resource_user_id = 0;
        $instance = null;

        if (!empty($userId)) {
            $orgData = $orgRepo->findByUserId($userId);
            $organization_id = $orgData ? (int) $orgData->id : 0;

            $resource_user_id = $organization_id > 0 && isset($orgData->owner_user_id)
                ? (int) $orgData->owner_user_id
                : $userId;
        }

        if (!empty($resource_user_id)) {
            $instance = WhatsappInstance::findByUserId($resource_user_id);
        }

        // $userId = get_current_user_id();
        // $instance = WhatsappInstance::findByUserId($userId);

        if (!$instance) {
            wp_send_json_error(['mensagem' => 'Instância não encontrada'], 404);
        }

        // As mensagens vêm ordenadas da mais recente para a mais antiga (DESC)
        $messages = WhatsappMessage::findByInstanceName($instance->getInstanceName());

        // Agrupa por remoteJid e threadId, e define 'lastMessageDateTime'
        $conversations = self::groupByRemoteJid($messages);

        $conversations = self::checkActiveSession($conversations, $instance->getInstanceName());

        // NOVO PASSO: Ordenar as conversas pela data/hora da última mensagem (mais recente primeiro)
        usort($conversations, function ($a, $b) {
            // Comparar as datas/horas da última mensagem. Retorna negativo se 'a' for mais recente que 'b'.
            return strtotime($b['lastMessageDateTime']) - strtotime($a['lastMessageDateTime']);
        });

        // Remove a chave de ordenação extra antes de enviar
        $conversations = array_map(function ($conv) {
            unset($conv['lastMessageDateTime']);
            return $conv;
        }, $conversations);

        wp_send_json_success(['conversations' => $conversations]);
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

    /**
     * Deprecated
     */
    // public static function groupByRemoteJid(array $messages): array
    // {
    //     $conversations = [];

    //     foreach ($messages as $msg) {
    //         if (is_object($msg)) {
    //             $msg = (array)$msg;
    //         }

    //         $remoteJid = $msg['remoteJid'];

    //         // Se a conversa ainda não existir, cria a estrutura inicial.
    //         if (!isset($conversations[$remoteJid])) {
    //             $conversations[$remoteJid] = [
    //                 'id' => $remoteJid,
    //                 'lastMessage' => '',
    //                 'messages' => [],
    //                 'name' => $msg['pushName'] ?? null,
    //                 // 'paused' => (bool)$paused,
    //             ];
    //         }

    //         // Adiciona a mensagem atual ao array de mensagens da conversa.
    //         $conversations[$remoteJid]['messages'][] = [
    //             'message_id' => $msg['messageId'],
    //             'message' => $msg['message'],
    //             'from_me' => (bool)$msg['fromMe'],
    //             'date_time' => $msg['dateTime']->format('Y-m-d H:i:s'),
    //         ];

    //         // Atualiza a última mensagem.
    //         $conversations[$remoteJid]['lastMessage'] = $msg['message'];

    //         // Se o nome ainda não foi definido, usa o push_name da mensagem.
    //         if (empty($conversations[$remoteJid]['name']) && !empty($msg['pushName'])) {
    //             $conversations[$remoteJid]['name'] = $msg['pushName'];
    //         }
    //     }
    //     return $conversations;
    // }
    public static function groupByRemoteJid(array $messages): array
    {
        $conversations = [];

        foreach ($messages as $msg) {
            if (is_object($msg)) {
                $msg = (array)$msg;
            }

            $remoteJid = $msg['remoteJid'];
            // Usamos o threadId. Se ele for NULL ou vazio, use uma string padrão.
            $threadId = !empty($msg['threadId']) ? $msg['threadId'] : 'default_thread';

            // Chave composta para agrupar por RemoteJid e ThreadId
            $key = $remoteJid . '-' . $threadId;

            // Se a conversa (thread) ainda não existir, cria a estrutura inicial.
            if (!isset($conversations[$key])) {
                $conversations[$key] = [
                    'id' => $remoteJid,
                    'threadId' => $threadId,
                    // Como a consulta está ordenada por DESC, a primeira mensagem é a última da thread.
                    'lastMessage' => $msg['message'],
                    'lastMessageDateTime' => $msg['dateTime']->format('Y-m-d H:i:s'), // Armazena a data/hora para ordenação
                    'messages' => [],
                    'name' => $msg['pushName'] ?? null,
                ];
            }

            // Adiciona a mensagem atual ao array de mensagens da conversa.
            // **IMPORTANTE**: As mensagens dentro do 'messages' ainda estão em ordem DESC. 
            // Você pode precisar reordenar isso no frontend ou na próxima seção.
            $conversations[$key]['messages'][] = [
                'message_id' => $msg['messageId'],
                'message' => $msg['message'],
                'from_me' => (bool)$msg['fromMe'],
                'date_time' => $msg['dateTime']->format('Y-m-d H:i:s'),
            ];

            // Se o nome ainda não foi definido, usa o push_name da mensagem.
            if (empty($conversations[$key]['name']) && !empty($msg['pushName'])) {
                $conversations[$key]['name'] = $msg['pushName'];
            }
        }

        foreach ($conversations as $key => $conv) {
            // Usa array_reverse para colocar as mensagens na ordem ASC (mais antiga para a mais nova)
            $conversations[$key]['messages'] = array_reverse($conversations[$key]['messages']);
        }

        return $conversations;
    }

    private static function checkActiveSession(array $conversations, string $instanceName): array
{
    global $wpdb;
    
    $rawKeys = array_keys($conversations);
    $remoteJids = [];
    foreach ($rawKeys as $key) {
        $parts = explode('-', $key, 2); 
        $remoteJids[] = $parts[0];
    }
    $remoteJids = array_unique($remoteJids);

    if (empty($remoteJids)) {
        return $conversations;
    }

    // Prepara os valores para o IN (...) de forma segura
    $placeholders = implode(',', array_fill(0, count($remoteJids), '%s'));
    $table = 'human_sessions'; // Assumindo que a variável $table está correta ou que é uma constante

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

    error_log('Active JIDs (Sessões Ativas): ');
    error_log(print_r($activeJids, true));

    foreach ($conversations as $fullKey => &$conv) {
        $jidParts = explode('-', $fullKey, 2);
        $remoteJid = $jidParts[0];

        $conv['paused'] = (bool) in_array($remoteJid, $activeJids, true);
    }

    return $conversations;
}
}

add_action('wp_ajax_list_conversations', ['WhatsappMessageController', 'listConversations']);

add_action('wp_ajax_send_whatsapp_message', ['WhatsappMessageController', 'sendMessage']);
