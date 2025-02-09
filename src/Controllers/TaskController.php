<?php

namespace Controllers;

use Core\Security;
use Core\Session;
use Models\Task;

class TaskController {
    private Security $security;
    private Session $session;
    private Task $taskModel;
    private AuthController $auth;

    public function __construct() {
        $this->security = Security::getInstance();
        $this->session = new Session();
        $this->taskModel = new Task();
        $this->auth = new AuthController();
    }

    // タスク一覧の取得
    public function index(array $filters = []): array {
        if (!$this->auth->isAuthenticated()) {
            return [
                'success' => false,
                'message' => '認証が必要です。'
            ];
        }

        try {
            $userId = $this->session->getUserId();
            $tasks = $this->taskModel->getTasksByUser($userId, $filters);

            return [
                'success' => true,
                'data' => $tasks
            ];
        } catch (\Exception $e) {
            error_log("タスク一覧取得エラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'タスクの取得中にエラーが発生しました。'
            ];
        }
    }

    // タスクの作成
    public function create(array $data): array {
        if (!$this->auth->isAuthenticated()) {
            return [
                'success' => false,
                'message' => '認証が必要です。'
            ];
        }

        // CSRF対策
        if (!isset($data['csrf_token']) || 
            !$this->security->validateCsrfToken($data['csrf_token'])) {
            return [
                'success' => false,
                'message' => '不正なリクエストです。'
            ];
        }

        // バリデーション
        if (!$this->validateTaskData($data)) {
            return [
                'success' => false,
                'message' => '入力内容に誤りがあります。'
            ];
        }

        try {
            $userId = $this->session->getUserId();
            $taskData = array_merge($data, ['user_id' => $userId]);
            
            $taskId = $this->taskModel->createTask($taskData);

            if (!$taskId) {
                return [
                    'success' => false,
                    'message' => 'タスクの作成に失敗しました。'
                ];
            }

            return [
                'success' => true,
                'message' => 'タスクを作成しました。',
                'task_id' => $taskId
            ];

        } catch (\Exception $e) {
            error_log("タスク作成エラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'タスクの作成中にエラーが発生しました。'
            ];
        }
    }

    // タスクの更新
    public function update(int $taskId, array $data): array {
        if (!$this->auth->isAuthenticated()) {
            return [
                'success' => false,
                'message' => '認証が必要です。'
            ];
        }

        // CSRF対策
        if (!isset($data['csrf_token']) || 
            !$this->security->validateCsrfToken($data['csrf_token'])) {
            return [
                'success' => false,
                'message' => '不正なリクエストです。'
            ];
        }

        try {
            $userId = $this->session->getUserId();
            $task = $this->taskModel->find($taskId);

            if (!$task || $task['user_id'] !== $userId) {
                return [
                    'success' => false,
                    'message' => 'タスクが見つかりません。'
                ];
            }

            if (!$this->taskModel->update($taskId, $data)) {
                return [
                    'success' => false,
                    'message' => 'タスクの更新に失敗しました。'
                ];
            }

            return [
                'success' => true,
                'message' => 'タスクを更新しました。'
            ];

        } catch (\Exception $e) {
            error_log("タスク更新エラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'タスクの更新中にエラーが発生しました。'
            ];
        }
    }

    // タスクの削除
    public function delete(int $taskId): array {
        if (!$this->auth->isAuthenticated()) {
            return [
                'success' => false,
                'message' => '認証が必要です。'
            ];
        }

        try {
            $userId = $this->session->getUserId();
            $task = $this->taskModel->find($taskId);

            if (!$task || $task['user_id'] !== $userId) {
                return [
                    'success' => false,
                    'message' => 'タスクが見つかりません。'
                ];
            }

            if (!$this->taskModel->softDelete($taskId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'タスクの削除に失敗しました。'
                ];
            }

            return [
                'success' => true,
                'message' => 'タスクを削除しました。'
            ];

        } catch (\Exception $e) {
            error_log("タスク削除エラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'タスクの削除中にエラーが発生しました。'
            ];
        }
    }

    // タスクの完了状態を切り替え
    public function toggleComplete(int $taskId): array {
        if (!$this->auth->isAuthenticated()) {
            return [
                'success' => false,
                'message' => '認証が必要です。'
            ];
        }

        try {
            $userId = $this->session->getUserId();

            if (!$this->taskModel->toggleComplete($taskId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'タスクの状態更新に失敗しました。'
                ];
            }

            return [
                'success' => true,
                'message' => 'タスクの状態を更新しました。'
            ];

        } catch (\Exception $e) {
            error_log("タスク状態更新エラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'タスクの状態更新中にエラーが発生しました。'
            ];
        }
    }

    // タスクの詳細を取得
    public function show(int $taskId): array {
        if (!$this->auth->isAuthenticated()) {
            return [
                'success' => false,
                'message' => '認証が必要です。'
            ];
        }

        try {
            $userId = $this->session->getUserId();
            $task = $this->taskModel->find($taskId);

            if (!$task || $task['user_id'] !== $userId) {
                return [
                    'success' => false,
                    'message' => 'タスクが見つかりません。'
                ];
            }

            return [
                'success' => true,
                'data' => $task
            ];

        } catch (\Exception $e) {
            error_log("タスク詳細取得エラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'タスクの取得中にエラーが発生しました。'
            ];
        }
    }

    // タスクデータのバリデーション
    private function validateTaskData(array $data): bool {
        return (
            isset($data['title']) && 
            strlen($data['title']) > 0 &&
            strlen($data['title']) <= 255 &&
            (!isset($data['due_date']) || strtotime($data['due_date']) !== false)
        );
    }
}