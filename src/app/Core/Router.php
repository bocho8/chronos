<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

namespace App\Core;

class Router
{
    private $routes = [];
    private $middleware = [];
    private $groupMiddleware = [];
    private $groupPrefix = '';
    
    /**
     * Add a GET route
     */
    public function get($path, $handler, $middleware = [])
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    /**
     * Add a POST route
     */
    public function post($path, $handler, $middleware = [])
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    /**
     * Add a PUT route
     */
    public function put($path, $handler, $middleware = [])
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }
    
    /**
     * Add a DELETE route
     */
    public function delete($path, $handler, $middleware = [])
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }
    
    /**
     * Add a PATCH route
     */
    public function patch($path, $handler, $middleware = [])
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }
    
    /**
     * Create a route group
     */
    public function group($options, $callback)
    {
        $oldPrefix = $this->groupPrefix;
        $oldMiddleware = $this->groupMiddleware;
        
        if (isset($options['prefix'])) {
            $this->groupPrefix = $oldPrefix . $options['prefix'];
        }
        
        if (isset($options['middleware'])) {
            $this->groupMiddleware = array_merge($oldMiddleware, $options['middleware']);
        }
        
        $callback($this);
        
        $this->groupPrefix = $oldPrefix;
        $this->groupMiddleware = $oldMiddleware;
    }
    
    /**
     * Add middleware
     */
    public function middleware($name, $callback)
    {
        $this->middleware[$name] = $callback;
    }
    
    /**
     * Add a route
     */
    private function addRoute($method, $path, $handler, $middleware = [])
    {
        $fullPath = $this->groupPrefix . $path;
        $fullMiddleware = array_merge($this->groupMiddleware, $middleware);
        
        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $fullMiddleware
        ];
    }
    
    /**
     * Dispatch the request
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $path = substr($path, strlen($basePath));
        }
        
        // Handle language switching before processing other routes
        if ($method === 'POST' && $this->handleLanguageSwitching()) {
            return;
        }
        
        // Check for missing static files
        if ($this->handleMissingFiles($path)) {
            return;
        }
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                return $this->executeRoute($route, $path);
            }
        }
        
        $this->handleNotFound();
    }
    
    /**
     * Handle language switching requests
     */
    private function handleLanguageSwitching()
    {
        if (isset($_POST['change_language'])) {
            // Initialize session with the same configuration as the login page
            if (session_status() === PHP_SESSION_NONE) {
                require_once __DIR__ . '/../../config/session.php';
                initSecureSession();
            }
            
            require_once __DIR__ . '/../../components/LanguageSwitcher.php';
            $languageSwitcher = new \LanguageSwitcher();
            
            if ($languageSwitcher->handleLanguageChange()) {
                // Use a simple HTML redirect to avoid header conflicts
                echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . $_SERVER['REQUEST_URI'] . '"></head><body>Redirecting...</body></html>';
                exit();
            }
            return true;
        }
        return false;
    }
    
    /**
     * Handle missing static files
     */
    private function handleMissingFiles($path)
    {
        require_once __DIR__ . '/../../helpers/FileChecker.php';
        
        // Check if it's a request for a static file
        if (\FileChecker::isStaticFile($path)) {
            // Check if the file exists
            if (!\FileChecker::checkFile($path)) {
                // File doesn't exist, show file not found page
                $errorController = new \App\Controllers\ErrorController();
                $errorController->showFileNotFound($path);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if route matches
     */
    private function matchRoute($route, $method, $path)
    {
        if ($route['method'] !== $method) {
            return false;
        }
        
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $path);
    }
    
    /**
     * Execute the route
     */
    private function executeRoute($route, $path)
    {
        $params = $this->extractParams($route['path'], $path);
        
        foreach ($route['middleware'] as $middlewareName) {
            if (isset($this->middleware[$middlewareName])) {
                $result = call_user_func($this->middleware[$middlewareName]);
                if ($result === false) {
                    return;
                }
            }
        }
        
        $handler = $route['handler'];
        
        if (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);
            $controllerClass = "App\\Controllers\\{$controller}";
            
            if (class_exists($controllerClass)) {
                $instance = new $controllerClass($this->getDatabase());
                if (method_exists($instance, $method)) {
                    call_user_func_array([$instance, $method], $params);
                } else {
                    $this->handleNotFound();
                }
            } else {
                $this->handleNotFound();
            }
        } elseif (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } else {
            $this->handleNotFound();
        }
    }
    
    /**
     * Extract parameters from path
     */
    private function extractParams($routePath, $actualPath)
    {
        $params = [];
        
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $actualPath, $matches)) {
            array_shift($matches);
            $params = $matches;
        }
        
        return $params;
    }
    
    /**
     * Handle 404 Not Found
     */
    private function handleNotFound()
    {
        require_once __DIR__ . '/../Controllers/ErrorController.php';
        $errorController = new \App\Controllers\ErrorController();
        $errorController->show404();
    }
    
    /**
     * Handle 500 Internal Server Error
     */
    private function handleServerError($message = null)
    {
        require_once __DIR__ . '/../Controllers/ErrorController.php';
        $errorController = new \App\Controllers\ErrorController();
        $errorController->show500($message);
    }
    
    /**
     * Handle 403 Forbidden
     */
    private function handleForbidden()
    {
        require_once __DIR__ . '/../Controllers/ErrorController.php';
        $errorController = new \App\Controllers\ErrorController();
        $errorController->show403();
    }
    
    /**
     * Get database connection
     */
    private function getDatabase()
    {
        static $database = null;
        
        if ($database === null) {
            $dbConfig = require __DIR__ . '/../../config/database.php';
            $database = new \Database($dbConfig);
        }
        
        return $database->getConnection();
    }
    
    /**
     * Load routes from file
     */
    public function loadRoutes($file)
    {
        if (file_exists($file)) {
            require $file;
        }
    }
}
