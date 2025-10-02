<?php

namespace App\Controllers\Admin;

require_once __DIR__ . '/../../../helpers/ResponseHelper.php';
require_once __DIR__ . '/../../../helpers/ValidationHelper.php';
require_once __DIR__ . '/../../../helpers/Translation.php';
require_once __DIR__ . '/../../../app/Models/Teacher.php';

use PDO;
use Exception;

class TeacherController
{
    private $teacherModel;
    private $translation;
    
    public function __construct($database)
    {
        $this->teacherModel = new \App\Models\Teacher($database);
        $this->translation = Translation::getInstance();
    }
    
    /**
     * Display a listing of teachers
     */
    public function index()
    {
        try {
            $teachers = $this->teacherModel->getAllTeachers();
            ResponseHelper::success('Teachers retrieved successfully', $teachers);
        } catch (Exception $e) {
            error_log("Error in TeacherController@index: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for creating a new teacher
     */
    public function create()
    {
        // This would typically return a view for creating teachers
        // For API responses, we can return form data or validation rules
        ResponseHelper::success('Form data retrieved successfully', [
            'validation_rules' => $this->getValidationRules()
        ]);
    }
    
    /**
     * Store a newly created teacher
     */
    public function store()
    {
        try {
            $errors = $this->validateTeacherData($_POST);
            if (!empty($errors)) {
                ResponseHelper::validationError($errors);
            }
            
            $teacherData = [
                'cedula' => trim($_POST['cedula']),
                'nombre' => trim($_POST['nombre']),
                'apellido' => trim($_POST['apellido']),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'contrasena' => $_POST['contrasena'],
                'fecha_envio_disponibilidad' => $_POST['fecha_envio_disponibilidad'] ?? null,
                'horas_asignadas' => (int)($_POST['horas_asignadas'] ?? 0),
                'porcentaje_margen' => (float)($_POST['porcentaje_margen'] ?? 0.00)
            ];
            
            $teacherId = $this->teacherModel->createTeacher($teacherData);
            
            if ($teacherId === false) {
                ResponseHelper::error('Error creating teacher. Please verify that the ID number is not already in use.');
            }
            
            $this->logActivity("Created teacher with ID $teacherId");
            ResponseHelper::success('Teacher created successfully', ['id_teacher' => $teacherId]);
        } catch (Exception $e) {
            error_log("Error in TeacherController@store: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Display the specified teacher
     */
    public function show($id)
    {
        try {
            $teacher = $this->teacherModel->getTeacherById($id);
            
            if (!$teacher) {
                ResponseHelper::notFound('Teacher');
            }
            
            ResponseHelper::success('Teacher retrieved successfully', $teacher);
        } catch (Exception $e) {
            error_log("Error in TeacherController@show: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for editing the specified teacher
     */
    public function edit($id)
    {
        try {
            $teacher = $this->teacherModel->getTeacherById($id);
            
            if (!$teacher) {
                ResponseHelper::notFound('Teacher');
            }
            
            ResponseHelper::success('Teacher data retrieved successfully', [
                'teacher' => $teacher,
                'validation_rules' => $this->getValidationRules(false)
            ]);
        } catch (Exception $e) {
            error_log("Error in TeacherController@edit: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Update the specified teacher
     */
    public function update($id)
    {
        try {
            $errors = $this->validateTeacherData($_POST, false);
            if (!empty($errors)) {
                ResponseHelper::validationError($errors);
            }
            
            $teacherData = [
                'nombre' => trim($_POST['nombre']),
                'apellido' => trim($_POST['apellido']),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'fecha_envio_disponibilidad' => $_POST['fecha_envio_disponibilidad'] ?? null,
                'horas_asignadas' => (int)($_POST['horas_asignadas'] ?? 0),
                'porcentaje_margen' => (float)($_POST['porcentaje_margen'] ?? 0.00)
            ];
            
            if (!empty($_POST['contrasena'])) {
                $teacherData['contrasena'] = $_POST['contrasena'];
            }
            
            $result = $this->teacherModel->updateTeacher($id, $teacherData);
            
            if ($result === false) {
                ResponseHelper::error('Error updating teacher');
            }
            
            $this->logActivity("Updated teacher with ID $id");
            ResponseHelper::success('Teacher updated successfully');
        } catch (Exception $e) {
            error_log("Error in TeacherController@update: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Remove the specified teacher
     */
    public function destroy($id)
    {
        try {
            $result = $this->teacherModel->deleteTeacher($id);
            
            if ($result === false) {
                ResponseHelper::error('Error deleting teacher');
            }
            
            $this->logActivity("Deleted teacher with ID $id");
            ResponseHelper::success('Teacher deleted successfully');
        } catch (Exception $e) {
            error_log("Error in TeacherController@destroy: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Search teachers
     */
    public function search()
    {
        try {
            $searchTerm = $_GET['q'] ?? $_POST['q'] ?? '';
            
            if (empty($searchTerm)) {
                return $this->index();
            }
            
            $teachers = $this->teacherModel->searchTeachers($searchTerm);
            
            if ($teachers === false) {
                ResponseHelper::error('Error searching teachers');
            }
            
            ResponseHelper::success('Search completed', $teachers);
        } catch (Exception $e) {
            error_log("Error in TeacherController@search: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
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
                'update' => $this->update($_POST['id_teacher'] ?? $_GET['id'] ?? null),
                'delete' => $this->destroy($_POST['id_teacher'] ?? $_GET['id'] ?? null),
                'get' => $this->show($_GET['id'] ?? $_POST['id'] ?? null),
                'search' => $this->search(),
                'list' => $this->index(),
                default => $this->index()
            };
        } catch (Exception $e) {
            error_log("Error in TeacherController@handleRequest: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Validate teacher data
     */
    private function validateTeacherData($data, $requirePassword = true)
    {
        $errors = [];
        
        $errors['cedula'] = ValidationHelper::validateCedula($data['cedula'] ?? '');
        $errors['nombre'] = ValidationHelper::validateName($data['nombre'] ?? '', 'nombre');
        $errors['apellido'] = ValidationHelper::validateName($data['apellido'] ?? '', 'apellido');
        $errors['email'] = ValidationHelper::validateEmail($data['email'] ?? '', false);
        $errors['telefono'] = ValidationHelper::validatePhone($data['telefono'] ?? '');
        
        if ($requirePassword) {
            $errors['contrasena'] = ValidationHelper::validatePassword($data['contrasena'] ?? '', true);
        } elseif (!empty($data['contrasena'])) {
            $errors['contrasena'] = ValidationHelper::validatePassword($data['contrasena'], false);
        }
        
        if (isset($data['horas_asignadas'])) {
            $errors['horas_asignadas'] = ValidationHelper::validateNumericRange($data['horas_asignadas'], 'horas_asignadas', 0);
        }
        
        if (isset($data['porcentaje_margen'])) {
            $errors['porcentaje_margen'] = ValidationHelper::validateNumericRange($data['porcentaje_margen'], 'porcentaje_margen', 0, 100);
        }
        
        return array_filter($errors);
    }
    
    /**
     * Get validation rules for frontend
     */
    private function getValidationRules($requirePassword = true)
    {
        return [
            'cedula' => ['required' => true, 'type' => 'cedula'],
            'nombre' => ['required' => true, 'type' => 'name'],
            'apellido' => ['required' => true, 'type' => 'name'],
            'email' => ['required' => false, 'type' => 'email'],
            'telefono' => ['required' => false, 'type' => 'phone'],
            'contrasena' => ['required' => $requirePassword, 'type' => 'password'],
            'horas_asignadas' => ['required' => false, 'type' => 'numeric', 'min' => 0],
            'porcentaje_margen' => ['required' => false, 'type' => 'numeric', 'min' => 0, 'max' => 100]
        ];
    }
    
    /**
     * Log user activity
     */
    private function logActivity($action)
    {
        try {
            $this->teacherModel->logActivity($action);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
