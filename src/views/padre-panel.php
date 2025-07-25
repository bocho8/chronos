<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Seguimiento | SIM</title>
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
    .padre-card {
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 2px 16px rgba(34,57,122,0.08);
      padding: 32px 32px 32px 32px;
      width: 900px;
      margin-top: 24px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    .padre-card h2 {
      color: #22397A;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 18px;
      text-align: left;
    }
    .padre-seccion {
      margin-bottom: 32px;
      width: 100%;
    }
    .padre-mini-calendario {
      display: flex;
      align-items: flex-start;
      gap: 32px;
      margin-bottom: 32px;
    }
    .mini-calendario {
      border-radius: 12px;
      overflow: hidden;
      border: 2px solid #22397A;
      background: #fff;
      min-width: 260px;
      font-size: 0.95rem;
    }
    .mini-calendario th, .mini-calendario td {
      border: 1.5px solid #22397A;
      padding: 4px 8px;
      text-align: center;
      min-width: 40px;
      background: #fff;
      color: #22397A;
    }
    .mini-calendario th {
      background: #22397A;
      color: #fff;
      font-weight: 700;
      font-size: 0.95rem;
    }
    .mini-calendario .almuerzo {
      background: #E5E5E5;
      font-weight: 700;
      color: #22397A;
    }
    .padre-evaluaciones {
      width: 100%;
    }
    .padre-evaluacion-item {
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 12px 0;
      border-bottom: 1.5px solid #E5E5E5;
    }
    .padre-evaluacion-item:last-child { border-bottom: none; }
    .padre-eval-icon {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #D9D9D9;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      color: #22397A;
      font-weight: 700;
    }
    .padre-eval-materia {
      font-weight: 700;
      color: #22397A;
      font-size: 1.08rem;
    }
    .padre-eval-tipo {
      font-size: 0.97rem;
      color: #666;
      margin-top: 2px;
    }
    .padre-eval-fecha {
      margin-left: auto;
      color: #22397A;
      font-size: 1.05rem;
      font-weight: 600;
    }
    @media (max-width: 1100px) {
      .padre-card { width: 98vw; padding: 16px 4px; }
      .padre-mini-calendario { flex-direction: column; gap: 12px; }
    }
    @media (max-width: 900px) {
      .main-container { flex-direction: column; }
      .sidebar { width: 100%; min-height: unset; padding-top: 0; border-right: none; }
      .content { padding: 24px 8px; }
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
    <div class="header-title">Bienvenido (Padre/Madre)</div>
    <div class="menu-icon">
      <span></span><span></span><span></span>
    </div>
  </header>
  <div class="main-container">
    <nav class="sidebar">
      <ul>
        <li class="active">Horario de mi hijo</li>
        <li>Calificaciones</li>
      </ul>
    </nav>
    <main class="content">
      <section class="padre-card">
        <h2>Bienvenido al Panel de Seguimiento</h2>
        <div class="padre-seccion padre-mini-calendario">
          <div>
            <div style="font-size:1.2rem;font-weight:700;color:#22397A;margin-bottom:12px;">Calendario semanal</div>
            <table class="mini-calendario">
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
        </div>
        <div class="padre-seccion">
          <div style="font-size:1.2rem;font-weight:700;color:#22397A;margin-bottom:12px;">Próximas Evaluaciones</div>
          <div class="padre-evaluaciones">
            <div class="padre-evaluacion-item">
              <div class="padre-eval-icon">17</div>
              <div>
                <div class="padre-eval-materia">Matemáticas</div>
                <div class="padre-eval-tipo">Examen oral</div>
              </div>
              <div class="padre-eval-fecha">Fecha: 25/10/2023</div>
            </div>
            <div class="padre-evaluacion-item">
              <div class="padre-eval-icon">17</div>
              <div>
                <div class="padre-eval-materia">Programación</div>
                <div class="padre-eval-tipo">Escrito</div>
              </div>
              <div class="padre-eval-fecha">Fecha: 27/10/2023</div>
            </div>
            <div class="padre-evaluacion-item">
              <div class="padre-eval-icon">17</div>
              <div>
                <div class="padre-eval-materia">Sistemas operativos</div>
                <div class="padre-eval-tipo">Presentación de proyecto</div>
              </div>
              <div class="padre-eval-fecha">Fecha: 30/10/2023</div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html> 