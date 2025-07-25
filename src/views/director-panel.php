<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Director | SIM</title>
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
    .director-card {
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
    .director-card h2 {
      color: #22397A;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 18px;
      text-align: left;
    }
    .director-resumen {
      width: 100%;
      display: flex;
      gap: 32px;
      margin-bottom: 32px;
    }
    .director-resumen-item {
      flex: 1;
      background: #F8F8F8;
      border-radius: 12px;
      padding: 18px 24px;
      min-width: 120px;
      font-size: 1.05rem;
      color: #22397A;
      font-weight: 600;
      text-align: center;
    }
    .director-vencimientos {
      width: 100%;
      display: flex;
      gap: 32px;
      margin-bottom: 18px;
    }
    .director-vencimiento-item {
      flex: 1;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 1px 4px rgba(34,57,122,0.05);
      padding: 18px 24px;
      min-width: 120px;
      font-size: 1.05rem;
      color: #22397A;
      font-weight: 600;
      text-align: center;
      border: 1.5px solid #E5E5E5;
    }
    .director-vencimiento-item strong {
      display: block;
      font-size: 1.1rem;
      margin-bottom: 8px;
    }
    @media (max-width: 1100px) {
      .director-card { width: 98vw; padding: 16px 4px; }
      .director-resumen, .director-vencimientos { flex-direction: column; gap: 12px; }
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
    <div class="header-title">Bienvenido (Director)</div>
    <div class="menu-icon">
      <span></span><span></span><span></span>
    </div>
  </header>
  <div class="main-container">
    <nav class="sidebar">
      <ul>
        <li class="active">Inicio</li>
        <li>Horarios Semanales</li>
        <li>Asignación Docentes</li>
      </ul>
    </nav>
    <main class="content">
      <section class="director-card">
        <h2>DIRECTORA: Ana Maria Mendez</h2>
        <div style="margin-bottom:18px;">Julio 2023</div>
        <button class="btn" style="background:#22397A;color:#fff;padding:10px 24px;border-radius:8px;font-weight:700;margin-bottom:24px;">Firma Digital</button>
        <div class="director-resumen">
          <div class="director-resumen-item">Horarios por Aprobar<br><b>3</b></div>
          <div class="director-resumen-item">Docentes en Grupo<br><b>2</b></div>
          <div class="director-resumen-item">Próximos Vencimientos<br><b>3</b></div>
        </div>
        <div style="font-size:1.2rem;font-weight:700;color:#22397A;margin-bottom:12px;">Próximos Vencimientos</div>
        <div class="director-vencimientos">
          <div class="director-vencimiento-item">
            <strong>Certificado de Inscripción</strong>
            Vence en 10 días
          </div>
          <div class="director-vencimiento-item">
            <strong>Informe de Inspección</strong>
            Vence en 15 días
          </div>
          <div class="director-vencimiento-item">
            <strong>Documentación ANEP</strong>
            Vence en 32 días
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html> 