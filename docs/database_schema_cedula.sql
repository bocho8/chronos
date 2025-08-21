-- Modified Database Schema with CEDULA as Primary Key
-- This schema replaces the email-based login with cedula-based login

-- DROP SCHEMA public;
CREATE SCHEMA public AUTHORIZATION pg_database_owner;

COMMENT ON SCHEMA public IS 'standard public schema';

-- Sequences for other tables
CREATE SEQUENCE public.bloque_horario_id_bloque_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.disponibilidad_id_disponibilidad_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.docente_id_docente_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.grupo_id_grupo_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.hijo_id_hijo_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.horario_id_horario_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.liceo_id_liceo_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.log_id_log_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.materia_id_materia_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.observacion_id_observacion_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.observacion_predefinida_id_observacion_predefinida_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.padre_id_padre_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.pauta_anep_id_pauta_anep_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE public.permiso_id_permiso_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

-- Base tables
CREATE TABLE public.bloque_horario (
	id_bloque serial4 NOT NULL,
	nombre_bloque varchar(100) NOT NULL,
	hora_inicio time NOT NULL,
	hora_fin time NOT NULL,
	CONSTRAINT bloque_horario_pkey PRIMARY KEY (id_bloque),
	CONSTRAINT check_horario_valido CHECK ((hora_fin > hora_inicio))
);

CREATE TABLE public.grupo (
	id_grupo serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	nivel varchar(50) NOT NULL,
	CONSTRAINT grupo_pkey PRIMARY KEY (id_grupo)
);

CREATE TABLE public.liceo (
	id_liceo serial4 NOT NULL,
	nombre varchar(200) NOT NULL,
	CONSTRAINT liceo_pkey PRIMARY KEY (id_liceo)
);

CREATE TABLE public.observacion_predefinida (
	id_observacion_predefinida serial4 NOT NULL,
	texto text NOT NULL,
	es_sistema bool DEFAULT false NULL,
	activa bool DEFAULT true NULL,
	CONSTRAINT observacion_predefinida_pkey PRIMARY KEY (id_observacion_predefinida)
);

CREATE TABLE public.pauta_anep (
	id_pauta_anep serial4 NOT NULL,
	nombre varchar(200) NOT NULL,
	dias_minimos int4 DEFAULT 1 NOT NULL,
	dias_maximos int4 DEFAULT 5 NOT NULL,
	condiciones_especiales text NULL,
	CONSTRAINT check_dias_validos CHECK (((dias_maximos >= dias_minimos) AND (dias_minimos > 0))),
	CONSTRAINT pauta_anep_pkey PRIMARY KEY (id_pauta_anep)
);

CREATE TABLE public.permiso (
	id_permiso serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	descripcion text NULL,
	CONSTRAINT permiso_pkey PRIMARY KEY (id_permiso)
);

CREATE TABLE public.rol (
	nombre_rol varchar(50) NOT NULL,
	descripcion text NULL,
	CONSTRAINT rol_pkey PRIMARY KEY (nombre_rol)
);

-- MODIFIED: usuario table now uses cedula as primary key
CREATE TABLE public.usuario (
	cedula varchar(8) NOT NULL, -- Cédula de Identidad (7-8 digits)
	nombre varchar(100) NOT NULL,
	apellido varchar(100) NOT NULL,
	email varchar(150) NULL, -- Made optional since we're using cedula for login
	telefono varchar(20) NULL,
	contrasena_hash varchar(255) NOT NULL,
	nombre_rol varchar(50) NOT NULL,
	CONSTRAINT usuario_cedula_key UNIQUE (cedula),
	CONSTRAINT usuario_pkey PRIMARY KEY (cedula),
	CONSTRAINT usuario_nombre_rol_fkey FOREIGN KEY (nombre_rol) REFERENCES public.rol(nombre_rol),
	CONSTRAINT check_cedula_format CHECK (cedula ~ '^[0-9]{7,8}$') -- Ensures cedula is 7-8 digits
);

-- Insert default roles
INSERT INTO public.rol (nombre_rol, descripcion) VALUES
('Admin', 'Administrador del sistema'),
('Coordinador', 'Coordinador académico'),
('Docente', 'Profesor'),
('Padre/Madre', 'Padre o madre de estudiante'),
('Director', 'Director del liceo');

