<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

namespace App\Controllers\Admin;

require_once __DIR__ . '/../../../helpers/ResponseHelper.php';
require_once __DIR__ . '/../../../helpers/Translation.php';
require_once __DIR__ . '/../../../models/Database.php';
require_once __DIR__ . '/../../../models/Padre.php';

use Exception;

class ParentAssignmentController
{
    private $padreModel;
    private $translation;
    
    public function __construct($database = null)
    {
        if ($database) {
            $this->padreModel = new \Padre($database);
        } else {
            require_once __DIR__ . '/../../../config/database.php';
            $dbConfig = require __DIR__ . '/../../../config/database.php';
            $db = new \Database($dbConfig);
            $this->padreModel = new \Padre($db->getConnection());
        }
        $this->translation = \Translation::getInstance();
    }
    
    /**
     * Display the parent assignment view
     */
    public function index()
    {
        try {
            require __DIR__ . '/../../../views/admin/AdminAsignacionPadres.php';
        } catch (Exception $e) {
            error_log("Error in ParentAssignmentController@index: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Handle AJAX requests for parent assignments
     */
    public function handleRequest()
    {
        header('Content-Type: application/json');
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            switch ($action) {
                case 'assign_groups':
                    $this->assignGroups();
                    break;
                case 'remove_group':
                    $this->removeGroup();
                    break;
                case 'get_parent_groups':
                    $this->getParentGroups();
                    break;
                case 'get_all_assignments':
                    $this->getAllAssignments();
                    break;
                default:
                    \ResponseHelper::error('Acción no válida', null, 400);
            }
        } catch (Exception $e) {
            error_log("Error in ParentAssignmentController@handleRequest: " . $e->getMessage());
            \ResponseHelper::error('Error interno del servidor', null, 500);
        }
    }
    
    /**
     * Assign groups to a parent
     */
    private function assignGroups()
    {
        $id_padre = $_POST['id_padre'] ?? '';
        $id_grupos = $_POST['id_grupos'] ?? [];
        $replace = $_POST['replace'] ?? false;
        
        if (empty($id_padre) || !is_numeric($id_padre)) {
            \ResponseHelper::error('ID de padre inválido');
            return;
        }
        
        if (empty($id_grupos) || !is_array($id_grupos)) {
            \ResponseHelper::error('Debe seleccionar al menos un grupo');
            return;
        }
        
        // If replace mode, first remove all existing assignments
        if ($replace) {
            $currentGroups = $this->padreModel->getGroupsForParent($id_padre);
            if ($currentGroups !== false) {
                foreach ($currentGroups as $group) {
                    $this->padreModel->removeGroupFromParent($id_padre, $group['id_grupo']);
                }
            }
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($id_grupos as $id_grupo) {
            if (!is_numeric($id_grupo)) {
                $errors[] = "ID de grupo inválido: $id_grupo";
                continue;
            }
            
            // Skip if already assigned (unless in replace mode, which was already cleared)
            if (!$replace && $this->padreModel->hasAccessToGroup($id_padre, $id_grupo)) {
                $successCount++;
                continue;
            }
            
            $result = $this->padreModel->assignGroupToParent($id_padre, $id_grupo);
            if ($result) {
                $successCount++;
            } else {
                $errors[] = "Error asignando grupo $id_grupo";
            }
        }
        
        if ($successCount > 0) {
            $message = "Se asignaron $successCount grupos exitosamente";
            if (!empty($errors)) {
                $message .= ". Errores: " . implode(', ', $errors);
            }
            \ResponseHelper::success($message);
        } else {
            \ResponseHelper::error('No se pudo asignar ningún grupo. Errores: ' . implode(', ', $errors));
        }
    }
    
    /**
     * Remove a group from a parent
     */
    private function removeGroup()
    {
        $id_padre = $_POST['id_padre'] ?? '';
        $id_grupo = $_POST['id_grupo'] ?? '';
        
        if (empty($id_padre) || !is_numeric($id_padre)) {
            \ResponseHelper::error('ID de padre inválido');
            return;
        }
        
        if (empty($id_grupo) || !is_numeric($id_grupo)) {
            \ResponseHelper::error('ID de grupo inválido');
            return;
        }
        
        $result = $this->padreModel->removeGroupFromParent($id_padre, $id_grupo);
        
        if ($result) {
            \ResponseHelper::success('Grupo removido exitosamente');
        } else {
            \ResponseHelper::error('Error removiendo grupo');
        }
    }
    
    /**
     * Get groups assigned to a parent
     */
    private function getParentGroups()
    {
        $id_padre = $_GET['id_padre'] ?? $_POST['id_padre'] ?? '';
        
        if (empty($id_padre) || !is_numeric($id_padre)) {
            \ResponseHelper::error('ID de padre inválido');
            return;
        }
        
        $grupos = $this->padreModel->getGroupsForParent($id_padre);
        
        if ($grupos !== false) {
            \ResponseHelper::success('Grupos obtenidos exitosamente', $grupos);
        } else {
            \ResponseHelper::error('Error obteniendo grupos del padre');
        }
    }
    
    /**
     * Get all parent assignments
     */
    private function getAllAssignments()
    {
        $padres = $this->padreModel->getAllParentsWithGroups();
        
        if ($padres !== false) {
            \ResponseHelper::success('Asignaciones obtenidas exitosamente', $padres);
        } else {
            \ResponseHelper::error('Error obteniendo asignaciones');
        }
    }
}

