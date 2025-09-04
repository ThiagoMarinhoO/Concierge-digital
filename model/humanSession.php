<?php

class HumanSession
{
    private static $pdo;
    private static $host = DB_HOST;
    private static $dbname = DB_NAME;
    private static $user = DB_USER;
    private static $pass = DB_PASSWORD;

    private $id;
    private $remoteJid;
    private $instanceName;
    private $startedAt;
    private $endedAt;

    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->id           = $data['id'] ?? null;
            $this->remoteJid    = $data['remote_jid'] ?? null;
            $this->instanceName = $data['instance_name'] ?? null;
            $this->startedAt    = $data['started_at'] ?? null;
            $this->endedAt      = $data['ended_at'] ?? null;
        }
    }

    // --- Getters e Setters ---
    public function getId() { return $this->id; }
    public function getRemoteJid() { return $this->remoteJid; }
    public function getInstanceName() { return $this->instanceName; }
    public function getStartedAt() { return $this->startedAt; }
    public function getEndedAt() { return $this->endedAt; }

    public function setRemoteJid($remoteJid) { $this->remoteJid = $remoteJid; }
    public function setInstanceName($instanceName) { $this->instanceName = $instanceName; }

    // --- Conexão ---
    private static function connect()
    {
        if (!self::$pdo) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
            self::$pdo = new PDO($dsn, self::$user, self::$pass);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }

    // --- Criação/Atualização da Tabela ---
    public static function createOrUpdateTable()
    {
        $pdo = self::connect();

        $pdo->exec("CREATE TABLE IF NOT EXISTS human_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            remote_jid VARCHAR(255) NOT NULL,
            instance_name VARCHAR(255) NOT NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $expected_columns = [
            'id'            => 'INT',
            'remote_jid'    => 'VARCHAR(255)',
            'instance_name' => 'VARCHAR(255)',
            'started_at'    => 'DATETIME',
            'ended_at'      => 'DATETIME',
        ];

        $stmt = $pdo->query("SHOW COLUMNS FROM human_sessions");
        $existing_columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[$row['Field']] = strtoupper(preg_replace('/\(.*\)/', '', $row['Type']));
        }

        foreach ($expected_columns as $column => $expected_type) {
            $expected_type_upper = strtoupper($expected_type);
            if (!isset($existing_columns[$column])) {
                $pdo->exec("ALTER TABLE human_sessions ADD COLUMN $column $expected_type");
            } elseif ($existing_columns[$column] !== $expected_type_upper) {
                $pdo->exec("ALTER TABLE human_sessions MODIFY COLUMN $column $expected_type");
            }
        }
    }

    // --- Iniciar Sessão ---
    public static function start(string $remoteJid, string $instanceName): bool
    {
        $pdo = self::connect();

        $stmt = $pdo->prepare("
            INSERT INTO human_sessions (remote_jid, instance_name, started_at)
            VALUES (:remote_jid, :instance_name, NOW())
        ");

        return $stmt->execute([
            ':remote_jid'    => $remoteJid,
            ':instance_name' => $instanceName
        ]);
    }

    // --- Encerrar Sessão ---
    public static function end(string $remoteJid, string $instanceName): bool
    {
        $pdo = self::connect();

        $stmt = $pdo->prepare("
            UPDATE human_sessions
            SET ended_at = NOW()
            WHERE remote_jid = :remote_jid
              AND instance_name = :instance_name
              AND ended_at IS NULL
        ");

        return $stmt->execute([
            ':remote_jid'    => $remoteJid,
            ':instance_name' => $instanceName
        ]);
    }

    // --- Verificar se há sessão ativa ---
    public static function isActive(string $remoteJid, string $instanceName): bool
    {
        $pdo = self::connect();

        $stmt = $pdo->prepare("
            SELECT id FROM human_sessions
            WHERE remote_jid = :remote_jid
              AND instance_name = :instance_name
              AND ended_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            ':remote_jid'    => $remoteJid,
            ':instance_name' => $instanceName
        ]);

        return $stmt->fetchColumn() !== false;
    }

    // --- Buscar sessão ativa mais recente ---
    public static function getActiveSession(string $remoteJid, string $instanceName): ?self
    {
        $pdo = self::connect();

        $stmt = $pdo->prepare("
            SELECT * FROM human_sessions
            WHERE remote_jid = :remote_jid
              AND instance_name = :instance_name
              AND ended_at IS NULL
            ORDER BY started_at DESC
            LIMIT 1
        ");

        $stmt->execute([
            ':remote_jid'    => $remoteJid,
            ':instance_name' => $instanceName
        ]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new self($data) : null;
    }
}
