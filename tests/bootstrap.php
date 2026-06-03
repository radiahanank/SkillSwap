<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// ── Fake DB result object (no mocking needed, just a plain class) ─────────────
class FakeResult
{
    public int   $num_rows;
    private array $rows;
    private mixed $single;

    public function __construct(int $numRows, array $fetchData, array $fetchAll)
    {
        $this->num_rows = $numRows;
        $this->single   = $numRows > 0 ? $fetchData : null;
        $this->rows     = $fetchAll;
    }

    public function fetch_assoc(): ?array { return $this->single; }
    public function fetch_all(int $mode = MYSQLI_ASSOC): array { return $this->rows; }
}

// ── Fake DB statement ─────────────────────────────────────────────────────────
class FakeStmt
{
    private bool       $executeReturn;
    private FakeResult $result;

    public function __construct(bool $executeReturn, FakeResult $result)
    {
        $this->executeReturn = $executeReturn;
        $this->result        = $result;
    }

    public function bind_param(string $types, mixed &...$vars): void {}
    public function execute(): bool { return $this->executeReturn; }
    public function get_result(): FakeResult { return $this->result; }
    public function close(): void {}
}

// ── Fake DB connection ────────────────────────────────────────────────────────
class FakeConn
{
    private FakeStmt $stmt;

    public function __construct(FakeStmt $stmt) { $this->stmt = $stmt; }
    public function prepare(string $sql): FakeStmt { return $this->stmt; }
    public function query(string $sql): FakeResult { return $this->stmt->get_result(); }
}

// ── MockDbTrait ───────────────────────────────────────────────────────────────
trait MockDbTrait
{
    protected function mockConn(
        bool  $stmtExecute = true,
        int   $numRows     = 0,
        array $fetchData   = [],
        array $fetchAll    = []
    ): FakeConn {
        $result = new FakeResult($numRows, $fetchData, $fetchAll);
        $stmt   = new FakeStmt($stmtExecute, $result);
        return  new FakeConn($stmt);
    }
}

