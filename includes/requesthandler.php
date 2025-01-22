<?php
add_action('wp_ajax_concierge_chat', 'concierge_chat');
add_action('wp_ajax_nopriv_concierge_chat', 'concierge_chat');

function concierge_chat()
{
    $userMensagem = isset($_POST['mensagem']) ? $_POST['mensagem'] : null;
    $chatbotId = isset($_POST['assistantId']) ? $_POST['assistantId'] : null;

    // error_log('---- assistantId ---');
    // error_log($chatbotId);

    $chatbot = new Chatbot();

    $resMensagem = $chatbot->enviarMensagem($userMensagem, $chatbotId);

    // error_log('---- Resposta do sistema -----');
    // error_log(print_r($resMensagem, true));


    $jsonResponse = json_encode(array("responseMessage" => $resMensagem));
    echo $jsonResponse;
    exit;
}

add_action('wp_ajax_create_chatbot', 'create_chatbot');
add_action('wp_ajax_nopriv_create_chatbot', 'create_chatbot');

// Decode the parameters received from the index.html file and store them in the $paramsFetch array.
// $paramsFetch = json_decode(
//     file_get_contents("php://input"),
//     true
// );

// $ChatBot = new ChatBot();

// // Send the message to our AI.
// $resMessage = $ChatBot->sendMessage($paramsFetch["message"]);

// // Next, we return the response in JSON format and exit the execution.
// $jsonResponse = json_encode(array("responseMessage" => $resMessage));
// echo $jsonResponse;
// exit;

function create_chatbot() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $chatbot_options = $_POST['chatbot_options'] ?? '';
        $chatbot_name = $_POST['chatbot_name'] ?? '';

        // Se quiser decodificar JSON enviado no payload
        if ($chatbot_options) {
            $chatbot_options = json_decode(stripslashes($chatbot_options), true);
            error_log(print_r($chatbot_options, true)); // Verifique a estrutura do array decodificado
        }

        $chatbot = new Chatbot();

        $chatbot->createChatbot($chatbot_name, $chatbot_options);

        wp_send_json_success(['chatbotName' => $chatbot_name]);
    } else {
        wp_send_json_error(['message' => 'Método inválido']);
    }
}


add_action('wp_ajax_delete_question', 'delete_question');
add_action('wp_ajax_nopriv_delete_question', 'delete_question');

function delete_question()
{
    $question_id = isset($_POST['question_id']) ? $_POST['question_id'] : null;

    $question = new Question();
    $question->deleteQuestion($question_id);


    // $jsonResponse = json_encode(array("responseMessage" => $resMensagem));
    // echo $jsonResponse;
    exit;
}