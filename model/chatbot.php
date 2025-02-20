<?php

use Smalot\PdfParser\Parser;

class Chatbot
{
    protected $api_key;

    private $endpoint;

    private $wpdb;

    private $table;

    private $id;
    private $name;
    private $welcome_message;
    private $instructions;
    private $user_id;
    private $image;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->wpdb->prefix . 'chatbot';
        $this->api_key = '';
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';
        $this->user_id = get_current_user_id();
    }

    public function createTable()
    {
        $charset_collate = $this->wpdb->get_charset_collate();
        // $users_table = $this->wpdb->prefix . 'users';

        $sql = "CREATE TABLE {$this->table} (
            id VARCHAR(255) PRIMARY KEY,
            chatbot_name TEXT NOT NULL,
            chatbot_welcome_message TEXT NOT NULL,
            chatbot_options TEXT NOT NULL,
            chatbot_image TEXT,
            user_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function save()
    {
        $data = [
            'id' => $this->id,
            'chatbot_name' => $this->name,
            'chatbot_welcome_message' => $this->welcome_message,
            'chatbot_options' => $this->instructions,
            'chatbot_image' => $this->image,
            'user_id' => $this->user_id,
            'created_at' => current_time('mysql')
        ];

        $format = ['%s', '%s', '%s', '%s', '%s', '%d', '%s'];

        $this->wpdb->insert($this->table, $data, $format);
    }
    public function createChatbot($chatbot_name, $chatbot_options, $chatbot_image = null, $chatbot_welcome_message = null)
    {
        $data = [
            'chatbot_name' => $chatbot_name,
            'chatbot_welcome_message' => $chatbot_welcome_message,
            'chatbot_options' => json_encode($chatbot_options),
            'user_id' => $this->user_id,
        ];

        $formats = [
            '%s', // chatbot_name
            '%s', // chatbot_welcome_message
            '%s', // chatbot_options
            '%d', // user_id
        ];


        if ($chatbot_image !== null) {
            $data['chatbot_image'] = $chatbot_image;
            $formats[] = '%s';
        }

        $result = $this->wpdb->insert($this->table, $data, $formats);

        return $result !== false;
    }

    public function updateChatbot($id, $chatbot_name = null, $chatbot_options = null, $chatbot_image = null, $chatbot_welcome_message = null, $user_id)
    {
        if (empty($id)) {
            return false;
        }

        $data = [];
        $formats = [];

        if ($chatbot_name !== null) {
            $data['chatbot_name'] = $chatbot_name;
            $formats[] = '%s';
        }

        if ($chatbot_options !== null) {
            $current_options = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT chatbot_options FROM {$this->table} WHERE id = %d AND user_id = %d",
                    $id,
                    $user_id
                )
            );
            $current_options = json_decode($current_options, true);
            $new_options = array_replace_recursive((array) $current_options, $chatbot_options);
            $data['chatbot_options'] = json_encode($new_options);
            $formats[] = '%s';
        }

        if ($chatbot_image !== null && !empty($chatbot_image)) {
            $data['chatbot_image'] = $chatbot_image;
            $formats[] = '%s';
        }

        if ($chatbot_welcome_message !== null) {
            $data['chatbot_welcome_message'] = $chatbot_welcome_message;
            $formats[] = '%s';
        }

        if (empty($data)) {
            return false;
        }

        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $id, 'user_id' => $user_id],
            $formats,
            ['%d', '%d']
        );

        return $result !== false;
    }

    public function getAllChatbots()
    {
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE user_id = %d", $this->user_id);
        return $this->wpdb->get_results($sql);
    }

    public function getChatbotById($id, $user_id)
{
    
    $sql = $this->wpdb->prepare(
        "SELECT * FROM {$this->table} WHERE id = %d AND user_id = %d",
        $id,
        $user_id
    );

    $chatbot = $this->wpdb->get_row($sql, ARRAY_A);

    if (!$chatbot) {
        global $wpdb;
        plugin_log("Nenhum chatbot encontrado. Erro: " . $wpdb->last_error);
    } else {
        $chatbot['chatbot_options'] = json_decode($chatbot['chatbot_options'], true);
    }

    return $chatbot;
}


    public function deleteChatbot($id)
    {
        $sql = $this->wpdb->prepare("DELETE FROM {$this->table} WHERE id = %d AND user_id = %d", $id, $this->user_id);

        return $this->wpdb->query($sql);
    }

    public function getChatbotByName($chatbot_name)
    {
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE chatbot_name = %d", $chatbot_name);

        return $this->wpdb->get_results($sql);
    }

    public function getChatbotIdByUser($user_id)
    {
        // Verifica se a tabela existe
        $table_exists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->table
            )
        );

        if (!$table_exists) {
            return false;
        }

        // Consulta o ID do chatbot relacionado ao usuário
        $sql = $this->wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE user_id = %d LIMIT 1",
            $user_id
        );

        $chatbot_id = $this->wpdb->get_var($sql);

        return $chatbot_id ? $chatbot_id : false;
    }

    public function userHasChatbot($user_id)
    {
        // Verifica se a tabela existe
        $table_exists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->table
            )
        );

        if (!$table_exists) {
            // Retorna falso se a tabela não existir
            return false;
        }

        // Consulta se há chatbots para o usuário
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $user_id
        );
        $count = $this->wpdb->get_var($sql);

        return $count > 0;
    }

    public function enviarMensagem(string $mensagem, $chatbot_id, $user_id)
    {
        $chatbot = new Chatbot();
        $currentChatbot = $chatbot->getChatbotById($chatbot_id, $user_id);

        $question = new Question();
        $chatbotFixedQuestions = $question->getQuestionsByCategory('Regras Gerais');

        if (empty($currentChatbot)) {
            return json_encode([
                'error' => true,
                'message' => 'Dados do concierge ausentes ou inválidos. Não foi possível gerar a mensagem.'
            ]);
        }

        $chatbot_trainning = [];

        foreach ($currentChatbot['chatbot_options'] as $option) {
            $training_phrase = $option['training_phrase'];
            $resposta = $option['resposta'];

            if ($option['field_type'] == 'file') {
                $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $resposta);

                if (file_exists($file_path)) {
                    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

                    if ($file_extension == 'pdf') {
                        $parser = new Parser();
                        $pdf = $parser->parseFile($file_path);
                        $file_content = $pdf->getText();
                        // plugin_log(print_r($file_content , true));
                    } elseif (in_array($file_extension, ['mp3', 'wav', 'm4a', 'ogg'])) {
                        $file_content = $chatbot->transcribe_audio_with_whisper($file_path);
                        // plugin_log(print_r($file_content , true));
                    } else {
                        $file_content = file_get_contents($file_path);
                    }

                    if (!empty($file_content)) {
                        $file_content = mb_convert_encoding($file_content, 'UTF-8', 'UTF-8');
                        $file_content = preg_replace('/[^\x20-\x7E\n\r\t]/u', '', $file_content);
                    }

                    $sanitized_file_content = substr($file_content, 0, 5000);
                    $chatbot_trainning[] = $training_phrase . ' ' . $sanitized_file_content;
                }
            } else {
                $chatbot_trainning[] = $training_phrase . ' ' . $resposta;
            }
        }

        foreach ($chatbotFixedQuestions as $question) {
            $chatbot_trainning[] = $question['response'];
        }

        $chatbot_trainning[] = 'seu nome é ' . $currentChatbot['chatbot_name'];

        $training_context = implode("\n", $chatbot_trainning);

        $training_context = $chatbot->truncate_to_token_limit($training_context, 120000);

        $data = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $training_context
                ],
                [
                    'role' => 'user',
                    'content' => $mensagem
                ],
            ],
            'model' => 'gpt-4',
            'temperature' => 0.5
        ];

        // plugin_log(print_r($data, true));

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => json_encode($data),
                'ignore_errors' => true,
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($this->endpoint, false, $context);

        if ($response === false) {
            throw new Exception('Erro ao realizar a solicitação.');
        }

        $arrResult = json_decode($response, true);

        if (isset($arrResult['choices'][0]['message']['content'])) {
            $resultMessage = $arrResult['choices'][0]['message']['content'];

            $chatbot_image = $currentChatbot['chatbot_image'];

            $result = [
                'message' => $resultMessage,
                'image' => $chatbot_image,
            ];

            // plugin_log(print_r($result, true));

            return json_encode($result);
        }

        return json_encode([
            'error' => true,
            'message' => 'Erro ao processar a resposta da API.'
        ]);
    }

    /**
     * Trunca o contexto ao limite de tokens permitido.
     */
    private function truncate_to_token_limit($context, $max_tokens)
    {
        $words = explode(' ', $context);
        if (count($words) > $max_tokens) {
            return implode(' ', array_slice($words, 0, $max_tokens));
        }
        return $context;
    }

    public function transcribe_audio_with_whisper($file_path)
    {
        $url = 'https://api.openai.com/v1/audio/transcriptions';
        $boundary = uniqid();
        $delimiter = '--------------------------' . $boundary;

        $file_content = file_get_contents($file_path);
        $file_name = basename($file_path);
        $file_mime = mime_content_type($file_path); // Detecta o tipo correto do arquivo

        $file_data = "--$delimiter\r\n" .
            "Content-Disposition: form-data; name=\"file\"; filename=\"$file_name\"\r\n" .
            "Content-Type: $file_mime\r\n\r\n" .
            $file_content . "\r\n" .
            "--$delimiter\r\n" .
            "Content-Disposition: form-data; name=\"model\"\r\n\r\n" .
            "whisper-1\r\n" .
            "--$delimiter--\r\n";

        $headers = [
            "Authorization: Bearer " . $this->api_key,
            "Content-Type: multipart/form-data; boundary=$delimiter",
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $file_data,
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            plugin_log('Erro na solicitação ao Whisper API');
        }

        $result = json_decode($response, true);


        return $result['text'] ?? '';
    }


    
    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getWelcomeMessage()
    {
        return $this->welcome_message;
    }

    public function getInstructions()
    {
        return $this->instructions;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getImage()
    {
        return $this->image;
    }

    // Setters
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setWelcomeMessage($welcome_message)
    {
        $this->welcome_message = $welcome_message;
    }

    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }
}