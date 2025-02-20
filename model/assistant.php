<?php
class Assistant
{
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
        $this->table = $this->wpdb->prefix . 'assistants';
        $this->user_id = get_current_user_id();
    }

    public function createTable()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table} (
            id VARCHAR(55) PRIMARY KEY,
            name TEXT NOT NULL,
            welcome_message TEXT NOT NULL,
            instructions TEXT NOT NULL,
            image TEXT NOT NULL,
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
            'name' => $this->name,
            'welcome_message' => $this->welcome_message,
            'instructions' => $this->instructions,
            'image' => $this->image,
            'user_id' => $this->user_id,
            'created_at' => current_time('mysql')
        ];

        $format = ['%s', '%s', '%s', '%s', '%s', '%d', '%s'];

        $this->wpdb->insert($this->table, $data, $format);
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