-- Insert sample admin user (cedula: 12345678, password: admin123)
INSERT INTO public.usuario (cedula, nombre, apellido, email, telefono, contrasena_hash, nombre_rol) VALUES
('12345678', 'Admin', 'Sistema', 'admin@sim.edu.uy', '099123456', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');

-- Other tables with updated foreign key references
CREATE TABLE public.materia (
	id_materia serial4 NOT NULL,
	nombre varchar(200) NOT NULL,
	horas_semanales int4 DEFAULT 1 NOT NULL,
	id_pauta_anep int4 NOT NULL,
	en_conjunto bool DEFAULT false NULL,
	id_grupo_compartido int4 NULL,
	es_programa_italiano bool DEFAULT false NULL,
	CONSTRAINT check_horas_positivas CHECK ((horas_semanales > 0)),
	CONSTRAINT materia_pkey PRIMARY KEY (id_materia),
	CONSTRAINT materia_id_grupo_compartido_fkey FOREIGN KEY (id_grupo_compartido) REFERENCES public.grupo(id_grupo),
	CONSTRAINT materia_id_pauta_anep_fkey FOREIGN KEY (id_pauta_anep) REFERENCES public.pauta_anep(id_pauta_anep)
);

CREATE TABLE public.rol_permiso (
	nombre_rol varchar(50) NOT NULL,
	id_permiso int4 NOT NULL,
	CONSTRAINT rol_permiso_pkey PRIMARY KEY (nombre_rol, id_permiso),
	CONSTRAINT rol_permiso_id_permiso_fkey FOREIGN KEY (id_permiso) REFERENCES public.permiso(id_permiso) ON DELETE CASCADE,
	CONSTRAINT rol_permiso_nombre_rol_fkey FOREIGN KEY (nombre_rol) REFERENCES public.rol(nombre_rol) ON DELETE CASCADE
);

-- MODIFIED: docente table now references cedula instead of id_usuario
CREATE TABLE public.docente (
	id_docente serial4 NOT NULL,
	cedula_usuario varchar(8) NOT NULL, -- Changed from id_usuario to cedula_usuario
	trabaja_otro_liceo bool DEFAULT false NULL,
	fecha_envio_disponibilidad date NULL,
	horas_asignadas int4 DEFAULT 0 NULL,
	porcentaje_margen numeric(5, 2) DEFAULT 0.00 NULL,
	CONSTRAINT docente_pkey PRIMARY KEY (id_docente),
	CONSTRAINT docente_cedula_usuario_fkey FOREIGN KEY (cedula_usuario) REFERENCES public.usuario(cedula) ON DELETE CASCADE
);

CREATE TABLE public.docente_liceo (
	id_docente int4 NOT NULL,
	id_liceo int4 NOT NULL,
	CONSTRAINT docente_liceo_pkey PRIMARY KEY (id_docente, id_liceo),
	CONSTRAINT docente_liceo_id_docente_fkey FOREIGN KEY (id_docente) REFERENCES public.docente(id_docente) ON DELETE CASCADE,
	CONSTRAINT docente_liceo_id_liceo_fkey FOREIGN KEY (id_liceo) REFERENCES public.liceo(id_liceo) ON DELETE CASCADE
);

CREATE TABLE public.docente_materia (
	id_docente int4 NOT NULL,
	id_materia int4 NOT NULL,
	CONSTRAINT docente_materia_pkey PRIMARY KEY (id_docente, id_materia),
	CONSTRAINT docente_materia_id_docente_fkey FOREIGN KEY (id_docente) REFERENCES public.docente(id_docente) ON DELETE CASCADE,
	CONSTRAINT docente_materia_id_materia_fkey FOREIGN KEY (id_materia) REFERENCES public.materia(id_materia) ON DELETE CASCADE
);

CREATE TABLE public.horario (
	id_horario serial4 NOT NULL,
	id_grupo int4 NOT NULL,
	id_docente int4 NOT NULL,
	id_materia int4 NOT NULL,
	id_bloque int4 NOT NULL,
	dia varchar(20) NOT NULL,
	CONSTRAINT check_dia_horario CHECK (((dia)::text = ANY ((ARRAY['LUNES'::character varying, 'MARTES'::character varying, 'MIERCOLES'::character varying, 'JUEVES'::character varying, 'VIERNES'::character varying, 'SABADO'::character varying, 'DOMINGO'::character varying])::text[]))),
	CONSTRAINT horario_id_docente_id_bloque_dia_key UNIQUE (id_docente, id_bloque, dia),
	CONSTRAINT horario_id_grupo_id_bloque_dia_key UNIQUE (id_grupo, id_bloque, dia),
	CONSTRAINT horario_pkey PRIMARY KEY (id_horario),
	CONSTRAINT horario_id_bloque_fkey FOREIGN KEY (id_bloque) REFERENCES public.bloque_horario(id_bloque),
	CONSTRAINT horario_id_docente_fkey FOREIGN KEY (id_docente) REFERENCES public.docente(id_docente),
	CONSTRAINT horario_id_grupo_fkey FOREIGN KEY (id_grupo) REFERENCES public.grupo(id_grupo),
	CONSTRAINT horario_id_materia_fkey FOREIGN KEY (id_materia) REFERENCES public.materia(id_materia)
);

-- MODIFIED: log table now references cedula instead of id_usuario
CREATE TABLE public.log (
	id_log serial4 NOT NULL,
	cedula_usuario varchar(8) NOT NULL, -- Changed from id_usuario to cedula_usuario
	accion varchar(200) NOT NULL,
	fecha timestamp DEFAULT CURRENT_TIMESTAMP NULL,
	detalle text NULL,
	CONSTRAINT log_pkey PRIMARY KEY (id_log),
	CONSTRAINT log_cedula_usuario_fkey FOREIGN KEY (cedula_usuario) REFERENCES public.usuario(cedula)
);

CREATE TABLE public.observacion (
	id_observacion serial4 NOT NULL,
	id_docente int4 NOT NULL,
	id_observacion_predefinida int4 NULL,
	tipo varchar(50) NOT NULL,
	descripcion text NULL,
	motivo_texto text NULL,
	CONSTRAINT observacion_pkey PRIMARY KEY (id_observacion),
	CONSTRAINT observacion_id_docente_fkey FOREIGN KEY (id_docente) REFERENCES public.docente(id_docente) ON DELETE CASCADE,
	CONSTRAINT observacion_id_observacion_predefinida_fkey FOREIGN KEY (id_observacion_predefinida) REFERENCES public.observacion_predefinida(id_observacion_predefinida)
);

-- MODIFIED: padre table now references cedula instead of id_usuario
CREATE TABLE public.padre (
	id_padre serial4 NOT NULL,
	cedula_usuario varchar(8) NOT NULL, -- Changed from id_usuario to cedula_usuario
	CONSTRAINT padre_pkey PRIMARY KEY (id_padre),
	CONSTRAINT padre_cedula_usuario_fkey FOREIGN KEY (cedula_usuario) REFERENCES public.usuario(cedula) ON DELETE CASCADE
);

CREATE TABLE public.disponibilidad (
	id_disponibilidad serial4 NOT NULL,
	id_docente int4 NOT NULL,
	id_bloque int4 NOT NULL,
	dia varchar(20) NOT NULL,
	disponible bool DEFAULT true NULL,
	CONSTRAINT check_dia_valido CHECK (((dia)::text = ANY ((ARRAY['LUNES'::character varying, 'MARTES'::character varying, 'MIERCOLES'::character varying, 'JUEVES'::character varying, 'VIERNES'::character varying, 'SABADO'::character varying, 'DOMINGO'::character varying])::text[]))),
	CONSTRAINT disponibilidad_pkey PRIMARY KEY (id_disponibilidad),
	CONSTRAINT disponibilidad_id_bloque_fkey FOREIGN KEY (id_bloque) REFERENCES public.bloque_horario(id_bloque),
	CONSTRAINT disponibilidad_id_docente_fkey FOREIGN KEY (id_docente) REFERENCES public.docente(id_docente) ON DELETE CASCADE
);

CREATE TABLE public.hijo (
	id_hijo serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	apellido varchar(100) NOT NULL,
	id_grupo int4 NULL,
	id_padre int4 NOT NULL,
	CONSTRAINT hijo_pkey PRIMARY KEY (id_hijo),
	CONSTRAINT hijo_id_grupo_fkey FOREIGN KEY (id_grupo) REFERENCES public.grupo(id_grupo),
	CONSTRAINT hijo_id_padre_fkey FOREIGN KEY (id_padre) REFERENCES public.padre(id_padre) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_usuario_cedula ON public.usuario(cedula);
CREATE INDEX idx_usuario_email ON public.usuario(email);
CREATE INDEX idx_usuario_rol ON public.usuario(nombre_rol);
CREATE INDEX idx_docente_cedula ON public.docente(cedula_usuario);
CREATE INDEX idx_padre_cedula ON public.padre(cedula_usuario);
CREATE INDEX idx_log_cedula ON public.log(cedula_usuario);
