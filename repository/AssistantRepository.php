<?php

class AssistantRepository {

    private $wpdb;
    private $table_name_full;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name_full = $wpdb->prefix . 'chatbot';
    }

    public function findAllByUserId(int $user_id): ?array {
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table_name_full} WHERE user_id = %d", $user_id);

        $assistants = $this->wpdb->get_results($sql);

        return $assistants ?: null;
    }
}