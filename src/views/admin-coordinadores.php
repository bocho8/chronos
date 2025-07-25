<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registros de Coordinadores | SIM</title>
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
    .coordinadores-card {
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 2px 16px rgba(34,57,122,0.08);
      padding: 32px 32px 16px 32px;
      width: 480px;
      margin-top: 24px;
    }
    .coordinadores-card h2 {
      color: #22397A;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 24px;
      text-align: left;
    }
    .coordinadores-btns {
      display: flex;
      gap: 16px;
      margin-bottom: 18px;
    }
    .coordinadores-btns .btn {
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
    .coordinadores-btns .btn:hover {
      background: #1a2c5a;
    }
    .coordinadores-lista {
      margin-top: 8px;
    }
    .coordinador-item {
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 14px 0;
      border-bottom: 1.5px solid #E5E5E5;
    }
    .coordinador-item:last-child { border-bottom: none; }
    .coordinador-avatar {
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
    .coordinador-info {
      flex: 1;
    }
    .coordinador-nombre {
      font-weight: 700;
      color: #22397A;
      font-size: 1.08rem;
    }
    .coordinador-rol {
      font-size: 0.97rem;
      color: #666;
      margin-top: 2px;
    }
    @media (max-width: 900px) {
      .main-container { flex-direction: column; }
      .sidebar { width: 100%; min-height: unset; padding-top: 0; border-right: none; }
      .content { padding: 24px 8px; }
      .coordinadores-card { width: 98vw; padding: 16px 4px; }
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
    <div class="header-title">Bienvenido (ADMIN)</div>
    <div class="menu-icon">
      <span></span><span></span><span></span>
    </div>
  </header>
  <div class="main-container">
    <nav class="sidebar">
      <ul>
        <li>Docentes</li>
        <li class="active">Coordinadores</li>
        <li>Materias</li>
        <li>Horarios</li>
      </ul>
    </nav>
    <main class="content">
      <section class="coordinadores-card">
        <h2>Registros de Coordinadores</h2>
        <div class="coordinadores-btns">
          <button class="btn">Eliminar Seleccionados</button>
          <button class="btn">Agregar Coordinador</button>
        </div>
        <div class="coordinadores-lista">
          <div class="coordinador-item">
            <div class="coordinador-avatar">👤</div>
            <div class="coordinador-info">
              <div class="coordinador-nombre">Alberto De Mattos</div>
              <div class="coordinador-rol">Coordinador</div>
            </div>
          </div>
          <div class="coordinador-item">
            <div class="coordinador-avatar">👤</div>
            <div class="coordinador-info">
              <div class="coordinador-nombre">Patricia Molinari</div>
              <div class="coordinador-rol">Coordinadora</div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html> 