<?php

class OrganizationService {
    
    private $orgRepository;
    private $userRepository;
    
    public function __construct(OrganizationRepository $orgRepo, UserRepository $userRepo) {
        $this->orgRepository = $orgRepo;
        $this->userRepository = $userRepo;
    }
    
    /**
     * Valida, cria a organização e define o usuário como Organizador.
     * @return array Resultado da operação.
     */
    public function setupNewOrganization(string $org_name, int $user_id): array {
        
        // O papel que será atribuído ao criador
        $role_to_assign = 'charlie_organizer'; 
        
        $user = get_user_by('ID', $user_id);
        $roles = $user ? $user->roles : [];

        error_log("Charlie Setup: User ID {$user_id} Roles: " . implode(',', $roles));

        $is_admin = in_array('administrator', $roles, true);
        $is_organizer = in_array('charlie_organizer', $roles, true);

        if (!$is_admin && !$is_organizer) {
            return [
                'success' => false, 
                'message' => 'Apenas administradores ou organizadores podem criar organizações.'
            ];
        }
        
        // --- 1. Validação (Pode ser substituído pela Policy no futuro) ---
        // $existing_org_id = (int) get_user_meta($user_id, 'charlie_organization_id', true);
        $existing_org_id = $this->orgRepository->findByOwnerUserId($user_id);
        if ($existing_org_id > 0) {
             return [
                 'success' => false, 
                 'message' => 'Este usuário já está vinculado a uma organização.',
                 'organization_id' => $existing_org_id
             ];
        }
        
        // --- 2. Criar a Organização (Repository) ---
        $organization_id = $this->orgRepository->create($org_name, $user_id);
        
        if (!$organization_id) {
             return ['success' => false, 'message' => 'Falha na criação da organização no banco de dados.'];
        }
        
        // --- 3. Atribuir a Organização e o Role (UserRepository) ---
        $success = $this->userRepository->assignOrganizationAndRole(
            $user_id, 
            $organization_id, 
            $role_to_assign
        );

        if (!$success) {
            // Se o usuário falhar, idealmente você deve deletar a organização recém-criada (transação)
            // Para simplificar, vamos apenas logar e falhar a operação.
            error_log("Charlie Setup: Falha ao atribuir Org ID e Role ao usuário {$user_id}.");
             return ['success' => false, 'message' => 'Organização criada, mas falha ao vincular usuário.'];
        }
        
        return [
            'success' => true, 
            'message' => "Organização '{$org_name}' criada com sucesso. Usuário {$user_id} definido como {$role_to_assign}.",
            'organization_id' => $organization_id
        ];
    }

    public function addMemberToOrganization(int $organizationId, string $email, string $password, int $managerId): bool
    {
        // 1. Verificação de Permissão: O managerId deve ter permissão para gerenciar a organização.
        // Simplificado: Assumimos que o controller já checou que o manager é um organizador válido.
        $organization = $this->orgRepository->findById($organizationId);
        if (!$organization) {
            throw new Exception("Organização não encontrada.", 404);
        }

        // 2. Encontrar ou Criar o Usuário pelo Email
        $user = get_user_by('email', $email);
        
        // Se o usuário não existir, cria um novo (cenário de convite simplificado)
        if (!$user) {
            // Gera um nome de usuário a partir do email (ex: joao.silva)
            $username_base = sanitize_user(explode('@', $email)[0], true);
            $username = $username_base;
            $counter = 1;
            
            // Garante que o username é único
            while (username_exists($username)) {
                $username = $username_base . $counter++;
            }

            // Gera uma senha aleatória segura
            // $random_password = wp_generate_password(12, false);

            $user_data = array(
                'user_login'    => $username,
                'user_pass'     => $password, 
                'user_email'    => $email,
                'role'          => 'charlie_operator',
                'display_name'  => $username,
            );

            $user_id_or_error = wp_insert_user($user_data);

            if (is_wp_error($user_id_or_error)) {
                throw new Exception("Erro ao criar novo usuário: " . $user_id_or_error->get_error_message(), 500);
            }

            // Carrega o objeto do novo usuário
            $user = get_user_by('ID', $user_id_or_error);
            
            if (!$user) {
                throw new Exception("Usuário criado, mas não pode ser carregado. Erro interno.", 500);
            }
        }
        
        // ID do usuário a ser adicionado
        $userId = (int) $user->ID;

        // 3. Checar se o usuário já pertence a esta ou outra organização
        $currentOrg = $this->orgRepository->findByUserId($userId);
        if ($currentOrg && (int) $currentOrg->id === $organizationId) {
            throw new Exception("O usuário já é membro desta organização.", 409);
        }
        if ($currentOrg && (int) $currentOrg->id !== 0) {
            throw new Exception("O usuário já pertence a outra organização ({$currentOrg->name}).", 409);
        }
        
        // 4. Atribuição da Organização e Role
        $result = $this->userRepository->assignOrganizationAndRole($userId, $organizationId, 'charlie_operator');

        if (!$result) {
            throw new Exception("Erro ao atribuir organização e role ao usuário.", 500);
        }

        return true;
    }

    public function removeMember(int $userId, int $managerId): bool
    {
        // 1. Verificação de Permissão: O controller deve garantir que o managerId é um organizador.

        // 2. O usuário não pode remover a si mesmo se for o único organizador (para evitar orfanação)
        if ($userId === $managerId) {
            throw new Exception("Você não pode remover a si mesmo através desta interface. Transfira a posse primeiro.", 403);
        }

        // 3. Limpar a Organização e Roles (ID 0 significa "sem organização")
        // Role 'subscriber' é o role padrão do WP após a remoção dos roles da Charlie
        $result = $this->userRepository->assignOrganizationAndRole($userId, 0, '');

        if (!$result) {
            throw new Exception("Erro ao remover o usuário da organização.", 500);
        }

        //remover o usuário do wordpress
        wp_delete_user($userId);

        //checar se o usuário foi removido
        $user = get_user_by('ID', $userId);

        if ($user) {
            throw new Exception("Erro ao remover o usuário do WordPress.", 500);
        }
        
        return true;
    }
}