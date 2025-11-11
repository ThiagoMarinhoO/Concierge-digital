<?php

class OrganizationRepository
{

    private $wpdb;
    private $table_name_full;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name_full = $wpdb->prefix . 'charlie_organizations';
    }

    public function create(string $name, int $owner_user_id): int
    {
        $result = $this->wpdb->insert(
            $this->table_name_full,
            [
                'name' => $name,
                'owner_user_id' => $owner_user_id,
                'created_at' => current_time('mysql', 1) // Garante o fuso horário correto
            ],
            ['%s', '%d', '%s']
        );

        if ($result === false) {
            // Lógica de tratamento de erro do banco de dados (opcional, mas boa prática)
            error_log("Charlie DB Error creating organization: " . $this->wpdb->last_error);
            return 0;
        }

        return $this->wpdb->insert_id;
    }

    public function findById(int $id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name_full} WHERE id = %d LIMIT 1",
            $id
        );

        $organization = $this->wpdb->get_row($sql);

        return $organization;
    }

    /**
     * Busca o ID da organização onde o usuário é o proprietário (owner_user_id).
     * @param int $userId O ID do usuário.
     * @return int O ID da Organização ou 0 se não encontrar.
     */
    public function findByOwnerUserId(int $userId): object
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name_full} WHERE owner_user_id = %d LIMIT 1",
            $userId
        );

        $organization = $this->wpdb->get_row($sql);

        return $organization;
    }

    public function findByUserId(int $userId): ?object
    {
        $orgId = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT organization_id FROM {$this->wpdb->users} WHERE ID = %d",
                $userId
            )
        );

        if (empty($orgId) || (int) $orgId === 0) {
            return null;
        }

        $organization = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name_full} WHERE id = %d",
                $orgId
            )
        );

        return $organization ?: null;
    }
}
