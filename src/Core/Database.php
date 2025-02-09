<?php

namespace Core;

use PDO;
use PDOException;

class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;

    private function __construct() {
        $this->config = require __DIR__ . '/../../config/database.php';
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        if ($this->connection === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    $this->config['host'],
                    $this->config['database'],
                    $this->config['charset']
                );

                $this->connection = new PDO(
                    $dsn,
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['options']
                );
            } catch (PDOException $e) {
                // エラーログを記録
                error_log("データベース接続エラー: " . $e->getMessage());
                throw new PDOException("データベース接続に失敗しました。管理者に連絡してください。");
            }
        }
        return $this->connection;
    }

    public function beginTransaction(): bool {
        return $this->getConnection()->beginTransaction();
    }

    public function commit(): bool {
        return $this->getConnection()->commit();
    }

    public function rollBack(): bool {
        return $this->getConnection()->rollBack();
    }

    public function prepare(string $sql): \PDOStatement {
        return $this->getConnection()->prepare($sql);
    }

    public function lastInsertId(?string $name = null): string {
        return $this->getConnection()->lastInsertId($name);
    }

    // トランザクションを使用した安全な実行
    public function executeTransaction(callable $callback) {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    // シングルトンパターンの保証
    private function __clone() {}
    private function __wakeup() {}
}