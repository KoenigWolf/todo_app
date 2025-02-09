<?php

namespace Models;

use Core\Database;
use PDO;
use PDOException;

abstract class BaseModel {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // レコードの取得
    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    // 条件に基づくレコードの取得
    public function findBy(array $conditions, array $orderBy = []): array {
        $where = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $where[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $orderByClause = '';
        if (!empty($orderBy)) {
            $orders = [];
            foreach ($orderBy as $column => $direction) {
                $orders[] = "{$column} {$direction}";
            }
            $orderByClause = 'ORDER BY ' . implode(', ', $orders);
        }

        $sql = "SELECT * FROM {$this->table} {$whereClause} {$orderByClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // レコードの作成
    public function create(array $data): ?int {
        $data = $this->filterFillableFields($data);
        
        $fields = array_keys($data);
        $placeholders = array_map(fn($field) => ":{$field}", $fields);
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Create record error: " . $e->getMessage());
            return null;
        }
    }

    // レコードの更新
    public function update(int $id, array $data): bool {
        $data = $this->filterFillableFields($data);
        
        $fields = array_map(fn($field) => "{$field} = :{$field}", array_keys($data));
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :id",
            $this->table,
            implode(', ', $fields),
            $this->primaryKey
        );

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(array_merge($data, ['id' => $id]));
        } catch (PDOException $e) {
            error_log("Update record error: " . $e->getMessage());
            return false;
        }
    }

    // レコードの削除
    public function delete(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Delete record error: " . $e->getMessage());
            return false;
        }
    }

    // 全レコードの取得
    public function all(): array {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // fillableフィールドのフィルタリング
    protected function filterFillableFields(array $data): array {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    // hiddenフィールドの除外
    protected function removeHiddenFields(array $data): array {
        return array_diff_key($data, array_flip($this->hidden));
    }

    // トランザクション処理
    protected function transaction(callable $callback) {
        return Database::getInstance()->executeTransaction($callback);
    }

    // カスタムクエリの実行
    protected function query(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}