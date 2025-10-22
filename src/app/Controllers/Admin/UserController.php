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
require_once __DIR__ . '/../../../models/Usuario.php';

use PDO;
use Exception;

class UserController
{
    private $usuarioModel;
    private $translation;
    
    public function __construct($database = null)
    {
        if ($database) {
            $this->usuarioModel = new \Usuario($database);
        } else {
            // Initialize database connection if not provided
            require_once __DIR__ . '/../../../config/database.php';
            $dbConfig = require __DIR__ . '/../../../config/database.php';
            $db = new \Database($dbConfig);
            $this->usuarioModel = new \Usuario($db->getConnection());
        }
        $this->translation = \Translation::getInstance();
    }
    
    /**
     * Display a listing of users
     */
    public function index()
    {
        try {
            // For now, redirect to the existing admin users view
            require __DIR__ . '/../../../views/admin/AdminUsuarios.php';
        } catch (Exception $e) {
            error_log("Error in UserController@index: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        try {
            require __DIR__ . '/../../../views/admin/AdminUsuarios.php';
        } catch (Exception $e) {
            error_log("Error in UserController@create: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Store a newly created user
     */
    public function store()
    {
        try {
            $usuarioData = $this->validateUsuarioData($_POST);
            if ($usuarioData === false) {
                return;
            }
            
            $userId = $this->usuarioModel->createUsuario($usuarioData);
            
            if ($userId) {
                $this->logActivity("Created user ID $userId");
                \ResponseHelper::success('User created successfully', ['id' => $userId]);
            } else {
                \ResponseHelper::error('Error creating user');
            }
        } catch (Exception $e) {
            error_log("Error in UserController@store: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Display the specified user
     */
    public function show($id)
    {
        try {
            $user = $this->usuarioModel->getUsuarioById($id);
            
            if (!$user) {
                \ResponseHelper::notFound('User');
            }
            
            \ResponseHelper::success('User retrieved successfully', $user);
        } catch (Exception $e) {
            error_log("Error in UserController@show: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for editing the specified user
     */
    public function edit($id)
    {
        try {
            $user = $this->usuarioModel->getUsuarioById($id);
            
            if (!$user) {
                \ResponseHelper::notFound('User');
            }
            
            \ResponseHelper::success('User data retrieved successfully', $user);
        } catch (Exception $e) {
            error_log("Error in UserController@edit: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Update the specified user
     */
    public function update($id)
    {
        try {
            $usuarioData = $this->validateUsuarioData($_POST);
            if ($usuarioData === false) {
                return;
            }
            
            $result = $this->usuarioModel->updateUsuario($id, $usuarioData);
            
            if ($result) {
                $this->logActivity("Updated user ID $id");
                \ResponseHelper::success('User updated successfully');
            } else {
                \ResponseHelper::error('Error updating user');
            }
        } catch (Exception $e) {
            error_log("Error in UserController@update: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Remove the specified user
     */
    public function destroy($id)
    {
        try {
            if (!$this->usuarioModel->getUsuarioById($id)) {
                \ResponseHelper::notFound('User');
            }
            
            $result = $this->usuarioModel->deleteUsuario($id);
            
            if ($result) {
                $this->logActivity("Deleted user ID $id");
                \ResponseHelper::success('User deleted successfully');
            } else {
                \ResponseHelper::error('Error deleting user');
            }
        } catch (Exception $e) {
            error_log("Error in UserController@destroy: " . $e->getMessage());
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
            error_log("Error in UserController@handleRequest: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Validate user data
     */
    private function validateUsuarioData($data)
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
        
        if (empty($data['rol'])) {
            $errors['rol'] = 'Role is required';
        }
        
        if (!empty($errors)) {
            \ResponseHelper::validationError($errors);
            return false;
        }
        
        return [
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'rol' => $data['rol'],
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
            error_log("User activity: " . $action);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
