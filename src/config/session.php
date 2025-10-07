<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

define('SESSION_TIMEOUT', 30);
define('SESSION_WARNING_TIME', 5);
define('SESSION_REGENERATE_INTERVAL', 15);
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CHRONOS_SESSION');
        
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT * 60,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
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
