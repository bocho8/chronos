<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Coordinador | SIM</title>
  <link rel="stylesheet" href="../../public/css/estilos.css">
  <script src="../../public/js/menu.js" defer></script>
  <style>
    .main-container {
      display: flex;
      min-height: calc(100vh - 70px);
      background: #fff;
    }
    .sidebar {
      background: #F3F3F3;
      width: 220px;
      min-height: calc(100vh - 70px);
      padding-top: 32px;
      box-sizing: border-box;
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
      border-right: 1.5px solid #E5E5E5;
    }
    .sidebar ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .sidebar li {
      padding: 16px 32px;
      color: #22397A;
      font-weight: 600;
      font-size: 1.08rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      border-radius: 8px 0 0 8px;
      margin-bottom: 6px;
      transition: background 0.2s;
    }
    .sidebar li.active, .sidebar li:hover {
      background: #D9D9D9;
      border-left: 5px solid #22397A;
    }
    .content {
      flex: 1;
      padding: 48px 40px 32px 40px;
      background: #fff;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    .panel-card {
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 2px 16px rgba(34,57,122,0.08);
      padding: 32px 32px 32px 32px;
      width: 700px;
      margin-top: 24px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    .panel-card h2 {
      color: #22397A;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 18px;
      text-align: left;
    }
    .panel-card h3 {
      color: #22397A;
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 18px;
      margin-top: 32px;
      text-align: left;
    }
    .coordinadores-lista {
      width: 100%;
      margin-bottom: 32px;
    }
    .coordinador-item {
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 18px 0;
      border-bottom: 1.5px solid #E5E5E5;
    }
    .coordinador-item:last-child { border-bottom: none; }
    .coordinador-avatar {
      width: 54px;
      height: 54px;
      border-radius: 50%;
      background: #D9D9D9;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.2rem;
      color: #22397A;
      font-weight: 700;
    }
    .coordinador-info {
      flex: 1;
    }
    .coordinador-nombre {
      font-weight: 700;
      color: #22397A;
      font-size: 1.15rem;
    }
    .coordinador-exp {
      font-size: 0.97rem;
      color: #222;
      margin-top: 2px;
    }
    .coordinador-btns {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .coordinador-btns .btn {
      background: #fff;
      color: #22397A;
      border: 1.5px solid #22397A;
      border-radius: 8px;
      padding: 8px 18px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
    }
    .coordinador-btns .btn:hover {
      background: #22397A;
      color: #fff;
    }
    .panel-horarios {
      width: 100%;
      margin-top: 32px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    .panel-horarios h3 {
      margin-bottom: 18px;
      margin-top: 0;
    }
    .panel-horarios-btns {
      display: flex;
      gap: 18px;
    }
    .panel-horarios-btns .btn {
      background: #22397A;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 14px 32px;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
    }
    .panel-horarios-btns .btn:hover {
      background: #1a2c5a;
    }
    @media (max-width: 900px) {
      .main-container { flex-direction: column; }
      .sidebar { width: 100%; min-height: unset; padding-top: 0; border-right: none; }
      .content { padding: 24px 8px; }
      .panel-card { width: 98vw; padding: 16px 4px; }
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
    <div class="header-title">Bienvenido (COORDINADOR)</div>
    <div class="menu-icon">
      <span></span><span></span><span></span>
    </div>
  </header>
  <div class="main-container">
    <nav class="sidebar">
      <ul>
        <li class="active">Inicio</li>
        <li>Docentes</li>
        <li>Calendario</li>
      </ul>
    </nav>
    <main class="content">
      <section class="panel-card">
        <h2>Bienvenido al Panel Coordinador</h2>
        <div style="color:#444;margin-bottom:32px;">Gestione docentes y horarios.</div>
        <div class="coordinadores-lista">
          <div class="coordinador-item">
            <div class="coordinador-avatar">👤</div>
            <div class="coordinador-info">
              <div class="coordinador-nombre">Coordinador Alberto De Mattos</div>
              <div class="coordinador-exp">Experiencia: 15 años</div>
            </div>
            <div class="coordinador-btns">
              <button class="btn">Cerrar Sesión</button>
              <button class="btn">Perfil</button>
            </div>
          </div>
          <div class="coordinador-item">
            <div class="coordinador-avatar">👤</div>
            <div class="coordinador-info">
              <div class="coordinador-nombre">Coordinadora Patricia Molinari</div>
              <div class="coordinador-exp">Experiencia: 12 años</div>
            </div>
            <div class="coordinador-btns">
              <button class="btn">Cerrar Sesión</button>
              <button class="btn">Perfil</button>
            </div>
          </div>
        </div>
        <div class="panel-horarios">
          <h3>Panel de Coordinación de Horarios</h3>
          <div style="color:#444;margin-bottom:18px;">Gestione y asigne horarios para los docentes y grupos.</div>
          <div class="panel-horarios-btns">
            <button class="btn">Asignar Manual</button>
            <button class="btn">Generar Automático</button>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html> 