<?php

class ActiveCampaignController {
    public static function store() {
        $apiUrl = isset($_POST['api_url']) ? sanitize_text_field($_POST['api_url']) : '';
        $apiKey = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $assistantId = isset($_POST['assistant_id']) ? sanitize_text_field($_POST['assistant_id']) : '';


        /**
         * 
         * FAZER UMA REQ PARA O ACTIVE CAMPAIGN PRA VALIDAR AS CREDENCIAIS ANTES DE SALVAR
         * 
        */

        $service = new ActiveCampaignService($apiUrl, $apiKey);
        $isValid = $service->validateCredentials();

        if (!$isValid) {
            wp_send_json_error([
                'invalid_credentials' => true,
                'message' => 'Credenciais inválidas. Por favor, verifique a URL da API e a chave da API.'
            ]);
            return;
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'active_campaign_variables';
        
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE assistant_id = %s",
                $assistantId
            )
        );

        error_log('Existing record: ' . print_r($existing, true));

        if ($existing) {
            $updated = $wpdb->update(
                $table_name,
                [
                    'api_url' => $apiUrl,
                    'api_key' => $apiKey,
                ],
                ['assistant_id' => $assistantId],
                [
                    '%s',
                    '%s',
                ],
                ['%s']
            );

            if ($updated === false) {
                wp_send_json_error('Erro ao atualizar as credenciais.');
            } else {
                wp_send_json_success();
            }
        } else {
            $inserted = $wpdb->insert(
                $table_name,
                [
                    'api_url' => $apiUrl,
                    'api_key' => $apiKey,
                    'assistant_id' => $assistantId,
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                ]
            );

            if ($inserted === false) {
                wp_send_json_error('Erro ao salvar as credenciais.');
            } else {
                wp_send_json_success();
            }
        }
    }
}

add_action('wp_ajax_save_active_campaign_credentials', [ActiveCampaignController::class, 'store']);
