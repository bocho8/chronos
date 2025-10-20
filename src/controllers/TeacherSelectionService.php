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
    public function selectBestTeacher($subjectId, $groupId, $blockId, $day) {
        try {
            // Get all teachers who can teach this subject
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
                if ($availScore == 0) {
                    continue; // Skip unavailable teachers
                }
                
                // 2. Calculate workload score (30% weight)
                $workloadScore = $this->calculateWorkloadScore($teacher['id_docente']);
                
                // 3. Check conflicts (15% weight)
                $conflictScore = $this->checkConflicts($teacher['id_docente'], $groupId, $day, $blockId);
                if ($conflictScore == 0) {
                    continue; // Skip if hard conflict
                }
                
                // 4. Check preferences/history (5% weight)
                $prefScore = $this->checkTeachingHistory($teacher['id_docente'], $subjectId, $groupId);
                
                // Calculate weighted final score
                $finalScore = (
                    ($availScore * 0.50) +
                    ($workloadScore * 0.30) +
                    ($conflictScore * 0.15) +
                    ($prefScore * 0.05)
                );
                
                $scores[] = [
                    'teacher_id' => $teacher['id_docente'],
                    'teacher_name' => $teacher['nombre'] . ' ' . $teacher['apellido'],
                    'final_score' => round($finalScore, 1),
                    'breakdown' => [
                        'availability' => $availScore,
                        'workload' => $workloadScore,
                        'conflicts' => $conflictScore,
                        'preference' => $prefScore
                    ]
                ];
            }
            
            if (empty($scores)) {
                return [
                    'success' => false,
                    'message' => 'No hay docentes disponibles para este horario'
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
            
            // Get alternatives (top 3 excluding selected)
            $alternatives = array_slice(array_filter($scores, function($s) use ($selectedTeacher) {
                return $s['teacher_id'] != $selectedTeacher['teacher_id'];
            }), 0, 3);
            
            return [
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
            
        } catch (Exception $e) {
            error_log("Error in TeacherSelectionService: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno al seleccionar docente'
            ];
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
            return 50; // No record - assume available
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
            SELECT COUNT(*) as conflict_count
            FROM horario 
            WHERE id_docente = ? AND dia = ? AND id_bloque = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$teacherId, $day, $blockId]);
        $conflictCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['conflict_count'];
        
        if ($conflictCount == 0) {
            return 100; // No conflicts
        }
        
        // Check if it's the same group (hard conflict)
        $sameGroupQuery = "
            SELECT COUNT(*) as same_group_count
            FROM horario 
            WHERE id_docente = ? AND dia = ? AND id_bloque = ? AND id_grupo = ?
        ";
        
        $sameGroupStmt = $this->db->prepare($sameGroupQuery);
        $sameGroupStmt->execute([$teacherId, $day, $blockId, $groupId]);
        $sameGroupCount = (int)$sameGroupStmt->fetch(PDO::FETCH_ASSOC)['same_group_count'];
        
        if ($sameGroupCount > 0) {
            return 0; // Hard conflict - same group
        }
        
        return 50; // Soft conflict - different group
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
