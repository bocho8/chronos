<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Docente | SIM</title>
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
    .docente-card {
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 2px 16px rgba(34,57,122,0.08);
      padding: 32px 32px 32px 32px;
      width: 600px;
      margin-top: 24px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    .docente-perfil {
      display: flex;
      align-items: center;
      gap: 18px;
      margin-bottom: 24px;
    }
    .docente-avatar {
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
    .docente-info {
      flex: 1;
    }
    .docente-nombre {
      font-weight: 700;
      color: #22397A;
      font-size: 1.15rem;
    }
    .docente-exp {
      font-size: 0.97rem;
      color: #222;
      margin-top: 2px;
    }
    .docente-btns {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .docente-btns .btn {
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
    .docente-btns .btn:hover {
      background: #22397A;
      color: #fff;
    }
    .docente-horas {
      display: flex;
      gap: 32px;
      margin-bottom: 24px;
    }
    .docente-horas-box {
      background: #E5E5E5;
      border-radius: 12px;
      padding: 18px 32px;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-width: 120px;
    }
    .docente-horas-label {
      color: #22397A;
      font-size: 1.05rem;
      font-weight: 600;
      margin-bottom: 8px;
    }
    .docente-horas-valor {
      color: #22397A;
      font-size: 1.5rem;
      font-weight: 700;
    }
    .docente-actualizar {
      margin-top: 12px;
      width: 220px;
      background: #22397A;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 14px 0;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
      align-self: flex-start;
    }
    .docente-actualizar:hover {
      background: #1a2c5a;
    }
    @media (max-width: 900px) {
      .main-container { flex-direction: column; }
      .sidebar { width: 100%; min-height: unset; padding-top: 0; border-right: none; }
      .content { padding: 24px 8px; }
      .docente-card { width: 98vw; padding: 16px 4px; }
      .docente-horas { flex-direction: column; gap: 12px; }
      .docente-actualizar { width: 100%; }
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
    <div class="header-title">Bienvenido (Docente)</div>
    <div class="menu-icon">
      <span></span><span></span><span></span>
    </div>
  </header>
  <div class="main-container">
    <nav class="sidebar">
      <ul>
        <li class="active">Inicio</li>
        <li>Mis Horarios</li>
      </ul>
    </nav>
    <main class="content">
      <section class="docente-card">
        <div class="docente-perfil">
          <div class="docente-avatar">👤</div>
          <div class="docente-info">
            <div class="docente-nombre">Profesor Marcos Mendez</div>
            <div class="docente-exp">Experiencia: 10 años en educación</div>
          </div>
          <div class="docente-btns">
            <button class="btn">Cerrar Sesión</button>
            <button class="btn">Perfil</button>
          </div>
        </div>
        <div style="font-size:1.2rem;font-weight:700;color:#22397A;margin-bottom:18px;">Horas asignadas esta semana</div>
        <div class="docente-horas">
          <div class="docente-horas-box">
            <div class="docente-horas-label">Horas Asignadas</div>
            <div class="docente-horas-valor">15</div>
          </div>
          <div class="docente-horas-box">
            <div class="docente-horas-label">Horas Totales</div>
            <div class="docente-horas-valor">20</div>
          </div>
        </div>
        <div style="font-size:1.1rem;font-weight:600;color:#22397A;margin-bottom:8px;margin-top:18px;">Actualizar Disponibilidad</div>
        <div style="color:#444;margin-bottom:18px;">Realiza cambios en tus franjas disponibles.</div>
        <button class="docente-actualizar">Actualizar</button>
      </section>
    </main>
  </div>
</body>
</html> 