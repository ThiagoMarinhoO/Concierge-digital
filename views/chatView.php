<?php

class ChatViewComponent
{
    public function __construct() {}

    public static function render()
    {
        if (!is_user_logged_in()) {
            return '<p class="text-white font-bold">Você precisa estar logado para acessar esta página.</p>';
        }

        if(!get_active_subscription_product_id()){
            return '<p class="text-white font-bold text-center">Recurso bloqueado. Para desbloquear:<br><a href="' . get_home_url() . '/#planos" class="underline text-lime-400 hover:text-lime-300">Obtenha um plano agora</a>.</p>';
        }
        
        $benefits = get_user_subscription_benefits(get_current_user_ID());

        $is_Chat = $benefits['dashboard_completo'];

        if(!$is_Chat){
            return '<p class="text-white font-bold text-center">Recurso bloqueado. Para desbloquear:<br><a href="' . get_home_url() . '/#planos" class="underline text-lime-400 hover:text-lime-300">Faça upgrade agora</a>.</p>';
        }
        $orgRepo = new OrganizationRepository();

        $user_id = get_current_user_id();
        $currentUser = wp_get_current_user();
        $organization_id = 0;
        $resource_user_id = 0;
        $whatsappInstance = null;

        if (!empty($user_id)) {
            $orgData = $orgRepo->findByUserId($user_id);
            $organization_id = $orgData ? (int) $orgData->id : 0;

            $resource_user_id = $organization_id > 0 && isset($orgData->owner_user_id)
                ? (int) $orgData->owner_user_id
                : $user_id;
        }

        if (!empty($resource_user_id)) {
            $whatsappInstance = WhatsappInstance::findByUserId($resource_user_id);
        }

        // $instance = WhatsappInstance::findByUserId(get_current_user_id());

        error_log('Instancia ' . print_r($whatsappInstance, true));
        error_log('Organization id: ' . print_r($organization_id, true));
        error_log('Resource id: ' . print_r($resource_user_id, true));

        if (empty($whatsappInstance)) {
            return '<p class="text-white font-bold">Você precisa ter uma instancia cadastrada.</p>';
        }

        ob_start();
?>
        <div class="chat-container" data-instance="<?php echo esc_attr($whatsappInstance->getInstanceName()); ?>">
            <!-- Coluna da Esquerda: Lista de Conversas -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <img src="https://projetocharlie.humans.land/wp-content/uploads/2025/04/charlie-colorido_1.webp" alt="Logo Charlie" class="!h-8 !mb-5">
                    <input type="text" id="search-input" placeholder="Buscar por nome ou ID...">
                </div>
                <div id="conversation-list" class="conversation-list">
                    <!-- As conversas serão inseridas aqui pelo JavaScript -->
                </div>
            </div>

            <!-- Coluna da Direita: Janela de Chat -->
            <div class="chat-window">
                <div id="chat-header" class="chat-header">
                    <!-- O nome do cliente aparecerá aqui -->
                    <h2>Selecione uma conversa</h2>
                </div>
                <div id="chat-messages" class="chat-messages">
                    <!-- As mensagens da conversa selecionada aparecerão aqui -->
                    <div class="no-conversation-selected">
                        <i class="fas fa-comments"></i>
                        <p>Selecione uma conversa na lista à esquerda para ver as mensagens.</p>
                    </div>
                </div>
                <div class="chat-input">
                    <form id="chat-form">
                        <input type="text" id="message-input" placeholder="Digite sua mensagem..." autocomplete="off" disabled>
                        <button type="submit" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- <div class="w-full">
            <button id="humanSession" class="human-session-button bg-blue-500 text-white px-4 py-2 rounded ">
                Atendimento humano
            </button>
        </div> -->

<?php
        return ob_get_clean();
    }
}

add_shortcode('chatView_component', [ChatViewComponent::class, 'render']);
?>