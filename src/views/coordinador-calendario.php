<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calendario Semanal | SIM</title>
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
      align-items: center;
    }
    .calendario-card {
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 2px 16px rgba(34,57,122,0.08);
      padding: 32px 32px 32px 32px;
      width: 600px;
      margin-top: 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .calendario-card h2 {
      color: #22397A;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 24px;
      text-align: center;
    }
    .calendario-tabla-container {
      width: 100%;
      display: flex;
      justify-content: center;
    }
    .calendario-tabla {
      border-radius: 12px;
      overflow: hidden;
      border: 2px solid #22397A;
      background: #fff;
      min-width: 340px;
      font-size: 1rem;
      margin-left: auto;
    }
    .calendario-tabla th, .calendario-tabla td {
      border: 1.5px solid #22397A;
      padding: 8px 16px;
      text-align: center;
      min-width: 70px;
      background: #fff;
      color: #22397A;
    }
    .calendario-tabla th {
      background: #22397A;
      color: #fff;
      font-weight: 700;
      font-size: 1rem;
    }
    .calendario-tabla .almuerzo {
      background: #E5E5E5;
      font-weight: 700;
      color: #22397A;
    }
    .calendario-editar-btn {
      background: #22397A;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 14px 0;
      font-size: 1.1rem;
      font-weight: 700;
      width: 220px;
      margin: 32px auto 0 auto;
      cursor: pointer;
      transition: background 0.2s;
      display: block;
    }
    .calendario-editar-btn:hover {
      background: #1a2c5a;
    }
    @media (max-width: 900px) {
      .main-container { flex-direction: column; }
      .sidebar { width: 100%; min-height: unset; padding-top: 0; border-right: none; }
      .content { padding: 24px 8px; }
      .calendario-card { width: 98vw; padding: 16px 4px; }
      .calendario-tabla { min-width: unset; font-size: 0.95rem; }
      .calendario-editar-btn { width: 100%; }
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
        <li>Docentes</li>
        <li class="active">Calendario</li>
      </ul>
    </nav>
    <main class="content">
      <section class="calendario-card">
        <h2>Calendario semanal</h2>
        <div class="calendario-tabla-container">
          <table class="calendario-tabla">
            <thead>
              <tr>
                <th></th>
                <th>Lunes</th>
                <th>Martes</th>
                <th>Miércoles</th>
                <th>Jueves</th>
                <th>Viernes</th>
              </tr>
            </thead>
            <tbody>
              <tr><td>1a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
              <tr><td>2a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
              <tr><td>3a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
              <tr><td>4a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
              <tr><td>5a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
              <tr><td>6a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
              <tr><td colspan="6" class="almuerzo">ALMUERZO</td></tr>
              <tr><td>7a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
              <tr><td>8a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
              <tr><td>9a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
              <tr><td>10a</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td><td>Text</td></tr>
            </tbody>
          </table>
        </div>
        <button class="calendario-editar-btn">Editar</button>
      </section>
    </main>
  </div>
</body>
</html> 