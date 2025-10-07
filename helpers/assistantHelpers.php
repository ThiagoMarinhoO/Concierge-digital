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
            "description" => "Consulta os horários disponíveis na agenda do Google Calendar. Pode filtrar por data e período do dia.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "target_date" => [
                        "type" => "string",
                        "description" => "Data no formato dd/mm/YYYY. Quando fornecida, retorna os horários detalhados desse dia. Se omitida, retorna apenas a lista de dias e períodos (manhã/tarde/noite)."
                    ],
                    "period_of_day" => [
                        "type" => "string",
                        "enum" => ["manhã", "tarde", "noite"],
                        "description" => "Período do dia: manhã, tarde ou noite. Quando fornecido junto com target_date, filtra os horários disponíveis para esse período específico."
                    ]

                ],
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

    public static function assistant_tool_create_human_flag()
    {
        return [
            "name" => "create_human_flag",
            "description" => "Registra no banco de dados que o atendimento deve ser transferido para um humano.",
            "parameters" => [
                "type" => "object",
                "properties" => new \stdClass(),
                "required" => []
            ]
        ];
    }


    /**
     *  PROMPTS FUNÇÕES
     */
    // Pra essa entrar ainda deverá ser checado se o assistente possui a função
    public static function webFunctionsPrompt()
    {
        return "";
    }

    public static function webAndWhatsappPrompt()
    {
        return "- Sempre que o usuário demonstrar interesse em falar com um humano, atendente ou pedir contato via WhatsApp — como nas expressões: - \\\"quero falar com um humano\\\" - \\\"tem um número de WhatsApp?\\\" - \\\"me chama no WhatsApp\\\" - \\\"quero falar com alguém\\\" - \\\"pode me passar o WhatsApp?\\\" - \\\"falar com atendente\\\" - \\\"conversar no zap\\\" Você **deve acionar a função 'solicitar_conversacao_whatsapp'** sem argumentos. Não responda diretamente com um link ou mensagem de contato: apenas dispare a função e aguarde o sistema retornar o link. Depois que o sistema retornar o link, apresente-o ao usuário com uma resposta amigável, como: \\\"Claro! É só clicar aqui para conversar com a gente no WhatsApp: [link]\\\"";
    }

    public static function whatsappFunctionsPrompt()
    {
        return "- Quando necessário, você deve transferir a conversa para um humano. Regras para transferência para humano: 1. Se o usuário mencionar que deseja falar com um humano, atendente ou suporte, NÃO transfira imediatamente. 2. Primeiro, confirme a intenção com uma pergunta como: - \\\"Deseja que eu repasse o atendimento para um colaborador humano?\\\" - \\\"Posso chamar um atendente humano para continuar?\\\" 3. Aguarde a resposta do usuário: - Se a resposta for afirmativa (ex.: \\\"sim\\\", \\\"ok\\\", \\\"por favor\\\"), então chame a função 'create_human_flag'. - Se for negativa ou incerta, continue atendendo normalmente. 4. Só chame a função 'create_human_flag' uma vez para cada solicitação. 5. Mantenha sempre um tom educado, amigável e profissional. Importante: - Se o usuário não mencionar humano/atendente, não ofereça proativamente. - Sempre deixe claro quando for transferir que um humano assumirá a conversa.";
    }
}
