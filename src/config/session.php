<?php
/**
 * Session Security Configuration
 * Centralized session security settings
 */

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Session timeout settings (in minutes)
define('SESSION_TIMEOUT', 30);
define('SESSION_WARNING_TIME', 5); // Warning 5 minutes before timeout

// Session regeneration settings
define('SESSION_REGENERATE_INTERVAL', 15); // Regenerate session ID every 15 minutes

/**
 * Initialize secure session
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set session name
        session_name('CHRONOS_SESSION');
        
        // Set session parameters
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT * 60, // Convert to seconds
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        // Start session
        session_start();
        
        // Regenerate session ID if needed
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > SESSION_REGENERATE_INTERVAL * 60) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Set initial activity time
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        }
    }
}

/**
 * Check if session is valid and not expired
 * 
 * @return bool True if session is valid, false otherwise
 */
function isSessionValid() {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        return false;
    }
    
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    $timeout = SESSION_TIMEOUT * 60; // Convert to seconds
    return (time() - $_SESSION['last_activity']) <= $timeout;
}

/**
 * Update last activity timestamp
 */
function updateLastActivity() {
    $_SESSION['last_activity'] = time();
}

/**
 * Clean up expired session
 */
function cleanupExpiredSession() {
    if (isset($_SESSION['logged_in']) && !isSessionValid()) {
        session_unset();
        session_destroy();
        return true;
    }
    return false;
}

/**
 * Get session timeout warning time in seconds
 * 
 * @return int Seconds until session expires
 */
function getSessionTimeoutWarning() {
    if (!isset($_SESSION['last_activity'])) {
        return 0;
    }
    
    $elapsed = time() - $_SESSION['last_activity'];
    $timeout = SESSION_TIMEOUT * 60;
    $warning = SESSION_WARNING_TIME * 60;
    
    return max(0, $timeout - $elapsed - $warning);
}
