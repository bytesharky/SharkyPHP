<?php

namespace Sharky\Core\Database;

class Connect
{
    private $connection = null;
    private $connectType;
    private $pdoStatement;

    public function __construct($host, $user, $pass, $name, $port, $charset)
    {
        if ($this->connection) {
            return;
        }

        if ($this->connectType === 'mysqli') {
            $this->connection = new \mysqli(
                $host,
                $user,
                $pass,
                $name,
                $port
            );

            if ($this->connection->connect_error) {
                throw new \Exception("Connection failed: " . $this->connection->connect_error);
            }

            $this->connection->set_charset($charset);
        } else {
            $dsnParts = [
                "mysql:host={$host}",
                "port={$port}",
                "dbname={$name}",
                "charset={$charset}"
            ];

            $dsn = implode(';', $dsnParts);

            try {
                $this->connection = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]);
            } catch (\PDOException $e) {
                throw new \Exception("Connection failed: " . $e->getMessage());
            }
        }
    }

    public function query($sql, $params = [])
    {
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

    public function execute($sql, $params = [])
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

    public function beginTransaction()
    {
        if ($this->connectType === 'mysqli') {
            return $this->connection->begin_transaction();
        } else {
            return $this->connection->beginTransaction();
        }
    }

    public function commit()
    {
        if ($this->connectType === 'mysqli') {
            return $this->connection->commit();
        } else {
            return $this->connection->commit();
        }
    }

    public function rollback()
    {
        if ($this->connectType === 'mysqli') {
            return $this->connection->rollback();
        } else {
            return $this->connection->rollback();
        }
    }

    public function runTransaction(callable $callBack){
        try {
            $this->beginTransaction();

            if ($callBack($this)) {
                $this->commit();
                return true;
            } else {
                $this->rollback();
                return false;
            }
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }
    }

    public function lastInsertId()
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
