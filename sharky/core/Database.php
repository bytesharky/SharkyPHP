<?php

namespace Sharky\Core;

use Sharky\Core\Container;

class Database
{
    protected $connection = null;
    protected $config;
    protected $pdoStatement;

    public function __construct()
    {
        $this->config = Container::getInstance()->make('config')->get('database');
        $this->connect();
    }

    protected function connect()
    {
        if ($this->connection) {
            return;
        }

        if ($this->config['connect_type'] === 'mysqli') {
            $this->connection = new \mysqli(
                $this->config['db_host'],
                $this->config['db_user'],
                $this->config['db_pass'],
                $this->config['db_name'],
                $this->config['db_port']
            );

            if ($this->connection->connect_error) {
                throw new \Exception("Connection failed: " . $this->connection->connect_error);
            }

            $this->connection->set_charset($this->config['db_charset']);
        } else {
            $dsnParts = [
                "mysql:host={$this->config['db_host']}",
                "port={$this->config['db_port']}",
                "dbname={$this->config['db_name']}",
                "charset={$this->config['db_charset']}"
            ];

            $dsn = implode(';', $dsnParts);

            try {
                $this->connection = new \PDO($dsn, $this->config['db_user'], $this->config['db_pass'], [
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
        if ($this->config['connect_type'] === 'mysqli') {
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
        if ($this->config['connect_type'] === 'mysqli') {
            $stmt = $this->connection->prepare($sql);

            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
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
        if ($this->config['connect_type'] === 'mysqli') {
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
        if ($this->config['connect_type'] === 'mysqli') {
            return $this->connection->begin_transaction();
        } else {
            return $this->connection->beginTransaction();
        }
    }

    public function commit()
    {
        if ($this->config['connect_type'] === 'mysqli') {
            return $this->connection->commit();
        } else {
            return $this->connection->commit();
        }
    }

    public function rollback()
    {
        if ($this->config['connect_type'] === 'mysqli') {
            return $this->connection->rollback();
        } else {
            return $this->connection->rollback();
        }
    }

    public function lastInsertId()
    {
        if ($this->config['connect_type'] === 'mysqli') {
            return $this->connection->insert_id;
        } else {
            return $this->connection->lastInsertId();
        }
    }

    public function __destruct()
    {
        if ($this->connection) {
            if ($this->config['connect_type'] === 'mysqli') {
                $this->connection->close();
            } else {
                $this->connection = null;
            }
        }
    }
}
