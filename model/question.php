<?php
class Question
{
    private $wpdb;
    private $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->wpdb->prefix . 'questions';
    }

    public function createTable()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table} (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title TEXT NOT NULL,
        options TEXT DEFAULT NULL,
        training_phrase TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function addQuestion(string $title, string $training_phrase, array $options, array $categories): int
    {
        $this->wpdb->insert(
            $this->table,
            [
                'title' => $title,
                'options' => json_encode($options),
                'training_phrase' => $training_phrase
            ],
            [
                '%s',
                '%s',
                '%s'
            ]
        );

        $question_id = $this->wpdb->insert_id;

        if ($question_id) {
            $relation_table = $this->getRelationTable();
            foreach ($categories as $category_id) {
                $this->wpdb->insert(
                    $relation_table,
                    [
                        'question_id' => $question_id,
                        'category_id' => $category_id
                    ],
                    [
                        '%d',
                        '%d'
                    ]
                );
            }
        }

        return $question_id;
    }

    public function getAllQuestions(): array
    {
        return $this->wpdb->get_results("SELECT * FROM {$this->table}", ARRAY_A);
    }

    public function deleteQuestion($id)
    {
        $relation_table = $this->getRelationTable();
        $this->wpdb->delete($relation_table, ['question_id' => $id], ['%d']);
        $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    private function getRelationTable(): string
    {
        return $this->wpdb->prefix . 'question_category_relationships';
    }
}
