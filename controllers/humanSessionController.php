<?php

class HumanSessionController
{
    public static function startSession()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Você precisa estar logado.', 401);
        }

        // $thread_id     = isset($_POST['thread_id']) ? sanitize_text_field($_POST['thread_id']) : null;
        $instance_name = isset($_POST['instance_name']) ? sanitize_text_field($_POST['instance_name']) : null;
        $remoteJid = isset($_POST['remoteJid']) ? sanitize_text_field($_POST['remoteJid']) : null;

        if (!$instance_name || !$remoteJid) {
            wp_send_json_error(['mensagem' => 'Parâmetros obrigatórios ausentes.'], 400);
        }

        $success = HumanSession::start($remoteJid, $instance_name);

        if (!$success) {
            wp_send_json_error(['mensagem' => 'Falha ao iniciar sessão humana.'], 500);
        }

        wp_send_json_success(['mensagem' => 'Sessão criada com sucesso']);
    }

    public static function endSession()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Você precisa estar logado.', 401);
        }

        $instance_name = isset($_POST['instance_name']) ? sanitize_text_field($_POST['instance_name']) : null;
        $remoteJid = isset($_POST['remoteJid']) ? sanitize_text_field($_POST['remoteJid']) : null;

        if (!$instance_name || !$remoteJid) {
            wp_send_json_error(['mensagem' => 'Parâmetros obrigatórios ausentes.'], 400);
        }

        $success = HumanSession::end($remoteJid, $instance_name);

        if (!$success) {
            wp_send_json_error(['mensagem' => 'Nenhuma sessão ativa encontrada para encerrar.'], 404);
        }

        wp_send_json_success(['mensagem' => 'Sessão encerrada com sucesso']);
    }

    public static function checkSession()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Você precisa estar logado.', 401);
        }

        // $thread_id     = isset($_POST['thread_id']) ? sanitize_text_field($_POST['thread_id']) : null;
        // $instance_name = isset($_POST['instance_name']) ? sanitize_text_field($_POST['instance_name']) : null;
        $instance_name = isset($_POST['instance_name']) ? sanitize_text_field($_POST['instance_name']) : null;
        $remoteJid = isset($_POST['remoteJid']) ? sanitize_text_field($_POST['remoteJid']) : null;

        if (!$remoteJid || !$instance_name) {
            wp_send_json_error(['mensagem' => 'Parâmetros obrigatórios ausentes.'], 400);
        }

        $active = HumanSession::isActive($remoteJid, $instance_name);

        wp_send_json_success(['ativa' => $active]);
    }

    public static function toggleSession()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Você precisa estar logado.', 401);
        }

        $remoteJid = isset($_POST['remoteJid']) ? sanitize_text_field($_POST['remoteJid']) : null;
        $instanceName = isset($_POST['instanceName']) ? sanitize_text_field($_POST['instanceName']) : null;
        // $flagId = isset($_POST['flagId']) ? sanitize_text_field($_POST['flagId']) : null;


        if (!$instanceName) {
            $instanceName = WhatsappInstance::findByUserId(get_current_user_id())->getInstanceName();
        }

        if (!$remoteJid || !$instanceName) {
            wp_send_json_error(['mensagem' => 'Parâmetros obrigatórios ausentes.'], 400);
        }

        $isActive = HumanSession::isActive($remoteJid, $instanceName);

        $newIsActive = !$isActive;

        if ($isActive) {
            HumanSession::end($remoteJid, $instanceName);
            // if (!empty($flagId)) {
                HumanSessionFlag::clear($remoteJid, $instanceName);
            // }
        } else {
            HumanSession::start($remoteJid, $instanceName);
        }

        wp_send_json_success(['ativa' => $newIsActive]);
    }
}

// add_action('rest_api_init', function () {
//     register_rest_route('assistants/human-session', '/start', [
//         'methods' => 'POST',
//         'callback' => ['HumanSessionController', 'startSession'],
//     ]);

//     register_rest_route('assistants/human-session', '/end', [
//         'methods' => 'POST',
//         'callback' => ['HumanSessionController', 'endSession'],
//         'permission_callback' => function () {
//             return is_user_logged_in();
//         },
//     ]);
// });

add_action('wp_ajax_start_human_session', ['HumanSessionController', 'startSession']);
add_action('wp_ajax_end_human_session', ['HumanSessionController', 'endSession']);
add_action('wp_ajax_check_human_session', ['HumanSessionController', 'checkSession']);
add_action('wp_ajax_toggle_human_session', ['HumanSessionController', 'toggleSession']);
