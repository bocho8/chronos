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
    public static function success($message, $actions = []) {
        self::addToast($message, 'success', $actions);
    }
    
    /**
     * Show error toast
     */
    public static function error($message, $actions = []) {
        self::addToast($message, 'error', $actions);
    }
    
    /**
     * Show warning toast
     */
    public static function warning($message, $actions = []) {
        self::addToast($message, 'warning', $actions);
    }
    
    /**
     * Show info toast
     */
    public static function info($message, $actions = []) {
        self::addToast($message, 'info', $actions);
    }
    
    /**
     * Add toast to session for display on next page load
     */
    private static function addToast($message, $type, $actions = []) {
        if (!isset($_SESSION['toasts'])) {
            $_SESSION['toasts'] = [];
        }
        
        $_SESSION['toasts'][] = [
            'message' => $message,
            'type' => $type,
            'actions' => $actions,
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
            $actions = isset($toast['actions']) ? $toast['actions'] : [];
            
            if (!empty($actions)) {
                $actionsJson = json_encode($actions);
                $html .= "toastManager.{$type}('{$message}', {actions: {$actionsJson}});";
            } else {
                $html .= "toastManager.{$type}('{$message}');";
            }
        }
        
        $html .= '</script>';
        
        return $html;
    }
    
    /**
     * Show toast immediately (for AJAX responses)
     */
    public static function showImmediate($message, $type = 'info', $actions = []) {
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        
        if (!empty($actions)) {
            $actionsJson = json_encode($actions);
            echo "<script>toastManager.{$type}('{$message}', {actions: {$actionsJson}});</script>";
        } else {
            echo "<script>toastManager.{$type}('{$message}');</script>";
        }
    }

    /**
     * Show success toast with undo action
     */
    public static function successWithUndo($message, $undoText = 'Undo', $undoUrl = null) {
        $actions = [];
        if ($undoUrl) {
            $actions[] = [
                'text' => $undoText,
                'onClick' => "window.location.href = '{$undoUrl}'",
                'style' => 'primary'
            ];
        }
        self::success($message, $actions);
    }

    /**
     * Show error toast with retry action
     */
    public static function errorWithRetry($message, $retryText = 'Retry', $retryUrl = null) {
        $actions = [];
        if ($retryUrl) {
            $actions[] = [
                'text' => $retryText,
                'onClick' => "window.location.href = '{$retryUrl}'",
                'style' => 'primary'
            ];
        }
        self::error($message, $actions);
    }
}
