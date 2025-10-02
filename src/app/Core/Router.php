<?php

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
        
        // Remove base path if exists
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $path = substr($path, strlen($basePath));
        }
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                return $this->executeRoute($route, $path);
            }
        }
        
        // No route found
        $this->handleNotFound();
    }
    
    /**
     * Check if route matches
     */
    private function matchRoute($route, $method, $path)
    {
        if ($route['method'] !== $method) {
            return false;
        }
        
        // Convert route pattern to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $path);
    }
    
    /**
     * Execute the route
     */
    private function executeRoute($route, $path)
    {
        // Extract parameters
        $params = $this->extractParams($route['path'], $path);
        
        // Execute middleware
        foreach ($route['middleware'] as $middlewareName) {
            if (isset($this->middleware[$middlewareName])) {
                $result = call_user_func($this->middleware[$middlewareName]);
                if ($result === false) {
                    return; // Middleware blocked the request
                }
            }
        }
        
        // Execute handler
        $handler = $route['handler'];
        
        if (is_string($handler)) {
            // Format: "Controller@method"
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
        
        // Convert route pattern to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $actualPath, $matches)) {
            array_shift($matches); // Remove full match
            $params = $matches;
        }
        
        return $params;
    }
    
    /**
     * Handle 404 Not Found
     */
    private function handleNotFound()
    {
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }
    
    /**
     * Get database connection
     */
    private function getDatabase()
    {
        static $database = null;
        
        if ($database === null) {
            $dbConfig = require __DIR__ . '/../../config/database.php';
            $database = new \App\Models\Database($dbConfig);
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
