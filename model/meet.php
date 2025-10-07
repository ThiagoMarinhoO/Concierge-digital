<?php

class Meet {
    private string $id;
    private string $title;
    private DateTime $startTime;
    private string $assistant_id;

    public function getId(): string {
        return $this->id;
    }

    public function setId(string $id): void {
        $this->id = $id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle(string $title): void {
        $this->title = $title;
    }

    public function getStartTime(): DateTime {
        return $this->startTime;
    }

    public function setStartTime(DateTime $startTime): void {
        $this->startTime = $startTime;
    }

    public function getAssistantId(): string {
        return $this->assistant_id;
    }

    public function setAssistantId(string $assistant_id): void {
        $this->assistant_id = $assistant_id;
    }

    public static function createTable() {
        global $wpdb;
        
        $table_name = 'charlie' . 'meet';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            assistant_id VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function save() {
        global $wpdb;

        $table_name = 'charlie' . 'meet';

        $wpdb->insert(
            $table_name,
            [
                'title' => $this->title,
                'created_at' => current_time('mysql'),
                'assistant_id' => $this->assistant_id,
            ]
        );

        return $wpdb->insert_id;
    }

    public static function all($assistantId) {
        global $wpdb;

        $table_name = 'charlie' . 'meet';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE assistant_id = %s ORDER BY created_at DESC",
                $assistantId
            )
        );
    }
}