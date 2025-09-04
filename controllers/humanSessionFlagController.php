<?php

class HumanSessionFlagController
{
    public static function createFlag($remoteJid, $instanceName)
    {
        if (empty($remoteJid) || empty($instanceName)) {
            wp_send_json_error(['mensagem' => 'Dados inválidos'], 400);
        }

        $flag = new HumanSessionFlag($remoteJid, $instanceName);
        $flag->createOrUpdate();

        return $flag;

        // wp_send_json_success(['mensagem' => 'Flag criada com sucesso', 'flag' => $flag]);
    }

    public static function listFlags()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['mensagem' => 'Usuário não autenticado'], 401);
        }

        $userId = get_current_user_id();
        $instance = WhatsappInstance::findByUserId($userId);
        error_log('Instance: ' . print_r($instance, true));

        if (!$instance) {
            wp_send_json_error(['mensagem' => 'Instância não encontrada'], 404);
        }

        $flags = HumanSessionFlag::list($instance->getInstanceName());
        error_log('Flags: ' . print_r($flags, true));

        if (empty($flags)) {
            wp_send_json_success(['flags' => []]);
        } else {
            wp_send_json_success(['flags' => $flags]);
        }
    }

}

add_action('wp_ajax_list_human_session_flag', [HumanSessionFlagController::class, 'listFlags']);
