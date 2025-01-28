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
    field_type VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function addQuestion(string $title, string $training_phrase, array $options, array $categories, string $field_type): int
    {
        $this->wpdb->insert(
            $this->table,
            [
                'title' => $title,
                'options' => json_encode($options),
                'training_phrase' => $training_phrase,
                'field_type' => $field_type
            ],
            [
                '%s',
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
        $relation_table = $this->getRelationTable();
        $category_table = $this->wpdb->prefix . 'question_categories';

        $sql = "
        SELECT q.id, q.title, q.options, q.training_phrase, q.field_type, q.created_at,
               GROUP_CONCAT(c.title) AS categories
        FROM {$this->table} q
        LEFT JOIN {$relation_table} r ON q.id = r.question_id
        LEFT JOIN {$category_table} c ON r.category_id = c.id
        GROUP BY q.id
    ";

        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function deleteQuestion($id)
    {
        $relation_table = $this->getRelationTable();
        $this->wpdb->delete($relation_table, ['question_id' => $id], ['%d']);
        $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    public function updateQuestion($id, $title, $training_phrase, $options, $category, $field_type): bool
{
    $updated = $this->wpdb->update(
        $this->table,
        [
            'title' => $title,
            'options' => json_encode($options),
            'training_phrase' => $training_phrase,
            'field_type' => $field_type
        ],
        ['id' => $id],
        ['%s', '%s', '%s', '%s'],
        ['%d']
    );

    if ($updated === false) {
        return false;
    }

    $relation_table = $this->getRelationTable();

    $this->wpdb->delete($relation_table, ['question_id' => $id], ['%d']);

    $category_table = $this->wpdb->prefix . 'question_categories';
    $category_id = $this->wpdb->get_var(
        $this->wpdb->prepare("SELECT id FROM {$category_table} WHERE title = %s", $category)
    );

    if (!$category_id) {
        $this->wpdb->insert(
            $category_table,
            ['title' => $category],
            ['%s']
        );

        $category_id = $this->wpdb->insert_id;
    }

    $this->wpdb->insert(
        $relation_table,
        [
            'question_id' => $id,
            'category_id' => $category_id
        ],
        ['%d', '%d']
    );

    return true;
}


    private function getRelationTable(): string
    {
        return $this->wpdb->prefix . 'question_category_relationships';
    }

    public function getQuestionsByCategory(string $category_title): array
    {
        $relation_table = $this->getRelationTable();
        $category_table = $this->wpdb->prefix . 'question_categories';

        $sql = "
        SELECT q.id, q.title, q.training_phrase, q.options, q.field_type
        FROM {$this->table} q
        INNER JOIN {$relation_table} r ON q.id = r.question_id
        INNER JOIN {$category_table} c ON r.category_id = c.id
        WHERE c.title = %s
    ";

        return $this->wpdb->get_results($this->wpdb->prepare($sql, $category_title), ARRAY_A);
    }
}
