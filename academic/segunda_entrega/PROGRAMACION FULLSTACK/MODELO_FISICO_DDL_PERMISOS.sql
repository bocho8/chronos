-- =====================================================
-- MODELO FÍSICO - SISTEMA DE GESTIÓN DE HORARIOS CHRONOS
-- DDL (Data Definition Language) y Permisos
-- Segunda Entrega - Programación Full Stack
-- =====================================================

/*
Diagrama de Estructura de Base de Datos:

```mermaid
erDiagram
    USUARIO {
        varchar cedula PK
        varchar nombre
        varchar apellido
        varchar email UK
        varchar telefono
        date fecha_nacimiento
        enum tipo_usuario
        boolean activo
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    
    MATERIA {
        int id PK
        varchar codigo UK
        varchar nombre
        text descripcion
        int creditos
        boolean activa
        timestamp fecha_creacion
    }
    
    AULA {
        int id PK
        varchar numero
        varchar edificio
        int capacidad
        enum tipo_aula
        text equipamiento
        boolean activa
    }
    
    HORARIO {
        int id PK
        enum dia_semana
        time hora_inicio
        time hora_fin
        varchar periodo_academico
        boolean activo
    }
    
    RESERVA {
        int id PK
        varchar usuario_cedula FK
        int aula_id FK
        int horario_id FK
        int materia_id FK
        date fecha_reserva
        enum estado
        text motivo
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    
    USUARIO ||--o{ RESERVA : "realiza"
    AULA ||--o{ RESERVA : "se_reserva_en"
    HORARIO ||--o{ RESERVA : "tiene"
    MATERIA ||--o{ RESERVA : "es_para"
```
*/

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS chronos_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE chronos_db;

-- =====================================================
-- CREACIÓN DE TABLAS
-- =====================================================

-- Tabla Usuario
CREATE TABLE usuario (
    cedula VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    fecha_nacimiento DATE,
    tipo_usuario ENUM('estudiante', 'profesor', 'administrador') NOT NULL DEFAULT 'estudiante',
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cedula),
    UNIQUE KEY uk_usuario_email (email),
    INDEX idx_usuario_tipo (tipo_usuario),
    INDEX idx_usuario_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla Materia
