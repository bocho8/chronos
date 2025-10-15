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
require_once __DIR__ . '/../../../models/Coordinador.php';

use PDO;
use Exception;

class CoordinatorController
{
    private $coordinadorModel;
    private $translation;
    
    public function __construct($database = null)
    {
        if ($database) {
            $this->coordinadorModel = new \Coordinador($database);
        } else {
            // Initialize database connection if not provided
            require_once __DIR__ . '/../../../config/database.php';
            $dbConfig = require __DIR__ . '/../../../config/database.php';
            $db = new \Database($dbConfig);
            $this->coordinadorModel = new \Coordinador($db->getConnection());
        }
        $this->translation = \Translation::getInstance();
    }
    
    /**
     * Display a listing of coordinators
     */
    public function index()
    {
        try {
            // For now, redirect to the existing admin coordinators view
            require __DIR__ . '/../../../views/admin/AdminCoordinadores.php';
        } catch (Exception $e) {
            error_log("Error in CoordinatorController@index: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for creating a new coordinator
     */
    public function create()
    {
        try {
            require __DIR__ . '/../../../views/admin/AdminCoordinadores.php';
        } catch (Exception $e) {
            error_log("Error in CoordinatorController@create: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Store a newly created coordinator
     */
    public function store()
    {
        try {
            $coordinadorData = $this->validateCoordinatorData($_POST);
            if ($coordinadorData === false) {
                return;
            }
            
            $coordinatorId = $this->coordinadorModel->createCoordinator($coordinadorData);
            
            if ($coordinatorId) {
                $this->logActivity("Created coordinator ID $coordinatorId");
                \ResponseHelper::success('Coordinator created successfully', ['id' => $coordinatorId]);
            } else {
                \ResponseHelper::error('Error creating coordinator');
            }
        } catch (Exception $e) {
            error_log("Error in CoordinatorController@store: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Display the specified coordinator
     */
    public function show($id)
    {
        try {
            $coordinator = $this->coordinadorModel->getCoordinatorById($id);
            
            if (!$coordinator) {
                \ResponseHelper::notFound('Coordinator');
            }
            
            \ResponseHelper::success('Coordinator retrieved successfully', $coordinator);
        } catch (Exception $e) {
            error_log("Error in CoordinatorController@show: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for editing the specified coordinator
     */
    public function edit($id)
    {
        try {
            $coordinator = $this->coordinadorModel->getCoordinatorById($id);
            
            if (!$coordinator) {
                \ResponseHelper::notFound('Coordinator');
            }
            
            \ResponseHelper::success('Coordinator data retrieved successfully', $coordinator);
        } catch (Exception $e) {
            error_log("Error in CoordinatorController@edit: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Update the specified coordinator
     */
    public function update($id)
    {
        try {
            $coordinadorData = $this->validateCoordinatorData($_POST);
            if ($coordinadorData === false) {
                return;
            }
            
            $result = $this->coordinadorModel->updateCoordinator($id, $coordinadorData);
            
            if ($result) {
                $this->logActivity("Updated coordinator ID $id");
                \ResponseHelper::success('Coordinator updated successfully');
            } else {
                \ResponseHelper::error('Error updating coordinator');
            }
        } catch (Exception $e) {
            error_log("Error in CoordinatorController@update: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Remove the specified coordinator
     */
    public function destroy($id)
    {
        try {
            if (!$this->coordinadorModel->getCoordinatorById($id)) {
                \ResponseHelper::notFound('Coordinator');
            }
            
            $result = $this->coordinadorModel->deleteCoordinator($id);
            
            if ($result) {
                $this->logActivity("Deleted coordinator ID $id");
                \ResponseHelper::success('Coordinator deleted successfully');
            } else {
                \ResponseHelper::error('Error deleting coordinator');
            }
        } catch (Exception $e) {
            error_log("Error in CoordinatorController@destroy: " . $e->getMessage());
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
            error_log("Error in CoordinatorController@handleRequest: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Validate coordinator data
     */
    private function validateCoordinatorData($data)
    {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'Name is required';
        }
        
        if (empty($data['apellido'])) {
            $errors['apellido'] = 'Last name is required';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (!empty($errors)) {
            \ResponseHelper::validationError($errors);
            return false;
        }
        
        return [
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
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
            error_log("Coordinator activity: " . $action);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
