# Chronos - Documentación para Desarrolladores

## 📋 Tabla de Contenidos

1. [Resumen del Proyecto](#resumen-del-proyecto)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Configuración de Desarrollo](#configuración-de-desarrollo)
4. [Base de Datos](#base-de-datos)
5. [Autenticación](#autenticación)
6. [Estructura del Código](#estructura-del-código)

---

## 🎯 Resumen del Proyecto

**Chronos** es un sistema de gestión de horarios escolares desarrollado para la Escuela Italiana de Uruguay. Gestiona horarios de docentes, grupos de estudiantes y materias con soporte multiidioma.

### Características Principales

- **Autenticación Multi-rol**: Admin, Director, Coordinador, Docente, Padre
- **Identificación por Cédula**: Usuarios identificados por cédula uruguaya (7-8 dígitos)
- **Gestión de Horarios**: Con pautas ANEP
- **Soporte Multiidioma**: Español, Italiano, Inglés
- **Diseño Responsivo**: TailwindCSS v4

---

## 🛠 Stack Tecnológico

- **Backend**: PHP 8.1+, PostgreSQL 16, PDO
- **Frontend**: HTML5, TailwindCSS v4, JavaScript ES6+
- **Infraestructura**: Docker, Nginx, Node.js 20

---

## 🚀 Configuración de Desarrollo

### Prerrequisitos
- Docker 20.10+
- Node.js 18+
- Git

### Inicio Rápido

```bash
# Clonar e iniciar
git clone https://github.com/bocho8/chronos.git
cd chronos
docker compose up -d --build

# Acceder
# App: http://localhost
# BD: localhost:5432
```

### Comandos Útiles

```bash
# Desarrollo CSS
npm run tw:dev          # Watch mode
npm run tw:build        # Producción

# Docker
docker compose logs -f  # Ver logs
docker compose restart # Reiniciar
docker compose down -v # Limpiar todo (⚠️ elimina BD)
```

### Login por Defecto
- **Cédula**: 12345678
- **Contraseña**: admin123
- **Rol**: ADMIN

---

## 🗄 Base de Datos

### Conexión
- **Host**: postgres (Docker) / localhost (externo)
- **Puerto**: 5432
- **Base de Datos**: chronos_db
- **Usuario**: chronos_user
- **Contraseña**: chronos_pass

### Tablas Principales
- **`usuario`**: Cuentas con identificación por cédula
- **`rol`**: Roles del sistema
- **`docente`**: Perfiles de docentes
- **`grupo`**: Grupos de estudiantes
- **`materia`**: Materias con pautas ANEP
- **`horario`**: Asignaciones de horarios
- **`disponibilidad`**: Disponibilidad de docentes

### Acceso Rápido
```sql
-- Conectar
docker exec -it chronos-postgres psql -U chronos_user -d chronos_db

-- Ver usuarios
SELECT cedula, nombre, apellido FROM usuario;
```

---

## 🔐 Autenticación

### Flujo de Login
1. Usuario envía cédula + contraseña + rol
2. `Auth::authenticate()` valida credenciales
3. Sesión almacena datos de usuario
4. Redirigir a dashboard del rol

### Roles Disponibles
- **ADMIN**: Acceso completo
- **DIRECTOR**: Supervisión escolar
- **COORDINADOR**: Gestión de horarios
- **DOCENTE**: Acceso limitado a su horario
- **PADRE**: Solo lectura de información del estudiante

### Validación de Cédula
- Formato: 7-8 dígitos numéricos
- Patrón: `/^\d{7,8}$/`
- Debe ser única en el sistema

---

## 📁 Estructura del Código

### Archivos Clave
- **`src/models/Auth.php`**: Lógica de autenticación
- **`src/helpers/AuthHelper.php`**: Utilidades de sesión
- **`src/config/database.php`**: Configuración de BD
- **`src/views/login.php`**: Interfaz de login
- **`src/views/admin/index.php`**: Panel de administración

### Patrones de Código
- **MVC**: Modelos, Vistas, Controladores
- **PDO**: Para consultas de base de datos
- **Sesiones seguras**: Con timeout de 30 minutos
- **Validación**: Cliente y servidor

### Estándares
- **PHP**: PascalCase para clases, camelCase para métodos
- **HTML**: Semántico con ARIA labels
- **CSS**: TailwindCSS utilities
- **JavaScript**: ES6+ con comentarios JSDoc

---

## 🐛 Solución de Problemas

### Problemas Comunes

| Problema | Solución |
|----------|----------|
| CSS no carga | `npm run tw:build` |
| BD no conecta | `docker compose ps` |
| Sesión no funciona | Verificar `src/config/session.php` |
| Permisos | `chmod -R 755 public/` |

### Debug
```php
// Habilitar debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---