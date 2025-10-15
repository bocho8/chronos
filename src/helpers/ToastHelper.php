<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Toast Helper for Chronos System
 * Helper para mostrar notificaciones toast desde PHP
 */

class ToastHelper {
    
    /**
     * Show success toast
     */
    public static function success($message) {
        self::addToast($message, 'success');
    }
    
    /**
     * Show error toast
     */
    public static function error($message) {
        self::addToast($message, 'error');
    }
    
    /**
     * Show warning toast
     */
    public static function warning($message) {
        self::addToast($message, 'warning');
    }
    
    /**
     * Show info toast
     */
    public static function info($message) {
        self::addToast($message, 'info');
    }
    
    /**
     * Add toast to session for display on next page load
     */
    private static function addToast($message, $type) {
        if (!isset($_SESSION['toasts'])) {
            $_SESSION['toasts'] = [];
        }
        
        $_SESSION['toasts'][] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
    }
    
    /**
     * Get and clear toasts from session
     */
    public static function getToasts() {
        $toasts = $_SESSION['toasts'] ?? [];
        $_SESSION['toasts'] = [];
        return $toasts;
    }
    
    /**
     * Render toast container and JavaScript
     */
    public static function render() {
        $toasts = self::getToasts();
        
        if (empty($toasts)) {
            return '';
        }
        
        $html = '<div id="toastContainer"></div>';
        $html .= '<script>';
        
        foreach ($toasts as $toast) {
            $message = htmlspecialchars($toast['message'], ENT_QUOTES, 'UTF-8');
            $type = htmlspecialchars($toast['type'], ENT_QUOTES, 'UTF-8');
            
            $html .= "toastManager.{$type}('{$message}');";
        }
        
        $html .= '</script>';
        
        return $html;
    }
    
    /**
     * Show toast immediately (for AJAX responses)
     */
    public static function showImmediate($message, $type = 'info') {
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        
        echo "<script>toastManager.{$type}('{$message}');</script>";
    }
}
