<?php

/**
 * Application Configuration
 * Main configuration file for Chronos application
 */

return [
    // Application settings
    'name' => 'Chronos',
    'version' => '2.0.0',
    'environment' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true',
    'timezone' => 'America/Montevideo',
    
    // URL settings
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'asset_url' => getenv('ASSET_URL') ?: '/assets',
    
    // Database settings
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
    
    // Session settings
    'session' => [
        'driver' => 'file',
        'lifetime' => 120, // minutes
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
    
    // Cache settings
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
    
    // Logging settings
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
    
    // Mail settings
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
    
    // Security settings
    'security' => [
        'password_min_length' => 8,
        'password_require_special' => true,
        'session_timeout' => 120, // minutes
        'max_login_attempts' => 5,
        'lockout_duration' => 15, // minutes
        'csrf_protection' => true,
        'xss_protection' => true,
    ],
    
    // Localization settings
    'locale' => [
        'default' => 'es',
        'fallback' => 'en',
        'available' => ['es', 'en', 'it'],
        'rtl_languages' => [],
    ],
    
    // Pagination settings
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],
    
    // File upload settings
    'uploads' => [
        'max_size' => 2048, // KB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'path' => __DIR__ . '/../storage/uploads',
    ],
    
    // API settings
    'api' => [
        'version' => 'v1',
        'rate_limit' => 60, // requests per minute
        'throttle' => true,
    ],
    
    // School settings
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
    
    // Feature flags
    'features' => [
        'notifications' => true,
        'reports' => true,
        'calendar' => true,
        'file_uploads' => true,
        'api_access' => true,
        'maintenance_mode' => false,
    ],
];
