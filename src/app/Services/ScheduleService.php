<?php

namespace App\Services;

require_once __DIR__ . '/../../app/Models/Schedule.php';
require_once __DIR__ . '/../../app/Models/Teacher.php';
require_once __DIR__ . '/../../app/Models/Subject.php';
require_once __DIR__ . '/../../app/Models/Group.php';

class ScheduleService
{
    private $scheduleModel;
    private $teacherModel;
    private $subjectModel;
    private $groupModel;
    
    public function __construct($database)
    {
        $this->scheduleModel = new \App\Models\Schedule($database);
        $this->teacherModel = new \App\Models\Teacher($database);
        $this->subjectModel = new \App\Models\Subject($database);
        $this->groupModel = new \App\Models\Group($database);
    }
    
    /**
     * Create a new schedule
     */
    public function createSchedule($scheduleData)
    {
        try {
            // Validate schedule data
            $validation = $this->validateScheduleData($scheduleData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Datos de horario inválidos',
                    'errors' => $validation['errors']
                ];
            }
            
            // Check for conflicts
            $conflicts = $this->checkScheduleConflicts($scheduleData);
            if (!empty($conflicts)) {
                return [
                    'success' => false,
                    'message' => 'Conflicto de horarios detectado',
                    'conflicts' => $conflicts
                ];
            }
            
            // Create schedule
            $scheduleId = $this->scheduleModel->createSchedule($scheduleData);
            
            if ($scheduleId) {
                $this->logActivity('SCHEDULE_CREATED', "Horario creado con ID: $scheduleId");
                return [
                    'success' => true,
                    'message' => 'Horario creado exitosamente',
                    'schedule_id' => $scheduleId
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error creando horario'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error in ScheduleService::createSchedule: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Update an existing schedule
     */
    public function updateSchedule($scheduleId, $scheduleData)
    {
        try {
            // Check if schedule exists
            $existingSchedule = $this->scheduleModel->getScheduleById($scheduleId);
            if (!$existingSchedule) {
                return [
                    'success' => false,
                    'message' => 'Horario no encontrado'
                ];
            }
            
            // Validate schedule data
            $validation = $this->validateScheduleData($scheduleData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Datos de horario inválidos',
                    'errors' => $validation['errors']
                ];
            }
            
            // Check for conflicts (excluding current schedule)
            $conflicts = $this->checkScheduleConflicts($scheduleData, $scheduleId);
            if (!empty($conflicts)) {
                return [
                    'success' => false,
                    'message' => 'Conflicto de horarios detectado',
                    'conflicts' => $conflicts
                ];
            }
            
            // Update schedule
            $result = $this->scheduleModel->updateSchedule($scheduleId, $scheduleData);
            
            if ($result) {
                $this->logActivity('SCHEDULE_UPDATED', "Horario actualizado con ID: $scheduleId");
                return [
                    'success' => true,
                    'message' => 'Horario actualizado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error actualizando horario'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error in ScheduleService::updateSchedule: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Delete a schedule
     */
    public function deleteSchedule($scheduleId)
    {
        try {
            // Check if schedule exists
            $schedule = $this->scheduleModel->getScheduleById($scheduleId);
            if (!$schedule) {
                return [
                    'success' => false,
                    'message' => 'Horario no encontrado'
                ];
            }
            
            // Delete schedule
            $result = $this->scheduleModel->deleteSchedule($scheduleId);
            
            if ($result) {
                $this->logActivity('SCHEDULE_DELETED', "Horario eliminado con ID: $scheduleId");
                return [
                    'success' => true,
                    'message' => 'Horario eliminado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error eliminando horario'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error in ScheduleService::deleteSchedule: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Get teacher's schedule
     */
    public function getTeacherSchedule($teacherId, $startDate = null, $endDate = null)
    {
        try {
            $schedules = $this->scheduleModel->getSchedulesByTeacher($teacherId, $startDate, $endDate);
            
            return [
                'success' => true,
                'schedules' => $schedules
            ];
            
        } catch (Exception $e) {
            error_log("Error in ScheduleService::getTeacherSchedule: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Get group's schedule
     */
    public function getGroupSchedule($groupId, $startDate = null, $endDate = null)
    {
        try {
            $schedules = $this->scheduleModel->getSchedulesByGroup($groupId, $startDate, $endDate);
            
            return [
                'success' => true,
                'schedules' => $schedules
            ];
            
        } catch (Exception $e) {
            error_log("Error in ScheduleService::getGroupSchedule: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Check for schedule conflicts
     */
    private function checkScheduleConflicts($scheduleData, $excludeId = null)
    {
        $conflicts = [];
        
        // Check teacher availability
        $teacherConflicts = $this->scheduleModel->checkTeacherConflicts(
            $scheduleData['teacher_id'],
            $scheduleData['day_of_week'],
            $scheduleData['start_time'],
            $scheduleData['end_time'],
            $excludeId
        );
        
        if (!empty($teacherConflicts)) {
            $conflicts[] = [
                'type' => 'teacher',
                'message' => 'El docente ya tiene una clase en este horario'
            ];
        }
        
        // Check group availability
        $groupConflicts = $this->scheduleModel->checkGroupConflicts(
            $scheduleData['group_id'],
            $scheduleData['day_of_week'],
            $scheduleData['start_time'],
            $scheduleData['end_time'],
            $excludeId
        );
        
        if (!empty($groupConflicts)) {
            $conflicts[] = [
                'type' => 'group',
                'message' => 'El grupo ya tiene una clase en este horario'
            ];
        }
        
        // Check classroom availability (if classroom is specified)
        if (isset($scheduleData['classroom_id'])) {
            $classroomConflicts = $this->scheduleModel->checkClassroomConflicts(
                $scheduleData['classroom_id'],
                $scheduleData['day_of_week'],
                $scheduleData['start_time'],
                $scheduleData['end_time'],
                $excludeId
            );
            
            if (!empty($classroomConflicts)) {
                $conflicts[] = [
                    'type' => 'classroom',
                    'message' => 'El aula ya está ocupada en este horario'
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Validate schedule data
     */
    private function validateScheduleData($data)
    {
        $errors = [];
        
        // Required fields
        $requiredFields = ['teacher_id', 'subject_id', 'group_id', 'day_of_week', 'start_time', 'end_time'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' es requerido';
            }
        }
        
        // Validate day of week (1-7)
        if (isset($data['day_of_week']) && (!is_numeric($data['day_of_week']) || $data['day_of_week'] < 1 || $data['day_of_week'] > 7)) {
            $errors['day_of_week'] = 'Día de la semana debe ser entre 1 y 7';
        }
        
        // Validate time format
        if (isset($data['start_time']) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['start_time'])) {
            $errors['start_time'] = 'Formato de hora de inicio inválido (HH:MM)';
        }
        
        if (isset($data['end_time']) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['end_time'])) {
            $errors['end_time'] = 'Formato de hora de fin inválido (HH:MM)';
        }
        
        // Validate that end time is after start time
        if (isset($data['start_time']) && isset($data['end_time'])) {
            $startTime = strtotime($data['start_time']);
            $endTime = strtotime($data['end_time']);
            
            if ($endTime <= $startTime) {
                $errors['end_time'] = 'La hora de fin debe ser posterior a la hora de inicio';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Log activity
     */
    private function logActivity($action, $details)
    {
        try {
            if (isset($_SESSION['user']['id_usuario'])) {
                $this->scheduleModel->logActivity($_SESSION['user']['id_usuario'], $action, $details);
            }
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
