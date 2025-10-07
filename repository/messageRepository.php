<?php
class MessageRepository
{


    /**
     * DEPRECATED
     */

    // public static function getAssistantMessages(
    //     $assistantId,
    //     $startDate = null,
    //     $endDate = null
    // ) {
    //     $webMessages = DB::table('messages')
    //         ->where('assistant_id', $assistantId)
    //         ->groupBy('thread_id')
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     $whatsappMessages = DB::table('whatsapp_messages')
    //         ->where('assistant_id', $assistantId)
    //         ->groupBy('thread_id')
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     return $webMessages->merge($whatsappMessages)->sortByDesc('created_at');
    // }


    // Chat
    // public static function findAllWebChat(
    //     $assistantId,
    //     $userId,
    //     $startDate = null,
    //     $endDate = null,
    // ) {
    //     /**
    //      *      O chat é composto de
    //      *      id (thread_id), Canal(Estático web/Wpp), Status(Estático 'Finalizado'), Lead, Título(Primeira mensagem), Data e Horário (da última mensagem), Mensagens
    //      */

    //     $db = \Wenprise\Eloquent\Connection::instance();

    //     $webMessages = $db->table('messages')
    //         ->where('assistant_id', $assistantId)
    //         ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
    //             $query->whereBetween('created_at', [$startDate, $endDate]);
    //         })
    //         ->select($db->raw('thread_id, MAX(created_at) as created_at'))
    //         ->groupBy('thread_id');


    //     return $webMessages->get();
    // }

    // public static function findAllWhatsappChat(
    //     $assistantId,
    //     $userId,
    //     $startDate = null,
    //     $endDate = null,
    // ) {
    //     /**
    //      *      O chat é composto de
    //      *      id (thread_id), Canal(Estático web/Wpp), Status(Estático 'Finalizado'), Lead, Título(Primeira mensagem), Data e Horário (da última mensagem), Mensagens
    //      */

    //     $db = \Wenprise\Eloquent\Connection::instance();

    //     $webMessages = $db->table('messages')
    //         ->where('assistant_id', $assistantId)
    //         ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
    //             $query->whereBetween('created_at', [$startDate, $endDate]);
    //         })
    //         ->select($db->raw('thread_id, MAX(created_at) as created_at'))
    //         ->groupBy('thread_id');


    //     return $webMessages->get();
    // }

    // public static function findAllChat(
    //     $assistantId,
    //     $userId,
    //     $startDate = null,
    //     $endDate = null,
    // ) {
    //     $webChats = self::findAllWebChat($assistantId, $userId, $startDate, $endDate);
    //     $whatsappChats = self::findAllWhatsappChat($assistantId, $userId, $startDate, $endDate);

    //     return $webChats->merge($whatsappChats)->sortByDesc('created_at');
    // }

    /**
     * CHATS
     */

    public static function findAllChats(
        $assistantId,
        $instanceName = null,
        $startDate = null,
        $endDate = null,
    ) {
        $webChats = self::findAllWebChats($assistantId, $startDate, $endDate);
        $whatsappChats = $instanceName ? self::findAllWhatsappChats($instanceName, $startDate, $endDate) : [];

        return array_merge($webChats, $whatsappChats);
    }

    public static function findAllWebChats(
        $assistantId,
        $startDate = null,
        $endDate = null,
    ) {
        global $wpdb;

        $sql = "SELECT thread_id, MAX(date_time) as date_time FROM messages WHERE assistant_id = %s";
        $params = [$assistantId];

        if ($startDate && $endDate) {
            $sql .= " AND date_time BETWEEN %s AND %s";
            $params[] = $startDate;
            $params[] = $endDate;
        } else if ($startDate) {
            $sql .= " AND date_time >= %s";
            $params[] = $startDate;
        }

        $sql .= " GROUP BY thread_id ORDER BY date_time DESC";

        $query_prepared = $wpdb->prepare($sql, ...$params);

        return $wpdb->get_results($query_prepared);
    }

    public static function findAllWhatsappChats(
        $instanceName,
        $startDate = null,
        $endDate = null,
    ) {
        global $wpdb;

        $sql = "SELECT thread_id, MAX(date_time) as date_time FROM whatsapp_messages WHERE instance_name = %s";
        $params = [$instanceName];

        if ($startDate && $endDate) {
            $sql .= " AND date_time BETWEEN %s AND %s";
            $params[] = $startDate;
            $params[] = $endDate;
        } else if ($startDate) {
            $sql .= " AND date_time >= %s";
            $params[] = $startDate;
        }

        $sql .= " GROUP BY thread_id ORDER BY date_time DESC";

        $query_prepared = $wpdb->prepare($sql, ...$params);

        return $wpdb->get_results($query_prepared);
    }

