<?php
class QuestionCategory
{
    private $wpdb;
    private $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->wpdb->prefix . 'question_categories';
    }

    public function createTable()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table} (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function addCategory(string $title): void
    {
        $this->wpdb->insert(
            $this->table,
            ['title' => $title],
            ['%s']
        );
    }

    public function getAllCategories(): array
    {
        return $this->wpdb->get_results("SELECT * FROM {$this->table}", ARRAY_A);
    }

    public function deleteCategory(int $id): void
    {
        $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
    }
}

class QuestionCategoryRelationships
{
    private $wpdb;
    protected $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->wpdb->prefix . 'question_category_relationships';
    }

    public function createTable()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table} ( id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY, question_id BIGINT(20) UNSIGNED NOT NULL, category_id BIGINT(20) UNSIGNED NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (question_id) REFERENCES {$this->wpdb->prefix}questions(id) ON DELETE CASCADE, FOREIGN KEY (category_id) REFERENCES {$this->wpdb->prefix}question_categories(id) ON DELETE CASCADE ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}