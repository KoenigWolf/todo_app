<?php

namespace Models;

class Task extends BaseModel {
    protected string $table = 'tasks';
    protected array $fillable = [
        'user_id',
        'title',
        'description',
        'due_date',
        'priority',
        'is_completed',
        'category'
    ];

    // ユーザーのタスク一覧を取得
    public function getTasksByUser(int $userId, array $filters = []): array {
        $conditions = ['user_id' => $userId];
        $params = ['user_id' => $userId];
        $whereClause = ['user_id = :user_id'];

        // 完了状態でフィルタリング
        if (isset($filters['is_completed'])) {
            $whereClause[] = 'is_completed = :is_completed';
            $params['is_completed'] = $filters['is_completed'];
        }

        // カテゴリでフィルタリング
        if (!empty($filters['category'])) {
            $whereClause[] = 'category = :category';
            $params['category'] = $filters['category'];
        }

        // 優先度でフィルタリング
        if (!empty($filters['priority'])) {
            $whereClause[] = 'priority = :priority';
            $params['priority'] = $filters['priority'];
        }

        // 期限切れタスクのフィルタリング
        if (isset($filters['overdue']) && $filters['overdue']) {
            $whereClause[] = 'due_date < CURRENT_DATE() AND is_completed = 0';
        }

        // 検索クエリ
        if (!empty($filters['search'])) {
            $whereClause[] = '(title LIKE :search OR description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // ソート順の設定
        $orderBy = 'ORDER BY ';
        if (!empty($filters['sort_by'])) {
            $orderBy .= $filters['sort_by'];
            if (!empty($filters['sort_direction'])) {
                $orderBy .= ' ' . strtoupper($filters['sort_direction']);
            }
        } else {
            $orderBy .= 'due_date ASC, priority DESC';
        }

        $sql = sprintf(
            "SELECT * FROM %s WHERE %s %s",
            $this->table,
            implode(' AND ', $whereClause),
            $orderBy
        );

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("タスク取得エラー: " . $e->getMessage());
            return [];
        }
    }

    // タスクの作成
    public function createTask(array $data): ?int {
        try {
            return $this->transaction(function() use ($data) {
                // デフォルト値の設定
                $data['is_completed'] = $data['is_completed'] ?? false;
                $data['created_at'] = date('Y-m-d H:i:s');
                
                return $this->create($data);
            });
        } catch (\Exception $e) {
            error_log("タスク作成エラー: " . $e->getMessage());
            return null;
        }
    }

    // タスクの完了状態を更新
    public function toggleComplete(int $taskId, int $userId): bool {
        try {
            return $this->transaction(function() use ($taskId, $userId) {
                $task = $this->find($taskId);
                
                if (!$task || $task['user_id'] !== $userId) {
                    return false;
                }

                return $this->update($taskId, [
                    'is_completed' => !$task['is_completed'],
                    'completed_at' => !$task['is_completed'] ? date('Y-m-d H:i:s') : null
                ]);
            });
        } catch (\Exception $e) {
            error_log("タスク状態更新エラー: " . $e->getMessage());
            return false;
        }
    }

    // 期限切れタスクの取得
    public function getOverdueTasks(int $userId): array {
        return $this->query(
            "SELECT * FROM {$this->table} 
            WHERE user_id = :user_id 
            AND due_date < CURRENT_DATE() 
            AND is_completed = 0 
            ORDER BY due_date ASC",
            ['user_id' => $userId]
        );
    }

    // カテゴリ別のタスク数を取得
    public function getTaskCountByCategory(int $userId): array {
        return $this->query(
            "SELECT category, COUNT(*) as count 
            FROM {$this->table} 
            WHERE user_id = :user_id 
            GROUP BY category",
            ['user_id' => $userId]
        );
    }

    // タスクの削除（ソフトデリート）
    public function softDelete(int $taskId, int $userId): bool {
        try {
            return $this->update($taskId, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $userId
            ]);
        } catch (\Exception $e) {
            error_log("タスク削除エラー: " . $e->getMessage());
            return false;
        }
    }

    // 削除されたタスクの復元
    public function restore(int $taskId, int $userId): bool {
        try {
            return $this->update($taskId, [
                'deleted_at' => null,
                'deleted_by' => null
            ]);
        } catch (\Exception $e) {
            error_log("タスク復元エラー: " . $e->getMessage());
            return false;
        }
    }

    // タスクの優先度を更新
    public function updatePriority(int $taskId, int $userId, int $priority): bool {
        try {
            $task = $this->find($taskId);
            
            if (!$task || $task['user_id'] !== $userId) {
                return false;
            }

            return $this->update($taskId, ['priority' => $priority]);
        } catch (\Exception $e) {
            error_log("タスク優先度更新エラー: " . $e->getMessage());
            return false;
        }
    }
}