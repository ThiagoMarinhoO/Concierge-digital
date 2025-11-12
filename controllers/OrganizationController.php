<?php
class OrganizationController
{

    private $organizationService;

    public function __construct(OrganizationService $orgService)
    {
        $this->organizationService = $orgService;
        $this->registerActions();
    }

    /**
     * Registra as rotas do Charlie.
     */
    public function registerActions()
    {
        add_action('wp_ajax_create_organization', [$this, 'store']);
        // add_action('wp_ajax_get_organizations', [$this, 'index']);
    }

    /**
     * Cria uma nova organização (Store).
     */
    public function store()
    {
        // Obter o nome do parâmetro 'name'
        $org_name = $_POST['name'] ?? null;
        $user_id = get_current_user_id();

        if (!$org_name || $user_id === 0) {
            return wp_send_json_error(['message' => 'Parâmetros inválidos.'], 400);
        }

        $user_policy = user_can($user_id, 'manage_organization');
        if (empty($user_policy)) {
            wp_send_json_error(
                [
                    'message' => 'Você não está autorizado a realizar esta ação.'
                ],
                401
            );
        }

        // 1. Chamar o serviço
        $result = $this->organizationService->setupNewOrganization($org_name, $user_id);

        // 2. Retorno padronizado da API
        if ($result['success']) {
            return wp_send_json_success($result, 201); // 201 Created
        } else {
            return wp_send_json_error(['message' => $result['message']], 409); // 409 Conflict (já existe) ou 400 Bad Request
        }
    }

    /**
     * Busca os dados da organização do usuário atual (Index/Show).
     */
    // public function index(WP_REST_Request $request) {
    //     $user_id = get_current_user_id();

    //     // Usar um método de busca no Service
    //     $organization = $this->organizationService->getUserOrganizationData($user_id);

    //     if ($organization) {
    //         return new WP_REST_Response($organization, 200);
    //     } else {
    //         // Retorna um status de 'não encontrado' (ou vazio)
    //         return new WP_REST_Response(['organization_id' => 0, 'message' => 'Nenhuma organização encontrada.'], 200);
    //     }
    // }

    public function handleAddMember()
    {
        $organizationId = $_POST['organization_id'] ?? null;
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        $organizationOwnerId = get_current_user_id();

        $user_policy = user_can(get_current_user_id(), 'add_users_to_org');
        if (empty($user_policy)) {
            wp_send_json_error(
                [
                    'message' => 'Você não está autorizado a realizar esta ação.'
                ],
                401
            );
        }

        $result = $this->organizationService->addMemberToOrganization($organizationId, $email, $password, $organizationOwnerId);

        if (!$result) {
            wp_send_json_error('Não foi possível adicionar membro. Tente novamente.', 400);
        }

        wp_send_json_success('Membro adicionado com sucesso', 201);
    }

    public function handleRemoveMember()
    {
        $userId = $_POST['user_id'] ?? null;
        $currentUserId = get_current_user_id();

        $user_policy = user_can(get_current_user_id(), 'add_users_to_org');
        if (empty($user_policy)) {
            wp_send_json_error(
                [
                    'message' => 'Você não está autorizado a realizar esta ação.'
                ],
                401
            );
        }

        $result = $this->organizationService->removeMember($userId, $currentUserId);

        if (!$result) {
            wp_send_json_error('Não foi possível remover membro. Tente novamente.', 400);
        }

        wp_send_json_success('Membro removido com sucesso', 200);
    }
}

add_action('wp_ajax_create_organization', function () {
    $orgController = new OrganizationController(new OrganizationService(new OrganizationRepository(), new UserRepository()));
    $orgController->store();
});

add_action('wp_ajax_add_member_to_organization', function () {
    $orgController = new OrganizationController(new OrganizationService(new OrganizationRepository(), new UserRepository()));
    $orgController->handleAddMember();
});

add_action('wp_ajax_remove_member_from_organization', function () {
    $orgController = new OrganizationController(new OrganizationService(new OrganizationRepository(), new UserRepository()));
    $orgController->handleRemoveMember();
});
