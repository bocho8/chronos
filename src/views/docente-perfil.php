<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perfil Docente | SIM</title>
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
    .perfil-card {
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
    .perfil-header {
      display: flex;
      align-items: center;
      gap: 18px;
      margin-bottom: 24px;
    }
    .perfil-avatar {
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
    .perfil-info {
      flex: 1;
    }
    .perfil-nombre {
      font-weight: 700;
      color: #22397A;
      font-size: 1.15rem;
    }
    .perfil-materia {
      font-size: 1.05rem;
      color: #22397A;
      margin-top: 2px;
    }
    .perfil-exp {
      font-size: 0.97rem;
      color: #222;
      margin-top: 2px;
    }
    .perfil-datos {
      width: 100%;
      display: flex;
      gap: 24px;
      margin-bottom: 18px;
    }
    .perfil-datos-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .perfil-label {
      color: #22397A;
      font-size: 1.05rem;
      font-weight: 600;
      margin-bottom: 2px;
    }
    .perfil-valor {
      color: #222;
      font-size: 1.05rem;
      font-weight: 500;
    }
    .perfil-si {
      background: #6EFF6E;
      color: #22397A;
      border-radius: 8px;
      padding: 6px 24px;
      font-weight: 700;
      font-size: 1.05rem;
      display: inline-block;
      margin-right: 8px;
    }
    .perfil-no {
      background: #D9D9D9;
      color: #22397A;
      border-radius: 8px;
      padding: 6px 24px;
      font-weight: 700;
      font-size: 1.05rem;
      display: inline-block;
    }
    .perfil-editar-btn {
      margin-top: 18px;
      width: 180px;
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
    .perfil-editar-btn:hover {
      background: #1a2c5a;
    }
    @media (max-width: 900px) {
      .main-container { flex-direction: column; }
      .sidebar { width: 100%; min-height: unset; padding-top: 0; border-right: none; }
      .content { padding: 24px 8px; }
      .perfil-card { width: 98vw; padding: 16px 4px; }
      .perfil-datos { flex-direction: column; gap: 8px; }
      .perfil-editar-btn { width: 100%; }
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
      <section class="perfil-card">
        <div class="perfil-header">
          <div class="perfil-avatar">👤</div>
          <div class="perfil-info">
            <div class="perfil-nombre">Prof. Marcos Mendez</div>
            <div class="perfil-materia">Programación, Tutoría UTU LAB</div>
            <div class="perfil-exp">Educador apasionado con más de 10 años de experiencia.</div>
          </div>
        </div>
        <div style="font-size:1.2rem;font-weight:700;color:#22397A;margin-bottom:18px;">Información Personal</div>
        <div class="perfil-datos">
          <div class="perfil-datos-col">
            <div class="perfil-label">Nombre Completo</div>
            <div class="perfil-valor">Marcos Mendez</div>
          </div>
          <div class="perfil-datos-col">
            <div class="perfil-label">Número Celular</div>
            <div class="perfil-valor">123456789</div>
          </div>
          <div class="perfil-datos-col">
            <div class="perfil-label">Materias Asignadas</div>
            <div class="perfil-valor">Programación, Tutoría UTU LAB</div>
          </div>
        </div>
        <div class="perfil-datos">
          <div class="perfil-datos-col">
            <div class="perfil-label">Trabaja en otros liceos</div>
            <span class="perfil-si">Sí</span><span class="perfil-no">No</span>
          </div>
          <div class="perfil-datos-col">
            <div class="perfil-label">Nombres de otros liceos</div>
            <div class="perfil-valor">UTU Buceo</div>
          </div>
        </div>
        <button class="perfil-editar-btn">Editar</button>
      </section>
    </main>
  </div>
</body>
</html> 