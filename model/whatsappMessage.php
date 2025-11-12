<?php
class WhatsappMessage
{
    private static $pdo;
    private static $host = DB_HOST;
    private static $dbname = DB_NAME;
    private static $user = DB_USER;
    private static $pass = DB_PASSWORD;

    public string $messageId;
    public string $remoteJid;
    public string $instanceName;
    public string $message;
    public ?string $pushName = null;
    public ?string $threadId = null;
    public int $fromMe = 0;
    public DateTime $dateTime;

    private static function connect()
    {
        if (!self::$pdo) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
            self::$pdo = new PDO($dsn, self::$user, self::$pass);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }

    public static function createOrUpdateTable()
    {
        $pdo = self::connect();

        $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_messages (
            message_id VARCHAR(255) PRIMARY KEY,
            remote_jid VARCHAR(255) NOT NULL,
            instance_name VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            push_name VARCHAR(255),
            thread_id VARCHAR(255),
            from_me TINYINT(1) NOT NULL DEFAULT 0,
            date_time DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $expected_columns = [
            'message_id'    => 'VARCHAR(255)',
            'remote_jid'    => 'VARCHAR(255)',
            'instance_name' => 'VARCHAR(255)',
            'message'       => 'TEXT',
            'push_name'     => 'VARCHAR(255)',
            'thread_id'     => 'VARCHAR(255)',
            'from_me'       => 'TINYINT(1)',
            'date_time'     => 'DATETIME',
        ];

        $stmt = $pdo->query("SHOW COLUMNS FROM whatsapp_messages");
        $existing_columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[$row['Field']] = strtoupper(preg_replace('/\(.*\)/', '', $row['Type']));
        }

        foreach ($expected_columns as $column => $expected_type) {
            $expected_type_upper = strtoupper($expected_type);
            if (!isset($existing_columns[$column])) {
                $pdo->exec("ALTER TABLE whatsapp_messages ADD COLUMN $column $expected_type");
            } elseif ($existing_columns[$column] !== $expected_type_upper) {
                $pdo->exec("ALTER TABLE whatsapp_messages MODIFY COLUMN $column $expected_type");
            }
        }
    }

    public function save()
    {
        $pdo = self::connect();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM whatsapp_messages WHERE message_id = ?");
        $stmt->execute([$this->messageId]);
        $exists = $stmt->fetchColumn() > 0;

        error_log('saving message');

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE whatsapp_messages 
                SET remote_jid = ?, instance_name = ?, message = ?, push_name = ?, thread_id = ?, from_me = ?, date_time = ? 
                WHERE message_id = ?");

            // error_log(print_r([
            //     'from_me' => $this->fromMe,
            //     'tipo' => gettype($this->fromMe)
            // ], true));

            // die();

            return $stmt->execute([
                $this->remoteJid,
                $this->instanceName,
                $this->message,
                $this->pushName,
                $this->threadId,
                $this->fromMe,
                $this->dateTime->format('Y-m-d H:i:s'),
                $this->messageId
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO whatsapp_messages 
                (message_id, remote_jid, instance_name, message, push_name, thread_id, from_me, date_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $this->messageId,
                $this->remoteJid,
                $this->instanceName,
                $this->message,
                $this->pushName,
                $this->threadId,
                $this->fromMe,
                $this->dateTime->format('Y-m-d H:i:s')
            ]);
        }
    }

    public static function findById(string $messageId): ?self
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_messages WHERE message_id = ?");
        $stmt->execute([$messageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $msg = new self();
            $msg->messageId = $row['message_id'];
            $msg->remoteJid = $row['remote_jid'];
            $msg->instanceName = $row['instance_name'];
            $msg->message = $row['message'];
            $msg->pushName = $row['push_name'];
            $msg->threadId = $row['thread_id'];
            $msg->fromMe = (bool)$row['from_me'];
            $msg->dateTime = new DateTime($row['date_time']);
            return $msg;
        }
        return null;
    }

