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
            response TEXT DEFAULT NULL,
            prioridade INT DEFAULT NULL,
            required_field TEXT DEFAULT NULL,
            objective ENUM('nome', 'boas-vindas', 'nenhuma') DEFAULT 'nenhuma',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function getAllQuestions(): array
    {
        $relation_table = $this->getRelationTable();
        $category_table = $this->wpdb->prefix . 'question_categories';

        $sql = "
        SELECT q.id, q.title, q.options, q.training_phrase, q.field_type, q.response, q.prioridade, q.required_field , q.objective , q.created_at,
               GROUP_CONCAT(c.title) AS categories
        FROM {$this->table} q
        LEFT JOIN {$relation_table} r ON q.id = r.question_id
        LEFT JOIN {$category_table} c ON r.category_id = c.id
        GROUP BY q.id
        ";

        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function getAllCategories(): array
    {
        $category_table = $this->wpdb->prefix . 'question_categories';

        $sql = "SELECT id, title , video_url
            FROM {$category_table}
            WHERE display_frontend   = 1 
            ORDER BY position ASC";

        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function addQuestion(string $title = null, string $training_phrase = null, array $options = null, array $categories, string $field_type = null, ?string $response = null, string $required_field = 'Sim' , string $objective = 'nenhuma'): int
    {
        $this->wpdb->insert(
            $this->table,
            [
                'title' => $title,
                'options' => json_encode($options),
                'training_phrase' => $training_phrase,
                'field_type' => $field_type,
                'response' => $response,
                'required_field' => $required_field,
                'objective' => $objective
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
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

    public function addFixedQuestion(string $response): int
    {
        // ID fixo ou busca pela categoria "Regras Gerais"
        $category_table = $this->wpdb->prefix . 'question_categories';
        $relation_table = $this->getRelationTable();

        // Buscar o ID da categoria
        $category_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$category_table} WHERE title = %s",
            'Regras Gerais'
        ));

        if (!$category_id) {
            // Caso a categoria não exista, lance um erro
            throw new Exception('A categoria "Regras Gerais" não foi encontrada.');
        }

        // Adicionar a pergunta com apenas a resposta
        $this->wpdb->insert(
            $this->table,
            [
                'title' => '',
                'training_phrase' => '',
                'field_type' => '', // Define o tipo de campo como texto por padrão
                'response' => $response
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
            // Criar a relação entre a pergunta e a categoria
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

        return $question_id;
    }


    public function deleteQuestion($id)
    {
        $relation_table = $this->getRelationTable();
        $this->wpdb->delete($relation_table, ['question_id' => $id], ['%d']);
        $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    public function updateQuestion($id, $title, $training_phrase, $options, $category, $field_type, $question_response, $required_field, $prioridade): bool
    {
        $updated = $this->wpdb->update(
            $this->table,
            [
                'title' => $title,
                'options' => json_encode($options),
                'training_phrase' => $training_phrase,
                'field_type' => $field_type,
                'response' => $question_response,
                'required_field' => $required_field,
                'prioridade' => $prioridade
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d'],
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
        SELECT q.id, q.title, q.training_phrase, q.options, q.field_type, q.response, q.prioridade, q.required_field, q.objective
        FROM {$this->table} q
        INNER JOIN {$relation_table} r ON q.id = r.question_id
        INNER JOIN {$category_table} c ON r.category_id = c.id
        WHERE c.title = %s
        ORDER BY q.prioridade ASC
    ";

        return $this->wpdb->get_results($this->wpdb->prepare($sql, $category_title), ARRAY_A);
    }
}
