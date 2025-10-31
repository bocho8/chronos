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
require_once __DIR__ . '/../../../models/Grupo.php';

use Exception;

class GroupSubjectController
{
    private $grupoModel;
    private $translation;
    
    public function __construct($database = null)
    {
        if ($database) {
            $this->grupoModel = new \Grupo($database);
        } else {
            require_once __DIR__ . '/../../../config/database.php';
            $dbConfig = require __DIR__ . '/../../../config/database.php';
            $db = new \Database($dbConfig);
            $this->grupoModel = new \Grupo($db->getConnection());
        }
        $this->translation = \Translation::getInstance();
    }
    
    /**
     * Display the group subject assignment view
     */
    public function index()
    {
        try {
            require __DIR__ . '/../../../views/admin/AdminAsignacionMaterias.php';
        } catch (Exception $e) {
            error_log("Error in GroupSubjectController@index: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Handle AJAX requests for group subject assignments
     */
    public function handleRequest()
    {
        header('Content-Type: application/json');
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            switch ($action) {
                case 'assign_subjects':
                    $this->assignSubjects();
                    break;
                case 'remove_subject':
                    $this->removeSubject();
                    break;
                case 'get_group_subjects':
                    $this->getGroupSubjects();
                    break;
                case 'get_all_assignments':
                    $this->getAllAssignments();
                    break;
                default:
                    \ResponseHelper::error('Acción no válida', null, 400);
            }
        } catch (Exception $e) {
            error_log("Error in GroupSubjectController@handleRequest: " . $e->getMessage());
            \ResponseHelper::error('Error interno del servidor', null, 500);
        }
    }
    
    /**
     * Assign subjects to a group
     */
    private function assignSubjects()
    {
        $id_grupo = $_POST['id_grupo'] ?? '';
        $id_materias = $_POST['id_materias'] ?? [];
        
        if (empty($id_grupo) || !is_numeric($id_grupo)) {
            \ResponseHelper::error('ID de grupo inválido');
            return;
        }
        
        if (empty($id_materias) || !is_array($id_materias)) {
            \ResponseHelper::error('Debe seleccionar al menos una materia');
            return;
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($id_materias as $id_materia) {
            if (!is_numeric($id_materia)) {
                $errors[] = "ID de materia inválido: $id_materia";
                continue;
            }
            
            // Get default hours from the subject
            $horas_semanales = 1; // Default value
            
            $result = $this->grupoModel->assignSubjectToGroup($id_grupo, $id_materia, $horas_semanales);
            if ($result) {
                $successCount++;
            } else {
                $errors[] = "Error asignando materia $id_materia";
            }
        }
        
        if ($successCount > 0) {
            $message = "Se asignaron $successCount materias exitosamente";
            if (!empty($errors)) {
                $message .= ". Errores: " . implode(', ', $errors);
            }
            \ResponseHelper::success($message);
        } else {
            \ResponseHelper::error('No se pudo asignar ninguna materia. Errores: ' . implode(', ', $errors));
        }
    }
    
    /**
     * Remove a subject from a group
     */
    private function removeSubject()
    {
        $id_grupo = $_POST['id_grupo'] ?? '';
        $id_materia = $_POST['id_materia'] ?? '';
        
        if (empty($id_grupo) || !is_numeric($id_grupo)) {
            \ResponseHelper::error('ID de grupo inválido');
            return;
        }
        
        if (empty($id_materia) || !is_numeric($id_materia)) {
            \ResponseHelper::error('ID de materia inválido');
            return;
        }
        
        $result = $this->grupoModel->removeSubjectFromGroup($id_grupo, $id_materia);
        
        if ($result) {
            \ResponseHelper::success('Materia removida exitosamente');
        } else {
            \ResponseHelper::error('Error removiendo materia');
        }
    }
    
    /**
     * Get subjects assigned to a group
     */
    private function getGroupSubjects()
    {
        $id_grupo = $_GET['id_grupo'] ?? $_POST['id_grupo'] ?? '';
        
        if (empty($id_grupo) || !is_numeric($id_grupo)) {
            \ResponseHelper::error('ID de grupo inválido');
            return;
        }
        
        $materias = $this->grupoModel->getSubjectsForGroup($id_grupo);
        
        if ($materias !== false) {
            \ResponseHelper::success('Materias obtenidas exitosamente', $materias);
        } else {
            \ResponseHelper::error('Error obteniendo materias del grupo');
        }
    }
    
    /**
     * Get all group subject assignments
     */
    private function getAllAssignments()
    {
        $grupos = $this->grupoModel->getAllGroupsWithSubjects();
        
        if ($grupos !== false) {
            \ResponseHelper::success('Asignaciones obtenidas exitosamente', $grupos);
        } else {
            \ResponseHelper::error('Error obteniendo asignaciones');
        }
    }
}

