-- S.I.G.I.E Chronos E.S.R.E Database Schema
-- PostgreSQL Database for School Schedule Management System
-- Version: 2.0.0
-- Created: 2025
-- Updated: Based on new ER diagram structure

-- Drop and recreate schema
DROP SCHEMA IF EXISTS public CASCADE;
CREATE SCHEMA public AUTHORIZATION pg_database_owner;

-- Sequences
CREATE SEQUENCE usuario_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE docente_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE padre_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE bloque_horario_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE disponibilidad_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE materia_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE pauta_anep_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE horario_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE observacion_predefinida_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE observacion_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE grupo_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE log_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;

-- Core Tables
CREATE TABLE rol (
    nombre_rol VARCHAR(50) PRIMARY KEY,
    descripcion TEXT
);

CREATE TABLE usuario (
    id_usuario INTEGER PRIMARY KEY DEFAULT nextval('usuario_id_seq'),
    cedula VARCHAR(8) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    telefono VARCHAR(20),
    contrasena_hash VARCHAR(255) NOT NULL
);

CREATE TABLE usuario_rol (
    id_usuario INTEGER NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    nombre_rol VARCHAR(50) NOT NULL REFERENCES rol(nombre_rol) ON DELETE CASCADE,
    PRIMARY KEY (id_usuario, nombre_rol)
);

CREATE TABLE docente (
    id_docente INTEGER PRIMARY KEY DEFAULT nextval('docente_id_seq'),
    id_usuario INTEGER NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    trabaja_otro_liceo BOOLEAN DEFAULT FALSE,
    fecha_envio_disponibilidad DATE,
    horas_asignadas INTEGER DEFAULT 0,
    porcentaje_margen NUMERIC(5,2) DEFAULT 0.00
);

CREATE TABLE padre (
    id_padre INTEGER PRIMARY KEY DEFAULT nextval('padre_id_seq'),
    id_usuario INTEGER NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE bloque_horario (
    id_bloque INTEGER PRIMARY KEY DEFAULT nextval('bloque_horario_id_seq'),
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    CONSTRAINT check_horario_valido CHECK (hora_fin > hora_inicio)
);

CREATE TABLE disponibilidad (
    id_disponibilidad INTEGER PRIMARY KEY DEFAULT nextval('disponibilidad_id_seq'),
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_bloque INTEGER NOT NULL REFERENCES bloque_horario(id_bloque),
    dia VARCHAR(20) NOT NULL CHECK (dia IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO')),
    disponible BOOLEAN DEFAULT TRUE
);

-- Create pauta_anep table first since materia references it
CREATE TABLE pauta_anep (
    id_pauta_anep INTEGER PRIMARY KEY DEFAULT nextval('pauta_anep_id_seq'),
    nombre VARCHAR(200) NOT NULL,
    dias_minimos INTEGER DEFAULT 1 NOT NULL,
    dias_maximos INTEGER DEFAULT 5 NOT NULL,
    condiciones_especiales TEXT,
    CONSTRAINT check_dias_validos CHECK (dias_maximos >= dias_minimos AND dias_minimos > 0)
);

-- Create grupo table first since materia references it
CREATE TABLE grupo (
    id_grupo INTEGER PRIMARY KEY DEFAULT nextval('grupo_id_seq'),
    nombre VARCHAR(100) NOT NULL,
    nivel VARCHAR(50) NOT NULL
);

CREATE TABLE materia (
    id_materia INTEGER PRIMARY KEY DEFAULT nextval('materia_id_seq'),
    nombre VARCHAR(200) NOT NULL,
    horas_semanales INTEGER DEFAULT 1 NOT NULL CHECK (horas_semanales > 0),
    id_pauta_anep INTEGER NOT NULL REFERENCES pauta_anep(id_pauta_anep),
    en_conjunto BOOLEAN DEFAULT FALSE,
    id_grupo_compartido INTEGER REFERENCES grupo(id_grupo),
    es_programa_italiano BOOLEAN DEFAULT FALSE
);

-- Table to link parents to their children's groups
CREATE TABLE padre_grupo (
    id_padre INTEGER NOT NULL REFERENCES padre(id_padre) ON DELETE CASCADE,
    id_grupo INTEGER NOT NULL REFERENCES grupo(id_grupo) ON DELETE CASCADE,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_padre, id_grupo)
);

COMMENT ON TABLE padre_grupo IS 'Asignación de padres a grupos (clase de su hijo/a)';

-- Table to define which subjects belong to which groups (curriculum)
CREATE TABLE grupo_materia (
    id_grupo INTEGER NOT NULL REFERENCES grupo(id_grupo) ON DELETE CASCADE,
    id_materia INTEGER NOT NULL REFERENCES materia(id_materia) ON DELETE CASCADE,
    horas_semanales INTEGER NOT NULL DEFAULT 1 CHECK (horas_semanales > 0),
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_grupo, id_materia)
);

