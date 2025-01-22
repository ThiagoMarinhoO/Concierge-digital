<?php
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
        $this->api_key = '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';
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

    public function getChatbotById($id)
    {
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d AND user_id = %d", $id, $this->user_id);

        $chatbot = $this->wpdb->get_row($sql, ARRAY_A);

        if ($chatbot) {
            // Decodificar as opções do chatbot
            $chatbot['chatbot_options'] = json_decode($chatbot['chatbot_options'], true);
        }

        return $chatbot;
    }

    public  function deleteChatbot($id)
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

    public function enviarMensagem(string $mensagem, $chatbot_id)
    {
        $chatbot = new Chatbot();

        $currentChatbot = $chatbot->getChatbotById($chatbot_id);

        if (empty($currentChatbot)) {
            // error_log('Erro: Dados do concierge ausentes ou inválidos.');
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
                    $file_content = file_get_contents($file_path);

                    if (!empty($file_content)) {
                        // Converte o conteúdo para UTF-8 e remove caracteres inválidos
                        $file_content = mb_convert_encoding($file_content, 'UTF-8', 'UTF-8');
                        $file_content = preg_replace('/[^\x20-\x7E\n\r\t]/u', '', $file_content); // Remove caracteres não imprimíveis
                    }

                    $sanitized_file_content = substr($file_content, 0, 5000);

                    // Adicionar o conteúdo do arquivo ao treinamento
                    $chatbot_trainning[] = $training_phrase . ' ' . $sanitized_file_content;
                } else {
                    // error_log("Erro: Arquivo não encontrado em $file_path.");
                }
            } else {
                $chatbot_trainning[] = $training_phrase . ' ' . $resposta;
            }
        }

        $chatbot_trainning[] = 'seu nome é ' . $currentChatbot['chatbot_name'];

        // Loga os dados do concierge para debug
        // error_log('---- chatbot_trainning ----');
        // error_log(print_r($chatbot_trainning, true));

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

        function sanitize_for_json($input) {
            if (is_array($input)) {
                return array_map('sanitize_for_json', $input);
            } elseif (is_string($input)) {
                // Converte para UTF-8 e remove caracteres inválidos
                return mb_convert_encoding($input, 'UTF-8', 'UTF-8');
            }
            return $input;
        }
        
        // Sanitizar o array $data antes de codificar
        $data = sanitize_for_json($data);

        $json_data = json_encode($data, JSON_PRETTY_PRINT);

        // if (json_last_error() !== JSON_ERROR_NONE) {
        //     error_log('Erro ao codificar JSON: ' . json_last_error_msg());
        //     throw new Exception('Erro ao preparar o JSON para a API.');
        // }

        // error_log('JSON Enviado: ' . $json_data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        // Check for errors in the API response
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Error sending the message: ' . $error);
        }

        curl_close($ch);

        $arrResult = json_decode($response, true);
        $resultMessage = $arrResult["choices"][0]["message"]["content"];

        // error_log('------ mensagem do sistema -------');
        // error_log(print_r($arrResult, true));

        return $resultMessage;
    }
}
