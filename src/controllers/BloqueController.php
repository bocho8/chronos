<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/Horario.php';

class BloqueController {
    private $horarioModel;
    
    public function __construct($database) {
        $this->horarioModel = new Horario($database);
    }
    
    public function handleRequest($action, $data = []) {
        try {
            switch ($action) {
                case 'get_all':
                    return $this->getAllBloques();
                case 'create':
                    return $this->createBloque($data);
                case 'update':
                    return $this->updateBloque($data);
                case 'delete':
                    return $this->deleteBloque($data);
                default:
                    return ResponseHelper::error('Acción no válida: ' . $action);
            }
        } catch (Exception $e) {
            error_log("Error in BloqueController: " . $e->getMessage());
            return ResponseHelper::error('Error interno del servidor: ' . $e->getMessage());
        }
    }
    
    private function getAllBloques() {
        try {
            $bloques = $this->horarioModel->getAllBloques();
            return ResponseHelper::success('Bloques obtenidos exitosamente', $bloques);
        } catch (Exception $e) {
            error_log("Error getting all bloques: " . $e->getMessage());
            return ResponseHelper::error('Error al obtener los bloques horarios');
        }
    }
    
    private function createBloque($data) {
        // Validate required fields
        if (!isset($data['hora_inicio']) || !isset($data['hora_fin'])) {
            return ResponseHelper::error('Hora de inicio y hora de fin son requeridas');
        }
        
        $hora_inicio = trim($data['hora_inicio']);
        $hora_fin = trim($data['hora_fin']);
        
        // Validate time format
        if (!$this->validateTimeFormat($hora_inicio) || !$this->validateTimeFormat($hora_fin)) {
            return ResponseHelper::error('Formato de hora inválido. Use HH:MM');
        }
        
        // Validate that end time is after start time
        if (strtotime($hora_fin) <= strtotime($hora_inicio)) {
            return ResponseHelper::error('La hora de fin debe ser posterior a la hora de inicio');
        }
        
        // Check for overlapping time blocks
        $overlap = $this->checkTimeOverlap($hora_inicio, $hora_fin);
        if ($overlap) {
            return ResponseHelper::error('Este horario se superpone con un bloque existente');
        }
        
        try {
            $success = $this->horarioModel->createBloque($hora_inicio, $hora_fin);
            
            if ($success) {
                return ResponseHelper::success('Bloque horario creado exitosamente');
            } else {
                return ResponseHelper::error('Error al crear el bloque horario');
            }
        } catch (Exception $e) {
            error_log("Error creating bloque: " . $e->getMessage());
            return ResponseHelper::error('Error al crear el bloque horario');
        }
    }
    
    private function updateBloque($data) {
        // Validate required fields
        if (!isset($data['id_bloque']) || !isset($data['hora_inicio']) || !isset($data['hora_fin'])) {
            return ResponseHelper::error('ID del bloque, hora de inicio y hora de fin son requeridos');
        }
        
        // Check if id_bloque is empty or invalid
        if (empty($data['id_bloque']) || !is_numeric($data['id_bloque'])) {
            return ResponseHelper::error('ID del bloque es requerido y debe ser un número válido');
        }
        
        $id_bloque = (int)$data['id_bloque'];
        $hora_inicio = trim($data['hora_inicio']);
        $hora_fin = trim($data['hora_fin']);
        
        // Validate time format
        if (!$this->validateTimeFormat($hora_inicio) || !$this->validateTimeFormat($hora_fin)) {
            return ResponseHelper::error('Formato de hora inválido. Use HH:MM');
        }
        
        // Validate that end time is after start time
        if (strtotime($hora_fin) <= strtotime($hora_inicio)) {
            return ResponseHelper::error('La hora de fin debe ser posterior a la hora de inicio');
        }
        
        // Check if bloque exists
        $existingBloque = $this->horarioModel->getBloqueById($id_bloque);
        if (!$existingBloque) {
            return ResponseHelper::error('Bloque horario no encontrado');
        }
        
        // Check for overlapping time blocks (excluding current one)
        $overlap = $this->checkTimeOverlap($hora_inicio, $hora_fin, $id_bloque);
        if ($overlap) {
            return ResponseHelper::error('Este horario se superpone con otro bloque existente');
        }
        
        try {
            $success = $this->horarioModel->updateBloque($id_bloque, $hora_inicio, $hora_fin);
            
            if ($success) {
                return ResponseHelper::success('Bloque horario actualizado exitosamente');
            } else {
                return ResponseHelper::error('Error al actualizar el bloque horario');
            }
        } catch (Exception $e) {
            error_log("Error updating bloque: " . $e->getMessage());
            return ResponseHelper::error('Error al actualizar el bloque horario');
        }
    }
    
    private function deleteBloque($data) {
        // Validate required fields
        if (!isset($data['id_bloque'])) {
            return ResponseHelper::error('ID del bloque es requerido');
        }
        
        $id_bloque = (int)$data['id_bloque'];
        
        // Check if bloque exists
        $existingBloque = $this->horarioModel->getBloqueById($id_bloque);
        if (!$existingBloque) {
            return ResponseHelper::error('Bloque horario no encontrado');
        }
        
        // Check for dependencies
        $dependencies = $this->horarioModel->checkBloqueDependencies($id_bloque);
        if ($dependencies > 0) {
            return ResponseHelper::error("Este bloque está siendo usado en $dependencies horarios. No se puede eliminar.");
        }
        
        try {
            $success = $this->horarioModel->deleteBloque($id_bloque);
            
            if ($success) {
                return ResponseHelper::success('Bloque horario eliminado exitosamente');
            } else {
                return ResponseHelper::error('Error al eliminar el bloque horario');
            }
        } catch (Exception $e) {
            error_log("Error deleting bloque: " . $e->getMessage());
            return ResponseHelper::error('Error al eliminar el bloque horario');
        }
    }
    
    private function validateTimeFormat($time) {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
    }
    
    private function checkTimeOverlap($hora_inicio, $hora_fin, $excludeId = null) {
        try {
            $allBloques = $this->horarioModel->getAllBloques();
            
            foreach ($allBloques as $bloque) {
                // Skip the bloque being updated
                if ($excludeId && $bloque['id_bloque'] == $excludeId) {
                    continue;
                }
                
                $existingStart = strtotime($bloque['hora_inicio']);
                $existingEnd = strtotime($bloque['hora_fin']);
                $newStart = strtotime($hora_inicio);
                $newEnd = strtotime($hora_fin);
                
                // Check for overlap: new start is before existing end AND new end is after existing start
                if ($newStart < $existingEnd && $newEnd > $existingStart) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error checking time overlap: " . $e->getMessage());
            return false;
        }
    }
}
