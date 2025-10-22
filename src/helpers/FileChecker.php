<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

class FileChecker
{
    /**
     * Check if a file exists and is accessible
     */
    public static function checkFile($filePath)
    {
        // Remove query string and fragments
        $filePath = parse_url($filePath, PHP_URL_PATH);
        
        // Get the full file path
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
        
        // Check if file exists and is readable
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            return false;
        }
        
        // Check if it's a file (not a directory)
        if (!is_file($fullPath)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Handle file not found by redirecting to error page
     */
    public static function handleFileNotFound($requestedPath)
    {
        $errorController = new \App\Controllers\ErrorController();
        $errorController->showFileNotFound($requestedPath);
        exit();
    }
    
    /**
     * Check if the request is for a static file
     */
    public static function isStaticFile($path)
    {
        $staticExtensions = [
            'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 
            'woff', 'woff2', 'ttf', 'eot', 'pdf', 'txt', 'xml', 'json'
        ];
        
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), $staticExtensions);
    }
}
