<?php
/**
 * Materia Model
 * Maneja operaciones CRUD para materias
 */
class Materia {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Obtiene todas las materias
     */
    public function getAllMaterias() {
        try {
            $query = "SELECT m.*, p.nombre as pauta_anep_nombre, g.nombre as grupo_nombre 
                     FROM materia m 
                     LEFT JOIN pauta_anep p ON m.id_pauta_anep = p.id_pauta_anep 
                     LEFT JOIN grupo g ON m.id_grupo_compartido = g.id_grupo 
                     ORDER BY m.nombre ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all materias: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene una materia por ID
     */
    public function getMateriaById($id) {
        try {
            $query = "SELECT m.*, p.nombre as pauta_anep_nombre, g.nombre as grupo_nombre 
                     FROM materia m 
                     LEFT JOIN pauta_anep p ON m.id_pauta_anep = p.id_pauta_anep 
                     LEFT JOIN grupo g ON m.id_grupo_compartido = g.id_grupo 
                     WHERE m.id_materia = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting materia by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea una nueva materia
     */
    public function createMateria($data) {
        try {
            // Validar datos requeridos
            if (empty($data['nombre'])) {
                throw new Exception("El nombre de la materia es requerido");
            }
            
            // Verificar si ya existe una materia con el mismo nombre
            if ($this->materiaExists($data['nombre'])) {
                throw new Exception("Ya existe una materia con ese nombre");
            }
            
            // Obtener la primera pauta ANEP disponible si no se especifica
            if (empty($data['id_pauta_anep'])) {
                $data['id_pauta_anep'] = $this->getDefaultPautaAnep();
            }
            
            $query = "INSERT INTO materia (nombre, horas_semanales, id_pauta_anep, en_conjunto, id_grupo_compartido, es_programa_italiano) 
                     VALUES (:nombre, :horas_semanales, :id_pauta_anep, :en_conjunto, :id_grupo_compartido, :es_programa_italiano)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':horas_semanales', $data['horas_semanales'] ?? 1, PDO::PARAM_INT);
            $stmt->bindParam(':id_pauta_anep', $data['id_pauta_anep'], PDO::PARAM_INT);
            $stmt->bindParam(':en_conjunto', $data['en_conjunto'] ?? false, PDO::PARAM_BOOL);
            $stmt->bindParam(':id_grupo_compartido', $data['id_grupo_compartido'] ?? null, PDO::PARAM_INT);
            $stmt->bindParam(':es_programa_italiano', $data['es_programa_italiano'] ?? false, PDO::PARAM_BOOL);
            
            $stmt->execute();
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating materia: " . $e->getMessage());
            throw new Exception("Error al crear la materia: " . $e->getMessage());
        }
    }
    
    /**
     * Actualiza una materia existente
     */
    public function updateMateria($id, $data) {
        try {
            // Validar que la materia existe
            if (!$this->getMateriaById($id)) {
                throw new Exception("La materia no existe");
            }
            
            // Verificar si el nuevo nombre ya existe (excluyendo la materia actual)
            if (!empty($data['nombre']) && $this->materiaExists($data['nombre'], $id)) {
                throw new Exception("Ya existe otra materia con ese nombre");
            }
            
            $query = "UPDATE materia SET 
                     nombre = :nombre,
                     horas_semanales = :horas_semanales,
                     id_pauta_anep = :id_pauta_anep,
                     en_conjunto = :en_conjunto,
                     id_grupo_compartido = :id_grupo_compartido,
                     es_programa_italiano = :es_programa_italiano
                     WHERE id_materia = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':horas_semanales', $data['horas_semanales'] ?? 1, PDO::PARAM_INT);
            $stmt->bindParam(':id_pauta_anep', $data['id_pauta_anep'], PDO::PARAM_INT);
            $stmt->bindParam(':en_conjunto', $data['en_conjunto'] ?? false, PDO::PARAM_BOOL);
            $stmt->bindParam(':id_grupo_compartido', $data['id_grupo_compartido'] ?? null, PDO::PARAM_INT);
            $stmt->bindParam(':es_programa_italiano', $data['es_programa_italiano'] ?? false, PDO::PARAM_BOOL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating materia: " . $e->getMessage());
            throw new Exception("Error al actualizar la materia: " . $e->getMessage());
        }
    }
    
    /**
     * Elimina una materia
     */
    public function deleteMateria($id) {
        try {
            // Verificar que la materia existe
            if (!$this->getMateriaById($id)) {
                throw new Exception("La materia no existe");
            }
            
            // Verificar si la materia está siendo utilizada en horarios
            if ($this->materiaInUse($id)) {
                throw new Exception("No se puede eliminar la materia porque está siendo utilizada en horarios");
            }
            
            $query = "DELETE FROM materia WHERE id_materia = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting materia: " . $e->getMessage());
            throw new Exception("Error al eliminar la materia: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica si una materia existe por nombre
     */
    private function materiaExists($nombre, $excludeId = null) {
        try {
            $query = "SELECT id_materia FROM materia WHERE nombre = :nombre";
            if ($excludeId) {
                $query .= " AND id_materia != :exclude_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error checking if materia exists: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si una materia está siendo utilizada en horarios
     */
    private function materiaInUse($id) {
        try {
            $query = "SELECT COUNT(*) as count FROM horario WHERE id_materia = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking if materia is in use: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene la primera pauta ANEP disponible
     */
    private function getDefaultPautaAnep() {
        try {
            $query = "SELECT id_pauta_anep FROM pauta_anep ORDER BY id_pauta_anep ASC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id_pauta_anep'] : 1;
        } catch (PDOException $e) {
            error_log("Error getting default pauta ANEP: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Obtiene todas las pautas ANEP disponibles
     */
    public function getAllPautasAnep() {
        try {
            $query = "SELECT * FROM pauta_anep ORDER BY nombre ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all pautas ANEP: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene todos los grupos disponibles
     */
    public function getAllGrupos() {
        try {
            $query = "SELECT * FROM grupo ORDER BY nombre ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all grupos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get materia by name
     */
    public function getMateriaByName($nombre) {
        try {
            $query = "SELECT * FROM materia WHERE nombre = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$nombre]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting materia by name: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent materias
     */
    public function getRecentMaterias($limit = 5) {
        try {
            $query = "SELECT m.*, p.nombre as pauta_anep_nombre, g.nombre as grupo_nombre 
                     FROM materia m 
                     LEFT JOIN pauta_anep p ON m.id_pauta_anep = p.id_pauta_anep 
                     LEFT JOIN grupo g ON m.id_grupo_compartido = g.id_grupo 
                     ORDER BY m.fecha_creacion DESC
                     LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent materias: " . $e->getMessage());
            return false;
        }
    }
    
}
