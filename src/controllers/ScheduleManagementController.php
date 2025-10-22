<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../app/Services/ConflictDetectionService.php';
require_once __DIR__ . '/../models/Horario.php';

class ScheduleManagementController
{
    private $db;
    private $horarioModel;
    private $conflictService;
    
    public function __construct($database)
    {
        $this->db = $database;
        $this->horarioModel = new Horario($database);
        $this->conflictService = new ConflictDetectionService($database);
    }
    
    public function handleRequest()
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            match ($action) {
                'get' => $this->getSchedule(),
                'create_schedule' => $this->createSchedule(),
                'update_schedule' => $this->updateSchedule(),
                'delete_schedule' => $this->deleteSchedule(),
                'check_conflicts' => $this->checkConflicts(),
                'get_schedule_grid' => $this->getScheduleGrid(),
                'get_teacher_availability' => $this->getTeacherAvailability(),
                'get_subject_requirements' => $this->getSubjectRequirements(),
                'get_anep_guidelines' => $this->getANEPGuidelines(),
                'bulk_create_schedules' => $this->bulkCreateSchedules(),
                'bulk_delete' => $this->bulkDelete(),
                'bulk_copy' => $this->bulkCopy(),
                'bulk_move' => $this->bulkMove(),
                'bulk_edit' => $this->bulkEdit(),
                'validate_schedule' => $this->validateSchedule(),
                'get_teacher_workload' => $this->getTeacherWorkload(),
                default => throw new Exception("Acción no válida: $action")
            };
        } catch (Exception $e) {
            error_log("Error in ScheduleManagementController: " . $e->getMessage());
            ResponseHelper::error($e->getMessage());
        }
    }
    
    /**
     * Obtiene un horario específico por ID
     */
    private function getSchedule()
    {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de horario requerido");
        }
        
        $horario = $this->horarioModel->getHorarioById($id);
        if (!$horario) {
            ResponseHelper::notFound("Horario");
        }
        
        ResponseHelper::success("Horario obtenido exitosamente", $horario);
    }
    
    /**
     * Crea una nueva asignación de horario con detección de conflictos
     */
    private function createSchedule()
    {
        $data = $this->validateScheduleData($_POST);

        $conflicts = $this->conflictService->detectConflicts($data);

        $criticalConflicts = array_filter($conflicts, function($conflict) {
            return $conflict['severity'] === 'error';
        });
        
        if (!empty($criticalConflicts)) {
            ResponseHelper::error('No se puede crear el horario debido a conflictos críticos', [
                'conflicts' => $conflicts,
                'suggestions' => $this->conflictService->getConflictSuggestions($conflicts)
            ]);
        }
        
        try {
            $this->db->beginTransaction();
            
            $id = $this->horarioModel->createHorario($data);
            if (!$id) {
                throw new Exception("Error al crear el horario");
            }
            
            $this->logActivity("Creó asignación de horario ID: $id");
            $this->db->commit();
            
            ResponseHelper::success('Horario creado exitosamente', [
                'id' => $id,
                'warnings' => array_filter($conflicts, function($conflict) {
                    return $conflict['severity'] === 'warning';
                }),
                'info' => array_filter($conflicts, function($conflict) {
                    return $conflict['severity'] === 'info';
                })
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Actualiza una asignación de horario existente
     */
    private function updateSchedule()
    {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de horario requerido");
        }
        
        $data = $this->validateScheduleData($_POST, false);

        $conflicts = $this->conflictService->detectConflicts($data, $id);

        $criticalConflicts = array_filter($conflicts, function($conflict) {
            return $conflict['severity'] === 'error';
        });
        
        if (!empty($criticalConflicts)) {
            ResponseHelper::error('No se puede actualizar el horario debido a conflictos críticos', [
                'conflicts' => $conflicts,
                'suggestions' => $this->conflictService->getConflictSuggestions($conflicts)
            ]);
        }
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->horarioModel->updateHorario($id, $data);
            if (!$success) {
                throw new Exception("Error al actualizar el horario");
            }
            
            $this->logActivity("Actualizó asignación de horario ID: $id");
            $this->db->commit();
            
            ResponseHelper::success('Horario actualizado exitosamente', [
                'warnings' => array_filter($conflicts, function($conflict) {
                    return $conflict['severity'] === 'warning';
                }),
                'info' => array_filter($conflicts, function($conflict) {
                    return $conflict['severity'] === 'info';
                })
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Elimina una asignación de horario
     */
    private function deleteSchedule()
    {
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
    
    /**
     * Verifica conflictos sin crear el horario
     */
    private function checkConflicts()
    {
        $data = $this->validateScheduleData($_POST, false);
        $excludeId = $_POST['exclude_id'] ?? null;
        
        $conflicts = $this->conflictService->detectConflicts($data, $excludeId);
        $suggestions = $this->conflictService->getConflictSuggestions($conflicts);

        $anepConflicts = null;
        $regularConflicts = [];
        
        foreach ($conflicts as $conflict) {
            if ($conflict['type'] === 'anep') {
                $anepConflicts = $conflict['details'];
            } else {
                $regularConflicts[] = $conflict;
            }
        }
        
        ResponseHelper::success('Análisis de conflictos completado', [
            'anep_conflicts' => $anepConflicts,
            'regular_conflicts' => $regularConflicts,
            'suggestions' => $suggestions,
            'has_critical_conflicts' => !empty(array_filter($conflicts, function($conflict) {
                return $conflict['severity'] === 'error';
            }))
        ]);
    }
    
    /**
     * Obtiene la grilla de horarios completa
     */
    private function getScheduleGrid()
    {
        try {
            $grupoId = $_GET['grupo_id'] ?? null;
            $docenteId = $_GET['docente_id'] ?? null;
            
            if ($grupoId) {
                $horarios = $this->horarioModel->getHorariosByGrupo($grupoId);
            } elseif ($docenteId) {
                $horarios = $this->horarioModel->getHorariosByDocente($docenteId);
            } else {
                $horarios = $this->horarioModel->getAllHorarios();
            }
            
            if ($horarios === false) {
                throw new Exception("Error obteniendo horarios de la base de datos");
            }
            
            $bloques = $this->horarioModel->getAllBloques();
            
            if ($bloques === false) {
                throw new Exception("Error obteniendo bloques de la base de datos");
            }

            $grid = $this->organizeScheduleGrid($horarios, $bloques);
            
            ResponseHelper::success('Grilla de horarios obtenida', [
                'grid' => $grid,
                'horarios_count' => count($horarios),
                'bloques_count' => count($bloques)
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getScheduleGrid: " . $e->getMessage());
            ResponseHelper::error('Error obteniendo grilla de horarios: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtiene la disponibilidad de un docente
     */
    private function getTeacherAvailability()
    {
        $docenteId = $_GET['docente_id'] ?? null;
        if (!$docenteId) {
            ResponseHelper::error("ID de docente requerido");
        }
        
        $disponibilidad = $this->horarioModel->getDocenteDisponibilidad($docenteId);
        ResponseHelper::success('Disponibilidad obtenida', $disponibilidad);
    }
    
    /**
     * Obtiene los requerimientos de una materia
     */
    private function getSubjectRequirements()
    {
        $materiaId = $_GET['materia_id'] ?? null;
        if (!$materiaId) {
            ResponseHelper::error("ID de materia requerido");
        }
        
        try {
            $query = "SELECT m.*, pa.dias_minimos, pa.dias_maximos, pa.condiciones_especiales
                     FROM materia m
                     JOIN pauta_anep pa ON m.id_pauta_anep = pa.id_pauta_anep
                     WHERE m.id_materia = :id_materia";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_materia', $materiaId, PDO::PARAM_INT);
            $stmt->execute();
            
            $materia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$materia) {
                ResponseHelper::error('Materia no encontrada');
            }
            
            ResponseHelper::success('Requerimientos obtenidos', $materia);
            
        } catch (PDOException $e) {
            error_log("Error getting subject requirements: " . $e->getMessage());
            ResponseHelper::error('Error obteniendo requerimientos');
        }
    }
    
    /**
     * Obtiene las pautas ANEP
     */
    private function getANEPGuidelines()
    {
        try {
            $query = "SELECT * FROM pauta_anep ORDER BY nombre";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $pautas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ResponseHelper::success('Pautas ANEP obtenidas', $pautas);
            
        } catch (PDOException $e) {
            error_log("Error getting ANEP guidelines: " . $e->getMessage());
            ResponseHelper::error('Error obteniendo pautas ANEP');
        }
    }
    
    /**
     * Crea múltiples horarios en lote
     */
    private function bulkCreateSchedules()
    {
        $schedules = $_POST['schedules'] ?? [];
        
        if (empty($schedules) || !is_array($schedules)) {
            ResponseHelper::error('Lista de horarios requerida');
        }
        
        $results = [];
        $errors = [];
        
        try {
            $this->db->beginTransaction();
            
            foreach ($schedules as $index => $scheduleData) {
                try {
                    $validatedData = $this->validateScheduleData($scheduleData);

                    $conflicts = $this->conflictService->detectConflicts($validatedData);
                    $criticalConflicts = array_filter($conflicts, function($conflict) {
                        return $conflict['severity'] === 'error';
                    });
                    
                    if (!empty($criticalConflicts)) {
                        $errors[] = [
                            'index' => $index,
                            'message' => 'Conflicto crítico detectado',
                            'conflicts' => $conflicts
                        ];
                        continue;
                    }
                    
                    $id = $this->horarioModel->createHorario($validatedData);
                    if ($id) {
                        $results[] = [
                            'index' => $index,
                            'id' => $id,
                            'success' => true
                        ];
                    } else {
                        $errors[] = [
                            'index' => $index,
                            'message' => 'Error al crear horario'
                        ];
                    }
                    
                } catch (Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'message' => $e->getMessage()
                    ];
                }
            }
            
            $this->db->commit();
            
            ResponseHelper::success('Procesamiento en lote completado', [
                'created' => $results,
                'errors' => $errors,
                'total_processed' => count($schedules),
                'successful' => count($results),
                'failed' => count($errors)
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Valida un horario completo
     */
    private function validateSchedule()
    {
        $grupoId = $_GET['grupo_id'] ?? null;
        $docenteId = $_GET['docente_id'] ?? null;
        
        if (!$grupoId && !$docenteId) {
            ResponseHelper::error('ID de grupo o docente requerido');
        }
        
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'suggestions' => []
        ];

        ResponseHelper::success('Validación completada', $validation);
    }
    
    /**
     * Valida los datos de horario
     */
    private function validateScheduleData($data, $required = true)
    {
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
    
    /**
     * Organiza los horarios en una grilla
     */
    private function organizeScheduleGrid($horarios, $bloques)
    {
        $grid = [];
        $dias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
        
        foreach ($dias as $dia) {
            $grid[$dia] = [];
            foreach ($bloques as $bloque) {
                $grid[$dia][(int)$bloque['id_bloque']] = null;
            }
        }
        
        foreach ($horarios as $horario) {
            $dia = $horario['dia'];
            $idBloque = (int)$horario['id_bloque'];
            if (isset($grid[$dia]) && array_key_exists($idBloque, $grid[$dia])) {
                $grid[$dia][$idBloque] = $horario;
            }
        }
        return $grid;
    }
    
    /**
     * Registra actividad en el log
     */
    private function logActivity($accion)
    {
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
     * Elimina múltiples horarios en lote
     */
    private function bulkDelete()
    {
        $scheduleIds = json_decode($_POST['schedule_ids'] ?? '[]', true);
        
        if (empty($scheduleIds) || !is_array($scheduleIds)) {
            ResponseHelper::error('No se proporcionaron IDs de horarios válidos');
            return;
        }
        
        try {
            $deletedCount = 0;
            $errors = [];
            
            foreach ($scheduleIds as $scheduleId) {
                if (!is_numeric($scheduleId)) {
                    $errors[] = "ID de horario inválido: $scheduleId";
                    continue;
                }
                
                $query = "DELETE FROM horario WHERE id_horario = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id', $scheduleId, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $deletedCount++;
                } else {
                    $errors[] = "Error eliminando horario ID: $scheduleId";
                }
            }
            
            if ($deletedCount > 0) {
                $this->logActivity('Bulk delete schedules: ' . $deletedCount . ' deleted');
                ResponseHelper::success("Eliminados $deletedCount horario(s)", [
                    'deleted_count' => $deletedCount,
                    'errors' => $errors
                ]);
            } else {
                ResponseHelper::error('No se pudo eliminar ningún horario', $errors);
            }
            
        } catch (PDOException $e) {
            error_log("Error in bulkDelete: " . $e->getMessage());
            ResponseHelper::error('Error eliminando horarios: ' . $e->getMessage());
        }
    }
    
    /**
     * Copia múltiples horarios en lote
     */
    private function bulkCopy()
    {
        ResponseHelper::error('Funcionalidad de copia en lote en desarrollo');
    }
    
    /**
     * Mueve múltiples horarios en lote
     */
    private function bulkMove()
    {
        ResponseHelper::error('Funcionalidad de movimiento en lote en desarrollo');
    }
    
    /**
     * Edita múltiples horarios en lote
     */
    private function bulkEdit()
    {
        ResponseHelper::error('Funcionalidad de edición en lote en desarrollo');
    }

    private function getTeacherWorkload()
    {
        try {

            $query = "
                SELECT 
                    d.id_docente,
                    CONCAT(u.nombre, ' ', u.apellido) as nombre,
                    COUNT(h.id_horario) as horas,
                    STRING_AGG(DISTINCT m.nombre, ', ') as materias,
                    STRING_AGG(DISTINCT g.nombre, ', ') as grupos
                FROM docente d
                INNER JOIN usuario u ON d.id_usuario = u.id_usuario
                LEFT JOIN horario h ON d.id_docente = h.id_docente
                LEFT JOIN materia m ON h.id_materia = m.id_materia
                LEFT JOIN grupo g ON h.id_grupo = g.id_grupo
                GROUP BY d.id_docente, u.nombre, u.apellido
                ORDER BY horas DESC, nombre ASC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $processedTeachers = [];
            foreach ($teachers as $teacher) {
                $processedTeachers[] = [
                    'id' => $teacher['id_docente'],
                    'name' => $teacher['nombre'],
                    'hours' => (int)$teacher['horas'],
                    'subjects' => $teacher['materias'] ? array_filter(explode(', ', $teacher['materias'])) : [],
                    'groups' => $teacher['grupos'] ? array_filter(explode(', ', $teacher['grupos'])) : []
                ];
            }
            
            ResponseHelper::success('Datos de carga horaria obtenidos', [
                'teachers' => $processedTeachers,
                'total_teachers' => count($processedTeachers),
                'total_hours' => array_sum(array_column($processedTeachers, 'hours'))
            ]);
            
        } catch (PDOException $e) {
            error_log("Error getting teacher workload: " . $e->getMessage());
            ResponseHelper::error('Error obteniendo datos de carga horaria: ' . $e->getMessage());
        }
    }
}
