<?php

namespace App\Controllers\Admin;

require_once __DIR__ . '/../../../helpers/ResponseHelper.php';
require_once __DIR__ . '/../../../helpers/ValidationHelper.php';
require_once __DIR__ . '/../../../helpers/Translation.php';
require_once __DIR__ . '/../../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../../app/Models/Assignment.php';

use PDO;
use Exception;

class AssignmentController
{
    private $assignmentModel;
    private $translation;
    
    public function __construct($database)
    {
        $this->assignmentModel = new \App\Models\Assignment($database);
        $this->translation = Translation::getInstance();
    }
    
    /**
     * Display a listing of assignments
     */
    public function index()
    {
        try {
            $assignments = $this->assignmentModel->getAllAssignments();
            ResponseHelper::success('Assignments retrieved successfully', $assignments);
        } catch (Exception $e) {
            error_log("Error in AssignmentController@index: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for creating a new assignment
     */
    public function create()
    {
        // This would typically return a view for creating assignments
        // For API responses, we can return available teachers and subjects
        try {
            $teachers = $this->assignmentModel->getAvailableTeachers();
            $subjects = $this->assignmentModel->getAvailableSubjects();
            
            ResponseHelper::success('Form data retrieved successfully', [
                'teachers' => $teachers,
                'subjects' => $subjects
            ]);
        } catch (Exception $e) {
            error_log("Error in AssignmentController@create: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Store a newly created assignment
     */
    public function store()
    {
        try {
            $teacherId = $_POST['teacher_id'] ?? null;
            $subjectId = $_POST['subject_id'] ?? null;
            
            // Validation
            $errors = $this->validateAssignmentData($_POST);
            if (!empty($errors)) {
                ResponseHelper::validationError($errors);
            }
            
            // Check if assignment already exists
            if ($this->assignmentModel->assignmentExists($teacherId, $subjectId)) {
                ResponseHelper::error('This assignment already exists');
            }
            
            // Create assignment
            $assignmentId = $this->assignmentModel->createAssignment($teacherId, $subjectId);
            
            if ($assignmentId) {
                $this->logActivity("Assigned teacher ID $teacherId to subject ID $subjectId");
                ResponseHelper::success('Assignment created successfully', ['id' => $assignmentId]);
            } else {
                ResponseHelper::error('Error creating assignment');
            }
        } catch (Exception $e) {
            error_log("Error in AssignmentController@store: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Display the specified assignment
     */
    public function show($id)
    {
        try {
            $assignment = $this->assignmentModel->getAssignmentById($id);
            
            if (!$assignment) {
                ResponseHelper::notFound('Assignment');
            }
            
            ResponseHelper::success('Assignment retrieved successfully', $assignment);
        } catch (Exception $e) {
            error_log("Error in AssignmentController@show: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Show the form for editing the specified assignment
     */
    public function edit($id)
    {
        try {
            $assignment = $this->assignmentModel->getAssignmentById($id);
            
            if (!$assignment) {
                ResponseHelper::notFound('Assignment');
            }
            
            $teachers = $this->assignmentModel->getAvailableTeachers();
            $subjects = $this->assignmentModel->getAvailableSubjects();
            
            ResponseHelper::success('Assignment data retrieved successfully', [
                'assignment' => $assignment,
                'teachers' => $teachers,
                'subjects' => $subjects
            ]);
        } catch (Exception $e) {
            error_log("Error in AssignmentController@edit: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Update the specified assignment
     */
    public function update($id)
    {
        try {
            $teacherId = $_POST['teacher_id'] ?? null;
            $subjectId = $_POST['subject_id'] ?? null;
            
            // Validation
            $errors = $this->validateAssignmentData($_POST);
            if (!empty($errors)) {
                ResponseHelper::validationError($errors);
            }
            
            // Check if assignment exists
            if (!$this->assignmentModel->getAssignmentById($id)) {
                ResponseHelper::notFound('Assignment');
            }
            
            // Update assignment
            $result = $this->assignmentModel->updateAssignment($id, $teacherId, $subjectId);
            
            if ($result) {
                $this->logActivity("Updated assignment ID $id");
                ResponseHelper::success('Assignment updated successfully');
            } else {
                ResponseHelper::error('Error updating assignment');
            }
        } catch (Exception $e) {
            error_log("Error in AssignmentController@update: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Remove the specified assignment
     */
    public function destroy($id)
    {
        try {
            // Check if assignment exists
            if (!$this->assignmentModel->getAssignmentById($id)) {
                ResponseHelper::notFound('Assignment');
            }
            
            $result = $this->assignmentModel->deleteAssignment($id);
            
            if ($result) {
                $this->logActivity("Deleted assignment ID $id");
                ResponseHelper::success('Assignment deleted successfully');
            } else {
                ResponseHelper::error('Error deleting assignment');
            }
        } catch (Exception $e) {
            error_log("Error in AssignmentController@destroy: " . $e->getMessage());
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
                'update' => $this->update($_POST['id'] ?? $_GET['id'] ?? null),
                'delete' => $this->destroy($_POST['id'] ?? $_GET['id'] ?? null),
                'get' => $this->show($_GET['id'] ?? $_POST['id'] ?? null),
                'list' => $this->index(),
                default => $this->index()
            };
        } catch (Exception $e) {
            error_log("Error in AssignmentController@handleRequest: " . $e->getMessage());
            ResponseHelper::error('Internal server error', null, 500);
        }
    }
    
    /**
     * Validate assignment data
     */
    private function validateAssignmentData($data)
    {
        $errors = [];
        
        if (empty($data['teacher_id'])) {
            $errors['teacher_id'] = 'Teacher ID is required';
        }
        
        if (empty($data['subject_id'])) {
            $errors['subject_id'] = 'Subject ID is required';
        }
        
        return array_filter($errors);
    }
    
    /**
     * Log user activity
     */
    private function logActivity($action)
    {
        try {
            $this->assignmentModel->logActivity($action);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
