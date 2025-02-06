<?php

function remover_acentos($string) {
    $string = strtolower($string); // Opcional: converte para minúsculas
    $string = preg_replace('/[^a-z0-9 ]/i', '', iconv('UTF-8', 'ASCII//TRANSLIT', $string));
    return $string;
}