# Chronos

Sistema de gestión de horarios escolares desarrollado con PHP, TailwindCSS v4 y PostgreSQL.

## 🚀 Inicio rápido

### Requisitos
- [Docker](https://docs.docker.com/get-docker) 20.10+
- [Node.js](https://nodejs.org/) 18+
- [Git](https://git-scm.com/downloads)

### Instalación
```bash
git clone https://github.com/bocho8/chronos.git
cd chronos
docker compose up -d --build
```

El sitio estará disponible en **http://localhost**

## 🛠️ Desarrollo

### TailwindCSS
```bash
# Desarrollo (modo watch)
npm run tw:dev

# Producción (minificado)
npm run tw:build
```

### Base de datos
- **Host:** localhost:5432
- **Base de datos:** chronos_db  
- **Usuario:** chronos_user
- **Contraseña:** chronos_pass

Recomendamos [DBeaver](https://dbeaver.io/download) para administrar la base de datos.

## 📋 Comandos útiles

```bash
# Ver logs
docker compose logs -f

# Detener servicios
docker compose stop

# Reiniciar (mantiene datos)
docker compose restart

# Limpiar todo (⚠️ borra la base de datos)
docker compose down -v
```

## 📚 Documentación

- [Esquema de base de datos](docs/database/database_schema.sql)

## 📄 Licencia

Este proyecto está licenciado bajo la **Business Source License (BSL) 1.1**.

### Uso permitido
- ✅ **Uso no productivo**: Puedes ver, usar, modificar y distribuir el código para fines de desarrollo, pruebas y educación
- ✅ **Uso en producción**: Requiere una licencia comercial de Agustín Roizen

### Conversión a código abierto
La licencia se convertirá automáticamente a **Apache License, Version 2.0** tres años después de la primera distribución pública de cada versión, proporcionando un camino claro hacia la disponibilidad completa de código abierto.

Para más información, consulta el archivo [LICENSE](LICENSE) en la raíz del proyecto.