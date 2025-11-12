<?php
add_action('concierge_notify_message_quota', function ($user_id, $level, $used, $limit) {

    $user = get_userdata($user_id);
    if (!$user)
        return;

    $email = $user->user_email;
    $name = $user->display_name ?: $user->user_login;

    // Conteúdo base
    $subject = '';
    $message = '';

    if ($level === 50) {
        $subject = "⚠️ Atenção: Você usou 50% do seu pacote de mensagens";
        $message = "
            Olá, {$name}!

            Você já utilizou **{$used}** de **{$limit}** mensagens no ciclo atual.

            Estamos apenas te avisando para que você acompanhe seu consumo. Tudo segue normalmente.

            Atenciosamente,  
            Charlie";
    }

    if ($level === 80) {
        $subject = "🔥 80% do pacote de mensagens utilizado";
        $message = "
            Olá, {$name}!

            Você utilizou **{$used}** de **{$limit}** mensagens neste ciclo.

            Se sua demanda está aumentando, podemos te ajudar a ajustar ou ampliar seu plano.
            Entre em contato com nosso suporte para entender as opções disponíveis.

            Atenciosamente,  
            Charlie";
    }

    if ($level === 100) {
                    $subject = "⛔ Limite de mensagens atingido";
                    $message = "
            Olá, {$name}!

            Você atingiu o limite de **{$limit} mensagens** do seu plano.

            Seu serviço foi **temporariamente suspenso** até a renovação ou upgrade da assinatura.

            Se precisar de ajuda, nossa equipe está pronta para lhe atender.

            Atenciosamente,  
            Charlie";
    }
    wp_mail($email, $subject, $message);

}, 10, 4);