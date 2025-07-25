<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Asignación de Docentes | SIM</title>
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
    .asignacion-card {
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
    .asignacion-card h2 {
      color: #22397A;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 18px;
      text-align: left;
    }
    .asignacion-lista {
      width: 100%;
      display: flex;
      gap: 32px;
      margin-bottom: 32px;
    }
    .asignacion-docente {
      flex: 1;
      background: #F8F8F8;
      border-radius: 12px;
      padding: 18px 24px;
      min-width: 120px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    .asignacion-avatar {
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
      margin-bottom: 8px;
    }
    .asignacion-nombre {
      font-weight: 700;
      color: #22397A;
      font-size: 1.08rem;
      margin-bottom: 4px;
    }
    .asignacion-materia {
      font-size: 0.97rem;
      color: #666;
      margin-bottom: 4px;
    }
    .asignacion-horas {
      color: #22397A;
      font-size: 1.05rem;
      font-weight: 600;
      margin-bottom: 4px;
    }
    .asignacion-modulo {
      width: 100%;
      margin-top: 32px;
      background: #F8F8F8;
      border-radius: 12px;
      padding: 24px 24px 18px 24px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    .asignacion-modulo-titulo {
      font-size: 1.15rem;
      font-weight: 700;
      color: #22397A;
      margin-bottom: 18px;
    }
    .asignacion-modulo-form {
      display: flex;
      gap: 18px;
      align-items: center;
      margin-bottom: 12px;
      flex-wrap: wrap;
    }
    .asignacion-modulo-form select {
      padding: 10px 18px;
      border-radius: 8px;
      border: 1.5px solid #D9D9D9;
      font-size: 1rem;
      color: #22397A;
      background: #fff;
      font-family: inherit;
      min-width: 120px;
    }
    .asignacion-modulo-form button {
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
    .asignacion-modulo-form button:hover {
      background: #1a2c5a;
    }
    .asignacion-modulo-form .btn-cambios {
      background: #fff;
      color: #22397A;
      border: 1.5px solid #22397A;
      margin-right: 12px;
    }
    .asignacion-modulo-form .btn-cambios:hover {
      background: #22397A;
      color: #fff;
    }
    @media (max-width: 1100px) {
      .asignacion-card { width: 98vw; padding: 16px 4px; }
      .asignacion-lista { flex-direction: column; gap: 12px; }
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
        <li>Inicio</li>
        <li>Horarios Semanales</li>
        <li class="active">Asignación Docentes</li>
      </ul>
    </nav>
    <main class="content">
      <section class="asignacion-card">
        <h2>Módulo de Asignación de Docentes</h2>
        <div style="color:#444;margin-bottom:18px;">Asigne grupos y materias a los docentes utilizando drag & drop.</div>
        <div class="asignacion-lista">
          <div class="asignacion-docente">
            <div class="asignacion-avatar">👩‍🏫</div>
            <div class="asignacion-nombre">Maria Guberna</div>
            <div class="asignacion-materia">Química</div>
            <div class="asignacion-horas">10 horas asignadas</div>
          </div>
          <div class="asignacion-docente">
            <div class="asignacion-avatar">👨‍🏫</div>
            <div class="asignacion-nombre">Marcelo Simo</div>
            <div class="asignacion-materia">Física/Química</div>
            <div class="asignacion-horas">8 horas asignadas</div>
          </div>
          <div class="asignacion-docente">
            <div class="asignacion-avatar">👩‍🏫</div>
            <div class="asignacion-nombre">Lourdes Gianfione</div>
            <div class="asignacion-materia">Italiano</div>
            <div class="asignacion-horas">12 horas asignadas</div>
          </div>
        </div>
        <div class="asignacion-modulo">
          <div class="asignacion-modulo-titulo">Asignar Docente</div>
          <form class="asignacion-modulo-form">
            <select>
              <option>Seleccionar Docente</option>
              <option>Diego Robira</option>
              <option>Mary Tardini</option>
              <option>Gustavo Ferrari</option>
            </select>
            <select>
              <option>Seleccionar Grupo/Materia</option>
              <option>Matemáticas</option>
              <option>Programación</option>
              <option>Filosofía</option>
            </select>
            <button type="button" class="btn-cambios">Desverir cambios</button>
            <button type="submit">Asignar</button>
          </form>
        </div>
      </section>
    </main>
  </div>
</body>
</html> 