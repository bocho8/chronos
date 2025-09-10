# Chronos - Referencia Rápida

## 🚀 Inicio Rápido

```bash
git clone https://github.com/bocho8/chronos.git
cd chronos
docker compose up -d --build
# App: http://localhost
```

## 🔑 Login por Defecto
- **Cédula**: 12345678
- **Contraseña**: admin123
- **Rol**: ADMIN

## 🛠 Comandos

```bash
npm run tw:dev          # CSS watch
npm run tw:build        # CSS build
docker compose logs -f  # Ver logs
docker compose restart # Reiniciar
```

## 📁 Archivos Clave
- `src/models/Auth.php` - Autenticación
- `src/helpers/AuthHelper.php` - Sesiones
- `src/config/database.php` - BD
- `src/views/login.php` - Login

## 🗄 Base de Datos
```sql
docker exec -it chronos-postgres psql -U chronos_user -d chronos_db
SELECT cedula, nombre FROM usuario;
```

## 🐛 Problemas
- CSS no carga → `npm run tw:build`
- BD no conecta → `docker compose ps`
- Permisos → `chmod -R 755 public/`

---