COMMENT ON TABLE grupo_materia IS 'Materias asignadas a cada grupo (currícula)';

-- Add indexes for better performance
CREATE INDEX idx_padre_grupo_padre ON padre_grupo(id_padre);
CREATE INDEX idx_padre_grupo_grupo ON padre_grupo(id_grupo);
CREATE INDEX idx_grupo_materia_grupo ON grupo_materia(id_grupo);
CREATE INDEX idx_grupo_materia_materia ON grupo_materia(id_materia);

CREATE TABLE docente_materia (
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_materia INTEGER NOT NULL REFERENCES materia(id_materia) ON DELETE CASCADE,
    PRIMARY KEY (id_docente, id_materia)
);

CREATE TABLE horario (
    id_horario INTEGER PRIMARY KEY DEFAULT nextval('horario_id_seq'),
    id_grupo INTEGER NOT NULL REFERENCES grupo(id_grupo),
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente),
    id_materia INTEGER NOT NULL REFERENCES materia(id_materia),
    id_bloque INTEGER NOT NULL REFERENCES bloque_horario(id_bloque),
    dia VARCHAR(20) NOT NULL CHECK (dia IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'))
);

CREATE TABLE observacion_predefinida (
    id_observacion_predefinida INTEGER PRIMARY KEY DEFAULT nextval('observacion_predefinida_id_seq'),
    texto TEXT NOT NULL,
    es_sistema BOOLEAN DEFAULT FALSE,
    activa BOOLEAN DEFAULT TRUE
);

CREATE TABLE observacion (
    id_observacion INTEGER PRIMARY KEY DEFAULT nextval('observacion_id_seq'),
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_observacion_predefinida INTEGER REFERENCES observacion_predefinida(id_observacion_predefinida),
    tipo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    motivo_texto TEXT
);

CREATE TABLE log (
    id_log INTEGER PRIMARY KEY DEFAULT nextval('log_id_seq'),
    id_usuario INTEGER NOT NULL REFERENCES usuario(id_usuario),
    accion VARCHAR(200) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detalle TEXT
);

-- Additional tables for schedule publishing functionality
CREATE SEQUENCE horario_publicado_id_publicacion_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;

CREATE TABLE horario_publicado (
    id_publicacion INTEGER PRIMARY KEY DEFAULT nextval('horario_publicado_id_publicacion_seq'),
    id_horario_referencia INTEGER,
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    publicado_por INTEGER REFERENCES usuario(id_usuario),
    activo BOOLEAN DEFAULT TRUE,
    descripcion TEXT
);

CREATE SEQUENCE solicitud_publicacion_id_solicitud_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;

CREATE TABLE solicitud_publicacion (
    id_solicitud INTEGER PRIMARY KEY DEFAULT nextval('solicitud_publicacion_id_solicitud_seq'),
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    solicitado_por INTEGER NOT NULL REFERENCES usuario(id_usuario),
    estado VARCHAR(20) DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'aprobado', 'rechazado')),
    revisado_por INTEGER REFERENCES usuario(id_usuario),
    fecha_revision TIMESTAMP,
    notas TEXT,
    snapshot_hash VARCHAR(64) NOT NULL
);

-- Performance Indexes
CREATE INDEX idx_usuario_cedula ON usuario(cedula);
CREATE INDEX idx_usuario_email ON usuario(email);
CREATE INDEX idx_docente_usuario ON docente(id_usuario);
CREATE INDEX idx_padre_usuario ON padre(id_usuario);
CREATE INDEX idx_disponibilidad_docente ON disponibilidad(id_docente);
CREATE INDEX idx_disponibilidad_bloque ON disponibilidad(id_bloque);
CREATE INDEX idx_disponibilidad_dia ON disponibilidad(dia);
CREATE INDEX idx_materia_pauta ON materia(id_pauta_anep);
CREATE INDEX idx_docente_materia_docente ON docente_materia(id_docente);
CREATE INDEX idx_docente_materia_materia ON docente_materia(id_materia);
CREATE INDEX idx_horario_grupo ON horario(id_grupo);
CREATE INDEX idx_horario_docente ON horario(id_docente);
CREATE INDEX idx_horario_bloque ON horario(id_bloque);
CREATE INDEX idx_horario_dia ON horario(dia);
CREATE INDEX idx_observacion_docente ON observacion(id_docente);
CREATE INDEX idx_log_usuario ON log(id_usuario);
CREATE INDEX idx_log_fecha ON log(fecha);
CREATE INDEX idx_solicitud_publicacion_solicitado_por ON solicitud_publicacion(solicitado_por);
CREATE INDEX idx_solicitud_publicacion_estado ON solicitud_publicacion(estado);

