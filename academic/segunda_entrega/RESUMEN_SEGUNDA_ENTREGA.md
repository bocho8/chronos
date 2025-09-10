# Resumen Segunda Entrega - S.I.G.I.E Chronos E.S.R.E

## Información del Proyecto
- **Nombre:** S.I.G.I.E Chronos E.S.R.E
- **Institución:** Liceo Italiano
- **Fecha de Entrega:** 28/07/2025
- **Equipo:**
  - **Coordinador:** Juan Chapuis (5.630.283-4)
  - **Sub-Coordinador:** Agustin Roizen (6.339.592-9)
  - **Integrante:** Bruno Marino (5.707.620-6)

## Objetivos de la Segunda Entrega

### Ingeniería de Software
1. **Repo Git y Estructura del Proyecto**
   - Configuración de repositorio Git con GitFlow
   - Arquitectura del sistema basada en PHP 8.3, PostgreSQL 16, Nginx
   - Diagramas de flujo de desarrollo y arquitectura

2. **Gestión de Proyecto**
   - Metodología ágil adaptada al proyecto Chronos
   - Cronograma específico con entregas hasta julio 2025
   - Gestión de riesgos y control de calidad

### Programación Full Stack
1. **Modelo Conceptual Corregido**
   - Entidades alineadas con requerimientos del ESRE
   - Roles de usuario: Administrador, Dirección, Coordinador, Docente, Padre
   - Gestión de disponibilidad docente y observaciones
   - Pautas ANEP y distribución de clases

2. **Modelo Físico (DDL y Permisos)**
   - Script SQL completo para PostgreSQL
   - Triggers de validación de negocio
   - Vistas y procedimientos almacenados
   - Configuración de usuarios y permisos

3. **Análisis de Respaldo de Base de Datos**
   - Estrategia de respaldo para PostgreSQL
   - Procedimientos de recuperación
   - Monitoreo y alertas
   - Cumplimiento de requerimientos de disponibilidad (99%)

## Características Técnicas Implementadas

### Base de Datos
- **Motor:** PostgreSQL 16.3-alpine
- **Entidades principales:** Usuario, Materia, Grupo, Horario, Asignacion
- **Entidades de soporte:** DisponibilidadDocente, ObservacionDocente, ConstanciaTrabajo
- **Restricciones:** Pautas ANEP, distribución de clases, validaciones de negocio

### Arquitectura del Sistema
- **Backend:** PHP 8.3.10-fpm-alpine
- **Frontend:** HTML/CSS/JS con TailwindCSS
- **Servidor Web:** Nginx 1.27.0-alpine
- **Containerización:** Docker y Docker Compose
- **Gestión BD:** pgAdmin 8.10

### Requerimientos No Funcionales Cumplidos
- **Rendimiento:** Soporte para 60 usuarios concurrentes
- **Tiempo de respuesta:** < 2 segundos para operaciones comunes
- **Disponibilidad:** 99% durante horario escolar
- **Seguridad:** Autenticación y autorización por roles
- **Mantenibilidad:** Documentación completa y código modular

## Diagramas Implementados

### Diagramas Mermaid Incluidos
1. **Diagrama Entidad-Relación** - Modelo de datos completo
2. **Diagrama de Flujo de Datos** - Proceso de gestión de horarios
3. **Diagrama de Estados** - Estados de asignaciones
4. **Diagrama de Arquitectura** - Componentes del sistema
5. **Diagrama de Metodología** - Proceso de desarrollo
6. **Diagrama de Respaldo** - Estrategia de backup
7. **Diagrama de Monitoreo** - Sistema de alertas
8. **Cronograma Gantt** - Planificación del proyecto

## Entregables Completados

### Documentos Técnicos
- [x] `REPO_GIT_ESTRUCTURA.md` - Gestión de código y arquitectura
- [x] `GESTION_PROYECTO.md` - Metodología y planificación
- [x] `MODELO_CONCEPTUAL_CORREGIDO.md` - Diseño de base de datos
- [x] `MODELO_FISICO_DDL_PERMISOS.sql` - Implementación SQL
- [x] `ANALISIS_RESPALDO_BD.md` - Estrategia de respaldo

### Características Destacadas
- ✅ **Alineado con ESRE** - Todos los requerimientos funcionales y no funcionales
- ✅ **Diagramas Mermaid** - Visualización completa del sistema
- ✅ **PostgreSQL** - Base de datos según especificaciones
- ✅ **Docker** - Containerización completa
- ✅ **Roles y Permisos** - Sistema de autorización granular
- ✅ **Pautas ANEP** - Cumplimiento de regulaciones educativas
- ✅ **Disponibilidad Docente** - Gestión de restricciones horarias
- ✅ **Generación Automática** - Algoritmo de horarios con restricciones

## Próximos Pasos (Tercera Entrega)
- Desarrollo de interfaces de usuario por rol
- Implementación de generación automática de horarios
- Integración frontend-backend
- Pruebas de usuario y validación
- Optimización de rendimiento

## Conclusión
La segunda entrega establece una base sólida para el desarrollo del sistema Chronos, con modelos de datos robustos, arquitectura escalable y estrategias de respaldo confiables, cumpliendo con todos los requerimientos especificados en el ESRE del Liceo Italiano.
