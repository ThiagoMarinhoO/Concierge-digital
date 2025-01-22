<?php
add_action('wp_ajax_clear_session', 'clear_session');
add_action('wp_ajax_nopriv_clear_session', 'clear_session');

function clear_session()
{
    error_log('clear session');
    session_unset();
}

add_action('wp_ajax_save_chatbot_options', 'save_chatbot_options');
add_action('wp_ajax_nopriv_save_chatbot_options', 'save_chatbot_options');

