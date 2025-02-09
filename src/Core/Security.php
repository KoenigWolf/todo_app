<?php

namespace Core;

class Security {
    private array $config;
    private static ?Security $instance = null;

    private function __construct() {
        $this->config = require __DIR__ . '/../../config/config.php';
    }

    public static function getInstance(): Security {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // CSRF対策
    public function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken(?string $token): bool {
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
            return false;
        }

        if (empty($token)) {
            return false;
        }

        // トークンの有効期限チェック
        $lifetime = $this->config['security']['csrf']['token_lifetime'];
        if (time() - $_SESSION['csrf_token_time'] > $lifetime) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // パスワードのハッシュ化
    public function hashPassword(string $password): string {
        return password_hash(
            $password,
            $this->config['security']['password']['algorithm'],
            $this->config['security']['password']['options']
        );
    }

    // パスワードの検証
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    // 文字列のサニタイズ
    public function sanitize(string $string): string {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    // セッションIDの再生成
    public function regenerateSession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    // XSS対策用のヘッダー設定
    public function setSecurityHeaders(): void {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        if ($this->config['app']['env'] === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    // ランダムトークンの生成
    public function generateRandomToken(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }

    // セッション有効期限の確認と更新
    public function validateSession(): bool {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return true;
        }

        $lifetime = $this->config['security']['session']['lifetime'];
        if (time() - $_SESSION['last_activity'] > $lifetime) {
            session_unset();
            session_destroy();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    // シングルトンパターンの保証
    private function __clone() {}
    private function __wakeup() {}
}