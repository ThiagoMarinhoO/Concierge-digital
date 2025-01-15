<?php
add_action('wp_ajax_concierge_chat', 'concierge_chat');
add_action('wp_ajax_nopriv_concierge_chat', 'concierge_chat');

function concierge_chat()
{
    $userMensagem = isset($_POST['mensagem']) ? $_POST['mensagem'] : null;
    
    // error_log('---- Mensagem do usuário -----');
    // error_log(print_r($_POST, true));

    $chatbot = new Chatbot();

    $resMensagem = $chatbot->enviarMensagem($userMensagem);

    error_log('---- Resposta do sistema -----');
    error_log(print_r($resMensagem, true));


    $jsonResponse = json_encode(array("responseMessage" => $resMensagem));
    echo $jsonResponse;
    exit;
}

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