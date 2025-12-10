<?php

class HumanSessionRepository {
    private $wpdb;
    private $table_name_full;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name_full = 'human_sessions';
    }

    public function findAll(string|null $instanceName = null): array
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name_full} WHERE instance_name = %s AND ended_at IS NOT NULL ORDER BY started_at DESC",
            $instanceName
        );

        $sessions = $this->wpdb->get_results($sql);

        return $sessions ?: [];
    }
}