-- Table Documentation
COMMENT ON TABLE rol IS 'Roles de usuario y permisos';
COMMENT ON TABLE usuario IS 'Usuarios del sistema identificados por cédula uruguaya';
COMMENT ON TABLE usuario_rol IS 'Relación muchos a muchos entre usuarios y roles';
COMMENT ON TABLE docente IS 'Docentes con seguimiento de disponibilidad';
COMMENT ON TABLE padre IS 'Padres de estudiantes';
COMMENT ON TABLE bloque_horario IS 'Bloques de tiempo para programación';
COMMENT ON TABLE disponibilidad IS 'Disponibilidad de docentes para bloques y días específicos';
COMMENT ON TABLE materia IS 'Materias con pautas ANEP';
COMMENT ON TABLE docente_materia IS 'Relación muchos a muchos entre docentes y materias';
COMMENT ON TABLE pauta_anep IS 'Pautas ANEP para programación de materias';
COMMENT ON TABLE horario IS 'Asignaciones de horarios para grupos, docentes, materias y bloques';
COMMENT ON TABLE observacion_predefinida IS 'Plantillas de observaciones predefinidas';
COMMENT ON TABLE observacion IS 'Observaciones y notas de docentes';
COMMENT ON TABLE grupo IS 'Grupos de estudiantes y niveles';
COMMENT ON TABLE log IS 'Registro de actividad del sistema';
COMMENT ON TABLE horario_publicado IS 'Horarios publicados oficialmente';
COMMENT ON TABLE solicitud_publicacion IS 'Solicitudes de publicación de horarios';

-- Insert default roles
INSERT INTO rol (nombre_rol, descripcion) VALUES
('ADMIN', 'Administrador del sistema con acceso completo'),
('DIRECTOR', 'Director del centro educativo con permisos de supervisión'),
('COORDINADOR', 'Coordinador de horarios con permisos de gestión'),
('DOCENTE', 'Docente con acceso limitado a su horario'),
('PADRE', 'Padre con acceso de solo lectura a información del estudiante');

-- Insert default time blocks
INSERT INTO bloque_horario (id_bloque, hora_inicio, hora_fin) VALUES
(1, '08:00:00', '08:45:00'),
(2, '08:45:00', '09:30:00'),
(3, '09:35:00', '10:20:00'),
(4, '10:20:00', '11:15:00'),
(5, '11:15:00', '12:00:00'),
(6, '12:00:00', '12:45:00'),
(7, '13:20:00', '14:05:00'),
(8, '14:05:00', '14:50:00'),
(9, '14:55:00', '15:40:00'),
(10, '15:40:00', '16:25:00');

-- Insert default predefined observations
INSERT INTO observacion_predefinida (id_observacion_predefinida, texto, es_sistema, activa) VALUES
(1, 'Otro liceo', TRUE, TRUE),
(2, 'Licencia médica', TRUE, TRUE),
(3, 'Capacitación', TRUE, TRUE),
(4, 'Reunión coordinación', TRUE, TRUE),
(5, 'Evaluación institucional', TRUE, TRUE);

-- Insert default admin user
INSERT INTO usuario (id_usuario, cedula, nombre, apellido, email, telefono, contrasena_hash) VALUES
(1, '12345678', 'Administrador', 'Sistema', 'admin@chronos.edu.uy', '099123456', '$2y$10$6fF5.mtmT4ScYKYmJp6TpuaSfcbLVJQ20czlx.JK.a/f800qjD7Ri');

-- Assign admin role to default user
INSERT INTO usuario_rol (id_usuario, nombre_rol) VALUES
(1, 'ADMIN');

-- Create Zabbix database and user
CREATE DATABASE zabbix;
CREATE USER zabbix WITH PASSWORD 'zabbix';
GRANT ALL PRIVILEGES ON DATABASE zabbix TO zabbix;

-- Insert version information
COMMENT ON SCHEMA public IS 'Chronos Database Schema v2.0.0 - Updated 2025';
