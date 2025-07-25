<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calificaciones de Estudiante | SIM</title>
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
    .calificaciones-card {
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
    .calificaciones-card h2 {
      color: #22397A;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 18px;
      text-align: left;
    }
    .calificaciones-perfil {
      display: flex;
      align-items: center;
      gap: 18px;
      margin-bottom: 24px;
    }
    .calificaciones-avatar {
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
    .calificaciones-info {
      flex: 1;
    }
    .calificaciones-nombre {
      font-weight: 700;
      color: #22397A;
      font-size: 1.15rem;
    }
    .calificaciones-id {
      font-size: 1.05rem;
      color: #22397A;
      margin-top: 2px;
    }
    .calificaciones-desc {
      font-size: 0.97rem;
      color: #222;
      margin-top: 2px;
    }
    .calificaciones-lista {
      width: 100%;
      margin-bottom: 24px;
      display: flex;
      gap: 32px;
    }
    .calificaciones-materias {
      flex: 1;
      background: #F8F8F8;
      border-radius: 12px;
      padding: 18px 24px;
      min-width: 220px;
    }
    .calificaciones-materia-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 0;
      border-bottom: 1.5px solid #E5E5E5;
    }
    .calificaciones-materia-item:last-child { border-bottom: none; }
    .calificaciones-materia-nombre {
      font-weight: 700;
      color: #22397A;
      font-size: 1.08rem;
    }
    .calificaciones-materia-cant {
      margin-left: auto;
      color: #22397A;
      font-size: 1.05rem;
      font-weight: 600;
    }
    .calificaciones-resumen {
      flex: 2;
      background: #F8F8F8;
      border-radius: 12px;
      padding: 18px 24px;
      min-width: 320px;
      margin-left: 24px;
    }
    .calificaciones-resumen-titulo {
      font-size: 1.15rem;
      font-weight: 700;
      color: #22397A;
      margin-bottom: 12px;
    }
    .calificaciones-resumen-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 18px 32px;
    }
    .calificaciones-resumen-item {
      min-width: 120px;
      font-size: 1.05rem;
      color: #22397A;
      font-weight: 600;
      background: #fff;
      border-radius: 8px;
      padding: 10px 18px;
      box-shadow: 0 1px 4px rgba(34,57,122,0.05);
    }
    @media (max-width: 1100px) {
      .calificaciones-card { width: 98vw; padding: 16px 4px; }
      .calificaciones-lista { flex-direction: column; gap: 12px; }
      .calificaciones-resumen { margin-left: 0; }
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
    <div class="header-title">Bienvenido (Docente)</div>
    <div class="menu-icon">
      <span></span><span></span><span></span>
    </div>
  </header>
  <div class="main-container">
    <nav class="sidebar">
      <ul>
        <li>Horario de mi hijo</li>
        <li class="active">Calificaciones</li>
      </ul>
    </nav>
    <main class="content">
      <section class="calificaciones-card">
        <h2>Calificaciones de Estudiante</h2>
        <div style="color:#444;margin-bottom:18px;">Consulte las calificaciones más recientes.</div>
        <div class="calificaciones-perfil">
          <div class="calificaciones-avatar">👤</div>
          <div class="calificaciones-info">
            <div class="calificaciones-nombre">Bruno Marino</div>
            <div class="calificaciones-id">NIT: 345621</div>
            <div class="calificaciones-desc">Información general del alumno.</div>
          </div>
          <button class="btn" style="background:#22397A;color:#fff;padding:10px 24px;border-radius:8px;font-weight:700;">Ver Avanzado</button>
        </div>
        <div class="calificaciones-lista">
          <div class="calificaciones-materias">
            <div class="calificaciones-materia-item">
              <div class="calificaciones-materia-nombre">Matemáticas</div>
              <div class="calificaciones-materia-cant">1</div>
            </div>
            <div class="calificaciones-materia-item">
              <div class="calificaciones-materia-nombre">Programación</div>
              <div class="calificaciones-materia-cant">7</div>
            </div>
            <div class="calificaciones-materia-item">
              <div class="calificaciones-materia-nombre">Sistemas operativos</div>
              <div class="calificaciones-materia-cant">8</div>
            </div>
            <div class="calificaciones-materia-item">
              <div class="calificaciones-materia-nombre">Italiano</div>
              <div class="calificaciones-materia-cant">10</div>
            </div>
          </div>
          <div class="calificaciones-resumen">
            <div class="calificaciones-resumen-titulo">Resumen de Calificaciones</div>
            <div class="calificaciones-resumen-grid">
              <div class="calificaciones-resumen-item">Promedio General<br><b>88</b></div>
              <div class="calificaciones-resumen-item">Mejor Asignatura<br><b>Italiano</b></div>
              <div class="calificaciones-resumen-item">Asignaturas Aprobadas<br><b>3</b></div>
              <div class="calificaciones-resumen-item">Peor Asignatura<br><b>Matemáticas</b></div>
            </div>
          </div>
        </div>
        <button class="btn" style="background:#22397A;color:#fff;padding:10px 24px;border-radius:8px;font-weight:700;align-self:flex-end;">Descargar Informe</button>
      </section>
    </main>
  </div>
</body>
</html> 