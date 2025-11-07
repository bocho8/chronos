<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

namespace App\Middleware;

require_once __DIR__ . '/../../helpers/AuthHelper.php';

class RoleMiddleware
{
    /**
     * Check if user has required role
     */
    public static function handle($requiredRole)
    {
        if (!AuthMiddleware::handle()) {
            return false;
        }
        
        // Always allow admin access to everything
        if (\AuthHelper::hasRole('ADMIN')) {
            return true;
        }
        
        // Allow director access when admin is required
        if ($requiredRole === 'ADMIN' && \AuthHelper::hasRole('DIRECTOR')) {
            return true;
        }
        
        if (!\AuthHelper::hasRole($requiredRole)) {
            if (self::isAjaxRequest()) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden', 'message' => 'Insufficient permissions']);
                return false;
            } else {
                header('Location: /unauthorized');
                exit();
            }
        }
        
        return true;
    }
    
    /**
     * Create role-specific middleware
     */
    public static function admin()
    {
        return function() {
            return self::handle('ADMIN');
        };
    }
    
    public static function director()
    {
        return function() {
            return self::handle('DIRECTOR');
        };
    }
    
    public static function coordinator()
    {
        return function() {
            return self::handle('COORDINADOR');
        };
    }
    
    public static function teacher()
    {
        return function() {
            return self::handle('DOCENTE');
        };
    }
    
    public static function parent()
    {
        return function() {
            return self::handle('PADRE');
        };
    }
    
    /**
     * Middleware for routes accessible by admin or director
     */
    public static function adminOrDirector()
    {
        return function() {
            if (!AuthMiddleware::handle()) {
                return false;
            }
            
            // Allow admin or director access
            if (\AuthHelper::hasRole('ADMIN') || \AuthHelper::hasRole('DIRECTOR')) {
                return true;
            }
            
            if (self::isAjaxRequest()) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden', 'message' => 'Insufficient permissions']);
                return false;
            } else {
                header('Location: /unauthorized');
                exit();
            }
        };
    }
    
    /**
     * Middleware for routes accessible by admin or coordinator
     */
    public static function adminOrCoordinator()
    {
        return function() {
            if (!AuthMiddleware::handle()) {
                return false;
            }
            
            // Allow admin access (admin already has access to everything)
            if (\AuthHelper::hasRole('ADMIN') || \AuthHelper::hasRole('DIRECTOR')) {
                return true;
            }
            
            // Allow coordinator access
            if (\AuthHelper::hasRole('COORDINADOR')) {
                return true;
            }
            
            if (self::isAjaxRequest()) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden', 'message' => 'Insufficient permissions']);
                return false;
            } else {
                header('Location: /unauthorized');
                exit();
            }
        };
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
