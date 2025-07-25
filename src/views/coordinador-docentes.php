<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registros de Docentes | SIM</title>
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
    .docentes-card {
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 2px 16px rgba(34,57,122,0.08);
      padding: 32px 32px 16px 32px;
      width: 480px;
      margin-top: 24px;
    }
    .docentes-card h2 {
      color: #22397A;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 24px;
      text-align: left;
    }
    .docentes-btns {
      display: flex;
      gap: 16px;
      margin-bottom: 18px;
    }
    .docentes-btns .btn {
      background: #22397A;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 10px 24px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
    }
    .docentes-btns .btn:hover {
      background: #1a2c5a;
    }
    .docentes-lista {
      margin-top: 8px;
    }
    .docente-item {
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 14px 0;
      border-bottom: 1.5px solid #E5E5E5;
    }
    .docente-item:last-child { border-bottom: none; }
    .docente-avatar {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: #D9D9D9;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: #22397A;
      font-weight: 700;
    }
    .docente-info {
      flex: 1;
    }
    .docente-nombre {
      font-weight: 700;
      color: #22397A;
      font-size: 1.08rem;
    }
    .docente-materia {
      font-size: 0.97rem;
      color: #666;
      margin-top: 2px;
    }
    @media (max-width: 900px) {
      .main-container { flex-direction: column; }
      .sidebar { width: 100%; min-height: unset; padding-top: 0; border-right: none; }
      .content { padding: 24px 8px; }
      .docentes-card { width: 98vw; padding: 16px 4px; }
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
        <li>Inicio</li>
        <li class="active">Docentes</li>
        <li>Calendario</li>
      </ul>
    </nav>
    <main class="content">
      <section class="docentes-card">
        <h2>Registros de Docentes</h2>
        <div class="docentes-btns">
          <button class="btn">Eliminar Seleccionados</button>
          <button class="btn">Agregar Docente</button>
        </div>
        <div class="docentes-lista">
          <div class="docente-item">
            <div class="docente-avatar">👤</div>
            <div class="docente-info">
              <div class="docente-nombre">Juan Pérez</div>
              <div class="docente-materia">Matemáticas</div>
            </div>
          </div>
          <div class="docente-item">
            <div class="docente-avatar">👤</div>
            <div class="docente-info">
              <div class="docente-nombre">Ana Gómez</div>
              <div class="docente-materia">Historia</div>
            </div>
          </div>
          <div class="docente-item">
            <div class="docente-avatar">👤</div>
            <div class="docente-info">
              <div class="docente-nombre">Luis Rodríguez</div>
              <div class="docente-materia">Biología</div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html> 