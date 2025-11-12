<?php
function get_active_subscription_product_id( $user_id = null ) {
    if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
        return false;
    }

    if ( ! $user_id ) {
        return false;
    }

    if ( ! $user_id ) {
        return false;
    }

    $subscriptions = wcs_get_users_subscriptions( $user_id );

    if ( empty( $subscriptions ) ) {
        return false;
    }

    foreach ( $subscriptions as $subscription ) {

        if ( $subscription->has_status( 'active' ) ) {

            foreach ( $subscription->get_items() as $item ) {
                $product_id = $item->get_product_id();
                return (int) $product_id;
            }
        }
    }

    return false;
}

function get_all_benefits_reference( $product_id = null ) {

    $field = get_field_object('beneficios', $product_id);

    if ( ! $field || empty($field['choices']) ) {
        return [];
    }

    return array_keys($field['choices']);
}

function get_user_subscription_benefits( $user_id = null ) {

    $product_id = get_active_subscription_product_id( $user_id );

    if ( ! $product_id ) {
        return false; // não tem assinatura ativa
    }

    // Benefícios marcados no produto
    $selected = get_field('beneficios', $product_id);
    if ( ! is_array($selected) ) $selected = [];

    // Benefícios possíveis (dinâmico)
    $all = get_all_benefits_reference($product_id);

    $response = [];

    foreach ( $all as $benefit_key ) {
        $response[$benefit_key] = in_array( $benefit_key, $selected );
    }

    return $response;
}

function get_user_subscription_cycle_dates( $user_id = null ) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;

    $subscriptions = wcs_get_users_subscriptions($user_id);
    if (!$subscriptions) return false;

    foreach ($subscriptions as $subscription) {
        if ($subscription->has_status('active')) {

            $start = $subscription->get_date('last_order_date_paid') ?: $subscription->get_date('start');
            $end   = $subscription->get_date('next_payment');

            return [
                'start' => new DateTime($start, new DateTimeZone('UTC')),
                'end'   => new DateTime($end, new DateTimeZone('UTC')),
            ];
        }
    }

    return false;
}

function count_whatsapp_messages_in_period(DateTime $start, DateTime $end): int {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASSWORD);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM whatsapp_messages WHERE date_time BETWEEN ? AND ?");
    $stmt->execute([
        $start->format('Y-m-d H:i:s'),
        $end->format('Y-m-d H:i:s'),
    ]);
    return (int) $stmt->fetchColumn();
}

function count_chat_messages_in_period(DateTime $start, DateTime $end): int {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASSWORD);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE date_time BETWEEN ? AND ?");
    $stmt->execute([
        $start->format('Y-m-d H:i:s'),
        $end->format('Y-m-d H:i:s'),
    ]);
    return (int) $stmt->fetchColumn();
}

function get_user_total_messages_current_cycle($user_id = null) {

    $period = get_user_subscription_cycle_dates($user_id);
    if (!$period) return 0;

    $start = $period['start'];
    $end   = $period['end'];

    $whatsappCount = count_whatsapp_messages_in_period($start, $end);
    $chatCount     = count_chat_messages_in_period($start, $end);

    return $whatsappCount + $chatCount;
}

function get_user_message_limit($user_id = null) {
    $benefits = get_user_subscription_benefits($user_id);

    if (!$benefits) {
        return false;
    }

    if (!empty($benefits['mensagens_1000']) && $benefits['mensagens_1000']) {
        return 1000;
    }

    if (!empty($benefits['mensagens_5000']) && $benefits['mensagens_5000']) {
        return 5000;
    }

    return INF;
}

function check_user_message_quota($assistant_id = null) {

    $chatbot = new Chatbot();
    $user_id = $chatbot->getUserIdByChatbotId($assistant_id);
    $limit = get_user_message_limit($user_id);

    if ($limit === INF) return true;

    if (!$limit) return false;

    $used = get_user_total_messages_current_cycle($user_id);
    $percent = ($used / $limit) * 100;

    if ($percent >= 50 && $percent < 80) {
        do_action('concierge_notify_message_quota', $user_id, 50, $used, $limit);
    }

    if ($percent >= 80 && $percent < 100) {
        do_action('concierge_notify_message_quota', $user_id, 80, $used, $limit);
    }

    if ($percent >= 100) {
        do_action('concierge_notify_message_quota', $user_id, 100, $used, $limit);
        return false;
    }

    return true;
}