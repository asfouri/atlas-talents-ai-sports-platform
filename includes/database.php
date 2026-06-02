<?php
require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static bool $attempted = false;
    private static ?string $connectionError = null;

    private static function connect(): ?PDO {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        if (self::$attempted) {
            return null;
        }

        self::$attempted = true;

        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            self::$connectionError = null;
        } catch (PDOException $e) {
            self::$instance = null;
            self::$connectionError = $e->getMessage();
        }

        return self::$instance;
    }

    public static function isAvailable(): bool {
        return self::connect() instanceof PDO;
    }

    public static function getConnectionError(): ?string {
        self::connect();
        return self::$connectionError;
    }

    public static function getInstance(): PDO {
        $connection = self::connect();

        if (!$connection instanceof PDO) {
            $message = self::$connectionError ? 'Database unavailable: ' . self::$connectionError : 'Database unavailable.';
            throw new RuntimeException($message);
        }

        return $connection;
    }

    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int {
        $cols = implode(', ', array_map(fn(string $column): string => '`' . $column . '`', array_keys($data)));
        $placeholders = ':' . implode(', :', array_keys($data));
        self::query("INSERT INTO `$table` ($cols) VALUES ($placeholders)", $data);
        return (int) self::getInstance()->lastInsertId();
    }

    public static function lastInsertId(): int {
        return (int) self::getInstance()->lastInsertId();
    }

    public static function execute(string $sql): void {
        self::getInstance()->exec($sql);
    }

    public static function tableHasColumn(string $table, string $column): bool {
        if (!self::isAvailable()) {
            return false;
        }

        $result = self::fetchOne(
            "SELECT COUNT(*) AS total
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return (int) ($result['total'] ?? 0) > 0;
    }

    public static function ensureColumn(string $table, string $column, string $definition): void {
        if (!self::isAvailable() || self::tableHasColumn($table, $column)) {
            return;
        }

        self::execute("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }

    public static function getColumnType(string $table, string $column): ?string {
        if (!self::isAvailable()) {
            return null;
        }

        $result = self::fetchOne(
            "SELECT COLUMN_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return isset($result['COLUMN_TYPE']) ? (string) $result['COLUMN_TYPE'] : null;
    }
}
