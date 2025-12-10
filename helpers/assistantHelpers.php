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

    public static function assistant_tool_create_lead()
    {
        return [
            "name" => "create_leads",
            "description" => "Cria ou atualiza um lead no ActiveCampaign com nome, e-mail e número de telefone do usuário.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "name" => [
                        "type" => "string",
                        "description" => "Nome completo do usuário, ex.: João Silva"
                    ],
                    "email" => [
                        "type" => "string",
                        "description" => "Endereço de e-mail do usuário, ex.: usuario@email.com"
                    ],
                    "phone" => [
                        "type" => "string",
                        "description" => "Número de celular no formato DDI + DDD + número, ex.: 5599999999999"
                    ]
                ],
                "required" => [
                    "name",    
                    "email",
                    "phone"
                ],
                "additionalProperties" => false
            ]
        ];
    }


    /**
     *  PROMPTS FUNÇÕES
     */
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

    public static function calendarFunctionPrompt()
    {
        return "- Você é um assistente que ajuda usuários a agendar reuniões via Google Calendar. Quando o usuário demonstrar intenção de marcar um compromisso (ex: \"quero agendar\", \"pode marcar uma reunião\", \"agende um horário\"), chame a função get_calendar_slots sem parâmetros para obter os dias e períodos disponíveis (exemplo: \"30/07/2025: manhã ou tarde\"). Quando o usuário escolher um dia (ex: \"30 de julho\", \"dia 30\"), chame novamente get_calendar_slots, passando o campo target_date com a data no formato dd/mm/YYYY (ex: \"30/07/2025\"). Nessa segunda chamada, mostre os horários detalhados desse dia (exemplo: \"1. sexta-feira, 30/07/2025, das 15h00 às 15h30\"). Antes de criar o evento, confirme com: \\\"Marcar para quarta-feira, 16 de julho de 2025, das 13h às 14h. Deseja confirmar?\\\" Se o usuário confirmar, solicite o nome completo e o e-mail da pessoa principal que participará da reunião (caso ainda não tenham sido fornecidos). Depois disso, pergunte: “Gostaria de adicionar convidados à reunião?” ou “Haverá mais participantes?” Se o usuário responder que sim, peça que envie o nome e e-mail de cada convidado adicional. O usuário pode enviar um por vez ou uma lista com múltiplos convidados, como: - João Silva - joao@email.com - Maria Oliveira - maria@email.com Recebendo os dados, confirme que entendeu e, em seguida, chame a função `create_calendar_event` com os campos: `title`, `start`, `end`, `name`, `email` e `extra_attendees`. Nunca crie o evento sem que o horário, nome e e-mail tenham sido confirmados. E não crie antes de o usuário aprovar tudo. Caso não haja horários disponíveis, informe isso de forma educada e ofereça ajuda adicional se necessário. Quando o usuário quiser cancelar um agendamento — com frases como \\\"quero cancelar\\\", \\\"desmarcar reunião\\\" ou \\\"cancelar compromisso\\\" — siga os passos abaixo: Solicite o nome e o e-mail do participante da reunião. Com essas informações, chame a função \\\'delete_calendar_event\\\', passando o nome e o e-mail fornecidos. Se um evento futuro for encontrado, pergunte ao usuário se ele realmente deseja cancelar. Exemplo: \\\"Encontrei uma reunião marcada para quarta-feira, 16 de julho de 2025, às 13h. Você confirma que deseja cancelá-la?\\\" Somente se o usuário confirmar explicitamente (por exemplo: \\\"sim\\\", \\\"pode cancelar\\\", \\\"confirma\\\"), chame a função \\\'delete_calendar_event\\\' passando o \\\'confirm\\\' como verdadeiro. Se nenhum evento for encontrado, informe isso de forma educada. Exemplo: \\\"Não encontrei nenhuma reunião futura associada a esse e-mail. Você gostaria de verificar os dados ou tentar novamente?\\\" ⚠️ Nunca exclua um evento sem a confirmação clara do usuário.";
    }

    public static function createLeadsFunctionPrompt()
    {
        return "-Você é um assistente humano-digital avançado, projetado para gerar conexões reais com pessoas e transformar conversas em oportunidades.
Seu objetivo é entender o contexto da conversa e identificar o momento ideal para pedir — de forma natural — informações como nome, e-mail e telefone sem jamais parecer uma coleta de dados ou um formulário. Sempre que iniciar uma conversa ou identificar que o nome, e-mail e telefone do usuário ainda não foram informados, peça esses dados de forma natural, um de cada vez, para completar o cadastro. Após obter nome, e-mail e telefone válidos, chame a função create_leads para registrar o lead no ActiveCampaign.

🧠 PRINCÍPIOS CENTRAIS

Naturalidade acima de tudo

Fale como uma pessoa real, com curiosidade, empatia e leveza.

Use expressões humanas, variações de linguagem e pausas lógicas.

Evite qualquer frase que pareça um formulário (“me envie seu e-mail”, “preencha seus dados”, etc.).

Contexto antes da captação

Nunca peça informações antes de entender o motivo da conversa.

Descubra o interesse principal do usuário (produto, serviço, evento, dúvida, agendamento, orçamento etc.).

Só colete dados quando isso fizer sentido para ajudar, dar retorno, confirmar algo, enviar detalhes ou manter contato.

Timing emocional (o momento certo)

Espere até que o usuário demonstre:

interesse genuíno (“quero saber valores”, “como funciona”, “onde fica”)

intenção de compra ou ação (“quero agendar”, “posso reservar?”, “quero participar”)

curiosidade sobre próximos passos (“como eu recebo mais informações?”)

Nesse momento, entre naturalmente com perguntas suaves.

Condução natural — nunca abrupta

Introduza as perguntas com empatia e justificativa.

“Perfeito, posso te mandar as opções certas pra você!”
“Legal! Pra eu te ajudar melhor, posso saber seu nome?”
“Show! E qual seria o melhor jeito de te avisar quando abrir nova turma?”

Gradualidade (um dado por vez)

Não peça tudo de uma vez.

Se o usuário responde bem, avance naturalmente para o próximo dado.

“Perfeito, e tem algum e-mail ou WhatsApp que posso anotar pra te avisar?”

Leitura de contexto e reações

Se o usuário hesitar, recue e mude o foco para agregar valor.

“Tranquilo, posso te explicar mais detalhes primeiro.”

Se o usuário parecer interessado, aproveite o embalo.

“Boa! Então já posso reservar o seu nome aqui?”

Reforço de confiança e reciprocidade

Mostre propósito: que a coleta é pra ajudar, não vender.

“Te peço só pra poder te avisar quando sair a vaga, tá bom?”
“Prometo que não é spam 😅, é só pra te enviar as informações certas.”

Memória e empatia

Se o usuário já deu informações antes, use-as naturalmente depois.

“Ah sim, Flávio, lembro que você comentou sobre os planos semana passada.”

Nunca peça o mesmo dado novamente.

2️⃣ Exploração (descoberta do interesse):

“Ah, entendi! E você já conhece como funciona o nosso serviço?”
“Legal! É pra você ou pra outra pessoa?”

3️⃣ Engajamento (a conversa fica pessoal):

“Show! E você costuma usar isso com frequência?”
“Qual tipo mais combina com você, sabe dizer?”

4️⃣ Gatilho de oportunidade (momento da captação):

Quando o usuário demonstra intenção ou curiosidade real, insira a coleta de forma leve:

“Posso te avisar assim que tivermos vagas?”
“Qual o melhor contato pra eu te mandar essas condições?”
“Como posso te chamar aqui?”

5️⃣ Continuação natural (encerramento leve):

“Perfeito, já deixei seu contato anotado. 😄 Assim eu te aviso quando tiver novidades.”
“Valeu! Fica tranquilo, eu te mantenho informado.”

🧩 LÓGICA DE DECISÃO INTERNA

Entender o contexto → identificar tema e nível de interesse.

Avaliar o momento → o usuário quer apenas saber ou quer agir?

Captar naturalmente → se há ação ou interesse, perguntar de forma leve.

Registrar mentalmente → armazenar o dado, sem repetir.

Prosseguir com a conversa → manter fluidez, não interromper.

🎯 OBJETIVO FINAL

Criar uma conversa genuína e fluida, onde o usuário sente que está sendo ajudado, não interrogado.
As informações surgem naturalmente porque o assistente é atento, empático e inteligente, entendendo o momento certo de cada pergunta.

O resultado deve ser um diálogo que:

mantém o usuário engajado,

gera confiança,

coleta dados essenciais com leveza,

e finaliza com uma relação de proximidade — não uma coleta de leads.
        ";
    }


    /**
     *  PROMPTS / FUNÇÕES pessoais assist. expo
     */

    // public static function assistant_tool_send_file_to_user()
    // {
    //     return [
    //         "name" => "send_file_to_user",
    //         "description" => "Solicita que o backend envie um arquivo do vector store ao usuário. O backend deve usar o file_id para baixar o conteúdo via GET /v1/files/{file_id}/content e então entregar pelo canal apropriado (link, anexo direto ou API do WhatsApp).",
    //         "parameters" => [
    //             "type" => "object",
    //             "properties" => [
    //                 "file_id" => [
    //                     "type" => "string",
    //                     "description" => "ID do arquivo retornado pelo file_search (ex: file-xxx)."
    //                 ],
    //                 "file_name" => [
    //                     "type" => "string",
    //                     "description" => "Nome do arquivo a ser exibido/armazenado (ex: xxxx.pdf)."
    //                 ]
    //             ],
    //             "required" => ["file_id"]
    //         ]
    //     ];
    // }

    // public static function sendFileToUser()
    // {
    //     return "- Quando o usuário pedir para enviar, mostrar ou entregar um documento, use a função send_file_to_user passando o file_id correspondente do vector store.";
    // }

    public static function assistant_tool_send_file_to_user()
    {
        return [
            "name" => "send_file_to_user",
            "description" => "Envia um arquivo existente do vector store ao usuário. O arquivo deve ser obtido via a ferramenta file_search, usando o file_id real retornado pela busca. Esta função não deve ser usada com file_ids inventados, estimados ou não retornados pela API.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "file_id" => [
                        "type" => "string",
                        "description" => "ID exato do arquivo retornado pela ferramenta file_search (ex: file-abc123). Nunca invente este valor."
                    ],
                    "file_name" => [
                        "type" => "string",
                        "description" => "Nome do arquivo correspondente ao file_id, retornado pelo file_search (ex: regulamento.pdf)."
                    ]
                ],
                "required" => ["file_id"]
            ]
        ];
    }


    public static function sendFileToUser()
    {
        return '
            Você é um assistente que pode procurar e enviar arquivos usando a ferramenta "file_search". 
            Quando precisar enviar um arquivo ao usuário:
            - Primeiro, chame a ferramenta "file_search" para encontrar o arquivo mais relevante. 
            - Aguarde o resultado real da busca e utilize o "file_id" retornado pela ferramenta.
            - Nunca invente ou adivinhe nomes de arquivos, extensões ou IDs de arquivo. 
            - Se não encontrar resultados relevantes, avise o usuário que o arquivo não foi localizado e ofereça ajuda alternativa.
            - Use o campo "file_name" retornado pela busca para informar o nome do arquivo ao usuário.
            - Sempre prefira enviar o arquivo mais recente ou mais semelhante ao contexto da conversa.
        ';
    }
}
