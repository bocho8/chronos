<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

namespace App\Controllers\Admin;

require_once __DIR__ . '/../../../helpers/ResponseHelper.php';
require_once __DIR__ . '/../../../helpers/ValidationHelper.php';
require_once __DIR__ . '/../../../helpers/Translation.php';
require_once __DIR__ . '/../../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../../models/Grupo.php';

use PDO;
use Exception;

class GroupController
{
    private $grupoModel;
    private $translation;
    
    public function __construct($database = null)
    {
        if ($database) {
            $this->grupoModel = new \Grupo($database);
        } else {
            // Initialize database connection if not provided
            require_once __DIR__ . '/../../../config/database.php';
            $dbConfig = require __DIR__ . '/../../../config/database.php';
            $db = new \Database($dbConfig);
            $this->grupoModel = new \Grupo($db->getConnection());
        }
        $this->translation = \Translation::getInstance();
    }
    
    /**
     * Display a listing of groups
     */
    public function index()
    {
        try {
            // For now, redirect to the existing admin groups view
            require __DIR__ . '/../../../views/admin/AdminGrupos.php';
        } catch (Exception $e) {
            error_log("Error in GroupController@index: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for creating a new group
     */
    public function create()
    {
        try {
            require __DIR__ . '/../../../views/admin/AdminGrupos.php';
        } catch (Exception $e) {
            error_log("Error in GroupController@create: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Store a newly created group
     */
    public function store()
    {
        try {
            $grupoData = $this->validateGrupoData($_POST);
            if ($grupoData === false) {
                return;
            }
            
            $grupoId = $this->grupoModel->createGrupo($grupoData['nombre'], $grupoData['nivel'] ?? 1);
            
            if ($grupoId) {
                $this->logActivity("Created group ID $grupoId");
                \ResponseHelper::success('Group created successfully', ['id' => $grupoId]);
            } else {
                \ResponseHelper::error('Error creating group');
            }
        } catch (Exception $e) {
            error_log("Error in GroupController@store: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Display the specified group
     */
    public function show($id)
    {
        try {
            $grupo = $this->grupoModel->getGrupoById($id);
            
            if (!$grupo) {
                \ResponseHelper::notFound('Group');
            }
            
            \ResponseHelper::success('Group retrieved successfully', $grupo);
        } catch (Exception $e) {
            error_log("Error in GroupController@show: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for editing the specified group
     */
    public function edit($id)
    {
        try {
            $grupo = $this->grupoModel->getGrupoById($id);
            
            if (!$grupo) {
                \ResponseHelper::notFound('Group');
            }
            
            \ResponseHelper::success('Group data retrieved successfully', $grupo);
        } catch (Exception $e) {
            error_log("Error in GroupController@edit: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Update the specified group
     */
    public function update($id)
    {
        try {
            $grupoData = $this->validateGrupoData($_POST);
            if ($grupoData === false) {
                return;
            }
            
            $result = $this->grupoModel->updateGrupo($id, $grupoData['nombre'], $grupoData['nivel']);
            
            if ($result) {
                $this->logActivity("Updated group ID $id");
                \ResponseHelper::success('Group updated successfully');
            } else {
                \ResponseHelper::error('Error updating group');
            }
        } catch (Exception $e) {
            error_log("Error in GroupController@update: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Remove the specified group
     */
    public function destroy($id)
    {
        try {
            if (!$this->grupoModel->getGrupoById($id)) {
                \ResponseHelper::notFound('Group');
            }
            
            $result = $this->grupoModel->deleteGrupo($id);
            
            if ($result) {
                $this->logActivity("Deleted group ID $id");
                \ResponseHelper::success('Group deleted successfully');
            } else {
                \ResponseHelper::error('Error deleting group');
            }
        } catch (Exception $e) {
            error_log("Error in GroupController@destroy: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Handle legacy request routing
     */
    public function handleRequest()
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'index';
        
        try {
            match ($action) {
                'create' => $this->store(),
                'update' => $this->update($_POST['id'] ?? $_GET['id'] ?? null),
                'delete' => $this->destroy($_POST['id'] ?? $_GET['id'] ?? null),
                'get' => $this->show($_GET['id'] ?? $_POST['id'] ?? null),
                'list' => $this->index(),
                default => $this->index()
            };
        } catch (Exception $e) {
            error_log("Error in GroupController@handleRequest: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Validate group data
     */
    private function validateGrupoData($data)
    {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'Group name is required';
        }
        
        if (empty($data['codigo'])) {
            $errors['codigo'] = 'Group code is required';
        }
        
        if (empty($data['nivel'])) {
            $errors['nivel'] = 'Group level is required';
        }
        
        if (!empty($errors)) {
            \ResponseHelper::validationError($errors);
            return false;
        }
        
        return [
            'nombre' => $data['nombre'],
            'codigo' => $data['codigo'],
            'nivel' => $data['nivel'],
            'descripcion' => $data['descripcion'] ?? '',
            'activo' => $data['activo'] ?? 1
        ];
    }
    
    /**
     * Log user activity
     */
    private function logActivity($action)
    {
        try {
            // Add activity logging here if needed
            error_log("Group activity: " . $action);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
