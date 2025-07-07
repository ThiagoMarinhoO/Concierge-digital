<?php

class WhatsappInstanceService
{

    public static function markMessageAsRead($instanceName, $messageKey)
    {
        $encodedInstanceName = rawurlencode($instanceName);

        $endpoint = "/chat/markMessageAsRead/{$encodedInstanceName}";

        $readMessages = [
            "readMessages" => [
                $messageKey
            ]
        ];

        ClientEvolutionApi::postRequest($endpoint, $readMessages);
    }

    // public static function 
}