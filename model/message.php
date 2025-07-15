<?php

class Message
{
    private static $pdo;
    private static $host = DB_HOST;
    private static $dbname = DB_NAME;
    private static $user = DB_USER;
    private static $pass = DB_PASSWORD;

    private ?int $id = null;
    private string $message;
    private ?string $name = null;
    private ?string $phone = null;
    private ?string $threadId = null;
    private int $fromMe = 0;
    private string $assistant_id;
    private DateTime $dateTime;

    // === Getters e Setters ===
    public function getId(): ?int
    {
        return $this->id;
    }
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
    public function getMessage(): string
    {
        return $this->message;
    }
    public function setName(?string $name): void
    {
        $this->name = $name;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }
    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setThreadId(?string $threadId): void
    {
        $this->threadId = $threadId;
    }
    public function getThreadId(): ?string
    {
        return $this->threadId;
    }
    public function setFromMe(int $fromMe): void
    {
        $this->fromMe = $fromMe;
    }
    public function getFromMe(): int
    {
        return $this->fromMe;
    }
    public function setAssistantId(string $assistant_id): void
    {
        $this->assistant_id = $assistant_id;
    }
    public function getAssistantId(): string
    {
        return $this->assistant_id;
    }
    public function setDateTime(DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    // === Conexão ===
    private static function connect()
    {
        if (!self::$pdo) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
            self::$pdo = new PDO($dsn, self::$user, self::$pass);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }

    // === Criação da Tabela ===
    public static function createOrUpdateTable(): void
    {
        $pdo = self::connect();

        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            name VARCHAR(255),
            phone VARCHAR(255),
            thread_id VARCHAR(255),
            from_me TINYINT(1) NOT NULL DEFAULT 0,
            assistant_id VARCHAR(255) NOT NULL,
            date_time DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // === Salvar (Insert ou Update) ===
    public function save(): bool
    {
        $pdo = self::connect();

        if ($this->id !== null) {
            $stmt = $pdo->prepare("UPDATE messages SET
                message = ?, name = ?, phone = ?, thread_id = ?, from_me = ?, assistant_id = ?, date_time = ?
                WHERE id = ?");
            return $stmt->execute([
                $this->message,
                $this->name,
                $this->phone,
                $this->threadId,
                $this->fromMe,
                $this->assistant_id,
                $this->dateTime->format('Y-m-d H:i:s'),
                $this->id
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO messages
                (message, name, phone, thread_id, from_me, assistant_id, date_time)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([
                $this->message,
                $this->name,
                $this->phone,
                $this->threadId,
                $this->fromMe,
                $this->assistant_id,
                $this->dateTime->format('Y-m-d H:i:s')
            ]);
            if ($success) {
                $this->id = (int)$pdo->lastInsertId();
            }
            return $success;
        }
    }

    // === Buscar por ID ===
    public static function findById(int $id): ?self
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return self::mapRowToMessage($row);
        }

        return null;
    }

    // === Buscar por múltiplos critérios ===
    public static function findBy(string $assistantId, ?string $name, ?string $phone, ?string $threadId): array
    {
        $pdo = self::connect();

        $query = "SELECT * FROM messages WHERE assistant_id = ?";
        $params = [$assistantId];

        if ($name !== null) {
            $query .= " AND name = ?";
            $params[] = $name;
        }
        if ($phone !== null) {
            $query .= " AND phone = ?";
            $params[] = $phone;
        }
        if ($threadId !== null) {
            $query .= " AND thread_id = ?";
            $params[] = $threadId;
        }

        $query .= " ORDER BY date_time ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = self::mapRowToMessage($row);
        }

        return $messages;
    }

    // === Buscar todas as mensagens ===
    public static function findAll(): array
    {
        $pdo = self::connect();
        $stmt = $pdo->query("SELECT * FROM messages ORDER BY date_time ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = self::mapRowToMessage($row);
        }

        return $messages;
    }

    public static function findAllWithFilters(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $pdo = self::connect();

        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['thread_id'])) {
            $where .= " AND thread_id = ?";
            $params[] = $filters['thread_id'];
        }
        if (!empty($filters['assistant_id'])) {
            $where .= " AND assistant_id = ?";
            $params[] = $filters['assistant_id'];
        }
        if (!empty($filters['name'])) {
            $where .= " AND name = ?";
            $params[] = $filters['name'];
        }
        if (!empty($filters['phone'])) {
            $where .= " AND phone = ?";
            $params[] = $filters['phone'];
        }
        if (!empty($filters['message'])) {
            $where .= " AND message LIKE ?";
            $params[] = '%' . $filters['message'] . '%';
        }

        // Obter total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM messages $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Sanitizar valores
        $limit = (int)$limit;
        $offset = (int)$offset;

        // Montar query com LIMIT e OFFSET interpolados diretamente
        $query = "SELECT * FROM messages $where ORDER BY date_time ASC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $messages = array_map([self::class, 'mapRowToMessage'], $rows);

        return [
            'total' => $total,
            'messages' => $messages,
        ];
    }



    // === Auxiliar para mapear linha do banco para objeto ===
    private static function mapRowToMessage(array $row): self
    {
        $msg = new self();
        $msg->id = (int)$row['id'];
        $msg->message = $row['message'];
        $msg->name = $row['name'];
        $msg->phone = $row['phone'];
        $msg->threadId = $row['thread_id'];
        $msg->fromMe = (int)$row['from_me'];
        $msg->assistant_id = $row['assistant_id'];
        $msg->dateTime = new DateTime($row['date_time']);
        return $msg;
    }
}
