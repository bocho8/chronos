<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

define('SESSION_TIMEOUT', 30); // ESRE RF065: 30-minute timeout with 5-minute warning
define('SESSION_WARNING_TIME', 5);
define('SESSION_REGENERATE_INTERVAL', 15);
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CHRONOS_SESSION');
        
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT * 60,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to false for HTTP in Docker
            'httponly' => true,
            'samesite' => 'Lax' // Changed from Strict to Lax for better compatibility
        ]);
        
        session_start();
        
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > SESSION_REGENERATE_INTERVAL * 60) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        }
    }
}

function isSessionValid() {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        return false;
    }
    
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    $timeout = SESSION_TIMEOUT * 60;
    return (time() - $_SESSION['last_activity']) <= $timeout;
}

function updateLastActivity() {
    $_SESSION['last_activity'] = time();
}

function cleanupExpiredSession() {
    if (isset($_SESSION['logged_in']) && !isSessionValid()) {
        session_unset();
        session_destroy();
        return true;
    }
    return false;
}

function getSessionTimeoutWarning() {
    if (!isset($_SESSION['last_activity'])) {
        return 0;
    }
    
    $elapsed = time() - $_SESSION['last_activity'];
    $timeout = SESSION_TIMEOUT * 60;
    $warning = SESSION_WARNING_TIME * 60;
    
    return max(0, $timeout - $elapsed - $warning);
}
