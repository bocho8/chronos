<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Conflict Detection Service
 * Servicio para detectar conflictos en horarios
 */

class ConflictDetectionService
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Detecta todos los tipos de conflictos para una asignación de horario
     */
    public function detectConflicts($scheduleData, $excludeId = null)
    {
        $conflicts = [];

        $groupConflict = $this->checkGroupConflict($scheduleData, $excludeId);
        if ($groupConflict) {
            $conflicts[] = [
                'type' => 'group',
                'severity' => 'error',
                'message' => 'El grupo ya tiene una clase en este horario',
                'details' => $groupConflict
            ];
        }

        $teacherConflict = $this->checkTeacherConflict($scheduleData, $excludeId);
        if ($teacherConflict) {
            $conflicts[] = [
                'type' => 'teacher',
                'severity' => 'error',
                'message' => 'El docente ya tiene una clase en este horario',
                'details' => $teacherConflict
            ];
        }

        $availabilityConflict = $this->checkAvailabilityConflict($scheduleData);
        if ($availabilityConflict) {
            $conflicts[] = [
                'type' => 'availability',
                'severity' => 'warning',
                'message' => 'El docente no está disponible en este horario',
                'details' => $availabilityConflict
            ];
        }

        $workloadConflict = $this->checkWorkloadConflict($scheduleData);
        if ($workloadConflict) {
            $conflicts[] = [
                'type' => 'workload',
                'severity' => 'warning',
                'message' => 'La materia excede las horas semanales asignadas',
                'details' => $workloadConflict
            ];
        }

        $anepConflict = $this->checkANEPConflict($scheduleData);
        if ($anepConflict) {
            $conflicts[] = [
                'type' => 'anep',
                'severity' => 'info',
                'message' => 'No cumple con las pautas de distribución ANEP',
                'details' => $anepConflict
            ];
        }
        
        return $conflicts;
    }
    
    /**
     * Verifica conflicto de grupo
     */
    private function checkGroupConflict($data, $excludeId = null)
    {
        try {
            $query = "SELECT h.*, m.nombre as materia_nombre, 
                             u.nombre as docente_nombre, u.apellido as docente_apellido
                     FROM horario h
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     WHERE h.id_grupo = :id_grupo 
                     AND h.id_bloque = :id_bloque 
                     AND h.dia = :dia";
            
            if ($excludeId) {
                $query .= " AND h.id_horario != :exclude_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $data['id_grupo'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error checking group conflict: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica conflicto de docente
     */
    private function checkTeacherConflict($data, $excludeId = null)
    {
        try {
            $query = "SELECT h.*, g.nombre as grupo_nombre, m.nombre as materia_nombre
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     WHERE h.id_docente = :id_docente 
                     AND h.id_bloque = :id_bloque 
                     AND h.dia = :dia";
            
            if ($excludeId) {
                $query .= " AND h.id_horario != :exclude_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $data['id_docente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error checking teacher conflict: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica conflicto de disponibilidad
     */
    private function checkAvailabilityConflict($data)
    {
        try {
            $query = "SELECT d.*, bh.hora_inicio, bh.hora_fin
                     FROM disponibilidad d
                     JOIN bloque_horario bh ON d.id_bloque = bh.id_bloque
                     WHERE d.id_docente = :id_docente 
                     AND d.id_bloque = :id_bloque 
                     AND d.dia = :dia
                     AND d.disponible = false";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $data['id_docente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error checking availability conflict: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica conflicto de carga horaria
     */
    private function checkWorkloadConflict($data)
    {
        try {

            $materiaQuery = "SELECT horas_semanales FROM materia WHERE id_materia = :id_materia";
            $materiaStmt = $this->db->prepare($materiaQuery);
            $materiaStmt->bindParam(':id_materia', $data['id_materia'], PDO::PARAM_INT);
            $materiaStmt->execute();
            $materia = $materiaStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$materia) {
                return false;
            }
            
            $horasRequeridas = $materia['horas_semanales'];

            $horasQuery = "SELECT COUNT(*) as horas_asignadas
                          FROM horario h
                          WHERE h.id_materia = :id_materia 
                          AND h.id_grupo = :id_grupo";
            
            $horasStmt = $this->db->prepare($horasQuery);
            $horasStmt->bindParam(':id_materia', $data['id_materia'], PDO::PARAM_INT);
            $horasStmt->bindParam(':id_grupo', $data['id_grupo'], PDO::PARAM_INT);
            $horasStmt->execute();
            $horas = $horasStmt->fetch(PDO::FETCH_ASSOC);
            
            $horasAsignadas = $horas['horas_asignadas'];
            
            if ($horasAsignadas >= $horasRequeridas) {
                return [
                    'horas_requeridas' => $horasRequeridas,
                    'horas_asignadas' => $horasAsignadas,
                    'exceso' => $horasAsignadas - $horasRequeridas
                ];
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error checking workload conflict: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica conflicto con pautas ANEP
     */
    private function checkANEPConflict($data)
    {
        try {

            $query = "SELECT m.horas_semanales, m.nombre as materia_nombre, 
                             pa.dias_minimos, pa.dias_maximos, pa.condiciones_especiales,
                             pa.nombre as pauta_nombre
                     FROM materia m
                     JOIN pauta_anep pa ON m.id_pauta_anep = pa.id_pauta_anep
                     WHERE m.id_materia = :id_materia";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_materia', $data['id_materia'], PDO::PARAM_INT);
            $stmt->execute();
            $materia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$materia) {
                return false;
            }

            $diasQuery = "SELECT COUNT(DISTINCT h.dia) as dias_asignados,
                                COUNT(h.id_horario) as horas_asignadas,
                                ARRAY_AGG(DISTINCT h.dia ORDER BY h.dia) as dias_especificos
                         FROM horario h
                         WHERE h.id_materia = :id_materia 
                         AND h.id_grupo = :id_grupo";
            
            $diasStmt = $this->db->prepare($diasQuery);
            $diasStmt->bindParam(':id_materia', $data['id_materia'], PDO::PARAM_INT);
            $diasStmt->bindParam(':id_grupo', $data['id_grupo'], PDO::PARAM_INT);
            $diasStmt->execute();
            $dias = $diasStmt->fetch(PDO::FETCH_ASSOC);
            
            $diasAsignados = (int)$dias['dias_asignados'];
            $horasAsignadas = (int)$dias['horas_asignadas'];
            $diasEspecificos = $dias['dias_especificos'] ? explode(',', trim($dias['dias_especificos'], '{}')) : [];
            $diasMinimos = (int)$materia['dias_minimos'];
            $diasMaximos = (int)$materia['dias_maximos'];
            $horasSemanales = (int)$materia['horas_semanales'];
            
            $conflicts = [];

            if ($diasAsignados < $diasMinimos) {
                $conflicts[] = [
                    'type' => 'dias_insuficientes',
                    'message' => "La materia '{$materia['materia_nombre']}' necesita al menos {$diasMinimos} días de distribución, actualmente tiene {$diasAsignados}",
                    'severity' => 'error',
                    'current' => $diasAsignados,
                    'required' => $diasMinimos,
                    'suggestion' => "Agregar horarios en " . ($diasMinimos - $diasAsignados) . " días adicionales"
                ];
            }
            
            if ($diasAsignados > $diasMaximos) {
                $conflicts[] = [
                    'type' => 'dias_excesivos',
                    'message' => "La materia '{$materia['materia_nombre']}' no puede tener más de {$diasMaximos} días de distribución, actualmente tiene {$diasAsignados}",
                    'severity' => 'error',
                    'current' => $diasAsignados,
                    'required' => $diasMaximos,
                    'suggestion' => "Consolidar horarios en menos días"
                ];
            }

            $horasPorDia = $this->getHorasPorDia($data['id_materia'], $data['id_grupo']);
            foreach ($horasPorDia as $dia => $horas) {
                if ($horas > 2) {
                    $conflicts[] = [
                        'type' => 'horas_excesivas_dia',
                        'message' => "La materia '{$materia['materia_nombre']}' tiene {$horas} horas en {$dia}, máximo recomendado: 2 horas",
                        'severity' => 'warning',
                        'dia' => $dia,
                        'horas' => $horas,
                        'suggestion' => "Distribuir las horas en más días"
                    ];
                }
            }

            $distribucionRecomendada = $this->getDistribucionRecomendada($horasSemanales);
            if ($diasAsignados < $distribucionRecomendada['dias_minimos']) {
                $conflicts[] = [
                    'type' => 'distribucion_carga_horaria',
                    'message' => "Para {$horasSemanales} horas semanales, se recomienda al menos {$distribucionRecomendada['dias_minimos']} días de distribución",
                    'severity' => 'info',
                    'horas_semanales' => $horasSemanales,
                    'dias_recomendados' => $distribucionRecomendada['dias_minimos'],
                    'suggestion' => $distribucionRecomendada['suggestion']
                ];
            }

            if (!empty($materia['condiciones_especiales'])) {
                $condiciones = json_decode($materia['condiciones_especiales'], true);
                if ($condiciones) {
                    foreach ($condiciones as $condicion => $valor) {
                        $conflict = $this->verificarCondicionEspecial($condicion, $valor, $data, $diasEspecificos);
                        if ($conflict) {
                            $conflicts[] = $conflict;
                        }
                    }
                }
            }

            if ($this->tieneDiasConsecutivos($diasEspecificos) && $horasSemanales >= 4) {
                $conflicts[] = [
                    'type' => 'dias_consecutivos',
                    'message' => "Se detectaron días consecutivos para '{$materia['materia_nombre']}', se recomienda distribuir en días no consecutivos",
                    'severity' => 'warning',
                    'dias' => $diasEspecificos,
                    'suggestion' => "Separar los horarios en días no consecutivos"
                ];
            }
            
            return empty($conflicts) ? false : [
                'materia' => $materia['materia_nombre'],
                'pauta' => $materia['pauta_nombre'],
                'conflicts' => $conflicts,
                'resumen' => [
                    'dias_asignados' => $diasAsignados,
                    'dias_minimos' => $diasMinimos,
                    'dias_maximos' => $diasMaximos,
                    'horas_asignadas' => $horasAsignadas,
                    'horas_semanales' => $horasSemanales
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Error checking ANEP conflict: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene las horas por día para una materia y grupo
     */
    private function getHorasPorDia($idMateria, $idGrupo)
    {
        try {
            $query = "SELECT h.dia, COUNT(h.id_horario) as horas
                     FROM horario h
                     WHERE h.id_materia = :id_materia 
                     AND h.id_grupo = :id_grupo
                     GROUP BY h.dia";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_materia', $idMateria, PDO::PARAM_INT);
            $stmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['dia']] = (int)$row['horas'];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting horas por dia: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene la distribución recomendada según las horas semanales
     */
    private function getDistribucionRecomendada($horasSemanales)
    {
        if ($horasSemanales <= 2) {
            return [
                'dias_minimos' => 1,
                'dias_maximos' => 2,
                'suggestion' => 'Distribuir en 1-2 días máximo'
            ];
        } elseif ($horasSemanales <= 4) {
            return [
                'dias_minimos' => 2,
                'dias_maximos' => 3,
                'suggestion' => 'Distribuir en 2-3 días, máximo 2 horas por día'
            ];
        } elseif ($horasSemanales <= 6) {
            return [
                'dias_minimos' => 3,
                'dias_maximos' => 4,
                'suggestion' => 'Distribuir en 3-4 días, máximo 2 horas por día'
            ];
        } else {
            return [
                'dias_minimos' => 4,
                'dias_maximos' => 5,
                'suggestion' => 'Distribuir en 4-5 días, máximo 2 horas por día'
            ];
        }
    }
    
    /**
     * Verifica condiciones especiales de ANEP
     */
    private function verificarCondicionEspecial($condicion, $valor, $data, $diasEspecificos)
    {
        switch ($condicion) {
            case 'no_consecutivos':
                if ($valor && $this->tieneDiasConsecutivos($diasEspecificos)) {
                    return [
                        'type' => 'condicion_especial',
                        'message' => "Esta materia no puede tener días consecutivos",
                        'severity' => 'error',
                        'condicion' => $condicion,
                        'suggestion' => "Separar los horarios en días no consecutivos"
                    ];
                }
                break;
                
            case 'solo_manana':
                if ($valor && !$this->esHorarioManana($data['id_bloque'])) {
                    return [
                        'type' => 'condicion_especial',
                        'message' => "Esta materia solo puede ser en horario de mañana",
                        'severity' => 'error',
                        'condicion' => $condicion,
                        'suggestion' => "Seleccionar un horario de mañana (08:00-12:00)"
                    ];
                }
                break;
                
            case 'solo_tarde':
                if ($valor && !$this->esHorarioTarde($data['id_bloque'])) {
                    return [
                        'type' => 'condicion_especial',
                        'message' => "Esta materia solo puede ser en horario de tarde",
                        'severity' => 'error',
                        'condicion' => $condicion,
                        'suggestion' => "Seleccionar un horario de tarde (13:00-17:00)"
                    ];
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Verifica si hay días consecutivos
     */
    private function tieneDiasConsecutivos($dias)
    {
        $ordenDias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
        $indices = [];
        
        foreach ($dias as $dia) {
            $indice = array_search($dia, $ordenDias);
            if ($indice !== false) {
                $indices[] = $indice;
            }
        }
        
        sort($indices);
        
        for ($i = 0; $i < count($indices) - 1; $i++) {
            if ($indices[$i + 1] - $indices[$i] === 1) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica si es horario de mañana
     */
    private function esHorarioManana($idBloque)
    {
        try {
            $query = "SELECT hora_inicio FROM bloque_horario WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $stmt->execute();
            
            $bloque = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($bloque) {
                $hora = strtotime($bloque['hora_inicio']);
                return $hora >= strtotime('08:00:00') && $hora < strtotime('12:00:00');
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error checking horario manana: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si es horario de tarde
     */
    private function esHorarioTarde($idBloque)
    {
        try {
            $query = "SELECT hora_inicio FROM bloque_horario WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $stmt->execute();
            
            $bloque = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($bloque) {
                $hora = strtotime($bloque['hora_inicio']);
                return $hora >= strtotime('13:00:00') && $hora < strtotime('17:00:00');
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error checking horario tarde: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene sugerencias para resolver conflictos
     */
    public function getConflictSuggestions($conflicts)
    {
        $suggestions = [];
        
        foreach ($conflicts as $conflict) {
            switch ($conflict['type']) {
                case 'group':
                    $suggestions[] = [
                        'type' => 'alternative_times',
                        'message' => 'Buscar horarios alternativos para este grupo',
                        'action' => 'find_alternative_times'
                    ];
                    break;
                    
                case 'teacher':
                    $suggestions[] = [
                        'type' => 'alternative_teachers',
                        'message' => 'Buscar docentes alternativos para esta materia',
                        'action' => 'find_alternative_teachers'
                    ];
                    break;
                    
                case 'availability':
                    $suggestions[] = [
                        'type' => 'update_availability',
                        'message' => 'Actualizar disponibilidad del docente',
                        'action' => 'update_teacher_availability'
                    ];
                    break;
            }
        }
        
        return $suggestions;
    }
}
