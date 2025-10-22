<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Teacher Auto-Selection Service
 * Intelligent algorithm to select the best teacher for a subject assignment
 */
class TeacherSelectionService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Select the best teacher for a subject assignment based on multiple criteria
     * 
     * @param int $subjectId
     * @param int $groupId
     * @param int $blockId
     * @param string $day
     * @return array
     */
    public function selectBestTeacher($subjectId, $groupId, $blockId, $day, $skipExisting = false) {
        error_log("=== TEACHER SELECTION STARTED ===");
        error_log("Subject ID: $subjectId, Group ID: $groupId, Block ID: $blockId, Day: $day, Skip Existing: " . ($skipExisting ? 'true' : 'false'));
        
        try {
            // First, check if there's already a teacher for this subject in this group
            if (!$skipExisting) {
                $existingTeacher = $this->getExistingTeacherForSubjectInGroup($subjectId, $groupId);
                
                if ($existingTeacher) {
                    // Check if this teacher is available for the requested slot
                    $availScore = $this->checkAvailability($existingTeacher['id_docente'], $day, $blockId);
                    
                    if ($availScore > 0) {
                        // Existing teacher is available - check for conflicts
                        $conflictResult = $this->checkConflicts($existingTeacher['id_docente'], $groupId, $day, $blockId);
                        $conflictScore = $conflictResult['score'];
                        
                        if ($conflictScore > 0) {
                            // No conflicts - perfect! Use existing teacher
                            return [
                                'success' => true,
                                'selected_teacher' => [
                                    'id_docente' => $existingTeacher['id_docente'],
                                    'nombre' => $existingTeacher['nombre'],
                                    'apellido' => $existingTeacher['apellido']
                                ],
                                'reason' => 'existing_teacher',
                                'message' => 'Manteniendo consistencia: ' . $existingTeacher['nombre'] . ' ' . $existingTeacher['apellido'] . ' ya enseña esta materia en este grupo'
                            ];
                        }
                    }
                    
                    // Existing teacher is unavailable - return this info
                    return [
                        'success' => false,
                        'message' => 'El docente actual (' . $existingTeacher['nombre'] . ' ' . $existingTeacher['apellido'] . ') no está disponible en este horario',
                        'existing_teacher' => [
                            'id_docente' => $existingTeacher['id_docente'],
                            'nombre' => $existingTeacher['nombre'],
                            'apellido' => $existingTeacher['apellido']
                        ],
                        'reason' => 'existing_teacher_unavailable',
                        'can_use_alternative' => true
                    ];
                }
            }
            
            // No existing teacher or skipping existing - proceed with normal algorithm
            $teachers = $this->getTeachersForSubject($subjectId);
            
            if (empty($teachers)) {
                return [
                    'success' => false,
                    'message' => 'No hay docentes disponibles para esta materia'
                ];
            }
            
            
            $scores = [];
            
            foreach ($teachers as $teacher) {
                // 1. Check availability (CRITICAL - 50% weight)
                $availScore = $this->checkAvailability($teacher['id_docente'], $day, $blockId);
                error_log("Teacher {$teacher['nombre']} {$teacher['apellido']} (ID: {$teacher['id_docente']}) - Availability: {$availScore} for {$day} bloque {$blockId}");
                
                if ($availScore == 0) {
                    error_log("Skipping {$teacher['nombre']} {$teacher['apellido']} - not available");
                    continue; // Skip unavailable teachers
                }
                
                // 2. Calculate workload score (30% weight)
                $workloadScore = $this->calculateWorkloadScore($teacher['id_docente']);
                
                // 3. Check conflicts (15% weight)
                $conflictResult = $this->checkConflicts($teacher['id_docente'], $groupId, $day, $blockId);
                $conflictScore = $conflictResult['score'];
                error_log("Teacher {$teacher['nombre']} {$teacher['apellido']} (ID: {$teacher['id_docente']}) - Conflict Score: {$conflictScore} ({$conflictResult['type']}) for {$day} bloque {$blockId}");
                
                if ($conflictScore == 0) {
                    error_log("Skipping {$teacher['nombre']} {$teacher['apellido']} - hard conflict detected");
                    continue; // Skip if hard conflict
                }
                
                // For alternative teacher selection, we allow soft conflicts (different groups)
                // but still prefer teachers without conflicts
                
                // 4. Check preferences/history (5% weight)
                $prefScore = $this->checkTeachingHistory($teacher['id_docente'], $subjectId, $groupId);
                
                // Calculate weighted final score
                $finalScore = (
                    ($availScore * 0.50) +
                    ($workloadScore * 0.30) +
                    ($conflictScore * 0.15) +
                    ($prefScore * 0.05)
                );
                
                error_log("Teacher {$teacher['nombre']} {$teacher['apellido']} (ID: {$teacher['id_docente']}) - Final Score: " . round($finalScore, 1) . " (Availability: {$availScore}, Workload: {$workloadScore}, Conflicts: {$conflictScore}, Preference: {$prefScore})");
                
                $scores[] = [
                    'teacher_id' => $teacher['id_docente'],
                    'teacher_name' => $teacher['nombre'] . ' ' . $teacher['apellido'],
                    'final_score' => round($finalScore, 1),
                    'breakdown' => [
                        'availability' => $availScore,
                        'workload' => $workloadScore,
                        'conflicts' => $conflictScore,
                        'preference' => $prefScore
                    ],
                    'conflict_info' => $conflictResult
                ];
            }
            
            if (empty($scores)) {
                $message = $skipExisting ? 
                    'No hay docentes disponibles para este horario. Todos los docentes tienen conflictos de horario.' : 
                    'No hay docentes disponibles para este horario';
                return [
                    'success' => false,
                    'message' => $message
                ];
            }
            
            // Sort by score descending
            usort($scores, function($a, $b) {
                return $b['final_score'] <=> $a['final_score'];
            });
            
            // If tie, randomize among top scorers (within 1 point)
            $topScore = $scores[0]['final_score'];
            $topTeachers = array_filter($scores, function($s) use ($topScore) {
                return $s['final_score'] >= $topScore - 1;
            });
            
            $selectedTeacher = $topTeachers[array_rand($topTeachers)];
            error_log("Selected teacher: {$selectedTeacher['teacher_name']} (ID: {$selectedTeacher['teacher_id']}) with score: {$selectedTeacher['final_score']}");
            
            // Get alternatives (top 3 excluding selected)
            $alternatives = array_slice(array_filter($scores, function($s) use ($selectedTeacher) {
                return $s['teacher_id'] != $selectedTeacher['teacher_id'];
            }), 0, 3);
            
            $result = [
                'success' => true,
                'selected_teacher' => [
                    'id_docente' => $selectedTeacher['teacher_id'],
                    'nombre' => explode(' ', $selectedTeacher['teacher_name'])[0],
                    'apellido' => implode(' ', array_slice(explode(' ', $selectedTeacher['teacher_name']), 1)),
                    'final_score' => $selectedTeacher['final_score'],
                    'reason' => $this->getSelectionReason($selectedTeacher['breakdown'])
                ],
                'alternatives' => array_map(function($alt) {
                    $nameParts = explode(' ', $alt['teacher_name']);
                    return [
                        'id_docente' => $alt['teacher_id'],
                        'nombre' => $nameParts[0],
                        'apellido' => implode(' ', array_slice($nameParts, 1)),
                        'score' => $alt['final_score']
                    ];
                }, $alternatives)
            ];
            
            // Add conflict information if there are soft conflicts
            if ($selectedTeacher['conflict_info']['type'] === 'soft') {
                $result['conflict'] = [
                    'type' => 'soft',
                    'message' => 'El docente ya tiene una clase en este horario (' . $selectedTeacher['conflict_info']['details']['materia'] . ' - ' . $selectedTeacher['conflict_info']['details']['grupo'] . ')',
                    'details' => $selectedTeacher['conflict_info']['details']
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error in TeacherSelectionService: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno al seleccionar docente'
            ];
        } finally {
            error_log("=== TEACHER SELECTION COMPLETED ===");
        }
    }
    
    /**
     * Get teachers who can teach the subject
     */
    private function getTeachersForSubject($subjectId) {
        $query = "
            SELECT d.id_docente, u.nombre, u.apellido, d.horas_asignadas
            FROM docente d
            INNER JOIN usuario u ON d.id_usuario = u.id_usuario
            INNER JOIN docente_materia dm ON d.id_docente = dm.id_docente
            WHERE dm.id_materia = ?
            ORDER BY u.nombre, u.apellido
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$subjectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if there's already a teacher assigned to this subject in this group
     * Returns teacher ID if found, null otherwise
     */
    private function getExistingTeacherForSubjectInGroup($subjectId, $groupId) {
        $query = "
            SELECT DISTINCT h.id_docente, u.nombre, u.apellido
            FROM horario h
            JOIN docente d ON h.id_docente = d.id_docente
            JOIN usuario u ON d.id_usuario = u.id_usuario
            WHERE h.id_materia = ? AND h.id_grupo = ?
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$subjectId, $groupId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check teacher availability for specific day/time slot
     * Returns: 100 (available), 50 (no record), 0 (unavailable)
     */
    private function checkAvailability($teacherId, $day, $blockId) {
        $query = "
            SELECT disponible 
            FROM disponibilidad 
            WHERE id_docente = ? AND dia = ? AND id_bloque = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$teacherId, $day, $blockId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return 0; // No record - assume NOT available (safer approach)
        }
        
        return $result['disponible'] ? 100 : 0;
    }
    
    /**
     * Calculate workload balance score
     */
    private function calculateWorkloadScore($teacherId) {
        // Get teacher's current hours
        $teacherQuery = "SELECT horas_asignadas FROM docente WHERE id_docente = ?";
        $teacherStmt = $this->db->prepare($teacherQuery);
        $teacherStmt->execute([$teacherId]);
        $teacherHours = (int)$teacherStmt->fetch(PDO::FETCH_ASSOC)['horas_asignadas'];
        
        // Get average hours across all teachers
        $avgQuery = "
            SELECT AVG(horas_asignadas) as avg_hours 
            FROM docente
        ";
        $avgStmt = $this->db->prepare($avgQuery);
        $avgStmt->execute();
        $avgHours = (float)$avgStmt->fetch(PDO::FETCH_ASSOC)['avg_hours'];
        
        if ($avgHours == 0) {
            return 50; // Default if no data
        }
        
        $workloadRatio = $teacherHours / $avgHours;
        
        if ($workloadRatio < 0.7) {
            return 100; // Underutilized - prefer
        } else if ($workloadRatio < 1.0) {
            return 80;  // Balanced
        } else if ($workloadRatio < 1.3) {
            return 50;  // Slightly overloaded
        } else {
            return 20;  // Heavily overloaded - avoid
        }
    }
    
    /**
     * Check for scheduling conflicts
     * Returns: 100 (no conflicts), 50 (soft conflict), 0 (hard conflict)
     */
    private function checkConflicts($teacherId, $groupId, $day, $blockId) {
        $query = "
            SELECT h.id_materia, m.nombre as materia_nombre, h.id_grupo, g.nombre as grupo_nombre
            FROM horario h
            JOIN materia m ON h.id_materia = m.id_materia
            JOIN grupo g ON h.id_grupo = g.id_grupo
            WHERE h.id_docente = ? AND h.dia = ? AND h.id_bloque = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$teacherId, $day, $blockId]);
        $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($conflicts)) {
            return ['score' => 100, 'type' => 'none', 'details' => null]; // No conflicts
        }
        
        // Any conflict at the same time slot should be a hard conflict
        // A teacher cannot teach two different subjects at the same time
        $conflict = $conflicts[0];
        return [
            'score' => 0, 
            'type' => 'hard', 
            'details' => [
                'materia' => $conflict['materia_nombre'],
                'grupo' => $conflict['grupo_nombre']
            ]
        ]; // Hard conflict - teacher already has a class at this time
    }
    
    /**
     * Check teaching history and preferences
     */
    private function checkTeachingHistory($teacherId, $subjectId, $groupId) {
        // Check if teacher has taught this subject to this group before
        $historyQuery = "
            SELECT COUNT(*) as history_count
            FROM horario 
            WHERE id_docente = ? AND id_materia = ? AND id_grupo = ?
        ";
        
        $historyStmt = $this->db->prepare($historyQuery);
        $historyStmt->execute([$teacherId, $subjectId, $groupId]);
        $historyCount = (int)$historyStmt->fetch(PDO::FETCH_ASSOC)['history_count'];
        
        if ($historyCount > 0) {
            return 100; // Previously taught this subject to this group
        }
        
        // Check if teacher has taught this subject before (any group)
        $subjectQuery = "
            SELECT COUNT(*) as subject_count
            FROM horario 
            WHERE id_docente = ? AND id_materia = ?
        ";
        
        $subjectStmt = $this->db->prepare($subjectQuery);
        $subjectStmt->execute([$teacherId, $subjectId]);
        $subjectCount = (int)$subjectStmt->fetch(PDO::FETCH_ASSOC)['subject_count'];
        
        if ($subjectCount > 0) {
            return 75; // Has taught this subject before
        }
        
        return 50; // New to this subject
    }
    
    /**
     * Generate human-readable reason for selection
     */
    private function getSelectionReason($breakdown) {
        $reasons = [];
        
        if ($breakdown['availability'] == 100) {
            $reasons[] = "disponible";
        }
        
        if ($breakdown['workload'] >= 80) {
            $reasons[] = "carga equilibrada";
        } elseif ($breakdown['workload'] < 50) {
            $reasons[] = "poca carga de trabajo";
        }
        
        if ($breakdown['conflicts'] == 100) {
            $reasons[] = "sin conflictos";
        }
        
        if ($breakdown['preference'] >= 75) {
            $reasons[] = "experiencia previa";
        }
        
        if (empty($reasons)) {
            return "selección automática";
        }
        
        return "disponible con " . implode(" y ", $reasons);
    }
}