CREATE TABLE materia (
    id INT AUTO_INCREMENT,
    codigo VARCHAR(10) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    creditos INT NOT NULL,
    activa BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_materia_codigo (codigo),
    INDEX idx_materia_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla Aula
CREATE TABLE aula (
    id INT AUTO_INCREMENT,
    numero VARCHAR(10) NOT NULL,
    edificio VARCHAR(50) NOT NULL,
    capacidad INT NOT NULL,
    tipo_aula ENUM('teoria', 'laboratorio', 'aula_magna') NOT NULL DEFAULT 'teoria',
    equipamiento TEXT,
    activa BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id),
    UNIQUE KEY uk_aula_numero_edificio (numero, edificio),
    INDEX idx_aula_tipo (tipo_aula),
    INDEX idx_aula_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla Horario
CREATE TABLE horario (
    id INT AUTO_INCREMENT,
    dia_semana ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    periodo_academico VARCHAR(20) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id),
    INDEX idx_horario_dia (dia_semana),
    INDEX idx_horario_periodo (periodo_academico),
    INDEX idx_horario_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla Reserva
CREATE TABLE reserva (
    id INT AUTO_INCREMENT,
    usuario_cedula VARCHAR(20) NOT NULL,
    aula_id INT NOT NULL,
    horario_id INT NOT NULL,
    materia_id INT NOT NULL,
    fecha_reserva DATE NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada') NOT NULL DEFAULT 'pendiente',
    motivo TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_reserva_usuario (usuario_cedula) 
        REFERENCES usuario(cedula) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY fk_reserva_aula (aula_id) 
        REFERENCES aula(id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY fk_reserva_horario (horario_id) 
        REFERENCES horario(id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY fk_reserva_materia (materia_id) 
        REFERENCES materia(id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY uk_reserva_aula_horario_fecha (aula_id, horario_id, fecha_reserva),
    INDEX idx_reserva_usuario (usuario_cedula),
    INDEX idx_reserva_fecha (fecha_reserva),
    INDEX idx_reserva_estado (estado),
    INDEX idx_reserva_fecha_creacion (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger para validar horarios
DELIMITER $$
CREATE TRIGGER tr_horario_validar_tiempo
    BEFORE INSERT ON horario
    FOR EACH ROW
BEGIN
    IF NEW.hora_fin <= NEW.hora_inicio THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'La hora de fin debe ser mayor a la hora de inicio';
    END IF;
END$$
DELIMITER ;

-- Trigger para validar capacidad de reserva
DELIMITER $$
CREATE TRIGGER tr_reserva_validar_capacidad
    BEFORE INSERT ON reserva
    FOR EACH ROW
BEGIN
    DECLARE capacidad_aula INT;
    DECLARE reservas_existentes INT;
    
    SELECT capacidad INTO capacidad_aula 
    FROM aula 
    WHERE id = NEW.aula_id;
    
    SELECT COUNT(*) INTO reservas_existentes
    FROM reserva 
    WHERE aula_id = NEW.aula_id 
    AND horario_id = NEW.horario_id 
    AND fecha_reserva = NEW.fecha_reserva
    AND estado IN ('pendiente', 'confirmada');
    
    IF reservas_existentes >= capacidad_aula THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'No hay capacidad disponible en el aula para este horario';
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- VISTAS
-- =====================================================

-- Vista de reservas completas
CREATE VIEW v_reservas_completas AS
SELECT 
    r.id,
    r.fecha_reserva,
    r.estado,
    r.motivo,
    CONCAT(u.nombre, ' ', u.apellido) AS usuario_nombre,
    u.cedula,
    u.email,
    CONCAT(a.edificio, ' - ', a.numero) AS aula_completa,
    a.capacidad,
    a.tipo_aula,
    h.dia_semana,
    h.hora_inicio,
    h.hora_fin,
    m.nombre AS materia_nombre,
    m.codigo AS materia_codigo
FROM reserva r
JOIN usuario u ON r.usuario_cedula = u.cedula
JOIN aula a ON r.aula_id = a.id
JOIN horario h ON r.horario_id = h.id
JOIN materia m ON r.materia_id = m.id;

-- Vista de aulas disponibles
CREATE VIEW v_aulas_disponibles AS
SELECT 
    a.id,
    a.numero,
    a.edificio,
    a.capacidad,
    a.tipo_aula,
    a.equipamiento,
    h.dia_semana,
    h.hora_inicio,
    h.hora_fin,
    h.periodo_academico
FROM aula a
CROSS JOIN horario h
WHERE a.activa = TRUE 
AND h.activo = TRUE
AND NOT EXISTS (
    SELECT 1 FROM reserva r 
    WHERE r.aula_id = a.id 
    AND r.horario_id = h.id 
    AND r.fecha_reserva = CURDATE()
    AND r.estado IN ('pendiente', 'confirmada')
);

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

-- Procedimiento para crear reserva
DELIMITER $$
CREATE PROCEDURE sp_crear_reserva(
    IN p_usuario_cedula VARCHAR(20),
    IN p_aula_id INT,
    IN p_horario_id INT,
    IN p_materia_id INT,
    IN p_fecha_reserva DATE,
    IN p_motivo TEXT,
    OUT p_resultado VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'Error al crear la reserva';
    END;
    
    START TRANSACTION;
    
    INSERT INTO reserva (usuario_cedula, aula_id, horario_id, materia_id, fecha_reserva, motivo)
    VALUES (p_usuario_cedula, p_aula_id, p_horario_id, p_materia_id, p_fecha_reserva, p_motivo);
    
    COMMIT;
    SET p_resultado = 'Reserva creada exitosamente';
END$$
DELIMITER ;

-- =====================================================
-- CREACIÓN DE USUARIOS Y PERMISOS
-- =====================================================

-- Usuario para aplicación web
CREATE USER IF NOT EXISTS 'chronos_app'@'%' IDENTIFIED BY 'chronos_app_pass_2024';
GRANT SELECT, INSERT, UPDATE, DELETE ON chronos_db.* TO 'chronos_app'@'%';
GRANT EXECUTE ON chronos_db.* TO 'chronos_app'@'%';

-- Usuario para administración
CREATE USER IF NOT EXISTS 'chronos_admin'@'%' IDENTIFIED BY 'chronos_admin_pass_2024';
GRANT ALL PRIVILEGES ON chronos_db.* TO 'chronos_admin'@'%';

-- Usuario para reportes (solo lectura)
CREATE USER IF NOT EXISTS 'chronos_reports'@'%' IDENTIFIED BY 'chronos_reports_pass_2024';
GRANT SELECT ON chronos_db.* TO 'chronos_reports'@'%';

-- Usuario para backup
CREATE USER IF NOT EXISTS 'chronos_backup'@'%' IDENTIFIED BY 'chronos_backup_pass_2024';
GRANT SELECT, LOCK TABLES ON chronos_db.* TO 'chronos_backup'@'%';

-- =====================================================
-- DATOS DE PRUEBA
-- =====================================================

-- Insertar usuarios de prueba
INSERT INTO usuario (cedula, nombre, apellido, email, tipo_usuario) VALUES
('12345678', 'Juan', 'Pérez', 'juan.perez@universidad.edu', 'estudiante'),
('87654321', 'María', 'González', 'maria.gonzalez@universidad.edu', 'profesor'),
('11223344', 'Carlos', 'Admin', 'carlos.admin@universidad.edu', 'administrador');

-- Insertar materias de prueba
INSERT INTO materia (codigo, nombre, descripcion, creditos) VALUES
('PROG101', 'Programación I', 'Fundamentos de programación', 4),
('BD101', 'Bases de Datos', 'Diseño y administración de bases de datos', 3),
('WEB101', 'Desarrollo Web', 'Desarrollo de aplicaciones web', 4);

-- Insertar aulas de prueba
INSERT INTO aula (numero, edificio, capacidad, tipo_aula, equipamiento) VALUES
('A101', 'Edificio A', 30, 'teoria', 'Pizarra, proyector'),
('LAB201', 'Edificio B', 20, 'laboratorio', 'Computadoras, proyector'),
('MAG001', 'Edificio C', 100, 'aula_magna', 'Sistema de sonido, proyector 4K');

-- Insertar horarios de prueba
INSERT INTO horario (dia_semana, hora_inicio, hora_fin, periodo_academico) VALUES
('lunes', '08:00:00', '10:00:00', '2024-1'),
('lunes', '10:00:00', '12:00:00', '2024-1'),
('martes', '14:00:00', '16:00:00', '2024-1'),
('miercoles', '08:00:00', '10:00:00', '2024-1');

-- =====================================================
-- CONFIGURACIÓN DE SEGURIDAD
-- =====================================================

-- Configurar variables de seguridad
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
SET GLOBAL max_connections = 200;
SET GLOBAL innodb_buffer_pool_size = 128M;

-- Aplicar permisos
FLUSH PRIVILEGES;

-- =====================================================
-- COMENTARIOS FINALES
-- =====================================================

-- Este script crea la estructura completa de la base de datos
-- para el sistema de gestión de horarios Chronos
-- Incluye tablas, índices, triggers, vistas, procedimientos
-- y configuración de usuarios con permisos específicos
