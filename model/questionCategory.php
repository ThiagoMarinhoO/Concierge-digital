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
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        position INT(11) DEFAULT 0 NOT NULL,
        display_frontend TINYINT(1) DEFAULT 1 NOT NULL,
        has_tabs TINYINT(1) DEFAULT 0 NOT NULL,
        video_url TEXT DEFAULT NULL
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function addCategory(string $title, int $position = 0, int $display_frontend = 1 , string $video_url = null, bool $has_tab = null): void
    {
        $this->wpdb->insert(
            $this->table,
            [
                'title' => $title,
                'position' => $position,
                'display_frontend' => $display_frontend,
                'has_tab' => $has_tab,
                'video_url' => $video_url
            ],
            ['%s', '%d', '%d', '%d' , '%s']
        );
    }

    public function getAllCategories(): array
    {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table} 
         ORDER BY position ASC",
            ARRAY_A
        );
    }

    public function getCategoryByName(string $name)
    {
        $result = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE title = %s", $name), ARRAY_A);
        return $result ? $result : null;
    }

    public function getCategoryById(int $id)
    {
        $result = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
        return $result ? $result : null;
    }

    public function deleteCategory(int $id): void
    {
        $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    public function updateCategory($id, $title, $position , $video_url): bool
    {
        $updated = $this->wpdb->update(
            $this->table,
            [
                'title' => $title,
                'position' => $position,
                'video_url' => $video_url
            ],
            ['id' => $id],
        ['%s', '%d' , '%s'],
            ['%d']
        );

        return true;
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

        $sql = "CREATE TABLE {$this->table} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            question_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (question_id) REFERENCES {$this->wpdb->prefix}questions(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES {$this->wpdb->prefix}question_categories(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function addRelationship(int $question_id, int $category_id): void
    {
        $this->wpdb->insert(
            $this->table,
            [
                'question_id' => $question_id,
                'category_id' => $category_id
            ],
            ['%d', '%d']
        );
    }

    public function getCategoriesByQuestionId(int $question_id): array
    {
        return $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE question_id = %d", $question_id), ARRAY_A);
    }

    public function deleteRelationship(int $question_id, int $category_id): void
    {
        $this->wpdb->delete(
            $this->table,
            [
                'question_id' => $question_id,
                'category_id' => $category_id
            ],
            ['%d', '%d']
        );
    }
}
