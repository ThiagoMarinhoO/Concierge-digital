<?php

class AssistantHelpers
{
    public static function assistant_tool_continue_on_whatsapp()
    {
        return [
            "name" => "enviar_para_whatsapp",
            "description" => "Envia uma mensagem para o WhatsApp do usuário através da Evolution API.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "telefone" => [
                        "type" => "string",
                        "description" => "Número do WhatsApp do usuário (ex: 5521999988888)"
                    ],
                    "mensagem" => [
                        "type" => "string",
                        "description" => "Mensagem a ser enviada via WhatsApp"
                    ]
                ],
                "required" => ["telefone", "mensagem"]
            ]
        ];
    }

    public static function assistant_tool_send_to_whatsapp()
    {
        return [
            "name" => "solicitar_conversacao_whatsapp",
            "description" => "Retorna um link para o WhatsApp da instância atual para que o usuário possa iniciar uma conversa.",
            "parameters" => [
                "type" => "object",
                "properties" => new \stdClass(), // Nenhum parâmetro necessário
                "required" => []
            ]
        ];
    }

    public static function tool_handler_send_to_whatsapp($whatsappInstanceNumber, $thread_id)
    {
        // Remove o sufixo do número do WhatsApp
        $cleanNumber = explode('@', $whatsappInstanceNumber)[0];

        // Codifica o thread_id com base64 para "esconder"
        $encodedThreadId = base64_encode($thread_id);

        // Mensagem formatada
        $message = "Olá, gostaria de continuar nosso atendimento.\n\n`$encodedThreadId`";

        // Codifica para URL
        $encodedMessage = urlencode($message);

        // Monta o link
        $link = "https://wa.me/{$cleanNumber}?text={$encodedMessage}";

        return $link;
    }
}
