<?php

// Função para obter a chave da API de um arquivo seguro
function concierge_get_api_key() {
    $api_key_file = CONCIERGE_DIGITAL_PATH . 'api_key.php';

    // Verificar se o arquivo existe
    if (!file_exists($api_key_file)) {
        error_log('Erro: O arquivo api_key.php não foi encontrado. Caminho: ' . $api_key_file);
        return null;
    }

    // Obter a chave retornada pelo arquivo
    $api_key = include $api_key_file;

    // Verificar se a chave foi carregada corretamente
    if (empty($api_key)) {
        error_log('Erro: A chave da API retornada pelo arquivo api_key.php está vazia.');
        return null;
    }

    error_log('Chave da API carregada com sucesso: ' . $api_key);
    return $api_key;
}
