<?php

namespace Core;

class Session {
    private array $config;
    private Security $security;

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/config.php';
        $this->security = Security::getInstance();
        $this->start();
    }

    // セッションの開始と初期設定
    public function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // セッション設定
            $session_config = $this->config['security']['session'];
            session_name($session_config['name']);

            session_set_cookie_params([
                'lifetime' => $session_config['lifetime'],
                'path' => $session_config['path'],
                'domain' => $session_config['domain'],
                'secure' => $session_config['secure'],
                'httponly' => $session_config['httponly']
            ]);

            session_start();

            // セッションの初期化チェック
            if (!isset($_SESSION['initialized'])) {
                $this->initialize();
            }

            // セッションの有効性チェック
            if (!$this->security->validateSession()) {
                $this->destroy();
                $this->start();
            }
        }
    }

    // セッションの初期化
    private function initialize(): void {
        $_SESSION['initialized'] = true;
        $_SESSION['created_at'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // セッションIDの再生成
        $this->security->regenerateSession();
    }

    // セッション変数の設定
    public function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }

    // セッション変数の取得
    public function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    // セッション変数の存在確認
    public function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    // セッション変数の削除
    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    // セッション全体の取得
    public function all(): array {
        return $_SESSION;
    }

    // フラッシュメッセージの設定
    public function setFlash(string $key, $value): void {
        $_SESSION['flash'][$key] = $value;
    }

    // フラッシュメッセージの取得
    public function getFlash(string $key, $default = null) {
        if (isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $value;
        }
        return $default;
    }

    // フラッシュメッセージの存在確認
    public function hasFlash(string $key): bool {
        return isset($_SESSION['flash'][$key]);
    }

    // セッションの破棄
    public function destroy(): void {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    // セッションの有効性検証
    public function isValid(): bool {
        if (!isset($_SESSION['initialized'])) {
            return false;
        }

        // ユーザーエージェントの検証
        if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            return false;
        }

        // IPアドレスの検証（プロキシ環境では注意が必要）
        if ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            return false;
        }

        return $this->security->validateSession();
    }

    // ログイン状態の確認
    public function isLoggedIn(): bool {
        return $this->has('user_id') && $this->isValid();
    }

    // 認証ユーザーIDの取得
    public function getUserId(): ?int {
        return $this->get('user_id');
    }
}