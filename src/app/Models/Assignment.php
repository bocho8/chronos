<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

namespace App\Models;

require_once __DIR__ . '/../../helpers/ResponseHelper.php';

use PDO;
use Exception;

class Assignment
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Get all assignments with teacher and subject details
     */
    public function getAllAssignments()
    {
        $query = "
            SELECT dm.id_docente, dm.id_materia,
                   CONCAT(dm.id_docente, '_', dm.id_materia) as id,
                   u.nombre, u.apellido, 
                   m.nombre as materia_nombre 
            FROM docente_materia dm
            JOIN docente d ON dm.id_docente = d.id_docente
            JOIN usuario u ON d.id_usuario = u.id_usuario
            JOIN materia m ON dm.id_materia = m.id_materia
            ORDER BY u.apellido, u.nombre, m.nombre
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get assignment by ID
     */
    public function getAssignmentById($id)
    {
        $query = "
            SELECT dm.*, 
                   d.id_docente, 
                   u.nombre, u.apellido, 
                   m.id_materia, m.nombre as materia_nombre 
            FROM docente_materia dm
            JOIN docente d ON dm.id_docente = d.id_docente
            JOIN usuario u ON d.id_usuario = u.id_usuario
            JOIN materia m ON dm.id_materia = m.id_materia
            WHERE dm.id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if assignment already exists
     */
    public function assignmentExists($teacherId, $subjectId)
    {
        $query = "SELECT COUNT(*) FROM docente_materia WHERE id_docente = ? AND id_materia = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$teacherId, $subjectId]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Create new assignment
     */
    public function createAssignment($teacherId, $subjectId)
    {
        $query = "INSERT INTO docente_materia (id_docente, id_materia) VALUES (?, ?)";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute([$teacherId, $subjectId])) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update assignment
     */
    public function updateAssignment($id, $teacherId, $subjectId)
    {
        $query = "UPDATE docente_materia SET id_docente = ?, id_materia = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([$teacherId, $subjectId, $id]);
    }
    
    /**
     * Delete assignment
     */
    public function deleteAssignment($id)
    {
        // Parse the composite ID (format: "teacherId_materiaId")
        list($teacherId, $materiaId) = explode('_', $id);
        
        $query = "DELETE FROM docente_materia WHERE id_docente = ? AND id_materia = ?";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([$teacherId, $materiaId]);
    }
    
    /**
     * Get available teachers for assignment
     */
    public function getAvailableTeachers()
    {
        $query = "
            SELECT d.id_docente, u.nombre, u.apellido, u.cedula
            FROM docente d
            JOIN usuario u ON d.id_usuario = u.id_usuario
            WHERE d.activo = 1
            ORDER BY u.apellido, u.nombre
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get available subjects for assignment
     */
    public function getAvailableSubjects()
    {
        $query = "
            SELECT id_materia, nombre, codigo
            FROM materia
            WHERE activo = 1
            ORDER BY nombre
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get assignments by teacher
     */
    public function getAssignmentsByTeacher($teacherId)
    {
        $query = "
            SELECT dm.id_docente, dm.id_materia, 
                   m.nombre as materia_nombre,
                   CONCAT(dm.id_docente, '_', dm.id_materia) as id
            FROM docente_materia dm
            JOIN materia m ON dm.id_materia = m.id_materia
            WHERE dm.id_docente = ?
            ORDER BY m.nombre
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$teacherId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get assignments by subject
     */
    public function getAssignmentsBySubject($subjectId)
    {
        $query = "
            SELECT dm.*, u.nombre, u.apellido, u.cedula
            FROM docente_materia dm
            JOIN docente d ON dm.id_docente = d.id_docente
            JOIN usuario u ON d.id_usuario = u.id_usuario
            WHERE dm.id_materia = ?
            ORDER BY u.apellido, u.nombre
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$subjectId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Log user activity
     */
    public function logActivity($action)
    {
        $query = "INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $_SESSION['user']['id_usuario'],
            $action
        ]);
    }
}
