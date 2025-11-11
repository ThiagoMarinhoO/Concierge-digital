<?php

class UserRepository {

    private $wpdb;
    private $table_name_full;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name_full = $wpdb->prefix . 'users';
    }

    public function assignOrganizationAndRole(int $user_id, int $organization_id, string $role): bool {
        
        // 1. Atualiza a coluna charlie_organization_id no wp_users
        $db_update = $this->wpdb->update(
            $this->wpdb->users,
            [
                'organization_id' => $organization_id,
            ],
            ['ID' => $user_id],
            ['%d'],
            ['%d']
        );
        
        // 2. Atribui o role (usando API nativa do WP)
        $user = get_user_by('ID', $user_id);
        if ($user) {
            // Remove quaisquer roles padrão de Charlie para garantir apenas um
            $user->remove_role('charlie_operator'); 
            $user->remove_role('charlie_organizer');
            
            // Adiciona o novo role
            $user->add_role($role);
        }

        return $db_update !== false && $user !== false;
    }

    public function findAllByOrganizationId(int $organization_id): ?array {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name_full} WHERE organization_id = %d",
            $organization_id
        );

        $users = $this->wpdb->get_results($sql);

        return $users ?: null;
    }
}