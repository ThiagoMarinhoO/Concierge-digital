<?php

add_action('wp_ajax_gerar_script_chatbot', 'gerar_script_chatbot');
function gerar_script_chatbot()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado', 403);
    }
    $chatbot_id = isset($_GET['chatbotID']) ? $_GET['chatbotID'] : 0;
    $user_id = get_current_user_id();

    log_to_file($chatbot_id);

    $token = get_user_meta($user_id, 'chatbot_api_token', true);
    if (!$token) {
        $token = generate_chatbot_api_token($user_id);
    }

    $endpoint = esc_url(site_url('/wp-json/custom/v1/chatbot'));

    $script = "
        <script>
        (function(d, s) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '$endpoint?token=$token', true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    var cleanResponseText = xhr.responseText.replace(/\\\\|\\s+|\"/g, '').trim();
                    localStorage.setItem('chatbot_user_id', ".$user_id.");
                    localStorage.setItem('chatbot_id' , ".$chatbot_id.");
                    var script = document.createElement('script');
                    script.async = false;
                    script.defer = true;
                    script.src = cleanResponseText;
                    document.head.appendChild(script);
                } else {
                    console.error('Erro ao carregar o chatbot: ', xhr.status, xhr.statusText);
                }
            };

            xhr.onerror = function() {
                console.error('Erro na conexão com o servidor do chatbot.');
            };

            xhr.send();
        })(document, 'script');
        </script>
    ";
    wp_send_json_success(['script' => htmlspecialchars_decode($script)]);
}


add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/chatbot', [
        'methods' => 'GET',
        'callback' => 'custom_chatbot_script',
        'permission_callback' => 'validate_chatbot_token',
    ]);
});

function custom_chatbot_script()
{
    $file_path = esc_url(site_url('/wp-content/plugins/Concierge-digital/assets/chatbot.js'));

    $cleaned_path = str_replace(['\\', ' '], '', $file_path);

    return new WP_REST_Response($cleaned_path, 200, [
        'Content-Type' => 'application/javascript',
    ]);
}

function validate_chatbot_token()
{
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        error_log('Token ausente.');
        return new WP_Error('invalid_token', 'Token ausente.', array('status' => 403));
    }

    $user_query = new WP_User_Query([
        'meta_key' => 'chatbot_api_token',
        'meta_value' => $token,
        'number' => 1,
    ]);

    $users = $user_query->get_results();
    if (empty($users)) {
        error_log('Token inválido.');
        return new WP_Error('invalid_token', 'Token inválido.', array('status' => 403));
    }

    $user = $users[0];
    wp_set_current_user($user->ID);

    return true;
}

function chatbot_rest_api_init()
{
    register_rest_route('chatbot/v1', '/send_message', array(
        'methods' => 'POST',
        'callback' => 'handle_chatbot_message',
        'permission_callback' => '__return_true',
    ));
}

add_action('rest_api_init', 'chatbot_rest_api_init');

function handle_chatbot_message(WP_REST_Request $request)
{
    $message = sanitize_text_field($request->get_param('message'));
    $user_id = intval($request->get_param('user_id'));
    $chatbot_id = intval($request->get_param('chatbot_id'));

    error_log($chatbot_id);

    $chatbot = new Chatbot();
    $response = $chatbot->enviarMensagem($message, $chatbot_id , $user_id);

    return new WP_REST_Response(
        array(
            'status' => 'success',
            'response' => $response,
        ),
        200
    );
}