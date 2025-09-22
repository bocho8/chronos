<?php

class Docente {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAllDocentes() {
        try {
            $query = "SELECT d.id_docente, d.trabaja_otro_liceo, d.fecha_envio_disponibilidad, 
                             d.horas_asignadas, d.porcentaje_margen,
                             u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono
                      FROM docente d
                      INNER JOIN usuario u ON d.id_usuario = u.id_usuario
                      ORDER BY u.apellido, u.nombre";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo docentes: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDocenteById($id_docente) {
        try {
            $query = "SELECT d.id_docente, d.trabaja_otro_liceo, d.fecha_envio_disponibilidad, 
                             d.horas_asignadas, d.porcentaje_margen,
                             u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono
                      FROM docente d
                      INNER JOIN usuario u ON d.id_usuario = u.id_usuario
                      WHERE d.id_docente = :id_docente";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id_docente', $id_docente, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo docente por ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get teacher by cedula
     * 
     * @param string $cedula Teacher's cedula
     * @return array|false Teacher data or false on error
     */
    public function getDocenteByCedula($cedula) {
        try {
            $query = "SELECT d.id_docente, d.trabaja_otro_liceo, d.fecha_envio_disponibilidad, 
                             d.horas_asignadas, d.porcentaje_margen,
                             u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono
                      FROM docente d
                      INNER JOIN usuario u ON d.id_usuario = u.id_usuario
                      WHERE u.cedula = :cedula";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo docente por cédula: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new teacher
     * 
     * @param array $docenteData Teacher data
     * @return int|false Teacher ID if successful, false otherwise
     */
    public function createDocente($docenteData) {
        try {
            // Validate required fields
            if (empty($docenteData['cedula']) || empty($docenteData['nombre']) || 
                empty($docenteData['apellido']) || empty($docenteData['contrasena'])) {
                return false;
            }
            
            // Validate cedula format
            if (!preg_match('/^\d{7,8}$/', $docenteData['cedula'])) {
                return false;
            }
            
            // Check if cedula already exists
            if ($this->cedulaExists($docenteData['cedula'])) {
                return false;
            }
            
            // Hash password
            $passwordHash = password_hash($docenteData['contrasena'], PASSWORD_DEFAULT);
            
            // Begin transaction
            $this->db->beginTransaction();
            
            try {
                // Insert user first
                $userQuery = "INSERT INTO usuario (cedula, nombre, apellido, email, telefono, contrasena_hash) 
                              VALUES (:cedula, :nombre, :apellido, :email, :telefono, :contrasena_hash)";
                
                $userStmt = $this->db->prepare($userQuery);
                $userStmt->bindValue(':cedula', $docenteData['cedula'], PDO::PARAM_STR);
                $userStmt->bindValue(':nombre', $docenteData['nombre'], PDO::PARAM_STR);
                $userStmt->bindValue(':apellido', $docenteData['apellido'], PDO::PARAM_STR);
                $userStmt->bindValue(':email', $docenteData['email'], PDO::PARAM_STR);
                $userStmt->bindValue(':telefono', $docenteData['telefono'], PDO::PARAM_STR);
                $userStmt->bindValue(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
                
                $userStmt->execute();
                $userId = $this->db->lastInsertId();
                
                // Assign DOCENTE role
                $roleQuery = "INSERT INTO usuario_rol (id_usuario, nombre_rol) VALUES (:id_usuario, 'DOCENTE')";
                $roleStmt = $this->db->prepare($roleQuery);
                $roleStmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
                $roleStmt->execute();
                
                // Insert teacher record
                $docenteQuery = "INSERT INTO docente (id_usuario, trabaja_otro_liceo, fecha_envio_disponibilidad, 
                                                     horas_asignadas, porcentaje_margen) 
                                 VALUES (:id_usuario, :trabaja_otro_liceo, :fecha_envio_disponibilidad, 
                                         :horas_asignadas, :porcentaje_margen)";
                
                $docenteStmt = $this->db->prepare($docenteQuery);
                $docenteStmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
                $docenteStmt->bindValue(':trabaja_otro_liceo', $docenteData['trabaja_otro_liceo'] ?? false, PDO::PARAM_BOOL);
                $docenteStmt->bindValue(':fecha_envio_disponibilidad', $docenteData['fecha_envio_disponibilidad'], PDO::PARAM_STR);
                $docenteStmt->bindValue(':horas_asignadas', $docenteData['horas_asignadas'] ?? 0, PDO::PARAM_INT);
                $docenteStmt->bindValue(':porcentaje_margen', $docenteData['porcentaje_margen'] ?? 0.00, PDO::PARAM_STR);
                
                $docenteStmt->execute();
                $docenteId = $this->db->lastInsertId();
                
                // Commit transaction
                $this->db->commit();
                
                // Log teacher creation
                $this->logAction($docenteData['cedula'], 'DOCENTE_CREADO', 'Nuevo docente creado');
                
                return $docenteId;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $this->db->rollback();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error creando docente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update teacher information
     * 
     * @param int $id_docente Teacher ID
     * @param array $docenteData Updated teacher data
     * @return bool True if successful, false otherwise
     */
    public function updateDocente($id_docente, $docenteData) {
        try {
            // Get current teacher data
            $currentDocente = $this->getDocenteById($id_docente);
            if (!$currentDocente) {
                return false;
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            try {
                // Update user information
                $userQuery = "UPDATE usuario SET 
                              nombre = :nombre, 
                              apellido = :apellido, 
                              email = :email, 
                              telefono = :telefono
                              WHERE id_usuario = :id_usuario";
                
                $userStmt = $this->db->prepare($userQuery);
                $userStmt->bindValue(':nombre', $docenteData['nombre'], PDO::PARAM_STR);
                $userStmt->bindValue(':apellido', $docenteData['apellido'], PDO::PARAM_STR);
                $userStmt->bindValue(':email', $docenteData['email'], PDO::PARAM_STR);
                $userStmt->bindValue(':telefono', $docenteData['telefono'], PDO::PARAM_STR);
                $userStmt->bindValue(':id_usuario', $currentDocente['id_usuario'], PDO::PARAM_INT);
                
                $userStmt->execute();
                
                // Update teacher specific information
                $docenteQuery = "UPDATE docente SET 
                                trabaja_otro_liceo = :trabaja_otro_liceo, 
                                fecha_envio_disponibilidad = :fecha_envio_disponibilidad,
                                horas_asignadas = :horas_asignadas, 
                                porcentaje_margen = :porcentaje_margen
                                WHERE id_docente = :id_docente";
                
                $docenteStmt = $this->db->prepare($docenteQuery);
                $docenteStmt->bindValue(':trabaja_otro_liceo', $docenteData['trabaja_otro_liceo'] ?? false, PDO::PARAM_BOOL);
                $docenteStmt->bindValue(':fecha_envio_disponibilidad', $docenteData['fecha_envio_disponibilidad'], PDO::PARAM_STR);
                $docenteStmt->bindValue(':horas_asignadas', $docenteData['horas_asignadas'] ?? 0, PDO::PARAM_INT);
                $docenteStmt->bindValue(':porcentaje_margen', $docenteData['porcentaje_margen'] ?? 0.00, PDO::PARAM_STR);
                $docenteStmt->bindValue(':id_docente', $id_docente, PDO::PARAM_INT);
                
                $result = $docenteStmt->execute();
                
                // Commit transaction
                $this->db->commit();
                
                if ($result) {
                    // Log teacher update
                    $this->logAction($currentDocente['cedula'], 'DOCENTE_ACTUALIZADO', 'Docente actualizado');
                }
                
                return $result;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $this->db->rollback();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error actualizando docente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete teacher
     * 
     * @param int $id_docente Teacher ID
     * @return bool True if successful, false otherwise
     */
    public function deleteDocente($id_docente) {
        try {
            // Get teacher data before deletion for logging
            $docente = $this->getDocenteById($id_docente);
            if (!$docente) {
                return false;
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            try {
                // Delete teacher record (this will cascade to usuario due to foreign key)
                $query = "DELETE FROM docente WHERE id_docente = :id_docente";
                $stmt = $this->db->prepare($query);
                $stmt->bindValue(':id_docente', $id_docente, PDO::PARAM_INT);
                
                $result = $stmt->execute();
                
                // Commit transaction
                $this->db->commit();
                
                if ($result) {
                    // Log teacher deletion
                    $this->logAction($docente['cedula'], 'DOCENTE_ELIMINADO', 'Docente eliminado');
                }
                
                return $result;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $this->db->rollback();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error eliminando docente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if cedula already exists
     * 
     * @param string $cedula Cedula to check
     * @return bool True if exists, false otherwise
     */
    private function cedulaExists($cedula) {
        try {
            $query = "SELECT COUNT(*) FROM usuario WHERE cedula = :cedula";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Error verificando cédula: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log teacher actions
     * 
     * @param string $cedula Teacher's cedula
     * @param string $accion Action performed
     * @param string $detalle Action details
     * @return bool True if successful, false otherwise
     */
    private function logAction($cedula, $accion, $detalle) {
        try {
            // Get user ID from cedula
            $userQuery = "SELECT id_usuario FROM usuario WHERE cedula = :cedula";
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bindValue(':cedula', $cedula, PDO::PARAM_STR);
            $userStmt->execute();
            $userId = $userStmt->fetchColumn();
            
            if (!$userId) {
                return false;
            }
            
            $query = "INSERT INTO log (id_usuario, accion, detalle) VALUES (:id_usuario, :accion, :detalle)";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':accion', $accion, PDO::PARAM_STR);
            $stmt->bindValue(':detalle', $detalle, PDO::PARAM_STR);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error registrando log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get teachers count
     * 
     * @return int|false Number of teachers or false on error
     */
    public function getDocentesCount() {
        try {
            $query = "SELECT COUNT(*) FROM docente";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Error contando docentes: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search teachers by name or cedula
     * 
     * @param string $searchTerm Search term
     * @return array|false Array of teachers or false on error
     */
    public function searchDocentes($searchTerm) {
        try {
            $query = "SELECT d.id_docente, d.trabaja_otro_liceo, d.fecha_envio_disponibilidad, 
                             d.horas_asignadas, d.porcentaje_margen,
                             u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono
                      FROM docente d
                      INNER JOIN usuario u ON d.id_usuario = u.id_usuario
                      WHERE u.nombre ILIKE :search OR u.apellido ILIKE :search OR u.cedula ILIKE :search
                      ORDER BY u.apellido, u.nombre";
            
            $stmt = $this->db->prepare($query);
            $searchPattern = '%' . $searchTerm . '%';
            $stmt->bindValue(':search', $searchPattern, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error buscando docentes: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new teacher
     * 
     * @param string $cedula Teacher's ID number
     * @param string $nombre Teacher's first name
     * @param string $apellido Teacher's last name
     * @param string $email Teacher's email
     * @param string $telefono Teacher's phone number
     * @param string $password Teacher's password
     * @return bool True on success, false on error
     */
    public function createTeacher($cedula, $nombre, $apellido, $email, $telefono, $password) {
        try {
            $this->db->beginTransaction();
            
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // First, create the user
            $userQuery = "INSERT INTO usuario (cedula, nombre, apellido, email, telefono, password, fecha_creacion) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $userStmt = $this->db->prepare($userQuery);
            $userResult = $userStmt->execute([$cedula, $nombre, $apellido, $email, $telefono, $hashedPassword]);
            
            if (!$userResult) {
                $this->db->rollBack();
                return false;
            }
            
            // Get the newly created user ID
            $userId = $this->db->lastInsertId();
            
            // Create the teacher record
            $teacherQuery = "INSERT INTO docente (id_usuario, trabaja_otro_liceo, fecha_envio_disponibilidad, 
                           horas_asignadas, porcentaje_margen) 
                           VALUES (?, FALSE, NULL, 0, 100.0)";
            
            $teacherStmt = $this->db->prepare($teacherQuery);
            $teacherResult = $teacherStmt->execute([$userId]);
            
            if (!$teacherResult) {
                $this->db->rollBack();
                return false;
            }
            
            // Assign DOCENTE role
            $roleQuery = "INSERT INTO usuario_rol (id_usuario, nombre_rol) VALUES (?, 'DOCENTE')";
            $roleStmt = $this->db->prepare($roleQuery);
            $roleResult = $roleStmt->execute([$userId]);
            
            if (!$roleResult) {
                $this->db->rollBack();
                return false;
            }
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating teacher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent teachers
     * 
     * @param int $limit Number of recent teachers to return
     * @return array|false Array of recent teachers or false on error
     */
    public function getRecentTeachers($limit = 5) {
        try {
            $query = "SELECT d.id_docente, 
                             u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, u.fecha_creacion
                      FROM docente d
                      INNER JOIN usuario u ON d.id_usuario = u.id_usuario
                      ORDER BY u.fecha_creacion DESC
                      LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting recent teachers: " . $e->getMessage());
            return false;
        }
    }
}
