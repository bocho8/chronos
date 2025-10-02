<?php

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/Translation.php';

class AsignacionController {
    private $db;
    private $translation;
    
    public function __construct($database) {
        $this->db = $database;
        $this->translation = Translation::getInstance();
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'list';
        
        try {
            match ($action) {
                'create' => $this->createAsignacion(),
                'delete' => $this->deleteAsignacion(),
                'list' => $this->listAsignaciones(),
                default => $this->listAsignaciones()
            };
        } catch (Exception $e) {
            error_log("Error in AsignacionController: " . $e->getMessage());
            ResponseHelper::error('Error interno del servidor', null, 500);
        }
    }
    
    private function createAsignacion() {
        $id_docente = $_POST['id_docente'] ?? null;
        $id_materia = $_POST['id_materia'] ?? null;
        
        // Validation
        if (!$id_docente || !$id_materia) {
            ResponseHelper::error('ID de docente y materia son requeridos');
        }
        
        // Check if assignment already exists
        $checkQuery = "SELECT COUNT(*) FROM docente_materia WHERE id_docente = ? AND id_materia = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$id_docente, $id_materia]);
        
        if ($checkStmt->fetchColumn() > 0) {
            ResponseHelper::error('Esta asignación ya existe');
        }
        
        // Create assignment
        $insertQuery = "INSERT INTO docente_materia (id_docente, id_materia) VALUES (?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        
        if ($insertStmt->execute([$id_docente, $id_materia])) {
            // Log the action
            $this->logActivity("Asignó docente ID $id_docente a materia ID $id_materia");
            ResponseHelper::success('Asignación creada exitosamente');
        } else {
            ResponseHelper::error('Error creando la asignación');
        }
    }
    
    private function deleteAsignacion() {
        $id_docente = $_POST['id_docente'] ?? $_GET['id_docente'] ?? null;
        $id_materia = $_POST['id_materia'] ?? $_GET['id_materia'] ?? null;
        
        if (!$id_docente || !$id_materia) {
            ResponseHelper::error('ID de docente y materia son requeridos');
        }
        
        $deleteQuery = "DELETE FROM docente_materia WHERE id_docente = ? AND id_materia = ?";
        $deleteStmt = $this->db->prepare($deleteQuery);
        
        if ($deleteStmt->execute([$id_docente, $id_materia])) {
            // Log the action
            $this->logActivity("Eliminó asignación de docente ID $id_docente a materia ID $id_materia");
            ResponseHelper::success('Asignación eliminada exitosamente');
        } else {
            ResponseHelper::error('Error eliminando la asignación');
        }
    }
    
    private function listAsignaciones() {
        $query = "
            SELECT dm.*, 
                   d.id_docente, 
                   u.nombre, u.apellido, 
                   m.id_materia, m.nombre as materia_nombre 
            FROM docente_materia dm
            JOIN docente d ON dm.id_docente = d.id_docente
            JOIN usuario u ON d.id_usuario = u.id_usuario
            JOIN materia m ON dm.id_materia = m.id_materia
            ORDER BY u.apellido, u.nombre, m.nombre
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ResponseHelper::success('Asignaciones obtenidas exitosamente', $asignaciones);
    }
    
    private function logActivity($action) {
        try {
            $logQuery = "INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())";
            $logStmt = $this->db->prepare($logQuery);
            $logStmt->execute([
                $_SESSION['user']['id_usuario'],
                $action
            ]);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}

