<?php

class AssistantUsage
{

    // XXXXXXXXXXXXXXXX BANCO DE DADOS  XXXXXXXXXXXXXXX
    private $wpdb;
    private $table;

    // XXXXXXXXXXXXXXXX ENTIDADE  XXXXXXXXXXXXXXX
    private $total_tokens;
    private $total_prompt_tokens;
    private $total_completion_tokens;
    private $user_id;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->wpdb->prefix . 'assistant_usage';
        $this->user_id = get_current_user_id();
    }

    public function getTotalTokens()
    {
        return $this->total_tokens;
    }

    public function setTotalTokens($total_tokens)
    {
        $this->total_tokens = $total_tokens;
    }

    public function getTotalPromptTokens()
    {
        return $this->total_prompt_tokens;
    }

    public function setTotalPromptTokens($total_prompt_tokens)
    {
        $this->total_prompt_tokens = $total_prompt_tokens;
    }

    public function getTotalCompletionTokens()
    {
        return $this->total_completion_tokens;
    }

    public function setTotalCompletionTokens($total_completion_tokens)
    {
        $this->total_completion_tokens = $total_completion_tokens;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    // XXXXXXXXXXXXXXXX BANCO DE DADOS  XXXXXXXXXXXXXXX
    public static function createTable()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'assistant_usage';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            total_tokens int NOT NULL,
            total_prompt_tokens int NOT NULL,
            total_completion_tokens int NOT NULL,
            PRIMARY KEY  (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // XXXXXXXXXXXXXXXX BANCO DE DADOS ( COMUNICAÇÃO repositorio? )  XXXXXXXXXXXXXXX

    public function save()
    {
        $data = array(
            'total_tokens' => $this->total_tokens,
            'total_prompt_tokens' => $this->total_prompt_tokens,
            'total_completion_tokens' => $this->total_completion_tokens,
            'user_id' => $this->user_id
        );

        $format = array('%d', '%d', '%d', '%d');

        $this->wpdb->insert($this->table, $data, $format);

        if ($this->wpdb->last_error) {
            plugin_log("Erro no INSERT: " . $this->wpdb->last_error);
        }

        return $this;
    }

    public function update()
    {
        $data = array(
            'total_tokens' => $this->total_tokens,
            'total_prompt_tokens' => $this->total_prompt_tokens,
            'total_completion_tokens' => $this->total_completion_tokens
        );

        $where = array('user_id' => $this->user_id);
        $format = array('%d', '%d', '%d');
        $where_format = array('%d');

        $this->wpdb->update($this->table, $data, $where, $format, $where_format);
        return $this;
    }

    public function delete()
    {
        $where = array('user_id' => $this->user_id);
        $where_format = array('%d');

        $this->wpdb->delete($this->table, $where, $where_format);
        return $this;
    }

    public function load()
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE user_id = %d", $this->user_id);
        $result = $this->wpdb->get_row($query);

        if ($result) {
            $this->total_tokens = intval($result->total_tokens);
            $this->total_prompt_tokens = intval($result->total_prompt_tokens);
            $this->total_completion_tokens = intval($result->total_completion_tokens);
        } else {
            // Se não houver registro, inicializa os valores como 0
            $this->total_tokens = 0;
            $this->total_prompt_tokens = 0;
            $this->total_completion_tokens = 0;
        }

        return $this;
    }


    public function saveOrUpdate()
    {
        $query = $this->wpdb->prepare("SELECT COUNT(*) FROM $this->table WHERE user_id = %d", $this->user_id);
        $exists = $this->wpdb->get_var($query);

        if ($exists) {
            return $this->update();
        } else {
            return $this->save();
        }
    }


    // XXXXXXXXXXXXXXXX SERVIÇOS  XXXXXXXXXXXXXXX

}
