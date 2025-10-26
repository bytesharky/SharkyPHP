<?php

/**
 * @description 数据库连接类
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Core\Database;

use mysqli;
use PDO;
use PDOException;
use InvalidArgumentException;
use Sharky\Exceptions\ConnectionFailedException;
use Sharky\Exceptions\UnsupportedTypeException;

class Connect
{
    private $connection = null;
    private $connectType = 'PDO';
    private $pdoStatement;
    private $transactionLevel = 0;

    public function __construct($host, $type, $user, $pass, $name, $port, $charset, $timeout)
    {
        $type = strtolower($type);
        if (!in_array($type, ['mysqli', 'pdo'])) {
            throw new UnsupportedTypeException("不支持的链接类型: {$type}");
        }

        $this->connectType = $type;

        if ($this->connectType === 'mysqli') {

            // 启用严格模式
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $this->connection = new mysqli();

            $this->connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
            $this->connection->real_connect(
                $host,
                $user,
                $pass,
                $name,
                $port
            );
            if ($this->connection->connect_error) {
                throw new ConnectionFailedException("Connection failed: " . $this->connection->connect_error);
            }

            $this->connection->set_charset($charset);
        } else {
            $dsnParts = [
                "mysql:host={$host}",
                "port={$port}",
                "dbname={$name}",
                "charset={$charset}",
                "timeout={$timeout}"
            ];

            $dsn = implode(';', $dsnParts);

            try {
                $this->connection = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

            } catch (PDOException $e) {
                throw new ConnectionFailedException("Connection failed: " . $e->getMessage());
            }
        }
    }

    public function query($sql, $params = []): mixed
    {
        if (is_array($sql)) {
            throw new InvalidArgumentException('SQL 语句不能是数组');
        }
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                throw new InvalidArgumentException("SQL: {$sql}\n参数 $key 不能是数组");
            }
        }
        if ($this->connectType === 'mysqli') {
            $stmt = $this->connection->prepare($sql);

            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $this->pdoStatement = $this->connection->prepare($sql);
            $this->pdoStatement->execute($params);

            return $this->pdoStatement->fetchAll();
        }
    }

    public function execute($sql, $params = []): int
    {
        if ($this->connectType === 'mysqli') {
            $stmt = $this->connection->prepare($sql);

            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            return $affectedRows;
        } else {
            $this->pdoStatement = $this->connection->prepare($sql);
            $this->pdoStatement->execute($params);
            return $this->pdoStatement->rowCount();
        }
    }

    public function getFields($table)
    {
        if ($this->connectType === 'mysqli') {
            $result = $this->connection->query("SHOW COLUMNS FROM {$table}");
            $fields = [];

            while ($row = $result->fetch_assoc()) {
                $fields[] = $row['Field'];
            }

            return $fields;
        } else {
            $stmt = $this->connection->query("SHOW COLUMNS FROM {$table}");
            return array_column($stmt->fetchAll(), 'Field');
        }
    }

    public function getPrimarys($table): array
    {
        //$sql = "SHOW COLUMNS FROM {$table}"; => // Field 
        $sql = "SHOW INDEX FROM `{$table}` WHERE Key_name = 'PRIMARY'"; // Column_name
        if ($this->connectType === 'mysqli') {

            $result = $this->connection->query($sql);
            return array_column($result->fetch_all(MYSQLI_ASSOC), 'Column_name');
        } else {

            $stmt = $this->connection->query($sql);
            return array_column($stmt->fetchAll(), 'Column_name');
        }
    }

    public function beginTransaction(): bool
    {
        $level = ++$this->transactionLevel;
        $hasSavepoint = $level > 1;
        $savepoint = "sp_{$level}";

        if ($hasSavepoint) {
            $this->execute("SAVEPOINT {$savepoint}");
            return true;
        } else {
            if ($this->connectType === 'mysqli') {
                return $this->connection->begin_transaction();
            } else {
                return $this->connection->beginTransaction();
            }
        }
    }

    public function commit(): bool
    {
        if ($this->transactionLevel == 0)
            throw new \RuntimeException("当前不在事务中");

        $level = $this->transactionLevel--;
        $hasSavepoint = $level > 1;
        $savepoint = "sp_{$level}";
        if ($hasSavepoint) {
            $this->execute("RELEASE SAVEPOINT {$savepoint}");
            return true;
        } else {
            if ($this->connectType === 'mysqli') {
                return $this->connection->commit();
            } else {
                return $this->connection->commit();
            }
        }
    }

    public function rollback(): bool
    {
        if ($this->transactionLevel == 0)
            throw new \RuntimeException("当前不在事务中");

        $level = $this->transactionLevel--;
        $hasSavepoint = $level > 1;
        $savepoint = "sp_{$level}";

        if ($hasSavepoint) {
            $this->execute("ROLLBACK TO SAVEPOINT {$savepoint}");
            return true;
        } else {
            if ($this->connectType === 'mysqli') {
                return $this->connection->rollback();
            } else {
                return $this->connection->rollback();
            }
        }
    }

    public function runTransaction(callable $callback, bool $prevent = false): mixed
    {
        try {
            $this->beginTransaction();

            $result = $callback($this);

            if ($result !== false) {
                $this->commit();
                return $result;

            } else {
                $this->rollback();
                return false;
            }

        } catch (\Throwable $e) {

            $this->rollback();
            if ($prevent)
                return false;
            throw $e;
        }
    }

    public function lastInsertId(): bool|int|string
    {
        if ($this->connectType === 'mysqli') {
            return $this->connection->insert_id;
        } else {
            return $this->connection->lastInsertId();
        }
    }

    public function __destruct()
    {
        if ($this->connection) {
            if ($this->connectType === 'mysqli') {
                $this->connection->close();
            } else {
                $this->connection = null;
            }
        }
    }
}
