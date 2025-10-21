<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../models/Horario.php';

class HorarioController {
    private $db;
    private $horarioModel;
    
    public function __construct($database) {
        $this->db = $database;
        $this->horarioModel = new Horario($database);
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        error_log("HorarioController handleRequest - Action: '$action', Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . json_encode($_POST));
        error_log("GET data: " . json_encode($_GET));
        
        try {
            match ($action) {
                'get' => $this->getHorario(),
                'list' => $this->listHorarios(),
                'list_by_grupo' => $this->listHorariosByGrupo(),
                'list_by_docente' => $this->listHorariosByDocente(),
                'create' => $this->createHorario(),
                'update' => $this->updateHorario(),
                'delete' => $this->deleteHorario(),
                'get_disponibilidad' => $this->getDocenteDisponibilidad(),
                'update_disponibilidad' => $this->updateDocenteDisponibilidad(),
                'get_bloques' => $this->getBloques(),
                'get_grupos' => $this->getGrupos(),
                'get_materias' => $this->getMaterias(),
                'get_docentes' => $this->getDocentes(),
                'get_teachers_by_subject' => $this->getTeachersBySubject(),
                'get_available_assignments' => $this->getAvailableAssignments(),
                'auto_select_teacher' => $this->autoSelectTeacher(),
                'quick_create' => $this->quickCreate(),
                'quick_move' => $this->quickMove(),
                'swap_assignments' => $this->swapAssignments(),
                'check_availability' => $this->checkAvailability(),
                'get_teacher_availability_grid' => $this->getTeacherAvailabilityGrid(),
                default => throw new Exception("Acción no válida: $action")
            };
        } catch (Exception $e) {
            error_log("Error in HorarioController: " . $e->getMessage());
            ResponseHelper::error($e->getMessage());
        }
    }
    
    private function getHorario() {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) {
            ResponseHelper::error("ID de horario requerido");
        }
        
        $horario = $this->horarioModel->getHorarioById($id);
        
        if (!$horario) {
            ResponseHelper::notFound("Horario");
        }
        
        $horarioCompleto = $this->horarioModel->getHorarioCompletoById($id);
        
        if (!$horarioCompleto) {
            ResponseHelper::error("Error al obtener datos completos del horario");
        }
        
        ResponseHelper::success("Horario obtenido exitosamente", $horarioCompleto);
    }
    
    private function listHorarios() {
        $horarios = $this->horarioModel->getAllHorarios();
        if ($horarios === false) {
            ResponseHelper::error("Error al obtener los horarios");
        }
        
        ResponseHelper::success("Horarios obtenidos exitosamente", $horarios);
    }
    
    private function listHorariosByGrupo() {
        $idGrupo = $_POST['id_grupo'] ?? $_GET['id_grupo'] ?? null;
        if (!$idGrupo) {
            ResponseHelper::error("ID de grupo requerido");
        }
        
        $horarios = $this->horarioModel->getHorariosByGrupo($idGrupo);
        if ($horarios === false) {
            ResponseHelper::error("Error al obtener los horarios del grupo");
        }
        
        ResponseHelper::success("Horarios del grupo obtenidos exitosamente", $horarios);
    }
    
    private function listHorariosByDocente() {
        $idDocente = $_POST['id_docente'] ?? $_GET['id_docente'] ?? null;
        if (!$idDocente) {
            ResponseHelper::error("ID de docente requerido");
        }
        
        $horarios = $this->horarioModel->getHorariosByDocente($idDocente);
        if ($horarios === false) {
            ResponseHelper::error("Error al obtener los horarios del docente");
        }
        
        ResponseHelper::success("Horarios del docente obtenidos exitosamente", $horarios);
    }
    
