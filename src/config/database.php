<?php

return [
    'host' => 'postgres',
    'port' => '5432',
    'dbname' => 'chronos_db',
    'username' => 'chronos_user',
    'password' => 'chronos_pass',
    
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true
    ]
];
