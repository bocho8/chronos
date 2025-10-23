<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

class Horario {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAllHorarios() {
        try {
            $query = "SELECT h.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre, m.horas_semanales,
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     ORDER BY h.dia, b.hora_inicio, g.nombre";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all horarios: " . $e->getMessage());
            return false;
        }
    }
    
    public function getHorariosByGrupo($idGrupo) {
        try {
            $query = "SELECT h.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre,
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     WHERE h.id_grupo = :id_grupo
                     ORDER BY h.dia, b.hora_inicio";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting horarios by grupo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene horarios por docente
     */
    public function getHorariosByDocente($idDocente) {
        try {
            $query = "SELECT h.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     WHERE h.id_docente = :id_docente
                     ORDER BY h.dia, b.hora_inicio";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting horarios by docente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea una nueva asignación de horario
     */
    public function createHorario($data) {
        try {

            $requiredFields = ['id_grupo', 'id_docente', 'id_materia', 'id_bloque', 'dia'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }

            // Only check conflicts if force_override is not set or false
            $forceOverride = $data['force_override'] ?? false;
            
            if (!$forceOverride) {
                if ($this->hasScheduleConflict($data)) {
                    throw new Exception("Ya existe una asignación para este horario");
                }

                if (!$this->isDocenteAvailable($data['id_docente'], $data['id_bloque'], $data['dia'])) {
                    throw new Exception("El docente no está disponible en este horario");
                }
            }
            
            $query = "INSERT INTO horario (id_grupo, id_docente, id_materia, id_bloque, dia) 
                     VALUES (:id_grupo, :id_docente, :id_materia, :id_bloque, :dia)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $data['id_grupo'], PDO::PARAM_INT);
            $stmt->bindParam(':id_docente', $data['id_docente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_materia', $data['id_materia'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            
            $stmt->execute();
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating horario: " . $e->getMessage());
            throw new Exception("Error al crear el horario: " . $e->getMessage());
        }
    }
    
    /**
     * Actualiza una asignación de horario
     */
    public function updateHorario($id, $data) {
        try {

            if (!$this->getHorarioById($id)) {
                throw new Exception("El horario no existe");
            }

            // Only check conflicts if force_override is not set or false
            $forceOverride = $data['force_override'] ?? false;
            
            if (!$forceOverride) {
                if ($this->hasScheduleConflict($data, $id)) {
                    throw new Exception("Ya existe una asignación para este horario");
                }

                if (!empty($data['id_docente']) && !empty($data['id_bloque']) && !empty($data['dia'])) {
                    if (!$this->isDocenteAvailable($data['id_docente'], $data['id_bloque'], $data['dia'], $id)) {
                        throw new Exception("El docente no está disponible en este horario");
                    }
                }
            }
            
            $query = "UPDATE horario SET 
                     id_grupo = :id_grupo,
                     id_docente = :id_docente,
                     id_materia = :id_materia,
                     id_bloque = :id_bloque,
                     dia = :dia
                     WHERE id_horario = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':id_grupo', $data['id_grupo'], PDO::PARAM_INT);
            $stmt->bindParam(':id_docente', $data['id_docente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_materia', $data['id_materia'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating horario: " . $e->getMessage());
            throw new Exception("Error al actualizar el horario: " . $e->getMessage());
        }
    }
    
    /**
     * Elimina una asignación de horario
     */
    public function deleteHorario($id) {
        try {

            if (!$this->getHorarioById($id)) {
                throw new Exception("El horario no existe");
            }
            
            $query = "DELETE FROM horario WHERE id_horario = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting horario: " . $e->getMessage());
            throw new Exception("Error al eliminar el horario: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene un horario por ID
     */
    public function getHorarioById($id) {
        try {
            $query = "SELECT h.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre,
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     WHERE h.id_horario = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting horario by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene un horario completo con todos los datos necesarios para edición
     */
    public function getHorarioCompletoById($id) {
        try {
            $query = "SELECT h.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre, m.horas_semanales,
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     WHERE h.id_horario = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting complete horario by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si hay conflicto de horario
     */
    private function hasScheduleConflict($data, $excludeId = null) {
        try {

            $query = "SELECT id_horario FROM horario 
                     WHERE id_grupo = :id_grupo AND id_bloque = :id_bloque AND dia = :dia";
            if ($excludeId) {
                $query .= " AND id_horario != :exclude_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $data['id_grupo'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            
            if ($stmt->fetch()) {
                return true;
            }

            $query = "SELECT id_horario FROM horario 
                     WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            if ($excludeId) {
                $query .= " AND id_horario != :exclude_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $data['id_docente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error checking schedule conflict: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si un docente está disponible en un horario específico
     */
    private function isDocenteAvailable($idDocente, $idBloque, $dia, $excludeHorarioId = null) {
        try {
            $query = "SELECT disponible FROM disponibilidad 
                     WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $stmt->bindParam(':dia', $dia);
            $stmt->execute();
            
            $disponibilidad = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$disponibilidad) {
                return true;
            }
            
            return $disponibilidad['disponible'];
        } catch (PDOException $e) {
            error_log("Error checking docente availability: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Obtiene la disponibilidad de un docente
     */
    public function getDocenteDisponibilidad($idDocente) {
        try {
            $query = "SELECT d.*, b.hora_inicio, b.hora_fin
                     FROM disponibilidad d
                     JOIN bloque_horario b ON d.id_bloque = b.id_bloque
                     WHERE d.id_docente = :id_docente
                     ORDER BY d.dia, b.hora_inicio";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting docente disponibilidad: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualiza la disponibilidad de un docente
     */
    public function updateDocenteDisponibilidad($idDocente, $idBloque, $dia, $disponible) {
        try {

            $query = "SELECT id_disponibilidad FROM disponibilidad 
                     WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $stmt->bindParam(':dia', $dia);
            $stmt->execute();
            
            if ($stmt->fetch()) {

                $query = "UPDATE disponibilidad SET disponible = :disponible 
                         WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            } else {

                $query = "INSERT INTO disponibilidad (id_docente, id_bloque, dia, disponible) 
                         VALUES (:id_docente, :id_bloque, :dia, :disponible)";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $stmt->bindParam(':dia', $dia);
            $stmt->bindParam(':disponible', $disponible, PDO::PARAM_BOOL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating docente disponibilidad: " . $e->getMessage());
            throw new Exception("Error al actualizar la disponibilidad: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene todos los bloques horarios
     */
    public function getAllBloques() {
        try {
            $query = "SELECT * FROM bloque_horario ORDER BY hora_inicio";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all bloques: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un bloque horario por ID
     */
    public function getBloqueById($id_bloque) {
        try {
            $query = "SELECT * FROM bloque_horario WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $id_bloque, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting bloque by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea un nuevo bloque horario
     */
    public function createBloque($hora_inicio, $hora_fin) {
        try {
            $query = "INSERT INTO bloque_horario (hora_inicio, hora_fin) VALUES (:hora_inicio, :hora_fin)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':hora_inicio', $hora_inicio);
            $stmt->bindParam(':hora_fin', $hora_fin);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating bloque: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza un bloque horario existente
     */
    public function updateBloque($id_bloque, $hora_inicio, $hora_fin) {
        try {
            $query = "UPDATE bloque_horario SET hora_inicio = :hora_inicio, hora_fin = :hora_fin WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $id_bloque, PDO::PARAM_INT);
            $stmt->bindParam(':hora_inicio', $hora_inicio);
            $stmt->bindParam(':hora_fin', $hora_fin);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating bloque: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un bloque horario
     */
    public function deleteBloque($id_bloque) {
        try {
            $query = "DELETE FROM bloque_horario WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $id_bloque, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting bloque: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si un bloque horario tiene dependencias (usado en horarios)
     */
    public function checkBloqueDependencies($id_bloque) {
        try {
            $query = "SELECT COUNT(*) as count FROM horario WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $id_bloque, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error checking bloque dependencies: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtiene todos los grupos
     */
    public function getAllGrupos() {
        try {
            $query = "SELECT * FROM grupo ORDER BY nivel, nombre";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all grupos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un grupo por ID
     */
    public function getGrupoById($idGrupo) {
        try {
            $query = "SELECT * FROM grupo WHERE id_grupo = :id_grupo";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting grupo by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene todas las materias
     */
    public function getAllMaterias() {
        try {
            $query = "SELECT * FROM materia ORDER BY nombre";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all materias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene todos los docentes
     */
    public function getAllDocentes() {
        try {
            $query = "SELECT d.id_docente, u.nombre, u.apellido, u.email
                     FROM docente d
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     ORDER BY u.apellido, u.nombre";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all docentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene horarios no publicados
     */
    public function getUnpublishedSchedules() {
        try {

            $query = "SELECT DISTINCT 
                        1 as id_horario,
                        'Horario Generado' as nombre,
                        'Horario generado automáticamente para todos los grupos' as descripcion,
                        NOW() as fecha_creacion,
                        0 as publicado
                     FROM horario 
                     WHERE EXISTS (SELECT 1 FROM horario)
                     LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $publishedCheck = $this->db->prepare("SELECT COUNT(*) as count FROM publicacion WHERE activo = true");
            $publishedCheck->execute();
            $publishedCount = $publishedCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($publishedCount && $publishedCount['count'] > 0) {
                return [];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting unpublished schedules: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene horarios publicados (metadata de publicaciones)
     */
    public function getPublishedSchedules() {
        try {
            $query = "SELECT p.*, 
                            'Horario Oficial' as nombre,
                            'Horario publicado y visible para todos los usuarios' as descripcion
                     FROM publicacion p
                     WHERE p.activo = true
                     ORDER BY p.fecha_publicacion DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting published schedules: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene horarios publicados para un grupo específico
     */
    public function getPublishedSchedulesByGrupo($idGrupo) {
        try {
            $query = "SELECT hp.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre,
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario_publicado hp
                     JOIN publicacion p ON hp.id_publicacion = p.id_publicacion
                     JOIN grupo g ON hp.id_grupo = g.id_grupo
                     JOIN materia m ON hp.id_materia = m.id_materia
                     JOIN docente d ON hp.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON hp.id_bloque = b.id_bloque
                     WHERE hp.id_grupo = :id_grupo AND p.activo = true
                     ORDER BY hp.dia, b.hora_inicio";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting published schedules by grupo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene horarios publicados para un docente específico
     */
    public function getPublishedSchedulesByDocente($idDocente) {
        try {
            $query = "SELECT hp.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre,
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario_publicado hp
                     JOIN publicacion p ON hp.id_publicacion = p.id_publicacion
                     JOIN grupo g ON hp.id_grupo = g.id_grupo
                     JOIN materia m ON hp.id_materia = m.id_materia
                     JOIN docente d ON hp.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON hp.id_bloque = b.id_bloque
                     WHERE hp.id_docente = :id_docente AND p.activo = true
                     ORDER BY hp.dia, b.hora_inicio";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting published schedules by docente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene docentes asignados a una materia específica
     */
    public function getTeachersBySubject($subjectId) {
        try {
            $query = "SELECT d.id_docente, u.nombre, u.apellido 
                     FROM docente_materia dm
                     JOIN docente d ON dm.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     WHERE dm.id_materia = :id_materia
                     ORDER BY u.apellido, u.nombre";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_materia', $subjectId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting teachers by subject: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene todas las materias con conteo de docentes asignados
     */
    public function getSubjectsWithTeacherCounts() {
        try {
            $query = "SELECT m.*, COUNT(dm.id_docente) as teacher_count
                     FROM materia m
                     LEFT JOIN docente_materia dm ON m.id_materia = dm.id_materia
                     GROUP BY m.id_materia, m.nombre, m.horas_semanales, m.id_pauta_anep, m.en_conjunto, m.id_grupo_compartido, m.es_programa_italiano
                     ORDER BY m.nombre";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting subjects with teacher counts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Publica un horario creando un snapshot de todos los horarios actuales
     */
    public function publishSchedule($userId) {
        try {
            $this->db->beginTransaction();

            // Deactivate previous publications
            $deactivateQuery = "UPDATE publicacion SET activo = FALSE WHERE activo = TRUE";
            $this->db->exec($deactivateQuery);

            // Create new publication entry
            $publicationQuery = "INSERT INTO publicacion (publicado_por, descripcion, activo) 
                               VALUES (?, 'Horario oficial publicado por la dirección', TRUE)";
            $stmt = $this->db->prepare($publicationQuery);
            $stmt->execute([$userId]);
            $publicationId = $this->db->lastInsertId();

            // Copy all current schedules to published table
            $copyQuery = "INSERT INTO horario_publicado (id_publicacion, id_grupo, id_docente, id_materia, id_bloque, dia)
                         SELECT ?, id_grupo, id_docente, id_materia, id_bloque, dia
                         FROM horario";
            $stmt = $this->db->prepare($copyQuery);
            $result = $stmt->execute([$publicationId]);
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error publishing schedule: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a publish request and snapshot for all schedules
     */
    public function createPublishRequest($userId) {
        try {
            $this->db->beginTransaction();

            // Generate hash of current schedules
            $scheduleHash = $this->getScheduleHash();
            
            // Create publication entry with activo=FALSE (pending review)
            $publicationQuery = "INSERT INTO publicacion (publicado_por, descripcion, activo) 
                               VALUES (?, 'Horario pendiente de aprobación', FALSE)";
            $stmt = $this->db->prepare($publicationQuery);
            $stmt->execute([$userId]);
            $publicationId = $this->db->lastInsertId();

            // Copy all current schedules to published table (snapshot)
            $copyQuery = "INSERT INTO horario_publicado (id_publicacion, id_grupo, id_docente, id_materia, id_bloque, dia)
                         SELECT ?, id_grupo, id_docente, id_materia, id_bloque, dia
                         FROM horario";
            $stmt = $this->db->prepare($copyQuery);
            $stmt->execute([$publicationId]);
            
            // Create the request linked to the publication
            $query = "INSERT INTO solicitud_publicacion (solicitado_por, snapshot_hash, id_publicacion) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$userId, $scheduleHash, $publicationId]);
            
            if ($result) {
                $this->db->commit();
                return $this->db->lastInsertId();
            } else {
                $this->db->rollBack();
                return false;
            }
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating publish request: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets the current publish request status
     */
    public function getPublishRequestStatus() {
        try {
            $query = "SELECT id_solicitud, estado, fecha_solicitud, snapshot_hash FROM solicitud_publicacion 
                     WHERE estado = 'pendiente' 
                     ORDER BY fecha_solicitud DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                return 'none';
            }
            
            // Return the status without auto-canceling
            // Multiple pending requests are allowed
            return $request['estado'];
        } catch (PDOException $e) {
            error_log("Error getting publish request status: " . $e->getMessage());
            return 'none';
        }
    }

    /**
     * Approves a publish request and activates the snapshot
     */
    public function approvePublishRequest($requestId, $userId) {
        try {
            $this->db->beginTransaction();

            // Get the publication ID from the request
            $getPubQuery = "SELECT id_publicacion FROM solicitud_publicacion WHERE id_solicitud = ? AND estado = 'pendiente'";
            $stmt = $this->db->prepare($getPubQuery);
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                throw new Exception("Solicitud no encontrada o ya procesada");
            }
            
            $publicationId = $request['id_publicacion'];

            // Deactivate all other publications
            $deactivateQuery = "UPDATE publicacion SET activo = FALSE WHERE activo = TRUE";
            $this->db->exec($deactivateQuery);

            // Activate this publication
            $activateQuery = "UPDATE publicacion SET activo = TRUE WHERE id_publicacion = ?";
            $stmt = $this->db->prepare($activateQuery);
            $stmt->execute([$publicationId]);

            // Update the request status
            $updateQuery = "UPDATE solicitud_publicacion 
                           SET estado = 'aprobado', revisado_por = ?, fecha_revision = CURRENT_TIMESTAMP 
                           WHERE id_solicitud = ?";
            $stmt = $this->db->prepare($updateQuery);
            $stmt->execute([$userId, $requestId]);
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error approving publish request: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rejects a publish request and deletes the pending snapshot
     */
    public function rejectPublishRequest($requestId, $userId, $reason = '') {
        try {
            $this->db->beginTransaction();

            // Get the publication ID from the request
            $getPubQuery = "SELECT id_publicacion FROM solicitud_publicacion WHERE id_solicitud = ? AND estado = 'pendiente'";
            $stmt = $this->db->prepare($getPubQuery);
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                throw new Exception("Solicitud no encontrada o ya procesada");
            }
            
            $publicationId = $request['id_publicacion'];

            // Update the request status and clear the publication reference
            $updateQuery = "UPDATE solicitud_publicacion 
                           SET estado = 'rechazado', revisado_por = ?, fecha_revision = CURRENT_TIMESTAMP, notas = ?, id_publicacion = NULL
                           WHERE id_solicitud = ?";
            $stmt = $this->db->prepare($updateQuery);
            $stmt->execute([$userId, $reason, $requestId]);

            // Delete the pending snapshot (CASCADE will handle horario_publicado)
            $deleteQuery = "DELETE FROM publicacion WHERE id_publicacion = ? AND activo = FALSE";
            $stmt = $this->db->prepare($deleteQuery);
            $stmt->execute([$publicationId]);
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error rejecting publish request: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancels a pending publish request
     */
    public function cancelPublishRequest($requestId) {
        try {
            $query = "DELETE FROM solicitud_publicacion WHERE id_solicitud = ? AND estado = 'pendiente'";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$requestId]);
        } catch (PDOException $e) {
            error_log("Error canceling publish request: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets pending publication schedules for preview
     */
    public function getPendingPublicationSchedules($publicationId) {
        try {
            $query = "SELECT hp.*, g.nombre as grupo_nombre, g.nivel as grupo_nivel, 
                            m.nombre as materia_nombre, u.nombre as docente_nombre, 
                            u.apellido as docente_apellido, b.hora_inicio, b.hora_fin
                     FROM horario_publicado hp
                     JOIN grupo g ON hp.id_grupo = g.id_grupo
                     JOIN materia m ON hp.id_materia = m.id_materia
                     JOIN docente d ON hp.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON hp.id_bloque = b.id_bloque
                     WHERE hp.id_publicacion = ?
                     ORDER BY hp.dia, b.hora_inicio";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$publicationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting pending publication schedules: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a published schedule (sets activo = FALSE)
     */
    public function deletePublishedSchedule($publicationId, $userId) {
        try {
            $this->db->beginTransaction();

            // Check if the publication exists and is active
            $checkQuery = "SELECT id_publicacion FROM publicacion WHERE id_publicacion = ? AND activo = TRUE";
            $stmt = $this->db->prepare($checkQuery);
            $stmt->execute([$publicationId]);
            
            if (!$stmt->fetch()) {
                throw new Exception("Publicación no encontrada o ya inactiva");
            }

            // Deactivate the publication (set activo = FALSE)
            $deactivateQuery = "UPDATE publicacion SET activo = FALSE WHERE id_publicacion = ?";
            $stmt = $this->db->prepare($deactivateQuery);
            $result = $stmt->execute([$publicationId]);

            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error deleting published schedule: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generates a hash of current schedules to detect changes
     */
    public function getScheduleHash() {
        try {
            $query = "SELECT h.*, g.nombre as grupo_nombre, m.nombre as materia_nombre, 
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     ORDER BY h.id_horario";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create a string representation of all schedules
            $scheduleString = '';
            foreach ($schedules as $schedule) {
                $scheduleString .= implode('|', $schedule);
            }
            
            return md5($scheduleString);
        } catch (PDOException $e) {
            error_log("Error generating schedule hash: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Gets pending publish requests for director review
     */
    public function getPendingPublishRequests() {
        try {
            $query = "SELECT sp.*, u.nombre as solicitante_nombre, u.apellido as solicitante_apellido
                     FROM solicitud_publicacion sp
                     JOIN usuario u ON sp.solicitado_por = u.id_usuario
                     WHERE sp.estado = 'pendiente'
                     ORDER BY sp.fecha_solicitud DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting pending publish requests: " . $e->getMessage());
            return [];
        }
    }
}