    private function createHorario() {
        $data = $this->validateHorarioData($_POST);
        
        try {
            $this->db->beginTransaction();
            
            $id = $this->horarioModel->createHorario($data);
            if (!$id) {
                throw new Exception("Error al crear el horario");
            }
            
            $this->logActivity("Creó asignación de horario ID: $id");
            $this->db->commit();
            
            ResponseHelper::success('Horario creado exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function updateHorario() {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de horario requerido");
        }
        
        $data = $this->validateHorarioData($_POST, false);
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->horarioModel->updateHorario($id, $data);
            if (!$success) {
                throw new Exception("Error al actualizar el horario");
            }
            
            $this->logActivity("Actualizó asignación de horario ID: $id");
            $this->db->commit();
            
            ResponseHelper::success('Horario actualizado exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function deleteHorario() {
        $id = $_POST['id'] ?? $_POST['id_horario'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de horario requerido");
        }
        
        try {
            $this->db->beginTransaction();
            
            $horario = $this->horarioModel->getHorarioById($id);
            if (!$horario) {
                throw new Exception("Horario no encontrado");
            }
            
            $success = $this->horarioModel->deleteHorario($id);
            if (!$success) {
                throw new Exception("Error al eliminar el horario");
            }
            
            $this->logActivity("Eliminó asignación de horario: " . $horario['grupo_nombre'] . " - " . $horario['materia_nombre']);
            $this->db->commit();
            
            ResponseHelper::success('Horario eliminado exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function getDocenteDisponibilidad() {
        $idDocente = $_POST['id_docente'] ?? $_GET['id_docente'] ?? null;
        if (!$idDocente) {
            ResponseHelper::error("ID de docente requerido");
        }
        
        $disponibilidad = $this->horarioModel->getDocenteDisponibilidad($idDocente);
        ResponseHelper::success("Disponibilidad obtenida exitosamente", $disponibilidad);
    }
    
    private function updateDocenteDisponibilidad() {
        $idDocente = $_POST['id_docente'] ?? null;
        $idBloque = $_POST['id_bloque'] ?? null;
        $dia = $_POST['dia'] ?? null;
        $disponible = isset($_POST['disponible']) ? filter_var($_POST['disponible'], FILTER_VALIDATE_BOOLEAN) : null;
        
        if (!$idDocente || !$idBloque || !$dia || $disponible === null) {
            ResponseHelper::error("Todos los parámetros son requeridos");
        }
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->horarioModel->updateDocenteDisponibilidad($idDocente, $idBloque, $dia, $disponible);
            if (!$success) {
                throw new Exception("Error al actualizar la disponibilidad");
            }
            
            $disponibilidadText = $disponible ? 'disponible' : 'no disponible';
            $this->logActivity("Marcó docente ID $idDocente como $disponibilidadText para $dia bloque $idBloque");
            $this->db->commit();
            
            ResponseHelper::success('Disponibilidad actualizada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function getBloques() {
        $bloques = $this->horarioModel->getAllBloques();
        ResponseHelper::success("Bloques obtenidos exitosamente", $bloques);
    }
    
    private function getGrupos() {
        $grupos = $this->horarioModel->getAllGrupos();
        ResponseHelper::success("Grupos obtenidos exitosamente", $grupos);
    }
    
    private function getMaterias() {
        $materias = $this->horarioModel->getAllMaterias();
        ResponseHelper::success("Materias obtenidas exitosamente", $materias);
    }
    
    private function getDocentes() {
        $docentes = $this->horarioModel->getAllDocentes();
        ResponseHelper::success("Docentes obtenidos exitosamente", $docentes);
    }
    
    private function getTeachersBySubject() {
        $subjectId = $_GET['id_materia'] ?? null;
        
        if (!$subjectId) {
            ResponseHelper::error('ID de materia requerido');
        }
        
        $teachers = $this->horarioModel->getTeachersBySubject($subjectId);
        ResponseHelper::success("Docentes obtenidos exitosamente", $teachers);
    }
    
    private function validateHorarioData($data, $required = true) {
        $errors = [];
        $dias_validos = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
        
        if ($required && empty($data['id_grupo'])) {
            $errors['id_grupo'] = "El grupo es requerido";
        }
        
        if ($required && empty($data['id_docente'])) {
            $errors['id_docente'] = "El docente es requerido";
        }
        
        if ($required && empty($data['id_materia'])) {
            $errors['id_materia'] = "La materia es requerida";
        }
        
        if ($required && empty($data['id_bloque'])) {
            $errors['id_bloque'] = "El bloque horario es requerido";
        }
        
        if ($required && empty($data['dia'])) {
            $errors['dia'] = "El día es requerido";
        } elseif (!empty($data['dia'])) {
            $dia = strtoupper($data['dia']);
            if (!in_array($dia, $dias_validos)) {
                $errors['dia'] = "Día no válido";
            }
        }
        
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }
        
        $validated = [];
        
        if (!empty($data['id_grupo'])) {
            $validated['id_grupo'] = intval($data['id_grupo']);
        }
        
        if (!empty($data['id_docente'])) {
            $validated['id_docente'] = intval($data['id_docente']);
        }
        
        if (!empty($data['id_materia'])) {
            $validated['id_materia'] = intval($data['id_materia']);
        }
        
        if (!empty($data['id_bloque'])) {
            $validated['id_bloque'] = intval($data['id_bloque']);
        }
        
        if (!empty($data['dia'])) {
            $validated['dia'] = strtoupper($data['dia']);
        }
        
        return $validated;
    }
    
    private function logActivity($accion) {
        try {
            require_once __DIR__ . '/../helpers/AuthHelper.php';
            $user = AuthHelper::getCurrentUser();
            
            if ($user && isset($user['id_usuario'])) {
                $stmt = $this->db->prepare("INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())");
                $stmt->execute([$user['id_usuario'], $accion]);
            }
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
    
    /**
     * Get available subject-teacher assignments for drag-and-drop sidebar
     * Returns grouped assignments by subject with all teachers included
     */
    private function getAvailableAssignments() {
        $grupoId = $_GET['grupo_id'] ?? null;
        
        if (!$grupoId) {
            ResponseHelper::error("ID de grupo requerido");
        }
        
        try {
            error_log("Starting getAvailableAssignments for group: $grupoId");
            
            // Get subjects - check if group has assigned subjects first
            $grupoMateriaCheck = "SELECT COUNT(*) as count FROM grupo_materia WHERE id_grupo = ?";
            $checkStmt = $this->db->prepare($grupoMateriaCheck);
            $checkStmt->execute([$grupoId]);
            $hasAssignedSubjects = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if ($hasAssignedSubjects) {
                // Get only subjects assigned to this group
                $materiasQuery = "
                    SELECT DISTINCT m.id_materia, m.nombre, m.horas_semanales
                    FROM materia m
                    INNER JOIN grupo_materia gm ON m.id_materia = gm.id_materia
                    WHERE gm.id_grupo = ?
                    ORDER BY m.nombre
                ";
                $materiasStmt = $this->db->prepare($materiasQuery);
                $materiasStmt->execute([$grupoId]);
            } else {
                // Get all subjects if none assigned to group
                $materiasQuery = "SELECT id_materia, nombre, horas_semanales FROM materia ORDER BY nombre";
                $materiasStmt = $this->db->prepare($materiasQuery);
                $materiasStmt->execute();
            }
            $materias = $materiasStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($materias) . " subjects for group $grupoId");
            
            // Get teachers with their user info (nombre, apellido)
            $docentesQuery = "
                SELECT d.id_docente, u.nombre, u.apellido, d.horas_asignadas
                FROM docente d
                INNER JOIN usuario u ON d.id_usuario = u.id_usuario
                ORDER BY u.nombre, u.apellido
            ";
            $docentesStmt = $this->db->prepare($docentesQuery);
            $docentesStmt->execute();
            $docentes = $docentesStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($docentes) . " teachers");
            
            // Group assignments by subject
            $groupedAssignments = [];
            foreach ($materias as $materia) {
                $teachers = [];
                $totalAvailableTeachers = 0;
                $totalHoursAvailable = 0;
                
                foreach ($docentes as $docente) {
                    // Count hours already assigned for this combination
                    $hoursQuery = "
                        SELECT COUNT(*) as hours_assigned
                        FROM horario
                        WHERE id_docente = ? AND id_materia = ? AND id_grupo = ?
                    ";
                    $hoursStmt = $this->db->prepare($hoursQuery);
                    $hoursStmt->execute([$docente['id_docente'], $materia['id_materia'], $grupoId]);
                    $hoursAssigned = (int)$hoursStmt->fetch(PDO::FETCH_ASSOC)['hours_assigned'];
                    
                    // Use subject's weekly hours as max
                    $maxHours = (int)$materia['horas_semanales'];
                    $hoursAvailable = $maxHours - $hoursAssigned;
                    $isAvailable = $hoursAvailable > 0;
                    
                    // Debug logging for individual teacher calculation
                    error_log("Teacher {$docente['nombre']} {$docente['apellido']} for subject {$materia['nombre']}: assigned={$hoursAssigned}, max={$maxHours}, available={$hoursAvailable}, isAvailable=" . ($isAvailable ? 'true' : 'false'));
                    
                    if ($isAvailable) {
                        $totalAvailableTeachers++;
                        $totalHoursAvailable += $hoursAvailable;
                    }
                    
                    $teachers[] = [
                        'id_docente' => (int)$docente['id_docente'],
                        'nombre' => $docente['nombre'],
                        'apellido' => $docente['apellido'],
                        'hours_assigned' => $hoursAssigned,
                        'hours_available' => $hoursAvailable,
                        'is_available' => $isAvailable,
                        'hours_total' => $maxHours,
                        'global_hours_assigned' => (int)$docente['horas_asignadas'],
                        'score' => $this->calculateTeacherScore($docente['id_docente'], $materia['id_materia'])
                    ];
                }
                
                // Calculate subject-level availability (not per-teacher)
                // Check if subject has any hours assigned to any teacher
                $subjectHoursQuery = "
                    SELECT COUNT(*) as total_assigned_hours
                    FROM horario
                    WHERE id_materia = ? AND id_grupo = ?
                ";
                $subjectHoursStmt = $this->db->prepare($subjectHoursQuery);
                $subjectHoursStmt->execute([$materia['id_materia'], $grupoId]);
                $totalSubjectHoursAssigned = (int)$subjectHoursStmt->fetch(PDO::FETCH_ASSOC)['total_assigned_hours'];
                
                $subjectHoursAvailable = $materia['horas_semanales'] - $totalSubjectHoursAssigned;
                $totalHoursAvailable = $subjectHoursAvailable; // Use subject-level availability
                
                // Calculate percentage based on subject hours
                $availabilityPercentage = $materia['horas_semanales'] > 0 ? round(($subjectHoursAvailable / $materia['horas_semanales']) * 100) : 0;
                
                // Debug logging for availability calculation
                error_log("Subject {$materia['nombre']}: {$totalAvailableTeachers}/" . count($teachers) . " teachers available, {$subjectHoursAvailable}/{$materia['horas_semanales']} hours available, {$availabilityPercentage}% (subject-level)");
                
                $groupedAssignments[] = [
                    'id_materia' => (int)$materia['id_materia'],
                    'materia_nombre' => $materia['nombre'],
                    'horas_semanales' => (int)$materia['horas_semanales'],
                    'teachers' => $teachers,
                    'total_teachers' => count($teachers),
                    'available_teachers' => $totalAvailableTeachers,
                    'total_hours_available' => $totalHoursAvailable,
                    'availability_percentage' => $availabilityPercentage,
                    'is_auto_selectable' => $totalAvailableTeachers > 0
                ];
            }
            
            error_log("Successfully created " . count($groupedAssignments) . " grouped assignments for group $grupoId");
            ResponseHelper::success("Asignaciones agrupadas obtenidas exitosamente", $groupedAssignments);
            
        } catch (Exception $e) {
            error_log("Error getting available assignments: " . $e->getMessage());
            ResponseHelper::error("Error al obtener las asignaciones disponibles");
        }
    }
    
    /**
     * Calculate teacher score based on workload and teaching history
     */
    private function calculateTeacherScore($teacherId, $subjectId) {
        try {
            // Get teacher's current hours
            $teacherQuery = "SELECT horas_asignadas FROM docente WHERE id_docente = ?";
            $teacherStmt = $this->db->prepare($teacherQuery);
            $teacherStmt->execute([$teacherId]);
            $result = $teacherStmt->fetch(PDO::FETCH_ASSOC);
            $teacherHours = $result ? (int)$result['horas_asignadas'] : 0;
            
            // Get average hours
            $avgQuery = "SELECT AVG(horas_asignadas) as avg_hours FROM docente";
            $avgStmt = $this->db->prepare($avgQuery);
            $avgStmt->execute();
            $avgResult = $avgStmt->fetch(PDO::FETCH_ASSOC);
            $avgHours = $avgResult ? (float)$avgResult['avg_hours'] : 0;
            
            // Workload score (lower workload = higher score)
            $workloadRatio = $avgHours > 0 ? $teacherHours / $avgHours : 1;
            $workloadScore = max(0, 100 - ($workloadRatio * 50));
            
            // Teaching history score
            $historyQuery = "SELECT COUNT(*) as count FROM horario WHERE id_docente = ? AND id_materia = ?";
            $historyStmt = $this->db->prepare($historyQuery);
            $historyStmt->execute([$teacherId, $subjectId]);
            $historyResult = $historyStmt->fetch(PDO::FETCH_ASSOC);
            $historyCount = $historyResult ? (int)$historyResult['count'] : 0;
            $historyScore = min(30, $historyCount * 10); // Up to 30 points for history
            
            return round($workloadScore + $historyScore);
        } catch (Exception $e) {
            error_log("Error calculating teacher score: " . $e->getMessage());
            return 50; // Default mid-range score on error
        }
    }
    
    /**
     * Auto-select best teacher for a subject assignment
     */
    private function autoSelectTeacher() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            ResponseHelper::error("Datos JSON requeridos");
        }
        
        $subjectId = $data['id_materia'] ?? null;
        $groupId = $data['id_grupo'] ?? null;
        $blockId = $data['id_bloque'] ?? null;
        $day = $data['dia'] ?? null;
        
        if (!$subjectId || !$groupId || !$blockId || !$day) {
            ResponseHelper::error("Parámetros requeridos: id_materia, id_grupo, id_bloque, dia");
        }
        
        try {
            require_once __DIR__ . '/TeacherSelectionService.php';
            $selectionService = new TeacherSelectionService($this->db);
            
            $result = $selectionService->selectBestTeacher($subjectId, $groupId, $blockId, $day);
            
            if ($result['success']) {
                ResponseHelper::success("Docente seleccionado automáticamente", $result);
            } else {
                ResponseHelper::error($result['message']);
            }
            
        } catch (Exception $e) {
            error_log("Error in autoSelectTeacher: " . $e->getMessage());
            ResponseHelper::error("Error al seleccionar docente automáticamente");
        }
    }
    
    /**
     * Quick create assignment for drag-and-drop
     */
    private function quickCreate() {
        error_log("Starting quickCreate");
        
        $data = json_decode(file_get_contents('php://input'), true);
        error_log("Received data: " . json_encode($data));
        
        if (!$data) {
            error_log("No data received");
            ResponseHelper::error("Datos JSON requeridos");
        }
        
        try {
            error_log("Validating data...");
            error_log("Data to validate: " . json_encode($data));
            $this->validateHorarioData($data);
            error_log("Data validation passed");
            
            $this->db->beginTransaction();
            
            // Check for conflicts (unless force override)
            $forceOverride = $data['force_override'] ?? false;

            if (!$forceOverride) {
                error_log("Checking conflicts...");
                $conflicts = $this->checkConflicts($data);
                if (!empty($conflicts)) {
                    error_log("Conflicts found: " . implode(', ', $conflicts));
                    $this->db->rollback();
                    ResponseHelper::error("Conflicto detectado: " . implode(', ', $conflicts));
                }
                error_log("No conflicts found");
            } else {
                error_log("Skipping conflict check (force override enabled)");
            }
            
            // Validate hours available
            error_log("Validating hours available...");
            $hoursQuery = "SELECT COUNT(*) as hours_assigned FROM horario 
                           WHERE id_docente = ? AND id_materia = ? AND id_grupo = ?";
            $hoursStmt = $this->db->prepare($hoursQuery);
            $hoursStmt->execute([$data['id_docente'], $data['id_materia'], $data['id_grupo']]);
            $hoursAssigned = (int)$hoursStmt->fetch(PDO::FETCH_ASSOC)['hours_assigned'];
            
            // Get subject's weekly hours
            $subjectQuery = "SELECT horas_semanales FROM materia WHERE id_materia = ?";
            $subjectStmt = $this->db->prepare($subjectQuery);
            $subjectStmt->execute([$data['id_materia']]);
            $maxHours = (int)$subjectStmt->fetch(PDO::FETCH_ASSOC)['horas_semanales'];
            
            error_log("Hours validation: assigned=$hoursAssigned, max=$maxHours");
            
            if ($hoursAssigned >= $maxHours) {
                error_log("No hours available for this subject");
                $this->db->rollback();
                ResponseHelper::error("No hay horas disponibles para esta materia");
            }
            
            error_log("Creating horario...");
            $id = $this->horarioModel->createHorario($data);
            if (!$id) {
                throw new Exception("Error al crear el horario");
            }
            error_log("Horario created with ID: $id");
            
            // Get the created assignment with full details
            $assignment = $this->horarioModel->getHorarioCompletoById($id);
            error_log("Assignment details: " . json_encode($assignment));
            
            $this->logActivity("Creó asignación rápida ID: $id");
            $this->db->commit();
            
            ResponseHelper::success("Asignación creada exitosamente", $assignment);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Quick move assignment for drag-and-drop
     */
    private function quickMove() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            ResponseHelper::error("Datos JSON requeridos");
        }
        
        $idHorario = $data['id_horario'] ?? null;
        $newBloque = $data['new_bloque'] ?? null;
        $newDia = $data['new_dia'] ?? null;
        
        if (!$idHorario || !$newBloque || !$newDia) {
            ResponseHelper::error("ID de horario, nuevo bloque y nuevo día son requeridos");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Get current assignment
            $current = $this->horarioModel->getHorarioById($idHorario);
            if (!$current) {
                throw new Exception("Horario no encontrado");
            }
            
            // Check for conflicts with new position
            $conflictData = [
                'id_grupo' => $current['id_grupo'],
                'id_docente' => $current['id_docente'],
                'id_materia' => $current['id_materia'],
                'id_bloque' => $newBloque,
                'dia' => $newDia
            ];
            
            $conflicts = $this->checkConflicts($conflictData, $idHorario);
            if (!empty($conflicts)) {
                $this->db->rollback();
                ResponseHelper::error("Conflicto detectado: " . implode(', ', $conflicts));
            }
            
            // Update the assignment
            $success = $this->horarioModel->updateHorario($idHorario, [
                'id_grupo' => $current['id_grupo'],
                'id_materia' => $current['id_materia'],
                'id_docente' => $current['id_docente'],
                'id_bloque' => $newBloque,
                'dia' => $newDia
            ]);
            
            if (!$success) {
                throw new Exception("Error al mover el horario");
            }
            
            // Get updated assignment with full details
            $assignment = $this->horarioModel->getHorarioCompletoById($idHorario);
            
            $this->logActivity("Movió horario ID: $idHorario a $newDia bloque $newBloque");
            $this->db->commit();
            
            ResponseHelper::success("Horario movido exitosamente", $assignment);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Swap two assignments positions
     */
    private function swapAssignments() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        error_log("swapAssignments - Received data: " . json_encode($data));
        
        if (!$data) {
            error_log("swapAssignments - No data received");
            ResponseHelper::error("Datos JSON requeridos");
        }
        
        $idHorario1 = $data['id_horario_1'] ?? null;
        $idHorario2 = $data['id_horario_2'] ?? null;
        $forceOverride = $data['force_override'] ?? false;
        
        error_log("swapAssignments - Parsed: idHorario1=$idHorario1, idHorario2=$idHorario2, forceOverride=" . ($forceOverride ? 'true' : 'false'));
        
        if (!$idHorario1 || !$idHorario2) {
            error_log("swapAssignments - Missing IDs: idHorario1=$idHorario1, idHorario2=$idHorario2");
            ResponseHelper::error("IDs de ambos horarios son requeridos");
        }
        
        if ($idHorario1 == $idHorario2) {
            error_log("swapAssignments - Same IDs: idHorario1=$idHorario1, idHorario2=$idHorario2");
            ResponseHelper::error("No se puede intercambiar un horario consigo mismo");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Get both assignments
            error_log("swapAssignments - Getting assignment1 with ID: $idHorario1");
            $assignment1 = $this->horarioModel->getHorarioById($idHorario1);
            error_log("swapAssignments - Assignment1 result: " . json_encode($assignment1));
            
            error_log("swapAssignments - Getting assignment2 with ID: $idHorario2");
            $assignment2 = $this->horarioModel->getHorarioById($idHorario2);
            error_log("swapAssignments - Assignment2 result: " . json_encode($assignment2));
            
            if (!$assignment1) {
                error_log("swapAssignments - Assignment1 not found");
                throw new Exception("Horario 1 no encontrado");
            }
            if (!$assignment2) {
                error_log("swapAssignments - Assignment2 not found");
                throw new Exception("Horario 2 no encontrado");
            }
            
            // Store original positions
            $original1 = [
                'id_bloque' => $assignment1['id_bloque'],
                'dia' => $assignment1['dia']
            ];
            $original2 = [
                'id_bloque' => $assignment2['id_bloque'],
                'dia' => $assignment2['dia']
            ];
            
            // Check for conflicts if not forcing override
            if (!$forceOverride) {
                // For swap, we only need to check teacher availability, not group conflicts
                // since both groups already have classes in those time slots
                $teacherConflicts = [];
                
                // Check if teacher 1 is available in position 2 (only teacher conflicts)
                // Exclude both assignments being swapped from the check
                if (!$this->isDocenteAvailableForSwap($assignment1['id_docente'], $original2['id_bloque'], $original2['dia'], $idHorario1, $idHorario2)) {
                    $teacherConflicts[] = "El docente de la primera asignación no está disponible en el nuevo horario";
                }
                
                // Check if teacher 2 is available in position 1 (only teacher conflicts)
                // Exclude both assignments being swapped from the check
                if (!$this->isDocenteAvailableForSwap($assignment2['id_docente'], $original1['id_bloque'], $original1['dia'], $idHorario1, $idHorario2)) {
                    $teacherConflicts[] = "El docente de la segunda asignación no está disponible en el nuevo horario";
                }
                
                if (!empty($teacherConflicts)) {
                    $this->db->rollback();
                    ResponseHelper::error("Conflicto de disponibilidad detectado: " . implode(', ', $teacherConflicts));
                }
            }
            
            // Perform the swap by directly updating the database
            // This avoids the conflict checking in updateHorario()
            $query1 = "UPDATE horario SET id_bloque = :bloque, dia = :dia WHERE id_horario = :id";
            $stmt1 = $this->db->prepare($query1);
            $success1 = $stmt1->execute([
                ':bloque' => $original2['id_bloque'],
                ':dia' => $original2['dia'],
                ':id' => $idHorario1
            ]);
            
            $query2 = "UPDATE horario SET id_bloque = :bloque, dia = :dia WHERE id_horario = :id";
            $stmt2 = $this->db->prepare($query2);
            $success2 = $stmt2->execute([
                ':bloque' => $original1['id_bloque'],
                ':dia' => $original1['dia'],
                ':id' => $idHorario2
            ]);
            
            if (!$success1 || !$success2) {
                throw new Exception("Error al intercambiar los horarios");
            }
            
            // Get updated assignments with full details
            $updatedAssignment1 = $this->horarioModel->getHorarioCompletoById($idHorario1);
            $updatedAssignment2 = $this->horarioModel->getHorarioCompletoById($idHorario2);
            
            $this->logActivity("Intercambió horarios ID: $idHorario1 ↔ $idHorario2");
            $this->db->commit();
            
            ResponseHelper::success("Horarios intercambiados exitosamente", [
                'assignment1' => $updatedAssignment1,
                'assignment2' => $updatedAssignment2
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Check if a teacher is available for a swap operation
     * Only checks teacher availability, not group conflicts
     * For swaps, we need to exclude BOTH assignments being swapped
     */
    private function isDocenteAvailableForSwap($docenteId, $bloque, $dia, $excludeHorarioId = null, $excludeHorarioId2 = null) {
        try {
            // Check if teacher has any other assignment at this time (excluding both assignments being swapped)
            $query = "SELECT COUNT(*) as count FROM horario 
                     WHERE id_docente = :docente_id 
                     AND id_bloque = :bloque 
                     AND dia = :dia";
            
            $params = [
                ':docente_id' => $docenteId,
                ':bloque' => $bloque,
                ':dia' => $dia
            ];
            
            if ($excludeHorarioId) {
                $query .= " AND id_horario != :exclude_id";
                $params[':exclude_id'] = $excludeHorarioId;
            }
            
            if ($excludeHorarioId2) {
                $query .= " AND id_horario != :exclude_id2";
                $params[':exclude_id2'] = $excludeHorarioId2;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Teacher is available if they have no other assignments at this time
            return $result['count'] == 0;
            
        } catch (Exception $e) {
            error_log("Error checking teacher availability for swap: " . $e->getMessage());
            return false; // Assume not available if check fails
        }
    }
    
    /**
     * Check teacher availability for a specific time slot
     */
    private function checkAvailability() {
        $docenteId = $_GET['docente_id'] ?? null;
        $bloque = $_GET['bloque'] ?? null;
        $dia = $_GET['dia'] ?? null;
        
        if (!$docenteId || !$bloque || !$dia) {
            ResponseHelper::error("ID de docente, bloque y día son requeridos");
        }
        
        // Get actual availability from database
        $query = "SELECT disponible FROM disponibilidad 
                  WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':id_docente' => $docenteId,
            ':id_bloque' => $bloque,
            ':dia' => $dia
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $isAvailable = $result ? (bool)$result['disponible'] : true; // default true if no record
        
        ResponseHelper::success("Disponibilidad verificada", [
            'is_available' => $isAvailable,
            'docente_id' => $docenteId,
            'bloque' => $bloque,
            'dia' => $dia
        ]);
    }
    
    /**
     * Get teacher availability grid for all time slots
     */
    private function getTeacherAvailabilityGrid() {
        $docenteId = $_GET['docente_id'] ?? null;
        
        if (!$docenteId) {
            ResponseHelper::error("ID de docente requerido");
        }
        
        $query = "SELECT id_bloque, dia, disponible 
                  FROM disponibilidad 
                  WHERE id_docente = :id_docente";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id_docente' => $docenteId]);
        $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format as nested array for easy lookup: [dia][bloque] = available
        $grid = [];
        foreach ($availability as $slot) {
            if (!isset($grid[$slot['dia']])) {
                $grid[$slot['dia']] = [];
            }
            $grid[$slot['dia']][$slot['id_bloque']] = (bool)$slot['disponible'];
        }
        
        ResponseHelper::success("Disponibilidad del docente obtenida", [
            'docente_id' => $docenteId,
            'availability_grid' => $grid
        ]);
    }
    
    /**
     * Check for conflicts in schedule assignment
     */
    private function checkConflicts($data, $excludeId = null) {
        $conflicts = [];
        
        
        try {
            // Check teacher conflicts
            $teacherQuery = "
                SELECT h.id_horario, g.nombre as grupo_nombre, m.nombre as materia_nombre
                FROM horario h
                JOIN grupo g ON h.id_grupo = g.id_grupo
                JOIN materia m ON h.id_materia = m.id_materia
                WHERE h.id_docente = ? AND h.id_bloque = ? AND h.dia = ?
            ";
            
            if ($excludeId) {
                $teacherQuery .= " AND h.id_horario != ?";
            }
            
            $stmt = $this->db->prepare($teacherQuery);
            $params = [$data['id_docente'], $data['id_bloque'], $data['dia']];
            if ($excludeId) {
                $params[] = $excludeId;
            }
            $stmt->execute($params);
            $teacherConflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($teacherConflicts)) {
                $conflict = $teacherConflicts[0];
                $conflicts[] = "El docente ya tiene una clase en este horario ({$conflict['materia_nombre']} - {$conflict['grupo_nombre']})";
            }
            
            // Check group conflicts
            $groupQuery = "
                SELECT h.id_horario, u.nombre as docente_nombre, u.apellido as docente_apellido, m.nombre as materia_nombre
                FROM horario h
                JOIN docente d ON h.id_docente = d.id_docente
                JOIN usuario u ON d.id_usuario = u.id_usuario
                JOIN materia m ON h.id_materia = m.id_materia
                WHERE h.id_grupo = ? AND h.id_bloque = ? AND h.dia = ?
            ";
            
            if ($excludeId) {
                $groupQuery .= " AND h.id_horario != ?";
            }
            
            $stmt = $this->db->prepare($groupQuery);
            $params = [$data['id_grupo'], $data['id_bloque'], $data['dia']];
            if ($excludeId) {
                $params[] = $excludeId;
            }
            $stmt->execute($params);
            $groupConflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($groupConflicts)) {
                $conflict = $groupConflicts[0];
                $conflicts[] = "El grupo ya tiene una clase en este horario ({$conflict['materia_nombre']} - {$conflict['docente_nombre']} {$conflict['docente_apellido']})";
            }
            
            // Check teacher availability from disponibilidad table
            $availabilityQuery = "SELECT disponible FROM disponibilidad 
                                 WHERE id_docente = ? AND id_bloque = ? AND dia = ?";
            $stmt = $this->db->prepare($availabilityQuery);
            $stmt->execute([$data['id_docente'], $data['id_bloque'], $data['dia']]);
            $availability = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Only block if there's a record AND it's explicitly set to false (unavailable)
            // If no record exists, teacher is available by default
            if ($availability && ($availability['disponible'] === false || $availability['disponible'] === '0' || $availability['disponible'] === 0)) {
                $conflicts[] = "El docente no está disponible en este horario";
            }
            
        } catch (Exception $e) {
            error_log("Error checking conflicts: " . $e->getMessage());
            $conflicts[] = "Error al verificar conflictos";
        }
        
        return $conflicts;
    }
}
