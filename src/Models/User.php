<?php

namespace Models;

use Core\Security;

class User extends BaseModel {
    protected string $table = 'users';
    protected array $fillable = ['username', 'email', 'password', 'is_active'];
    protected array $hidden = ['password', 'password_reset_token'];

    private Security $security;

    public function __construct() {
        parent::__construct();
        $this->security = Security::getInstance();
    }

    // ユーザーの作成
    public function createUser(array $data): ?int {
        if (isset($data['password'])) {
            $data['password'] = $this->security->hashPassword($data['password']);
        }

        try {
            return $this->transaction(function() use ($data) {
                // メールアドレスの重複チェック
                if ($this->findByEmail($data['email'])) {
                    throw new \Exception('このメールアドレスは既に登録されています。');
                }

                return $this->create($data);
            });
        } catch (\Exception $e) {
            error_log("ユーザー作成エラー: " . $e->getMessage());
            return null;
        }
    }

    // メールアドレスでユーザーを検索
    public function findByEmail(string $email): ?array {
        $users = $this->findBy(['email' => $email]);
        return !empty($users) ? $users[0] : null;
    }

    // ユーザー名でユーザーを検索
    public function findByUsername(string $username): ?array {
        $users = $this->findBy(['username' => $username]);
        return !empty($users) ? $users[0] : null;
    }

    // ユーザー認証
    public function authenticate(string $email, string $password): ?array {
        $user = $this->findByEmail($email);
        
        if (!$user || !$this->security->verifyPassword($password, $user['password'])) {
            return null;
        }

        if (!$user['is_active']) {
            return null;
        }

        return $this->removeHiddenFields($user);
    }

    // パスワードの更新
    public function updatePassword(int $userId, string $newPassword): bool {
        return $this->update($userId, [
            'password' => $this->security->hashPassword($newPassword)
        ]);
    }

    // パスワードリセットトークンの生成
    public function generatePasswordResetToken(string $email): ?string {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        $token = $this->security->generateRandomToken();
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $success = $this->update($user['id'], [
            'password_reset_token' => $token,
            'password_reset_expires' => $expires
        ]);

        return $success ? $token : null;
    }

    // パスワードリセットトークンの検証
    public function verifyPasswordResetToken(string $token): ?array {
        $users = $this->findBy([
            'password_reset_token' => $token
        ]);

        if (empty($users)) {
            return null;
        }

        $user = $users[0];
        
        if (strtotime($user['password_reset_expires']) < time()) {
            return null;
        }

        return $this->removeHiddenFields($user);
    }

    // アカウントの無効化
    public function deactivate(int $userId): bool {
        return $this->update($userId, ['is_active' => false]);
    }

    // アカウントの有効化
    public function activate(int $userId): bool {
        return $this->update($userId, ['is_active' => true]);
    }

    // ログイン試行回数の更新
    public function updateLoginAttempts(int $userId, int $attempts): bool {
        return $this->update($userId, [
            'login_attempts' => $attempts,
            'last_login_attempt' => date('Y-m-d H:i:s')
        ]);
    }

    // ログイン試行回数のリセット
    public function resetLoginAttempts(int $userId): bool {
        return $this->update($userId, [
            'login_attempts' => 0,
            'last_login_attempt' => null
        ]);
    }

    // 最終ログイン日時の更新
    public function updateLastLogin(int $userId): bool {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }
}