    /**
     * MESSAGES
     */
    public static function findAllMessages(
        $assistantId,
        $instanceName = null,
        $startDate = null,
        $endDate = null,
    ) {
        error_log("Finding all messages for assistantId: $assistantId, instanceName: $instanceName, startDate: $startDate, endDate: $endDate");

        $webMessages = self::findAllWebMessages($assistantId, $startDate, $endDate);
        $whatsappMessages = $instanceName ? self::findAllWhatsappMessages($instanceName, $startDate, $endDate) : [];

        return array_merge($webMessages, $whatsappMessages);
    }

    public static function findAllWebMessages(
        $assistantId,
        $startDate = null,
        $endDate = null,
    ) {
        global $wpdb;

        $sql = "SELECT * FROM messages WHERE assistant_id = %s";
        $params = [$assistantId];

        if ($startDate && $endDate) {
            $sql .= " AND date_time BETWEEN %s AND %s";
            $params[] = $startDate;
            $params[] = $endDate;
        } else if ($startDate) {
            $sql .= " AND date_time >= %s";
            $params[] = $startDate;
        }

        $sql .= " ORDER BY date_time DESC";

        $query_prepared = $wpdb->prepare($sql, ...$params);

        return $wpdb->get_results($query_prepared);
    }

    public static function findAllWhatsappMessages(
        $instanceName,
        $startDate = null,
        $endDate = null,
    ) {
        global $wpdb;

        $sql = "SELECT * FROM whatsapp_messages WHERE instance_name = %s";
        $params = [$instanceName];

        if ($startDate && $endDate) {
            $sql .= " AND date_time BETWEEN %s AND %s";
            $params[] = $startDate;
            $params[] = $endDate;
        } else if ($startDate) {
            $sql .= " AND date_time >= %s";
            $params[] = $startDate;
        }

        $sql .= " ORDER BY date_time DESC";

        $query_prepared = $wpdb->prepare($sql, ...$params);

        return $wpdb->get_results($query_prepared);
    }

    /**
     * USERS
     */

    public static function findAllWhatsappUsers($instanceName)
    {
        global $wpdb;

        $sql = "SELECT DISTINCT remote_jid AS user FROM whatsapp_messages WHERE instance_name = %s ORDER BY user";
        $query_prepared = $wpdb->prepare($sql, $instanceName);

        return $wpdb->get_results($query_prepared);
    }


    /**
     * CHART METRICAS
     */
    // public static function getWeeklyMessageData()
    // {
    //     global $wpdb;

    //     $assistantId = isset($_POST['assistant_id']) ? sanitize_text_field($_POST['assistant_id']) : null;
    //     $instanceName = isset($_POST['instance_name']) ? sanitize_text_field($_POST['instance_name']) : null;

    //     $sql = $wpdb->prepare("
    //     SELECT 
    //         DATE(date_time) as message_date, 
    //         from_me,
    //         COUNT(*) as message_count 
    //     FROM 
    //         (
    //             SELECT date_time, from_me 
    //             FROM messages
    //             WHERE assistant_id = %s

    //             UNION ALL

    //             SELECT date_time, from_me 
    //             FROM whatsapp_messages
    //             WHERE instance_name = %s
    //         ) AS all_messages
    //     WHERE 
    //         date_time >= NOW() - INTERVAL 7 DAY
    //     GROUP BY 
    //         DATE(date_time), from_me
    //     ORDER BY 
    //         message_date ASC
    //     ", $assistantId, $instanceName);

    //     $results = $wpdb->get_results($sql, ARRAY_A);

    //     $assistantData = [];
    //     $userData = [];
    //     $categories = [];

    //     // Lista de últimos 7 dias
    //     $dayList = [];
    //     for ($i = 6; $i >= 0; $i--) {
    //         $date = date('Y-m-d', strtotime("-$i days"));
    //         $dayName = date('D', strtotime($date));
    //         $dayList[$date] = [
    //             'label' => $dayName,
    //             'y_assistant' => 0,
    //             'y_user' => 0
    //         ];
    //     }

    //     foreach ($results as $row) {
    //         $date = $row['message_date'];
    //         $count = (int) $row['message_count'];
    //         $fromMe = (int) $row['from_me'];

