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

    public static function assistant_tool_get_calendar_slots()
    {
        return [
            "name" => "get_calendar_slots",
            "description" => "Consulta os horários disponíveis do usuário para agendamento de compromissos.",
            "parameters" => [
                "type" => "object",
                "properties" => new \stdClass(), // Nenhum parâmetro necessário
                "required" => []
            ]
        ];
    }

    public static function assistant_tool_create_calendar_event()
    {
        return [
            "name" => "create_calendar_event",
            "description" => "Cria um evento no Google Calendar com horário, título, nome e e-mail do participante.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "title" => [
                        "type" => "string",
                        "description" => "Título do evento a ser criado."
                    ],
                    "start" => [
                        "type" => "string",
                        "description" => "Data e hora de início do evento no formato ISO 8601."
                    ],
                    "end" => [
                        "type" => "string",
                        "description" => "Data e hora de término do evento no formato ISO 8601."
                    ],
                    "name" => [
                        "type" => "string",
                        "description" => "Nome do participante da reunião."
                    ],
                    "email" => [
                        "type" => "string",
                        "description" => "E-mail do participante da reunião."
                    ],
                    "extra_attendees" => [
                        "type" => "array",
                        "description" => "Lista de convidados adicionais",
                        "items" => [
                            "type" => "object",
                            "properties" => [
                                "name" => [
                                    "type" => "string",
                                ],
                                "email" => [
                                    "type" => "string",
                                    "format" => "email"
                                ]
                            ],
                            "required" => ['email']
                        ],
                    ]
                ],
                "required" => ["start", "end", "name", "email"]
            ]
        ];
    }

    public static function assistant_tool_delete_calendar_event()
    {
        return [
            "name" => "delete_calendar_event",
            "description" => "Exclui uma reunião do Google Calendar com base no e-mail e nome do participante. Retorna detalhes do evento encontrado ou realiza a exclusão se confirmado.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "email" => [
                        "type" => "string",
                        "description" => "E-mail do participante da reunião."
                    ],
                    "name" => [
                        "type" => "string",
                        "description" => "Nome do participante da reunião."
                    ],
                    "confirm" => [
                        "type" => "boolean",
                        "description" => "Se true, confirma a exclusão do evento. Se false ou ausente, apenas retorna informações do evento encontrado."
                    ]
                ],
                "required" => ["email"]
            ]
        ];
    }
}
