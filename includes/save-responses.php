<?php
add_action('wp_ajax_save_responses', 'save_responses');
function save_responses(){
    $chatbot_options = isset($_POST['chatbotOptions']);
    $chatbot_id = isset($_POST['chatbot_id']);
    $chatbot_name = isset($_POST['chatbot_name']);
    $user_id = get_current_user_id();

    $chatbot = new Chatbot();
    $chatbot->updateChatbot($chatbot_id , $chatbot_name , $chatbot_options , '' , $user_id);
}