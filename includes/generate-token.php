<?php

function generate_chatbot_api_token($user_id) {
    $token = bin2hex(random_bytes(16));
    update_user_meta($user_id, 'chatbot_api_token', $token);
    return $token;
}