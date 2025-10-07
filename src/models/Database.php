<?php

class Database {
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $pdo;
    
    public function __construct($config = []) {
        $this->host = $config['host'] ?? 'localhost';
        $this->port = $config['port'] ?? '5432';
        $this->dbname = $config['dbname'] ?? 'chronos';
        $this->username = $config['username'] ?? 'postgres';
        $this->password = $config['password'] ?? '';
    }
    
    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch (PDOException $e) {
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                throw new PDOException("No se pudo conectar a la base de datos: " . $e->getMessage());
            }
        }
        
        return $this->pdo;
    }
    
    public function closeConnection() {
        $this->pdo = null;
    }
    
    public function testConnection() {
        try {
            $pdo = $this->getConnection();
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log("Error probando conexión: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get database information
     * 
     * @return array Database information
     */
    public function getDatabaseInfo() {
        try {
            $pdo = $this->getConnection();
            
            $version = $pdo->query('SELECT version()')->fetchColumn();
            
            $currentDb = $pdo->query('SELECT current_database()')->fetchColumn();
            
            $currentUser = $pdo->query('SELECT current_user')->fetchColumn();
            
            return [
                'version' => $version,
                'database' => $currentDb,
                'user' => $currentUser,
                'host' => $this->host,
                'port' => $this->port
            ];
            
        } catch (PDOException $e) {
            error_log("Error obteniendo información de la base de datos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute a query and return results
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array|false Query results or false on error
     */
    public function query($query, $params = []) {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error ejecutando query: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute a query and return single row
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array|false Single row or false on error
     */
    public function querySingle($query, $params = []) {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Error ejecutando query: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute a query and return count
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return int|false Count or false on error
     */
    public function queryCount($query, $params = []) {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Error ejecutando query: " . $e->getMessage());
            return false;
        }
    }
}
