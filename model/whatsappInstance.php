<?php

class WhatsappInstance {

    private static $pdo;

    // Dados de conexão — você pode ajustar para usar .env ou constantes
    private static $host = DB_HOST;
    private static $dbname = DB_NAME;
    private static $user = DB_USER;
    private static $pass = DB_PASSWORD;

    private $id;
    private $instanceId;
    private $instanceName;
    private $userId;
    private $assistant;

    // Conexão PDO automática
    private static function connect() {
        if (!self::$pdo) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
            self::$pdo = new PDO($dsn, self::$user, self::$pass);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }

    // Getters e Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getInstanceId() { return $this->instanceId; }
    public function setInstanceId($instanceId) { $this->instanceId = $instanceId; }

    public function getInstanceName() { return $this->instanceName; }
    public function setInstanceName($instanceName) { $this->instanceName = $instanceName; }

    public function getUserId() { return $this->userId; }
    public function setUserId($userId) { $this->userId = $userId; }

    public function getAssistant() { return $this->assistant; }
    public function setAssistant($assistant) { $this->assistant = $assistant; }

    //
    // Banco de dados
    //

    public static function createTable() {
        $pdo = self::connect();
        $sql = "CREATE TABLE IF NOT EXISTS whatsapp_instances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            instance_id VARCHAR(255) NOT NULL,
            instance_name VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            assistant VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        return $pdo->exec($sql);
    }

    public static function findById($id) {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_instances WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $instance = new self();
            $instance->setId($row['id']);
            $instance->setInstanceId($row['instance_id']);
            $instance->setInstanceName($row['instance_name']);
            $instance->setUserId($row['user_id']);
            $instance->setAssistant($row['assistant']);
            return $instance;
        }
        return null;
    }

    public static function findByUserId($userId) {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_instances WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $instance = new self();
            $instance->setId($row['id']);
            $instance->setInstanceId($row['instance_id']);
            $instance->setInstanceName($row['instance_name']);
            $instance->setUserId($row['user_id']);
            $instance->setAssistant($row['assistant']);
            return $instance;
        }
        return null;
    }

    public static function findByInstanceName($instanceName) {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_instances WHERE instance_name = ?");
        $stmt->execute([$instanceName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $instance = new self();
            $instance->setId($row['id']);
            $instance->setInstanceId($row['instance_id']);
            $instance->setInstanceName($row['instance_name']);
            $instance->setUserId($row['user_id']);
            $instance->setAssistant($row['assistant']);
            return $instance;
        }
        return null;
    }

    public static function findByAssistant($assistant) {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_instances WHERE assistant = ? LIMIT 1");
        $stmt->execute([$assistant]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $instance = new self();
            $instance->setId($row['id']);
            $instance->setInstanceId($row['instance_id']);
            $instance->setInstanceName($row['instance_name']);
            $instance->setUserId($row['user_id']);
            $instance->setAssistant($row['assistant']);
            return $instance;
        }

        return null;
    }

    public static function findAll() {
        $pdo = self::connect();
        $stmt = $pdo->query("SELECT * FROM whatsapp_instances");
        $instances = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $instance = new self();
            $instance->setId($row['id']);
            $instance->setInstanceId($row['instance_id']);
            $instance->setInstanceName($row['instance_name']);
            $instance->setUserId($row['user_id']);
            $instance->setAssistant($row['assistant']);
            $instances[] = $instance;
        }

        return $instances;
    }

    public static function findByInstanceId($instanceId) {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_instances WHERE instance_id = ?");
        $stmt->execute([$instanceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $instance = new self();
            $instance->setId($row['id']);
            $instance->setInstanceId($row['instance_id']);
            $instance->setInstanceName($row['instance_name']);
            $instance->setUserId($row['user_id']);
            $instance->setAssistant($row['assistant']);
            return $instance;
        }
        return null;
    }

    public function save() {
        $pdo = self::connect();

        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE whatsapp_instances SET instance_id = ?, instance_name = ?, user_id = ?, assistant = ? WHERE id = ?");
            return $stmt->execute([
                $this->instanceId,
                $this->instanceName,
                $this->userId,
                $this->assistant,
                $this->id
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO whatsapp_instances (instance_id, instance_name, user_id, assistant) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([
                $this->instanceId,
                $this->instanceName,
                $this->userId,
                $this->assistant
            ]);
            if ($result) {
                $this->id = $pdo->lastInsertId();
            }
            return $result;
        }
    }

    public function delete() {
        if ($this->id) {
            $pdo = self::connect();
            $stmt = $pdo->prepare("DELETE FROM whatsapp_instances WHERE id = ?");
            return $stmt->execute([$this->id]);
        }
        return false;
    }
}
