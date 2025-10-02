<?php

namespace App\Middleware;

require_once __DIR__ . '/../../helpers/AuthHelper.php';

class AuthMiddleware
{
    /**
     * Check if user is authenticated
     */
    public static function handle()
    {
        // Initialize session if not already done
        if (session_status() === PHP_SESSION_NONE) {
            require_once __DIR__ . '/../../config/session.php';
            initSecureSession();
        }
        
        // Check if user is logged in
        if (!AuthHelper::isLoggedIn()) {
            if (self::isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized', 'redirect' => '/login']);
                return false;
            } else {
                header('Location: /login');
                exit();
            }
        }
        
        // Check session timeout
        if (!AuthHelper::checkSessionTimeout()) {
            if (self::isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['error' => 'Session expired', 'redirect' => '/login']);
                return false;
            } else {
                header('Location: /login');
                exit();
            }
        }
        
        return true;
    }
    
    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
