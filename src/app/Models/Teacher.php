<?php

namespace App\Models;

require_once __DIR__ . '/../../helpers/ResponseHelper.php';

use PDO;
use PDOException;
use Exception;

class Teacher
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Get all teachers
     */
    public function getAllTeachers()
    {
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
            error_log("Error getting teachers: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get teacher by ID
     */
    public function getTeacherById($id)
    {
        try {
            $query = "SELECT d.id_docente, d.trabaja_otro_liceo, d.fecha_envio_disponibilidad, 
                             d.horas_asignadas, d.porcentaje_margen,
                             u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono
                      FROM docente d
                      INNER JOIN usuario u ON d.id_usuario = u.id_usuario
                      WHERE d.id_docente = :id_docente";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id_docente', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting teacher by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get teacher by cedula
     */
    public function getTeacherByCedula($cedula)
    {
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
            error_log("Error getting teacher by cedula: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new teacher
     */
    public function createTeacher($teacherData)
    {
        try {
            if (empty($teacherData['cedula']) || empty($teacherData['nombre']) || 
                empty($teacherData['apellido']) || empty($teacherData['contrasena'])) {
                return false;
            }
            
            if (!preg_match('/^\d{7,8}$/', $teacherData['cedula'])) {
                return false;
            }
            
            if ($this->cedulaExists($teacherData['cedula'])) {
                return false;
            }

            $passwordHash = password_hash($teacherData['contrasena'], PASSWORD_DEFAULT);
            
            $this->db->beginTransaction();
            
            try {

                $userQuery = "INSERT INTO usuario (cedula, nombre, apellido, email, telefono, contrasena_hash) 
                              VALUES (:cedula, :nombre, :apellido, :email, :telefono, :contrasena_hash)";
                
                $userStmt = $this->db->prepare($userQuery);
                $userStmt->bindValue(':cedula', $teacherData['cedula'], PDO::PARAM_STR);
                $userStmt->bindValue(':nombre', $teacherData['nombre'], PDO::PARAM_STR);
                $userStmt->bindValue(':apellido', $teacherData['apellido'], PDO::PARAM_STR);
                $userStmt->bindValue(':email', $teacherData['email'], PDO::PARAM_STR);
                $userStmt->bindValue(':telefono', $teacherData['telefono'], PDO::PARAM_STR);
                $userStmt->bindValue(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
                
                $userStmt->execute();
                $userId = $this->db->lastInsertId();

                $roleQuery = "INSERT INTO usuario_rol (id_usuario, nombre_rol) VALUES (:id_usuario, 'DOCENTE')";
                $roleStmt = $this->db->prepare($roleQuery);
                $roleStmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
                $roleStmt->execute();

                $teacherQuery = "INSERT INTO docente (id_usuario, trabaja_otro_liceo, fecha_envio_disponibilidad, 
                                                     horas_asignadas, porcentaje_margen) 
                                 VALUES (:id_usuario, :trabaja_otro_liceo, :fecha_envio_disponibilidad, 
                                         :horas_asignadas, :porcentaje_margen)";
                
                $teacherStmt = $this->db->prepare($teacherQuery);
                $teacherStmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
                $teacherStmt->bindValue(':trabaja_otro_liceo', $teacherData['trabaja_otro_liceo'] ?? false, PDO::PARAM_BOOL);
                $teacherStmt->bindValue(':fecha_envio_disponibilidad', $teacherData['fecha_envio_disponibilidad'], PDO::PARAM_STR);
                $teacherStmt->bindValue(':horas_asignadas', $teacherData['horas_asignadas'] ?? 0, PDO::PARAM_INT);
                $teacherStmt->bindValue(':porcentaje_margen', $teacherData['porcentaje_margen'] ?? 0.00, PDO::PARAM_STR);
                
                $teacherStmt->execute();
                $teacherId = $this->db->lastInsertId();

                $this->db->commit();
                
                return $teacherId;
                
            } catch (Exception $e) {

                $this->db->rollback();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error creating teacher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update teacher information
     */
    public function updateTeacher($id, $teacherData)
    {
        try {
            $currentTeacher = $this->getTeacherById($id);
            if (!$currentTeacher) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            try {
                $userQuery = "UPDATE usuario SET 
                              nombre = :nombre, 
                              apellido = :apellido, 
                              email = :email, 
                              telefono = :telefono";
                
                if (!empty($teacherData['contrasena'])) {
                    $userQuery .= ", contrasena_hash = :contrasena_hash";
                }
                
                $userQuery .= " WHERE id_usuario = :id_usuario";
                
                $userStmt = $this->db->prepare($userQuery);
                $userStmt->bindValue(':nombre', $teacherData['nombre'], PDO::PARAM_STR);
                $userStmt->bindValue(':apellido', $teacherData['apellido'], PDO::PARAM_STR);
                $userStmt->bindValue(':email', $teacherData['email'], PDO::PARAM_STR);
                $userStmt->bindValue(':telefono', $teacherData['telefono'], PDO::PARAM_STR);
                $userStmt->bindValue(':id_usuario', $currentTeacher['id_usuario'], PDO::PARAM_INT);
                
                if (!empty($teacherData['contrasena'])) {
                    $userStmt->bindValue(':contrasena_hash', password_hash($teacherData['contrasena'], PASSWORD_DEFAULT), PDO::PARAM_STR);
                }
                
                $userStmt->execute();
                
                $teacherQuery = "UPDATE docente SET 
                                trabaja_otro_liceo = :trabaja_otro_liceo, 
                                fecha_envio_disponibilidad = :fecha_envio_disponibilidad,
                                horas_asignadas = :horas_asignadas, 
                                porcentaje_margen = :porcentaje_margen
                                WHERE id_docente = :id_docente";
                
                $teacherStmt = $this->db->prepare($teacherQuery);
                $teacherStmt->bindValue(':trabaja_otro_liceo', $teacherData['trabaja_otro_liceo'] ?? false, PDO::PARAM_BOOL);
                $teacherStmt->bindValue(':fecha_envio_disponibilidad', $teacherData['fecha_envio_disponibilidad'], PDO::PARAM_STR);
                $teacherStmt->bindValue(':horas_asignadas', $teacherData['horas_asignadas'] ?? 0, PDO::PARAM_INT);
                $teacherStmt->bindValue(':porcentaje_margen', $teacherData['porcentaje_margen'] ?? 0.00, PDO::PARAM_STR);
                $teacherStmt->bindValue(':id_docente', $id, PDO::PARAM_INT);
                
                $result = $teacherStmt->execute();

                $this->db->commit();
                
                return $result;
                
            } catch (Exception $e) {

                $this->db->rollback();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error updating teacher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete teacher
     */
    public function deleteTeacher($id)
    {
        try {
            $teacher = $this->getTeacherById($id);
            if (!$teacher) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            try {
                $query = "DELETE FROM docente WHERE id_docente = :id_docente";
                $stmt = $this->db->prepare($query);
                $stmt->bindValue(':id_docente', $id, PDO::PARAM_INT);
                
                $result = $stmt->execute();

                $this->db->commit();
                
                return $result;
                
            } catch (Exception $e) {

                $this->db->rollback();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error deleting teacher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search teachers by name or cedula
     */
    public function searchTeachers($searchTerm)
    {
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
            error_log("Error searching teachers: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get teachers count
     */
    public function getTeachersCount()
    {
        try {
            $query = "SELECT COUNT(*) FROM docente";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Error counting teachers: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent teachers
     */
    public function getRecentTeachers($limit = 5)
    {
        try {
            $query = "SELECT d.id_docente, 
                             u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono
                      FROM docente d
                      INNER JOIN usuario u ON d.id_usuario = u.id_usuario
                      ORDER BY d.id_docente DESC
                      LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting recent teachers: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if cedula already exists
     */
    private function cedulaExists($cedula)
    {
        try {
            $query = "SELECT COUNT(*) FROM usuario WHERE cedula = :cedula";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Error checking cedula: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log user activity
     */
    public function logActivity($action)
    {
        try {
            $query = "INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $_SESSION['user']['id_usuario'],
                $action
            ]);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
