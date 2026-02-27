<?php
/**
 * Tickets Data Access Layer
 * SQLite-baserad ärendehantering med lazy singleton PDO
 */

/**
 * Get or create SQLite PDO connection (lazy singleton)
 */
function get_tickets_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbPath = defined('TICKETS_DB_PATH') ? TICKETS_DB_PATH : (DATA_PATH . '/tickets.db');

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // WAL journal mode for better concurrent access
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA busy_timeout=5000');

    // Create table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source TEXT NOT NULL DEFAULT 'contact',
            name TEXT,
            email TEXT,
            phone TEXT,
            subject TEXT NOT NULL,
            message TEXT NOT NULL,
            category TEXT DEFAULT 'general_question',
            status TEXT NOT NULL DEFAULT 'new',
            priority TEXT DEFAULT 'normal',
            ai_response TEXT,
            ai_actions TEXT,
            ai_resolved_at TEXT,
            resolved_by TEXT,
            admin_notes TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    return $pdo;
}

/**
 * Create a new ticket
 * @return int The new ticket ID
 */
function ticket_create(array $data): int {
    $db = get_tickets_db();

    $allowed = ['source', 'name', 'email', 'phone', 'subject', 'message', 'category', 'status', 'priority'];
    $fields = [];
    $placeholders = [];
    $values = [];

    foreach ($allowed as $key) {
        if (isset($data[$key])) {
            $fields[] = $key;
            $placeholders[] = ':' . $key;
            $values[':' . $key] = $data[$key];
        }
    }

    if (empty($fields)) {
        throw new \InvalidArgumentException('No valid fields provided');
    }

    $sql = 'INSERT INTO tickets (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    return (int) $db->lastInsertId();
}

/**
 * Get a single ticket by ID
 */
function ticket_get(int $id): ?array {
    $db = get_tickets_db();
    $stmt = $db->prepare('SELECT * FROM tickets WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * List tickets with optional filters and pagination
 *
 * Filters: status, source, category, search (subject/message)
 * Returns: ['tickets' => [...], 'total' => int]
 */
function ticket_list(array $filters = [], int $limit = 20, int $offset = 0): array {
    $db = get_tickets_db();

    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'status = :status';
        $params[':status'] = $filters['status'];
    }

    if (!empty($filters['source'])) {
        $where[] = 'source = :source';
        $params[':source'] = $filters['source'];
    }

    if (!empty($filters['category'])) {
        $where[] = 'category = :category';
        $params[':category'] = $filters['category'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(subject LIKE :search OR message LIKE :search)';
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM tickets {$whereClause}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Fetch page
    $stmt = $db->prepare("SELECT * FROM tickets {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll();

    return ['tickets' => $tickets, 'total' => $total];
}

/**
 * Update a ticket by ID
 */
function ticket_update(int $id, array $data): bool {
    $db = get_tickets_db();

    $allowed = ['status', 'category', 'priority', 'ai_response', 'ai_actions', 'ai_resolved_at', 'resolved_by', 'admin_notes'];
    $sets = [];
    $values = [':id' => $id];

    foreach ($allowed as $key) {
        if (array_key_exists($key, $data)) {
            $sets[] = "{$key} = :{$key}";
            $values[':' . $key] = $data[$key];
        }
    }

    if (empty($sets)) {
        return false;
    }

    $sets[] = "updated_at = datetime('now')";
    $sql = 'UPDATE tickets SET ' . implode(', ', $sets) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);

    return $stmt->execute($values);
}

/**
 * Count tickets by status
 */
function ticket_count_by_status(string $status): int {
    $db = get_tickets_db();
    $stmt = $db->prepare('SELECT COUNT(*) FROM tickets WHERE status = :status');
    $stmt->execute([':status' => $status]);
    return (int) $stmt->fetchColumn();
}

/**
 * Count all unresolved tickets (not resolved or closed)
 */
function ticket_count_unresolved(): int {
    $db = get_tickets_db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('resolved', 'closed')");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

/**
 * Count all tickets
 */
function ticket_count_all(): int {
    $db = get_tickets_db();
    $stmt = $db->prepare('SELECT COUNT(*) FROM tickets');
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}
