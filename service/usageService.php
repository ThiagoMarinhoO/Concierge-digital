<?php
class UsageService
{
    private static $tokenLimit = 50000;

    public static function usageControl()
    {
        $usage = new AssistantUsage();
        $usage->load();

        $tokenUsage = max($usage->getTotalPromptTokens(), $usage->getTotalCompletionTokens());
        $percentageUsed = ($tokenUsage / self::$tokenLimit) * 100;

        if ($percentageUsed >= 100) {

            $subscriptionRenewed = self::renewSubscriptionForUser($usage->getUserId());

            if ($subscriptionRenewed) {

                $usage->setTotalTokens(0);
                $usage->setTotalPromptTokens(0);
                $usage->setTotalCompletionTokens(0);
                $usage->update();

                return ['message' => 'Limite de tokens atingido. Seu plano foi recarregado automaticamente.'];
            } else {
                return ['message' => 'Falha ao renovar automaticamente. Entre em contato com o suporte.'];
            }

            // return ['message' => 'Limite de tokens atingido. Seu plano será recarregado automaticamente.'];
        }

        if ($percentageUsed >= 80) {
            return ['message' => 'Você usou 80% do seu plano. Seu plano será recarregado automaticamente em 100%.'];
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

    private static function renewSubscriptionForUser($userId)
    {
        plugin_log("Tentando renovar a assinatura para o usuário ID: " . $userId);

        if (!function_exists('wcs_get_users_subscriptions')) {
            plugin_log("WooCommerce Subscriptions não está ativo.");
            return false;
        }

        // Obtém as assinaturas do usuário
        $subscriptions = wcs_get_users_subscriptions($userId);

        if (!$subscriptions) {
            plugin_log("Nenhuma assinatura encontrada para o usuário ID: " . $userId);
            return false;
        }

        // Filtra apenas assinaturas ativas
        $activeSubscriptions = array_filter($subscriptions, function ($subscription) {
            return $subscription->has_status('active');
        });

        // Pega apenas a primeira assinatura ativa encontrada
        $currentSubscription = reset($activeSubscriptions);

        if (!$currentSubscription) {
            plugin_log("Nenhuma assinatura ativa encontrada para o usuário ID: " . $userId);
            return false;
        }

        // Criar um pedido de renovação
        $renewal_order = wcs_create_renewal_order($currentSubscription);

        if (!$renewal_order) {
            plugin_log("Erro ao criar pedido de renovação para a assinatura ID: " . $currentSubscription->get_id());
            return false;
        }
    
        plugin_log("Pedido de renovação criado: " . $renewal_order->get_id());
    
        // Define o método de pagamento como o da assinatura original
        $payment_method = $currentSubscription->get_payment_method();
        $renewal_order->set_payment_method($payment_method);
    
        // Processa o pagamento automaticamente
        $renewal_order->payment_complete();
    
        // Verifica se o pedido foi pago com sucesso
        if ($renewal_order->is_paid()) {
            plugin_log("Pagamento processado com sucesso para o pedido: " . $renewal_order->get_id());
            return true;
        } else {
            plugin_log("Falha ao processar pagamento para o pedido: " . $renewal_order->get_id());
            return false;
        }
    }
}
