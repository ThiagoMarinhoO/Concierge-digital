<?php
class UsageService
{
    private static $tokenLimit = 20000;

    public static function usageControl()
    {
        $usage = new AssistantUsage();
        $usage->load();

        $tokenUsage = max($usage->getTotalPromptTokens(), $usage->getTotalCompletionTokens());
        $percentageUsed = ($tokenUsage / self::$tokenLimit) * 100;

        if ($percentageUsed >= 100) {
            wp_send_json_error(['message' => 'Limite de tokens atingido. Você deve recarregar seu plano.', 'type' => 'limit']);
            return false;
        }

        if ($percentageUsed >= 80) {
            wp_send_json_success(['message' => 'Você usou 80% do seu plano. Considere recarregar.', 'type' => 'warning']);
        }

        return true;
    }



    public static function usagePercentages()
    {
        $usage = new AssistantUsage();
        $usage->load();

        $totalPromptTokens = $usage->getTotalPromptTokens();
        $totalCompletionTokens = $usage->getTotalCompletionTokens();
        $totalTokens = $totalPromptTokens + $totalCompletionTokens;

        $promptPercentage = ($totalPromptTokens / self::$tokenLimit) * 100;
        $completionPercentage = ($totalCompletionTokens / self::$tokenLimit) * 100;
        $totalPercentage = ($totalTokens / self::$tokenLimit) * 100;

        return [
            'prompt' => $promptPercentage,
            'completion' => $completionPercentage,
            'total' => $totalPercentage
        ];
    }

    public static function updateUsage($data)
    {

        plugin_log("Dados recebidos para atualização:");
        plugin_log(print_r($data, true));

        $usage = new AssistantUsage();
        $usage->load();

        $currentTotalTokens = $usage->getTotalTokens();
        $currentPromptTokens = $usage->getTotalPromptTokens();
        $currentCompletionTokens = $usage->getTotalCompletionTokens();

        plugin_log("Valores atuais:");
        plugin_log("Total Tokens: $currentTotalTokens");
        plugin_log("Prompt Tokens: $currentPromptTokens");
        plugin_log("Completion Tokens: $currentCompletionTokens");

        $newTotalTokens = $currentTotalTokens + intval($data['total_tokens']);
        $newPromptTokens = $currentPromptTokens + intval($data['prompt_tokens']);
        $newCompletionTokens = $currentCompletionTokens + intval($data['completion_tokens']);

        plugin_log("Novos valores:");
        plugin_log("Total Tokens: $newTotalTokens");
        plugin_log("Prompt Tokens: $newPromptTokens");
        plugin_log("Completion Tokens: $newCompletionTokens");

        $usage->setTotalTokens($newTotalTokens);
        $usage->setTotalPromptTokens($newPromptTokens);
        $usage->setTotalCompletionTokens($newCompletionTokens);

        $usage->saveOrUpdate();
    }


    private static function notifyUser($message)
    {
        // Aqui pode ser uma API de envio de e-mail ou notificação push
        error_log("NOTIFICAÇÃO PARA O USUÁRIO: " . $message);
    }

    private static function autoRechargePlan()
    {
        // Simula uma recarga automática (aqui pode chamar a API de cobrança, por exemplo)
        error_log("Recarga automática realizada com sucesso!");

        // Aqui podemos redefinir os tokens do usuário para 0 ou somar mais tokens
        $usage = new AssistantUsage();
        $usage->load();
        $usage->setTotalTokens(0);
        $usage->setTotalPromptTokens(0);
        $usage->setTotalCompletionTokens(0);
        $usage->update();
    }
}
