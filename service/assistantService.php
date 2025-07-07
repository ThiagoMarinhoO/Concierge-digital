<?php
class AssistantService
{
    public static function createAssistant() {}

    public static function submit_tool_outputs(array $tool_outputs, string $thread_id, string $run_id)
    {
        $api_url = defined('OPENAI_API_URL') ? OPENAI_API_URL : 'https://api.openai.com/v1';
        $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;

        if (!$api_key) {
            error_log("API KEY da OpenAI não definida.");
            return;
        }

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer $api_key",
            "OpenAI-Beta: assistants=v2"
        ];

        $payload = json_encode([
            "tool_outputs" => $tool_outputs
        ]);

        $url = "https://api.openai.com/v1/threads/$thread_id/runs/$run_id/submit_tool_outputs";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            error_log('Erro ao enviar tool_outputs: ' . curl_error($ch));
        }

        curl_close($ch);

        $response_data = json_decode($response, true);

        plugin_log("Resposta do submit_tool_outputs: " . print_r($response_data, true));

        return [
            'http_code' => $http_code,
            'response' => $response_data
        ];
    }
}
