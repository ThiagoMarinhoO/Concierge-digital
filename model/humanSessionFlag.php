<?php

class HumanSessionFlag
{
    private static $pdo;
    private static $host = DB_HOST;
    private static $dbname = DB_NAME;
    private static $user = DB_USER;
    private static $pass = DB_PASSWORD;

    private string $id;
    private string $remoteJid;
    private string $instance;
    private string $createdAt;
    private bool $closed;

    public function __construct(string $remoteJid, string $instance)
    {
        $this->id = self::generateUuid();
        $this->remoteJid = $remoteJid;
        $this->instance = $instance;
        $this->createdAt = date('Y-m-d H:i:s');
    }

    // --- Getters ---
    public function getId(): string
    {
        return $this->id;
    }

    public function getRemoteJid(): string
    {
        return $this->remoteJid;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    // --- Setters ---
    public function setRemoteJid(string $remoteJid): void
    {
        $this->remoteJid = $remoteJid;
    }

    public function setInstance(string $instance): void
    {
        $this->instance = $instance;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    // --- Utilitário para gerar UUID (sem pacote externo) ---
    public static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    // --- Conexão com PDO (igual à classe HumanSession) ---
    private static function connect()
    {
        if (!self::$pdo) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
            self::$pdo = new PDO($dsn, self::$user, self::$pass);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }

    // --- Cria a tabela, se não existir ---
    public static function createTable(): void
    {
        $pdo = self::connect();

        $sql = "CREATE TABLE IF NOT EXISTS human_flags (
            id CHAR(36) PRIMARY KEY,
            remote_jid VARCHAR(255) NOT NULL,
            instance VARCHAR(255) NOT NULL,
            closed BOOLEAN DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY unique_flag (remote_jid, instance)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $pdo->exec($sql);
    }

    // --- Create ou Update (por remote_jid + instance) ---
    public function createOrUpdate(): bool
    {
        $pdo = self::connect();

        $sql = "
            INSERT INTO human_flags (id, remote_jid, instance, created_at)
            VALUES (:id, :remote_jid, :instance, :created_at)
            ON DUPLICATE KEY UPDATE
                created_at = VALUES(created_at)
        ";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $this->id,
            ':remote_jid' => $this->remoteJid,
            ':instance' => $this->instance,
            ':created_at' => $this->createdAt
        ]);
    }

    // --- Opcional: remover flag (quando operador assumir) ---
    public static function clear(string $remoteJid, string $instance): bool
    {
        $pdo = self::connect();

        $stmt = $pdo->prepare("
            DELETE FROM human_flags
            WHERE remote_jid = :remote_jid AND instance = :instance
        ");

        return $stmt->execute([
            ':remote_jid' => $remoteJid,
            ':instance' => $instance
        ]);
    }

    // --- Opcional: listar flags recentes (para notificar no frontend) ---
    public static function listRecent(): array
    {
        $pdo = self::connect();

        $stmt = $pdo->query("
            SELECT * FROM human_flags
            ORDER BY created_at DESC
        ");

        return $stmt->fetchAll();
    }

    public static function list($instanceName): array
    {
        $pdo = self::connect();

        $stmt = $pdo->prepare("
            SELECT * FROM human_flags
            WHERE instance = :instance
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([':instance' => $instanceName]);

        return $stmt->fetchAll();
    }
}
