-- S.I.G.I.E Chronos E.S.R.E Database Schema
-- PostgreSQL Database for School Schedule Management System
-- Version: 1.0.0
-- Created: 2025

-- Drop and recreate schema
DROP SCHEMA IF EXISTS public CASCADE;
CREATE SCHEMA public AUTHORIZATION pg_database_owner;

-- Sequences
CREATE SEQUENCE bloque_horario_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE disponibilidad_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE docente_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE grupo_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE estudiante_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE horario_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE liceo_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE log_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE materia_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE observacion_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE observacion_predefinida_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE padre_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE pauta_anep_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE permiso_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE ano_academico_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE regla_generacion_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE version_horario_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE horario_version_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE combinacion_materias_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE disponibilidad_avanzada_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE configuracion_sistema_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE auditoria_horario_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE notificacion_preferencia_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;
CREATE SEQUENCE calendario_academico_id_seq INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1 NO CYCLE;

-- Core Tables
CREATE TABLE rol (
    nombre_rol VARCHAR(50) PRIMARY KEY,
    descripcion TEXT
);

CREATE TABLE permiso (
    id_permiso INTEGER PRIMARY KEY DEFAULT nextval('permiso_id_seq'),
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

CREATE TABLE usuario (
    cedula VARCHAR(8) PRIMARY KEY CHECK (cedula ~ '^[0-9]{7,8}$'),
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    telefono VARCHAR(20),
    contrasena_hash VARCHAR(255) NOT NULL,
    nombre_rol VARCHAR(50) NOT NULL REFERENCES rol(nombre_rol)
);

CREATE TABLE ano_academico (
    id_ano INTEGER PRIMARY KEY DEFAULT nextval('ano_academico_id_seq'),
    nombre VARCHAR(50) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo BOOLEAN DEFAULT FALSE,
    CONSTRAINT check_fechas_validas CHECK (fecha_fin > fecha_inicio)
);

CREATE TABLE grupo (
    id_grupo INTEGER PRIMARY KEY DEFAULT nextval('grupo_id_seq'),
    nombre VARCHAR(100) NOT NULL,
    nivel VARCHAR(50) NOT NULL
);

CREATE TABLE liceo (
    id_liceo INTEGER PRIMARY KEY DEFAULT nextval('liceo_id_seq'),
    nombre VARCHAR(200) NOT NULL
);

CREATE TABLE bloque_horario (
    id_bloque INTEGER PRIMARY KEY DEFAULT nextval('bloque_horario_id_seq'),
    nombre_bloque VARCHAR(100) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    CONSTRAINT check_horario_valido CHECK (hora_fin > hora_inicio)
);

CREATE TABLE pauta_anep (
    id_pauta_anep INTEGER PRIMARY KEY DEFAULT nextval('pauta_anep_id_seq'),
    nombre VARCHAR(200) NOT NULL,
    dias_minimos INTEGER DEFAULT 1 NOT NULL,
    dias_maximos INTEGER DEFAULT 5 NOT NULL,
    condiciones_especiales TEXT,
    CONSTRAINT check_dias_validos CHECK (dias_maximos >= dias_minimos AND dias_minimos > 0)
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

CREATE TABLE observacion_predefinida (
    id_observacion_predefinida INTEGER PRIMARY KEY DEFAULT nextval('observacion_predefinida_id_seq'),
    texto TEXT NOT NULL,
    es_sistema BOOLEAN DEFAULT FALSE,
    activa BOOLEAN DEFAULT TRUE
);

-- User Role Tables
CREATE TABLE docente (
    id_docente INTEGER PRIMARY KEY DEFAULT nextval('docente_id_seq'),
    cedula_usuario VARCHAR(8) NOT NULL REFERENCES usuario(cedula) ON DELETE CASCADE,
    trabaja_otro_liceo BOOLEAN DEFAULT FALSE,
    fecha_envio_disponibilidad DATE,
    horas_asignadas INTEGER DEFAULT 0,
    porcentaje_margen NUMERIC(5,2) DEFAULT 0.00
);

CREATE TABLE padre (
    id_padre INTEGER PRIMARY KEY DEFAULT nextval('padre_id_seq'),
    cedula_usuario VARCHAR(8) NOT NULL REFERENCES usuario(cedula) ON DELETE CASCADE
);

CREATE TABLE estudiante (
    id_estudiante INTEGER PRIMARY KEY DEFAULT nextval('estudiante_id_seq'),
    cedula VARCHAR(8) UNIQUE NOT NULL CHECK (cedula ~ '^[0-9]{7,8}$'),
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    id_grupo INTEGER REFERENCES grupo(id_grupo),
    id_ano_academico INTEGER NOT NULL REFERENCES ano_academico(id_ano),
    activo BOOLEAN DEFAULT TRUE,
    fecha_inscripcion DATE DEFAULT CURRENT_DATE
);

-- Relationship Tables
CREATE TABLE rol_permiso (
    nombre_rol VARCHAR(50) REFERENCES rol(nombre_rol) ON DELETE CASCADE,
    id_permiso INTEGER REFERENCES permiso(id_permiso) ON DELETE CASCADE,
    PRIMARY KEY (nombre_rol, id_permiso)
);

CREATE TABLE docente_liceo (
    id_docente INTEGER REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_liceo INTEGER REFERENCES liceo(id_liceo) ON DELETE CASCADE,
    PRIMARY KEY (id_docente, id_liceo)
);

CREATE TABLE docente_materia (
    id_docente INTEGER REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_materia INTEGER REFERENCES materia(id_materia) ON DELETE CASCADE,
    PRIMARY KEY (id_docente, id_materia)
);

CREATE TABLE combinacion_materias (
    id_combinacion INTEGER PRIMARY KEY DEFAULT nextval('combinacion_materias_id_seq'),
    id_materia_principal INTEGER NOT NULL REFERENCES materia(id_materia),
    id_materia_compartida INTEGER NOT NULL REFERENCES materia(id_materia),
    id_grupo_compartido INTEGER NOT NULL REFERENCES grupo(id_grupo),
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_materias_diferentes CHECK (id_materia_principal != id_materia_compartida)
);

-- Schedule Management Tables
CREATE TABLE version_horario (
    id_version INTEGER PRIMARY KEY DEFAULT nextval('version_horario_id_seq'),
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    id_ano_academico INTEGER NOT NULL REFERENCES ano_academico(id_ano),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_publicacion TIMESTAMP,
    estado VARCHAR(50) DEFAULT 'BORRADOR' CHECK (estado IN ('BORRADOR', 'GENERADO', 'PUBLICADO', 'ARCHIVADO')),
    activa BOOLEAN DEFAULT FALSE,
    id_coordinador VARCHAR(8) NOT NULL REFERENCES usuario(cedula)
);

CREATE TABLE horario_version (
    id_horario_version INTEGER PRIMARY KEY DEFAULT nextval('horario_version_id_seq'),
    id_version INTEGER NOT NULL REFERENCES version_horario(id_version) ON DELETE CASCADE,
    id_grupo INTEGER NOT NULL REFERENCES grupo(id_grupo),
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente),
    id_materia INTEGER NOT NULL REFERENCES materia(id_materia),
    id_bloque INTEGER NOT NULL REFERENCES bloque_horario(id_bloque),
    dia VARCHAR(20) NOT NULL CHECK (dia IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO')),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario_creacion VARCHAR(8) NOT NULL REFERENCES usuario(cedula),
    UNIQUE (id_version, id_docente, id_bloque, dia),
    UNIQUE (id_version, id_grupo, id_bloque, dia)
);

-- Availability and Preferences Tables
CREATE TABLE disponibilidad (
    id_disponibilidad INTEGER PRIMARY KEY DEFAULT nextval('disponibilidad_id_seq'),
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_bloque INTEGER NOT NULL REFERENCES bloque_horario(id_bloque),
    dia VARCHAR(20) NOT NULL CHECK (dia IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'))
);

CREATE TABLE disponibilidad_avanzada (
    id_disponibilidad_avanzada INTEGER PRIMARY KEY DEFAULT nextval('disponibilidad_avanzada_id_seq'),
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_bloque INTEGER NOT NULL REFERENCES bloque_horario(id_bloque),
    dia VARCHAR(20) NOT NULL CHECK (dia IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO')),
    preferencia VARCHAR(20) DEFAULT 'DISPONIBLE' CHECK (preferencia IN ('DISPONIBLE', 'PREFERIDO', 'EVITAR', 'NO_DISPONIBLE')),
    peso_preferencia INTEGER DEFAULT 1 CHECK (peso_preferencia >= 1 AND peso_preferencia <= 5)
);

CREATE TABLE observacion (
    id_observacion INTEGER PRIMARY KEY DEFAULT nextval('observacion_id_seq'),
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_observacion_predefinida INTEGER REFERENCES observacion_predefinida(id_observacion_predefinida),
    tipo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    motivo_texto TEXT
);

-- System Configuration and Audit Tables
CREATE TABLE regla_generacion_horario (
    id_regla INTEGER PRIMARY KEY DEFAULT nextval('regla_generacion_id_seq'),
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    tipo_regla VARCHAR(50) NOT NULL CHECK (tipo_regla IN ('DISTRIBUCION_DIAS', 'HORARIO_ESPECIAL', 'RESTRICCION_DOCENTE', 'PROGRAMA_ITALIANO', 'EDUCACION_FISICA')),
    activa BOOLEAN DEFAULT TRUE,
    prioridad INTEGER DEFAULT 1,
    parametros JSONB
);

CREATE TABLE configuracion_sistema (
    id_configuracion INTEGER PRIMARY KEY DEFAULT nextval('configuracion_sistema_id_seq'),
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcion TEXT,
    tipo VARCHAR(50) DEFAULT 'TEXTO' CHECK (tipo IN ('TEXTO', 'NUMERO', 'BOOLEANO', 'JSON')),
    editable BOOLEAN DEFAULT TRUE
);

CREATE TABLE auditoria_horario (
    id_auditoria INTEGER PRIMARY KEY DEFAULT nextval('auditoria_horario_id_seq'),
    id_horario_version INTEGER REFERENCES horario_version(id_horario_version),
    id_version INTEGER NOT NULL REFERENCES version_horario(id_version),
    accion VARCHAR(50) NOT NULL CHECK (accion IN ('CREAR', 'MODIFICAR', 'ELIMINAR', 'PUBLICAR', 'GENERAR_AUTOMATICO')),
    cedula_usuario VARCHAR(8) NOT NULL REFERENCES usuario(cedula),
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    datos_anteriores JSONB,
    datos_nuevos JSONB,
    motivo_cambio TEXT
);

CREATE TABLE notificacion_preferencia (
    id_notificacion_preferencia INTEGER PRIMARY KEY DEFAULT nextval('notificacion_preferencia_id_seq'),
    cedula_usuario VARCHAR(8) NOT NULL REFERENCES usuario(cedula),
    tipo_notificacion VARCHAR(50) NOT NULL CHECK (tipo_notificacion IN ('EMAIL', 'SMS', 'SISTEMA')),
    activa BOOLEAN DEFAULT TRUE,
    configuracion JSONB
);

CREATE TABLE calendario_academico (
    id_calendario INTEGER PRIMARY KEY DEFAULT nextval('calendario_academico_id_seq'),
    id_ano_academico INTEGER NOT NULL REFERENCES ano_academico(id_ano),
    fecha DATE NOT NULL,
    tipo VARCHAR(50) NOT NULL CHECK (tipo IN ('FERIADO', 'VACACIONES', 'EXAMEN', 'EVENTO_ESPECIAL')),
    descripcion TEXT,
    afecta_horarios BOOLEAN DEFAULT TRUE
);

-- Legacy compatibility table (deprecated)
CREATE TABLE horario (
    id_horario INTEGER PRIMARY KEY DEFAULT nextval('horario_id_seq'),
    id_grupo INTEGER NOT NULL REFERENCES grupo(id_grupo),
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente),
    id_materia INTEGER NOT NULL REFERENCES materia(id_materia),
    id_bloque INTEGER NOT NULL REFERENCES bloque_horario(id_bloque),
    dia VARCHAR(20) NOT NULL CHECK (dia IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO')),
    UNIQUE (id_docente, id_bloque, dia),
    UNIQUE (id_grupo, id_bloque, dia)
);

CREATE TABLE log (
    id_log INTEGER PRIMARY KEY DEFAULT nextval('log_id_seq'),
    cedula_usuario VARCHAR(8) NOT NULL REFERENCES usuario(cedula),
    accion VARCHAR(200) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detalle TEXT
);

CREATE TABLE hijo (
    id_hijo INTEGER PRIMARY KEY DEFAULT nextval('estudiante_id_seq'),
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    id_grupo INTEGER REFERENCES grupo(id_grupo),
    id_padre INTEGER NOT NULL REFERENCES padre(id_padre) ON DELETE CASCADE
);

-- Performance Indexes
CREATE INDEX idx_usuario_cedula ON usuario(cedula);
CREATE INDEX idx_usuario_email ON usuario(email);
CREATE INDEX idx_usuario_rol ON usuario(nombre_rol);
CREATE INDEX idx_docente_cedula ON docente(cedula_usuario);
CREATE INDEX idx_padre_cedula ON padre(cedula_usuario);
CREATE INDEX idx_log_cedula ON log(cedula_usuario);
CREATE INDEX idx_estudiante_grupo ON estudiante(id_grupo);
CREATE INDEX idx_estudiante_ano ON estudiante(id_ano_academico);
CREATE INDEX idx_horario_version_version ON horario_version(id_version);
CREATE INDEX idx_horario_version_grupo ON horario_version(id_grupo);
CREATE INDEX idx_horario_version_docente ON horario_version(id_docente);
CREATE INDEX idx_version_horario_ano ON version_horario(id_ano_academico);
CREATE INDEX idx_version_horario_estado ON version_horario(estado);
CREATE INDEX idx_auditoria_horario_fecha ON auditoria_horario(fecha_cambio);
CREATE INDEX idx_auditoria_horario_usuario ON auditoria_horario(cedula_usuario);
CREATE INDEX idx_disponibilidad_avanzada_docente ON disponibilidad_avanzada(id_docente);
CREATE INDEX idx_calendario_academico_fecha ON calendario_academico(fecha);
CREATE INDEX idx_materia_pauta ON materia(id_pauta_anep);
CREATE INDEX idx_docente_materia_docente ON docente_materia(id_docente);
CREATE INDEX idx_docente_materia_materia ON docente_materia(id_materia);
CREATE INDEX idx_disponibilidad_docente ON disponibilidad(id_docente);
CREATE INDEX idx_disponibilidad_bloque ON disponibilidad(id_bloque);
CREATE INDEX idx_observacion_docente ON observacion(id_docente);
CREATE INDEX idx_combinacion_materias_principal ON combinacion_materias(id_materia_principal);
CREATE INDEX idx_combinacion_materias_compartida ON combinacion_materias(id_materia_compartida);

-- Table Documentation
COMMENT ON TABLE rol IS 'User roles and permissions';
COMMENT ON TABLE usuario IS 'System users with cédula-based authentication';
COMMENT ON TABLE ano_academico IS 'Academic year management';
COMMENT ON TABLE grupo IS 'Student groups and levels';
COMMENT ON TABLE materia IS 'Subjects with ANEP guidelines';
COMMENT ON TABLE docente IS 'Teachers with availability tracking';
COMMENT ON TABLE version_horario IS 'Schedule versions and templates';
COMMENT ON TABLE horario_version IS 'Detailed schedules with full audit trail';
COMMENT ON TABLE disponibilidad_avanzada IS 'Advanced teacher time preferences';
COMMENT ON TABLE regla_generacion_horario IS 'Schedule generation rules and constraints';
COMMENT ON TABLE auditoria_horario IS 'Complete schedule change audit trail';
COMMENT ON TABLE configuracion_sistema IS 'Global system configuration';
COMMENT ON TABLE notificacion_preferencia IS 'User notification preferences';
COMMENT ON TABLE calendario_academico IS 'Academic calendar with special dates';
