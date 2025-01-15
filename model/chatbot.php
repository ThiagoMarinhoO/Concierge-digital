<?php
class Chatbot
{
    protected $api_key;

    private $endpoint;

    public function __construct()
    {
        $this->api_key = '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';
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';
    }

    public function enviarMensagem(string $mensagem): string
    {
        $conciergeData = $_SESSION['chatbotOptions'] ?? null;

        if (empty($conciergeData) || !is_array($conciergeData)) {
            error_log('Erro: Dados do concierge ausentes ou inválidos.');
            return json_encode([
                'error' => true,
                'message' => 'Dados do concierge ausentes ou inválidos. Não foi possível gerar a mensagem.'
            ]);
        }

        // Loga os dados do concierge para debug
        // error_log('---- Concierge Data ----');
        // error_log(json_encode($conciergeData));

        // Popula as variáveis com verificações e valores padrão
        $conciergeName = !empty($conciergeData['concierge_name']) ? $conciergeData['concierge_name'] : null;
        $conciergeObjective = !empty($conciergeData['concierge_objective']) ? $conciergeData['concierge_objective'] : null;
        $conciergeTone = !empty($conciergeData['concierge_tone']) ? $conciergeData['concierge_tone'] : null;
        $conciergeApproach = !empty($conciergeData['concierge_approach']) ? $conciergeData['concierge_approach'] : null;
        $formalLevel = !empty($conciergeData['formal_level']) ? $conciergeData['formal_level'] : null;
        $conciergeAudience = !empty($conciergeData['concierge_audience']) ? $conciergeData['concierge_audience'] : null;
        $conciergeKnowledgeLevel = !empty($conciergeData['concierge_knowledge_level']) ? $conciergeData['concierge_knowledge_level'] : null;
        $conciergeCustomTerms = !empty($conciergeData['concierge_custom_terms']) ? $conciergeData['concierge_custom_terms'] : null;

        $data = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "
                    Seu nome é {$conciergeName}.
                    Seu objetivo / função é {$conciergeObjective}.
                    Seu tom deve ser {$conciergeTone}.
                    Sua abordagem deve ser {$conciergeApproach}.
                    Sua formalidade deve ser {$formalLevel}.
                    Você não pode usar as seguintes palavras: {$conciergeCustomTerms}.
                    Sua audiência são {$conciergeAudience}.
                    Seu nível de conhecimento deve ser {$conciergeKnowledgeLevel}.
                "
                ],
                [
                    'role' => 'user',
                    'content' => $mensagem
                ],
            ],
            'model' => 'gpt-4o'
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        // Check for errors in the API response
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Error sending the message: ' . $error);
        }

        curl_close($ch);

        $arrResult = json_decode($response, true);
        $resultMessage = $arrResult["choices"][0]["message"]["content"];

        error_log('------ mensagem do sistema -------');
        error_log($resultMessage);

        return $resultMessage;
    }
}
