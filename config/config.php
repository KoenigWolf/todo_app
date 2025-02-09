<?php

return [
    'app' => [
        'name' => 'Todo Application',
        'env' => getenv('APP_ENV') ?: 'development',
        'debug' => getenv('APP_DEBUG') ?: true,
        'url' => getenv('APP_URL') ?: 'http://localhost',
        'timezone' => 'Asia/Tokyo',
        'charset' => 'UTF-8',
    ],

    'security' => [
        'session' => [
            'name' => 'todo_session',
            'lifetime' => 7200, // 2時間
            'path' => '/',
            'domain' => '',
            'secure' => false, // 本番環境ではtrueに設定
            'httponly' => true,
        ],
        'csrf' => [
            'token_lifetime' => 7200,
        ],
        'password' => [
            'algorithm' => PASSWORD_BCRYPT,
            'options' => [
                'cost' => 12
            ]
        ]
    ],

    'database' => require __DIR__ . '/database.php',

    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs',
        'level' => getenv('LOG_LEVEL') ?: 'debug',
    ],

    'maintenance' => [
        'enabled' => false,
        'message' => 'メンテナンス中です。しばらくお待ちください。',
    ]
];