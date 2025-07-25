<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio de Sesión | SIM</title>
  <link rel="stylesheet" href="../../public/css/estilos.css">
  <style>
    /* Ajuste visual para el logo placeholder */
    .logo-placeholder {
      width: 48px;
      height: 48px;
      background: #fff;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: #22397A;
      font-size: 1.1rem;
      border: 2px solid #22397A;
      box-sizing: border-box;
    }
    .header-logo-text {
      font-size: 1.1rem;
      font-weight: bold;
      line-height: 1.1;
      letter-spacing: 0.01em;
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="header-logo">
      <div class="logo-placeholder">SIM</div>
      <div class="header-logo-text">
        Scuola<br>Italiana di<br>Montevideo
      </div>
    </div>
    <div class="header-title">Sistema de Horarios SIM</div>
    <div class="menu-icon" style="visibility:hidden;">
      <span></span><span></span><span></span>
    </div>
  </header>
  <div class="login-bg">
    <section class="card login-card">
      <h2>Inicio de Sesión</h2>
      <form autocomplete="off">
        <label for="ci">C.I</label>
        <input type="text" id="ci" placeholder="C.I" autocomplete="off">
        <label for="password">Contraseña</label>
        <input type="password" id="password" placeholder="Contraseña" autocomplete="off">
        <button type="submit">Iniciar Sesión</button>
        <div class="flex" style="justify-content:space-between;align-items:center;">
          <a class="link" href="#">¿Olvidaste tu contraseña?</a>
          <select>
            <option>Roles</option>
            <option>Admin</option>
            <option>Coordinador</option>
            <option>Docente</option>
            <option>Padre/Madre</option>
            <option>Director</option>
          </select>
        </div>
      </form>
    </section>
  </div>
</body>
</html> 