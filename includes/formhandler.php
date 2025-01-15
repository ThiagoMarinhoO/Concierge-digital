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

function save_chatbot_options()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $concierge_name = $_POST['concierge_name'] ?? '';
        $concierge_objective = $_POST['concierge_objective'] ?? '';
        $other_objective = $_POST['other_objective'] ?? '';
        $concierge_tasks = $_POST['concierge_tasks'] ?? '';
        $other_tasks = $_POST['other_tasks'] ?? '';
        $concierge_tone = $_POST['concierge_tone'] ?? '';
        $other_tone = $_POST['other_tone'] ?? '';
        $concierge_initiation = $_POST['concierge_initiation'] ?? '';
        $concierge_approach = $_POST['concierge_approach'] ?? '';
        $formal_level = $_POST['formal_level'] ?? '';
        $concierge_content_priority = $_POST['concierge_content_priority'] ?? '';
        $brand_characteristic = $_POST['brand_characteristic'] ?? '';
        $concierge_custom_terms = $_POST['concierge_custom_terms'] ?? '';
        $concierge_audience = $_POST['concierge_audience'] ?? '';
        $other_audience = $_POST['other_audience'] ?? '';
        $concierge_knowledge_level = $_POST['concierge_knowledge_level'] ?? '';
    
        session_unset();
    
        $_SESSION['chatbotOptions'] = ['concierge_name' => $concierge_name, 'concierge_objective' => $concierge_objective, 'other_objective' => $other_objective, 'concierge_tasks' => $concierge_tasks, 'other_tasks' => $other_tasks, 'concierge_tone' => $concierge_tone, 'other_tone' => $other_tone, 'concierge_initiation' => $concierge_initiation, 'concierge_approach' => $concierge_approach, 'formal_level' => $formal_level, 'concierge_content_priority' => $concierge_content_priority, 'brand_characteristic' => $brand_characteristic, 'concierge_custom_terms' => $concierge_custom_terms, 'concierge_audience' => $concierge_audience, 'other_audience' => $other_audience, 'concierge_knowledge_level' => $concierge_knowledge_level,];
    
        return wp_send_json_success('Chatbot configurado');
    }
}