    public static function findByRemoteJid(string $remoteJid): array
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_messages WHERE remote_jid = ? ORDER BY date_time DESC");
        $stmt->execute([$remoteJid]);
        $messages = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $msg = new self();
            $msg->messageId = $row['message_id'];
            $msg->remoteJid = $row['remote_jid'];
            $msg->instanceName = $row['instance_name'];
            $msg->message = $row['message'];
            $msg->pushName = $row['push_name'];
            $msg->threadId = $row['thread_id'];
            $msg->fromMe = (bool)$row['from_me'];
            $msg->dateTime = new DateTime($row['date_time']);
            $messages[] = $msg;
        }

        return $messages;
    }

    public static function findAll(): array
    {
        $pdo = self::connect();
        $stmt = $pdo->query("SELECT * FROM whatsapp_messages ORDER BY date_time DESC");
        $messages = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $msg = new self();
            $msg->messageId = $row['message_id'];
            $msg->remoteJid = $row['remote_jid'];
            $msg->instanceName = $row['instance_name'];
            $msg->message = $row['message'];
            $msg->pushName = $row['push_name'];
            $msg->threadId = $row['thread_id'];
            $msg->fromMe = (bool)$row['from_me'];
            $msg->dateTime = new DateTime($row['date_time']);
            $messages[] = $msg;
        }

        return $messages;
    }

    public function delete(): bool
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare("DELETE FROM whatsapp_messages WHERE message_id = ?");
        return $stmt->execute([$this->messageId]);
    }

    /**
     * Deprecated
     */
    // public static function findByInstanceName(string $instanceName)
    // {
    //     $pdo = self::connect();
    //     $stmt = $pdo->prepare("SELECT * FROM whatsapp_messages WHERE instance_name = ? ORDER BY date_time ASC");
    //     $stmt->execute([$instanceName]);
    //     $messages = [];

    //     while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    //         $msg = new self();
    //         $msg->messageId = $row['message_id'];
    //         $msg->remoteJid = $row['remote_jid'];
    //         $msg->instanceName = $row['instance_name'];
    //         $msg->message = $row['message'];
    //         $msg->pushName = $row['push_name'];
    //         $msg->threadId = $row['thread_id'];
    //         $msg->fromMe = (bool)$row['from_me'];
    //         $msg->dateTime = new DateTime($row['date_time']);
    //         $messages[] = $msg;
    //     }

    //     return $messages;
    // }
    public static function findByInstanceName(string $instanceName)
    {
        $pdo = self::connect();
        // MUDANÇA AQUI: ORDER BY date_time DESC
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_messages WHERE instance_name = ? ORDER BY date_time DESC");
        $stmt->execute([$instanceName]);
        $messages = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $msg = new self();
            $msg->messageId = $row['message_id'];
            $msg->remoteJid = $row['remote_jid'];
            $msg->instanceName = $row['instance_name'];
            $msg->message = $row['message'];
            $msg->pushName = $row['push_name'];
            $msg->threadId = $row['thread_id']; // Certifique-se de que thread_id está no DB
            $msg->fromMe = (bool)$row['from_me'];
            $msg->dateTime = new DateTime($row['date_time']);
            $messages[] = $msg;
        }

        return $messages;
    }
    

    // Getters e Setters
    public function getRemoteJid(): string
    {
        return $this->remoteJid;
    }
    public function setRemoteJid(string $remoteJid): void
    {
        $this->remoteJid = $remoteJid;
    }

    public function getInstanceName(): string
    {
        return $this->instanceName;
    }
    public function setInstanceName(string $instanceName): void
    {
        $this->instanceName = $instanceName;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }
    public function setMessageId(string $messageId): void
    {
        $this->messageId = $messageId;
    }

    public function getPushName(): ?string
    {
        return $this->pushName;
    }
    public function setPushName(?string $pushName): void
    {
        $this->pushName = $pushName;
    }

    public function getThreadId(): ?string
    {
        return $this->threadId;
    }
    public function setThreadId(?string $threadId): void
    {
        $this->threadId = $threadId;
    }

    public function getFromMe(): int
    {
        return $this->fromMe;
    }
    public function setFromMe(int $fromMe): void
    {
        $this->fromMe = $fromMe;
    }

    public function isFromMe(): bool
    {
        return $this->fromMe === 1;
    }

    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }
    public function setDateTime(string|DateTime $dateTime): void
    {
        $this->dateTime = is_string($dateTime) ? new DateTime($dateTime) : $dateTime;
    }
}
