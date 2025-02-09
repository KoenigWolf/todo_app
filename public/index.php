<?php

// オートローディングとエラー設定
require_once __DIR__ . '/../vendor/autoload.php';

use Controllers\AuthController;
use Controllers\TaskController;
use Core\Security;
use Core\Session;

// エラー設定
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// 設定の読み込み
$config = require __DIR__ . '/../config/config.php';

// セッションの開始
$session = new Session();
$security = Security::getInstance();

// セキュリティヘッダーの設定
$security->setSecurityHeaders();

// ルーティング処理
$path = $_SERVER['PATH_INFO'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'];

// CSRFトークンの生成（フォーム表示時に使用）
$csrfToken = $security->generateCsrfToken();

// JSONレスポンス用ヘルパー関数
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 認証必須ルートの確認
$authRequired = [
    '/tasks',
    '/tasks/create',
    '/tasks/update',
    '/tasks/delete',
    '/tasks/toggle',
];

$auth = new AuthController();

// 認証チェック
if (in_array($path, $authRequired) && !$auth->isAuthenticated()) {
    jsonResponse([
        'success' => false,
        'message' => '認証が必要です。',
        'redirect' => '/login'
    ], 401);
}

try {
    // ルーティング
    switch (true) {
        // 認証関連
        case $path === '/login' && $method === 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $auth->login($data);
            jsonResponse($result, $result['success'] ? 200 : 400);
            break;

        case $path === '/register' && $method === 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $auth->register($data);
            jsonResponse($result, $result['success'] ? 201 : 400);
            break;

        case $path === '/logout' && $method === 'POST':
            $result = $auth->logout();
            jsonResponse($result);
            break;

        // タスク関連
        case $path === '/tasks' && $method === 'GET':
            $taskController = new TaskController();
            $filters = $_GET;
            $result = $taskController->index($filters);
            jsonResponse($result);
            break;

        case $path === '/tasks/create' && $method === 'POST':
            $taskController = new TaskController();
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $taskController->create($data);
            jsonResponse($result, $result['success'] ? 201 : 400);
            break;

        case preg_match('#^/tasks/(\d+)$#', $path, $matches) && $method === 'GET':
            $taskController = new TaskController();
            $result = $taskController->show((int)$matches[1]);
            jsonResponse($result);
            break;

        case preg_match('#^/tasks/(\d+)$#', $path, $matches) && $method === 'PUT':
            $taskController = new TaskController();
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $taskController->update((int)$matches[1], $data);
            jsonResponse($result);
            break;

        case preg_match('#^/tasks/(\d+)$#', $path, $matches) && $method === 'DELETE':
            $taskController = new TaskController();
            $result = $taskController->delete((int)$matches[1]);
            jsonResponse($result);
            break;

        case preg_match('#^/tasks/(\d+)/toggle$#', $path, $matches) && $method === 'POST':
            $taskController = new TaskController();
            $result = $taskController->toggleComplete((int)$matches[1]);
            jsonResponse($result);
            break;

        // デフォルトルート
        case $path === '/':
            if ($auth->isAuthenticated()) {
                header('Location: /tasks');
                exit;
            }
            header('Location: /login');
            exit;
            break;

        // 404 エラー
        default:
            jsonResponse([
                'success' => false,
                'message' => 'ページが見つかりません。'
            ], 404);
    }

} catch (\Exception $e) {
    // エラーログの記録
    error_log($e->getMessage());
    
    // エラーレスポンス
    jsonResponse([
        'success' => false,
        'message' => $config['app']['debug'] 
            ? $e->getMessage() 
            : 'システムエラーが発生しました。'
    ], 500);
}