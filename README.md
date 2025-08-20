# chronos
## Requisitos previos

| Herramienta | ¿Dónde conseguirla? |
|-------------|---------------------|
| **Docker** 20.10+ | https://docs.docker.com/get-docker |
| **Git**            | https://git-scm.com/downloads |
| **DBeaver** (opcional, cliente DB) | https://dbeaver.io/download |

## 1. Levantar el proyecto

```bash
# 1. Clonar
git clone https://github.com/bocho8/chronos.git
cd chronos

# 2. Construir y ejecutar
docker compose up -d --build
```

| Servicio   | URL / puerto            | Credenciales                                               |
| ---------- | ----------------------- | ---------------------------------------------------------- |
| Sitio web  | <http://localhost>      | —                                                          |
| PostgreSQL | localhost:5432          | user: `chronos_user` pass: `chronos_pass` db: `chronos_db` |

👉 Para administrar la base de datos, usar **DBeaver** y crear una conexión con estos datos:
- Host: `localhost`
- Puerto: `5432`
- Base de datos: `chronos_db`
- Usuario: `chronos_user`
- Contraseña: `chronos_pass`

## 2. Scripts de TailwindCSS
```bash
# Modo watch (desarrollo)
docker compose exec node npm run tw:dev

# Build minificado (producción)
docker compose exec node npm run tw:build
```

## 3. Log, para y limpiar
```bash
# Ver logs en tiempo real
docker compose logs -f

# Detener
docker compose stop

# Parar y borrar volúmenes (⚠️ pierdes la DB)
docker compose down -v
```