// ── MessageData ───────────────────────────────────────────────────────────────
if (!class_exists('MessageData')) {
    class MessageData
    {
        private $conn;
        public function __construct($conn) { $this->conn = $conn; }

        public function addMessage(int $sender_id, int $receiver_id, string $message_text): bool
        {
            $stmt = $this->conn->prepare("INSERT INTO messages (sender_id, receiver_id, MessageText) VALUES (?, ?, ?)");
            if (!$stmt) return false;
            $stmt->bind_param("iis", $sender_id, $receiver_id, $message_text);
            return $stmt->execute();
        }

        public function listMessages(int $user_id): array
        {
            $stmt = $this->conn->prepare("SELECT * FROM messages WHERE sender_id = ? OR receiver_id = ?");
            if (!$stmt) return [];
            $stmt->bind_param("ii", $user_id, $user_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        public function findMessage(int $message_id): ?array
        {
            $stmt = $this->conn->prepare("SELECT * FROM messages WHERE MessageID = ? LIMIT 1");
            if (!$stmt) return null;
            $stmt->bind_param("i", $message_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            return $row ?: null;
        }

        public function filterMessages(int $user_id, int $is_read): array
        {
            $stmt = $this->conn->prepare("SELECT * FROM messages WHERE receiver_id = ? AND IsRead = ?");
            if (!$stmt) return [];
            $stmt->bind_param("ii", $user_id, $is_read);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// ── MessageMiddle ─────────────────────────────────────────────────────────────
if (!class_exists('MessageMiddle')) {
    class MessageMiddle
    {
        private MessageData $data;
        public function __construct($conn) { $this->data = new MessageData($conn); }

        public function sendMessage($sender_id, $receiver_id, $message_text): array
        {
            if (empty(trim((string)$message_text)))
                return ['success' => false, 'error' => 'Message cannot be empty'];
            if (!is_numeric($sender_id) || !is_numeric($receiver_id))
                return ['success' => false, 'error' => 'Invalid user ID'];
            if ($sender_id == $receiver_id)
                return ['success' => false, 'error' => 'Cannot send message to yourself'];
            $result = $this->data->addMessage((int)$sender_id, (int)$receiver_id, (string)$message_text);
            return ['success' => $result];
        }

        public function getMessages($user_id): array { return $this->data->listMessages((int)$user_id); }
        public function getMessage($id): ?array       { return $this->data->findMessage((int)$id); }

        public function filterMessages($user_id, $is_read): array
        {
            if (!in_array($is_read, [0, 1]))
                return ['success' => false, 'error' => 'Invalid filter value'];
            return $this->data->filterMessages((int)$user_id, (int)$is_read);
        }
    }
}

// ── AuthValidator ─────────────────────────────────────────────────────────────
if (!class_exists('AuthValidator')) {
    class AuthValidator
    {
        public static function validateRegistration(array $data): array
        {
            $errors = [];
            foreach (['first_name','last_name','username','email','password'] as $field) {
                if (empty(trim((string)($data[$field] ?? ''))))
                    $errors[] = "Field '$field' is required.";
            }
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
                $errors[] = "Invalid email format.";
            if (!empty($data['password']) && strlen($data['password']) < 6)
                $errors[] = "Password must be at least 6 characters.";
            return $errors;
        }

        public static function validateLogin(string $email, string $password): array
        {
            $errors = [];
            if (empty($email))    $errors[] = "Email is required.";
            if (empty($password)) $errors[] = "Password is required.";
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
                $errors[] = "Invalid email format.";
            return $errors;
        }

        public static function verifyPassword(string $plain, string $hash): bool
        {
            return password_verify($plain, $hash) || $plain === $hash;
        }
    }
}

// ── SkillValidator ────────────────────────────────────────────────────────────
if (!class_exists('SkillValidator')) {
    class SkillValidator
    {
        private static array $validLevels = ['Beginner','Intermediate','Advanced'];
        private static array $validTypes  = ['Teach','Learn'];

        public static function validate(int $userId, int $skillId, string $level, string $type): array
        {
            $errors = [];
            if ($userId <= 0)  $errors[] = "Invalid user ID.";
            if ($skillId <= 0) $errors[] = "Invalid skill ID.";
            if (!in_array($level, self::$validLevels))
                $errors[] = "Invalid level: '$level'. Must be one of: " . implode(', ', self::$validLevels);
            if (!in_array($type, self::$validTypes))
                $errors[] = "Invalid type: '$type'. Must be one of: " . implode(', ', self::$validTypes);
            return $errors;
        }
    }
}

// ── RatingValidator ───────────────────────────────────────────────────────────
if (!class_exists('RatingValidator')) {
    class RatingValidator
    {
        public static function validate(int $reviewerId, int $reviewedId, int $stars): array
        {
            $errors = [];
            if ($reviewerId <= 0) $errors[] = "Invalid reviewer ID.";
            if ($reviewedId <= 0) $errors[] = "Invalid reviewed ID.";
            if ($reviewerId === $reviewedId) $errors[] = "You cannot rate yourself.";
            if ($stars < 1 || $stars > 5)   $errors[] = "Stars must be between 1 and 5.";
            return $errors;
        }
    }
}

// ── EventValidator ────────────────────────────────────────────────────────────
if (!class_exists('EventValidator')) {
    class EventValidator
    {
        public static function validateCreate(int $creatorId, string $location, string $dateTime): array
        {
            $errors = [];
            if ($creatorId <= 0)        $errors[] = "Invalid creator ID.";
            if (empty(trim($location))) $errors[] = "Location is required.";
            if (empty(trim($dateTime))) $errors[] = "Date and time are required.";
            if (!empty($dateTime) && strtotime($dateTime) === false)
                $errors[] = "Invalid date/time format.";
            return $errors;
        }

        public static function canDelete(int $requesterId, int $creatorId): bool
        {
            return $requesterId === $creatorId;
        }
    }
}

// ── SessionValidator ──────────────────────────────────────────────────────────
if (!class_exists('SessionValidator')) {
    class SessionValidator
    {
        private static array $validTransitions = [
            'Pending'  => ['Accepted', 'Rejected'],
            'Accepted' => [],
            'Rejected' => [],
        ];

        public static function validateCreate(int $u1, int $u2, string $offered, string $requested, string $dateTime): array
        {
            $errors = [];
            if ($u1 <= 0 || $u2 <= 0) $errors[] = "Both user IDs must be valid.";
            if ($u1 === $u2)           $errors[] = "Cannot create a session with yourself.";
            if (empty($offered))       $errors[] = "Skill offered is required.";
            if (empty($requested))     $errors[] = "Skill requested is required.";
            if (empty($dateTime))      $errors[] = "Date and time are required.";
            return $errors;
        }

        public static function canTransition(string $current, string $next): bool
        {
            return in_array($next, self::$validTransitions[$current] ?? [], true);
        }
    }
}

// ── NotificationHelper ────────────────────────────────────────────────────────
if (!class_exists('NotificationHelper')) {
    class NotificationHelper
    {
        private $conn;
        public function __construct($conn) { $this->conn = $conn; }

        public function create(int $userId, string $type, string $messageText): bool
        {
            if ($userId <= 0 || empty($type) || empty($messageText)) return false;
            $stmt = $this->conn->prepare("INSERT INTO notification (user_id, type, message_text) VALUES (?, ?, ?)");
            if (!$stmt) return false;
            $stmt->bind_param("iss", $userId, $type, $messageText);
            return $stmt->execute();
        }

        public function markAllRead(int $userId): bool
        {
            if ($userId <= 0) return false;
            $stmt = $this->conn->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            if (!$stmt) return false;
            $stmt->bind_param("i", $userId);
            return $stmt->execute();
        }

        public function getUnread(int $userId): array
        {
            if ($userId <= 0) return [];
            $stmt = $this->conn->prepare("SELECT * FROM notification WHERE user_id = ? AND is_read = 0");
            if (!$stmt) return [];
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}