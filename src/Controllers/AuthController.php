<?php

namespace Controllers;

use Core\Security;
use Core\Session;
use Models\User;

class AuthController {
    private Security $security;
    private Session $session;
    private User $userModel;

    public function __construct() {
        $this->security = Security::getInstance();
        $this->session = new Session();
        $this->userModel = new User();
    }

    // ログイン処理
    public function login(array $data): array {
        if (!isset($data['email']) || !isset($data['password'])) {
            return [
                'success' => false,
                'message' => 'メールアドレスとパスワードを入力してください。'
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
            $user = $this->userModel->authenticate(
                $data['email'],
                $data['password']
            );

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'メールアドレスまたはパスワードが正しくありません。'
                ];
            }

            // セッションの再生成
            $this->security->regenerateSession();

            // ユーザー情報をセッションに保存
            $this->session->set('user_id', $user['id']);
            $this->session->set('username', $user['username']);
            $this->session->set('email', $user['email']);

            // 最終ログイン日時の更新
            $this->userModel->updateLastLogin($user['id']);

            // ログイン試行回数のリセット
            $this->userModel->resetLoginAttempts($user['id']);

            return [
                'success' => true,
                'message' => 'ログインしました。',
                'user' => $user
            ];

        } catch (\Exception $e) {
            error_log("ログインエラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'ログイン処理中にエラーが発生しました。'
            ];
        }
    }

    // ログアウト処理
    public function logout(): array {
        try {
            // セッションの破棄
            $this->session->destroy();

            return [
                'success' => true,
                'message' => 'ログアウトしました。'
            ];
        } catch (\Exception $e) {
            error_log("ログアウトエラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'ログアウト処理中にエラーが発生しました。'
            ];
        }
    }

    // ユーザー登録
    public function register(array $data): array {
        // 入力値の検証
        if (!$this->validateRegistrationData($data)) {
            return [
                'success' => false,
                'message' => '入力内容に誤りがあります。'
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
            $userId = $this->userModel->createUser([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true
            ]);

            if (!$userId) {
                return [
                    'success' => false,
                    'message' => 'ユーザー登録に失敗しました。'
                ];
            }

            return [
                'success' => true,
                'message' => 'ユーザー登録が完了しました。',
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            error_log("ユーザー登録エラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'ユーザー登録中にエラーが発生しました。'
            ];
        }
    }

    // パスワードリセットメールの送信
    public function requestPasswordReset(string $email): array {
        try {
            $token = $this->userModel->generatePasswordResetToken($email);

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'パスワードリセットの処理に失敗しました。'
                ];
            }

            // TODO: メール送信処理の実装
            // $this->sendPasswordResetEmail($email, $token);

            return [
                'success' => true,
                'message' => 'パスワードリセットの手順をメールで送信しました。'
            ];

        } catch (\Exception $e) {
            error_log("パスワードリセットエラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'パスワードリセットの処理中にエラーが発生しました。'
            ];
        }
    }

    // パスワードのリセット
    public function resetPassword(string $token, string $newPassword): array {
        try {
            $user = $this->userModel->verifyPasswordResetToken($token);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'パスワードリセットトークンが無効です。'
                ];
            }

            if (!$this->userModel->updatePassword($user['id'], $newPassword)) {
                return [
                    'success' => false,
                    'message' => 'パスワードの更新に失敗しました。'
                ];
            }

            return [
                'success' => true,
                'message' => 'パスワードを更新しました。'
            ];

        } catch (\Exception $e) {
            error_log("パスワードリセットエラー: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'パスワードリセットの処理中にエラーが発生しました。'
            ];
        }
    }

    // 登録データの検証
    private function validateRegistrationData(array $data): bool {
        return (
            isset($data['username']) && 
            isset($data['email']) && 
            isset($data['password']) &&
            filter_var($data['email'], FILTER_VALIDATE_EMAIL) &&
            strlen($data['password']) >= 8 &&
            strlen($data['username']) >= 3
        );
    }

    // 認証状態の確認
    public function isAuthenticated(): bool {
        return $this->session->isLoggedIn();
    }

    // 現在のユーザー情報の取得
    public function getCurrentUser(): ?array {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $userId = $this->session->getUserId();
        return $this->userModel->find($userId);
    }
}