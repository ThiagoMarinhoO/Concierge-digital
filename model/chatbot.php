<?php

use Smalot\PdfParser\Parser;
class Chatbot
{
    protected $api_key;

    private $endpoint;

    private $wpdb;

    private $table;

    private $user_id;

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
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        chatbot_name TEXT NOT NULL,
        chatbot_options TEXT NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function createChatbot($chatbot_name, $chatbot_options)
    {
        $result = $this->wpdb->insert(
            $this->table,
            [
                'chatbot_name' => $chatbot_name,
                'chatbot_options' => json_encode($chatbot_options),
                'user_id' => $this->user_id,
            ],
            [
                '%s',
                '%s',
                '%d',
            ]
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
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = $id AND user_id = $user_id", $id, $this->user_id);

        $chatbot = $this->wpdb->get_row($sql, ARRAY_A);

        if ($chatbot) {
            // Decodificar as opções do chatbot
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

        if (empty($currentChatbot)) {
            return json_encode([
                'error' => true,
                'message' => 'Dados do concierge ausentes ou inválidos. Não foi possível gerar a mensagem.'
            ]);
        }

        $chatbot_trainning = array();

        foreach ($currentChatbot['chatbot_options'] as $option) {
            $training_phrase = $option['training_phrase'];
            $resposta = $option['resposta'];

            if (!empty($option['file_url'])) {
                $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $option['file_url']);

                if (file_exists($file_path)) {
                    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

                    if ($file_extension == 'pdf') {
                        $parser = new Parser();
                        $pdf = $parser->parseFile($file_path);
                        $file_content = $pdf->getText();
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

        $chatbot_trainning[] = 'seu nome é ' . $currentChatbot['chatbot_name'];

        $training_context = implode("\n", $chatbot_trainning);

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
            'model' => 'gpt-4o'
        ];

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
            return $arrResult['choices'][0]['message']['content'];
        }

        return json_encode([
            'error' => true,
            'message' => 'Erro ao processar a resposta da API.'
        ]);
    }

}
