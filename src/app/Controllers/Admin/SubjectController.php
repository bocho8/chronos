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
require_once __DIR__ . '/../../../models/Materia.php';

use PDO;
use Exception;

class SubjectController
{
    private $materiaModel;
    private $translation;
    
    public function __construct($database = null)
    {
        if ($database) {
            $this->materiaModel = new \Materia($database);
        } else {
            // Initialize database connection if not provided
            require_once __DIR__ . '/../../../config/database.php';
            $dbConfig = require __DIR__ . '/../../../config/database.php';
            $db = new \Database($dbConfig);
            $this->materiaModel = new \Materia($db->getConnection());
        }
        $this->translation = \Translation::getInstance();
    }
    
    /**
     * Display a listing of subjects
     */
    public function index()
    {
        try {
            // For now, redirect to the existing admin subjects view
            require __DIR__ . '/../../../views/admin/AdminMaterias.php';
        } catch (Exception $e) {
            error_log("Error in SubjectController@index: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for creating a new subject
     */
    public function create()
    {
        try {
            require __DIR__ . '/../../../views/admin/AdminMaterias.php';
        } catch (Exception $e) {
            error_log("Error in SubjectController@create: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Store a newly created subject
     */
    public function store()
    {
        try {
            $materiaData = $this->validateMateriaData($_POST);
            if ($materiaData === false) {
                return;
            }
            
            $materiaId = $this->materiaModel->createMateria($materiaData);
            
            if ($materiaId) {
                $this->logActivity("Created subject ID $materiaId");
                \ResponseHelper::success('Subject created successfully', ['id' => $materiaId]);
            } else {
                \ResponseHelper::error('Error creating subject');
            }
        } catch (Exception $e) {
            error_log("Error in SubjectController@store: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Display the specified subject
     */
    public function show($id)
    {
        try {
            $materia = $this->materiaModel->getMateriaById($id);
            
            if (!$materia) {
                \ResponseHelper::notFound('Subject');
            }
            
            \ResponseHelper::success('Subject retrieved successfully', $materia);
        } catch (Exception $e) {
            error_log("Error in SubjectController@show: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for editing the specified subject
     */
    public function edit($id)
    {
        try {
            $materia = $this->materiaModel->getMateriaById($id);
            
            if (!$materia) {
                \ResponseHelper::notFound('Subject');
            }
            
            \ResponseHelper::success('Subject data retrieved successfully', $materia);
        } catch (Exception $e) {
            error_log("Error in SubjectController@edit: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Update the specified subject
     */
    public function update($id)
    {
        try {
            $materiaData = $this->validateMateriaData($_POST);
            if ($materiaData === false) {
                return;
            }
            
            $result = $this->materiaModel->updateMateria($id, $materiaData);
            
            if ($result) {
                $this->logActivity("Updated subject ID $id");
                \ResponseHelper::success('Subject updated successfully');
            } else {
                \ResponseHelper::error('Error updating subject');
            }
        } catch (Exception $e) {
            error_log("Error in SubjectController@update: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Remove the specified subject
     */
    public function destroy($id)
    {
        try {
            if (!$this->materiaModel->getMateriaById($id)) {
                \ResponseHelper::notFound('Subject');
            }
            
            $result = $this->materiaModel->deleteMateria($id);
            
            if ($result) {
                $this->logActivity("Deleted subject ID $id");
                \ResponseHelper::success('Subject deleted successfully');
            } else {
                \ResponseHelper::error('Error deleting subject');
            }
        } catch (Exception $e) {
            error_log("Error in SubjectController@destroy: " . $e->getMessage());
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
            error_log("Error in SubjectController@handleRequest: " . $e->getMessage());
            \ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Validate subject data
     */
    private function validateMateriaData($data)
    {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'Subject name is required';
        }
        
        if (empty($data['codigo'])) {
            $errors['codigo'] = 'Subject code is required';
        }
        
        if (!empty($errors)) {
            \ResponseHelper::validationError($errors);
            return false;
        }
        
        return [
            'nombre' => $data['nombre'],
            'codigo' => $data['codigo'],
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
            error_log("Subject activity: " . $action);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
