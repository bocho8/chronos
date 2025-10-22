<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

namespace App\Controllers;

class ErrorController
{
    /**
     * Show 404 Not Found error page
     */
    public function show404()
    {
        http_response_code(404);
        require __DIR__ . '/../../views/errors/404.php';
    }
    
    /**
     * Show 500 Internal Server Error page
     */
    public function show500($message = null)
    {
        http_response_code(500);
        require __DIR__ . '/../../views/errors/500.php';
    }
    
    /**
     * Show 403 Forbidden error page
     */
    public function show403()
    {
        http_response_code(403);
        require __DIR__ . '/../../views/errors/403.php';
    }
    
    /**
     * Show file not found error page
     */
    public function showFileNotFound($filePath = null)
    {
        http_response_code(404);
        if ($filePath) {
            $_GET['file'] = $filePath;
        }
        require __DIR__ . '/../../views/errors/file-not-found.php';
    }
    
    /**
     * Show generic error page
     */
    public function showError($statusCode = 500, $message = null)
    {
        http_response_code($statusCode);
        
        switch ($statusCode) {
            case 404:
                $this->show404();
                break;
            case 403:
                $this->show403();
                break;
            case 500:
            default:
                $this->show500($message);
                break;
        }
    }
}
