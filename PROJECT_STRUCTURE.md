# プロジェクト構造

```
todo_app/
├── config/               # 設定ファイル
│   ├── config.php       # アプリケーション設定
│   ├── database.php     # データベース設定
│   └── schema.sql       # データベーススキーマ
│
├── src/                 # ソースコード
│   ├── Controllers/     # コントローラー
│   │   ├── AuthController.php
│   │   └── TaskController.php
│   │
│   ├── Models/          # モデル
│   │   ├── BaseModel.php
│   │   ├── User.php
│   │   └── Task.php
│   │
│   └── Core/            # コア機能
│       ├── Database.php
│       ├── Security.php
│       └── Session.php
│
├── public/              # 公開ディレクトリ
│   ├── index.php        # エントリーポイント
│   ├── css/
│   │   └── styles.css
│   ├── js/
│   └── uploads/         # アップロードファイル
│       └── .gitkeep
│
├── tests/               # テストファイル
│   ├── Controllers/
│   ├── Models/
│   └── Services/
│
├── logs/               # ログファイル
│   └── .gitkeep
│
├── cache/              # キャッシュファイル
│   └── .gitkeep
│
├── vendor/             # Composer依存関係（.gitignore）
│
├── .env.example        # 環境変数テンプレート
├── .gitignore         # Git除外設定
├── composer.json      # Composer設定
├── IMPROVEMENTS.md    # 改善報告書
└── README.md          # プロジェクト説明

```

## クリーンアップ実施内容

1. 不要ファイルの削除
   - 古いPHPファイル（login.html, welcome.php, logout.php, delete.php, edit_task.php）
   - 重複したスタイルシート

2. ディレクトリ構造の整理
   - MVC構造に基づくディレクトリ配置
   - 適切なファイル分離

3. アセット管理の改善
   - CSS/JSの適切な配置
   - アップロードディレクトリの整理

4. 設定ファイルの整理
   - 環境変数の分離
   - データベース設定の整理

5. セキュリティ強化
   - 機密情報の外部化
   - 適切なGitignore設定

## 注意事項

1. `vendor/`ディレクトリは`composer install`で生成されます
2. `.env`ファイルは`.env.example`をコピーして作成してください
3. データベースは`schema.sql`を使用して初期化してください