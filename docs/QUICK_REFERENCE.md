# Chronos - Guía de Referencia Rápida

> **Guía de Referencia Rápida** para desarrolladores que trabajan en el proyecto Chronos

## 🚀 Inicio Rápido

```bash
# Clonar e iniciar
git clone https://github.com/bocho8/chronos.git
cd chronos
docker compose up -d --build

# Acceder
# App: http://localhost
# BD: localhost:5432
```

## 🔑 Login por Defecto

- **Cédula**: 12345678
- **Contraseña**: admin123
- **Rol**: ADMIN

## 📁 Archivos Clave

| Archivo | Propósito |
|---------|-----------|
| `src/models/Auth.php` | Lógica de autenticación |
| `src/helpers/AuthHelper.php` | Gestión de sesiones |
| `src/config/database.php` | Configuración de base de datos |
| `src/views/login.php` | Interfaz de login |
| `docs/database/database_schema.sql` | Estructura de base de datos |

## 🛠 Comandos Comunes

```bash
# Desarrollo
npm run tw:dev          # Observar cambios de CSS
npm run tw:build        # Construir CSS para producción

# Docker
docker compose logs -f  # Ver logs
docker compose restart # Reiniciar servicios
docker compose down -v # Reinicio limpio (⚠️ elimina BD)
```

## 🔐 Flujo de Autenticación

1. Usuario envía cédula + contraseña + rol
2. `Auth::authenticate()` valida credenciales
3. Sesión almacena datos de usuario
4. Redirigir a dashboard específico del rol

## 🗄 Acceso Rápido a Base de Datos

```sql
-- Conectar a base de datos
docker exec -it chronos-postgres psql -U chronos_user -d chronos_db

-- Ver usuarios
SELECT cedula, nombre, apellido, email FROM usuario;

-- Ver roles
SELECT * FROM rol;
```

## 🎨 Desarrollo de CSS

- **Fuente**: `src/tailwind.css`
- **Salida**: `public/css/styles.css`
- **Observar**: `npm run tw:dev`

## 🐛 Problemas Comunes

| Problema | Solución |
|----------|----------|
| CSS no carga | Ejecutar `npm run tw:build` |
| Falló conexión a BD | Verificar `docker compose ps` |
| Sesión no funciona | Verificar `src/config/session.php` |
| Errores de permisos | `chmod -R 755 public/` |

## 📞 ¿Necesitas Ayuda?

- **Documentación Completa**: [DEVELOPER_DOCUMENTATION.md](DEVELOPER_DOCUMENTATION.md)
- **Estructura del Proyecto**: [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md)
- **Esquema de Base de Datos**: [database/database_schema.sql](database/database_schema.sql)

---

**Última Actualización**: 2025-01-27