    //         if (isset($dayList[$date])) {
    //             if ($fromMe === 1) {
    //                 $dayList[$date]['y_assistant'] = $count;
    //             } else {
    //                 $dayList[$date]['y_user'] = $count;
    //             }
    //         }
    //     }

    //     foreach ($dayList as $day) {
    //         $assistantData[] = $day['y_assistant'];
    //         $userData[] = $day['y_user'];
    //         $categories[] = $day['label'];
    //     }

    //     wp_send_json([
    //         'series' => [
    //             [
    //                 'name' => 'Assistente',
    //                 'color' => '#1A56DB',
    //                 'data' => $assistantData,
    //             ],
    //             [
    //                 'name' => 'Usuário',
    //                 'color' => '#FDBA8C',
    //                 'data' => $userData,
    //             ],
    //         ],
    //         'categories' => $categories
    //     ]);
    // }

    public static function getWeeklyMessageData()
    {
        global $wpdb;

        $assistantId = isset($_POST['assistant_id']) ? sanitize_text_field($_POST['assistant_id']) : null;
        $instanceName = isset($_POST['instance_name']) ? sanitize_text_field($_POST['instance_name']) : null;
        $period      = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '7days';

        if (!$assistantId) {
            wp_send_json_error(['message' => 'assistant_id é obrigatório']);
        }

        // Definir intervalo de tempo
        switch ($period) {
            case '30days':
                $interval = '30 DAY';
                $step     = 'day';
                $range    = 30;
                break;
            case '12months':
                $interval = '12 MONTH';
                $step     = 'month';
                $range    = 12;
                break;
            default:
                $interval = '7 DAY';
                $step     = 'day';
                $range    = 7;
                break;
        }

        // Query
        $sql = $wpdb->prepare("
        SELECT 
            " . ($step === 'month' ? "DATE_FORMAT(date_time, '%Y-%m')" : "DATE(date_time)") . " as period_label, 
            from_me,
            COUNT(*) as message_count 
        FROM 
            (
                SELECT date_time, from_me 
                FROM messages
                WHERE assistant_id = %s

                UNION ALL

                SELECT date_time, from_me 
                FROM whatsapp_messages
                WHERE instance_name = %s
            ) AS all_messages
        WHERE 
            date_time >= NOW() - INTERVAL $interval
        GROUP BY 
            period_label, from_me
        ORDER BY 
            period_label ASC
    ", $assistantId, $instanceName);

        $results = $wpdb->get_results($sql, ARRAY_A);

        $assistantData = [];
        $userData = [];
        $categories = [];

        // Construir lista base (dias ou meses)
        $dateList = [];
        if ($step === 'day') {
            for ($i = $range - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $label = ($range <= 7) ? date('D', strtotime($date)) : date('d/m', strtotime($date));
                $dateList[$step === 'day' ? $date : substr($date, 0, 7)] = [
                    'label' => $label,
                    'y_assistant' => 0,
                    'y_user' => 0
                ];
            }
        } else { // meses
            for ($i = $range - 1; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $label = date('M/Y', strtotime($date . "-01"));
                $dateList[$date] = [
                    'label' => $label,
                    'y_assistant' => 0,
                    'y_user' => 0
                ];
            }
        }

        // Preencher com os dados da query
        foreach ($results as $row) {
            $labelKey = $row['period_label'];
            $count = (int) $row['message_count'];
            $fromMe = (int) $row['from_me'];

            if (isset($dateList[$labelKey])) {
                if ($fromMe === 1) {
                    $dateList[$labelKey]['y_assistant'] = $count;
                } else {
                    $dateList[$labelKey]['y_user'] = $count;
                }
            }
        }

        foreach ($dateList as $day) {
            $assistantData[] = $day['y_assistant'];
            $userData[] = $day['y_user'];
            $categories[] = $day['label'];
        }

        wp_send_json([
            'series' => [
                [
                    'name' => 'Assistente',
                    'color' => '#1A56DB',
                    'data' => $assistantData,
                ],
                [
                    'name' => 'Usuário',
                    'color' => '#FDBA8C',
                    'data' => $userData,
                ],
            ],
            'categories' => $categories
        ]);
    }
}

add_action('wp_ajax_find_all_web_messages', [MessageRepository::class, 'findAllWebMessages']);
add_action('wp_ajax_find_all_whatsapp_messages', [MessageRepository::class, 'findAllWhatsappMessages']);

add_action('wp_ajax_weekly_messages', [MessageRepository::class, 'getWeeklyMessageData']);
