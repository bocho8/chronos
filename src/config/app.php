<?php
/**
 * Copyright (C) 2025 Agustín Roizen
 * Use of this software is governed by the Business Source License included in the LICENSE.TXT file and at www.mariadb.com/bsl11.
 * 
 * Change Date: Three years from the date of the first publicly available distribution of each version
 * 
 * On the date above, in accordance with the Business Source License, use of this software will be governed by the open source license specified in the LICENSE.TXT file.
 */

/**
 * Application Configuration
 * Main configuration file for Chronos application
 */

return [

    'name' => 'Chronos',
    'version' => '2.0.0',
    'environment' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true',
    'timezone' => 'America/Montevideo',

    'url' => getenv('APP_URL') ?: 'http://localhost',
    'asset_url' => getenv('ASSET_URL') ?: '/assets',

    'database' => [
        'default' => 'pgsql',
        'connections' => [
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => getenv('DB_HOST') ?: 'localhost',
                'port' => getenv('DB_PORT') ?: '5432',
                'database' => getenv('DB_DATABASE') ?: 'chronos',
                'username' => getenv('DB_USERNAME') ?: 'postgres',
                'password' => getenv('DB_PASSWORD') ?: '',
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
            ]
        ]
    ],

    'session' => [
        'driver' => 'file',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => true,
        'files' => __DIR__ . '/../storage/sessions',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => 'chronos_session',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax',
    ],

    'cache' => [
        'default' => 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../storage/cache',
            ],
            'database' => [
                'driver' => 'database',
                'table' => 'cache',
                'connection' => null,
            ],
        ],
    ],

    'logging' => [
        'default' => 'file',
        'channels' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../storage/logs/app.log',
                'level' => 'debug',
            ],
            'error' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../storage/logs/error.log',
                'level' => 'error',
            ],
        ],
    ],

    'mail' => [
        'driver' => 'smtp',
        'host' => getenv('MAIL_HOST') ?: 'localhost',
        'port' => getenv('MAIL_PORT') ?: 587,
        'username' => getenv('MAIL_USERNAME'),
        'password' => getenv('MAIL_PASSWORD'),
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from' => [
            'address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@chronos.edu.uy',
            'name' => getenv('MAIL_FROM_NAME') ?: 'Chronos',
        ],
    ],

    'security' => [
        'password_min_length' => 8,
        'password_require_special' => true,
        'session_timeout' => 120,
        'max_login_attempts' => 5,
        'lockout_duration' => 15,
        'csrf_protection' => true,
        'xss_protection' => true,
    ],

    'locale' => [
        'default' => 'es',
        'fallback' => 'en',
        'available' => ['es', 'en', 'it'],
        'rtl_languages' => [],
    ],

    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

    'uploads' => [
        'max_size' => 2048,
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'path' => __DIR__ . '/../storage/uploads',
    ],

    'api' => [
        'version' => 'v1',
        'rate_limit' => 60,
        'throttle' => true,
    ],

    'school' => [
        'name' => 'Scuola Italiana di Montevideo',
        'address' => 'Av. Italia 2727, 11600 Montevideo, Uruguay',
        'phone' => '+598 2 708 0000',
        'email' => 'info@scuolaitaliana.edu.uy',
        'website' => 'https://www.scuolaitaliana.edu.uy',
        'timezone' => 'America/Montevideo',
        'academic_year' => date('Y'),
        'semester' => 1, // 1 or 2
    ],

    'features' => [
        'notifications' => true,
        'reports' => true,
        'calendar' => true,
        'file_uploads' => true,
        'api_access' => true,
        'maintenance_mode' => false,
    ],
];
