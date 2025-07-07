<?php

class WhatsappMessageHelpers
{
    public static function formatMessage($message)
    {
        // Formata a mensagem para o padrão do WhatsApp
        return trim($message);
    }

    public static function formatRemoteJid($remoteJid)
    {
        // Formata o Remote JID para o padrão do WhatsApp
        return preg_replace('/[^a-zA-Z0-9@.-]/', '', $remoteJid);
    }
}
