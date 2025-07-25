<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Añadir Materia | SIM</title>
  <link rel="stylesheet" href="../../public/css/estilos.css">
  <script src="../../public/js/menu.js" defer></script>
  <style>
    .materias-bg {
      min-height: calc(100vh - 70px);
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .materias-card {
      background: #E5E5E5;
      border-radius: 32px;
      padding: 48px 40px 32px 40px;
      width: 900px;
      box-shadow: 0 2px 16px rgba(34,57,122,0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .materias-card h2 {
      color: #22397A;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 32px;
      text-align: center;
    }
    .materias-form {
      width: 100%;
      display: flex;
      gap: 40px;
      margin-bottom: 24px;
    }
    .materias-form-col {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .materias-form label {
      font-size: 1rem;
      color: #22397A;
      font-weight: 500;
      margin-bottom: 8px;
      margin-top: 10px;
    }
    .materias-form input {
      width: 100%;
      padding: 12px 16px;
      border-radius: 8px;
      border: 1.5px solid #D9D9D9;
      font-size: 1rem;
      margin-bottom: 16px;
      background: #fff;
      color: #22397A;
      box-sizing: border-box;
    }
    .materias-form input:focus {
      border-color: #22397A;
    }
    .chips {
      display: flex;
      gap: 12px;
      margin-bottom: 12px;
      flex-wrap: wrap;
    }
    .chip {
      background: #D9D9D9;
      color: #22397A;
      border-radius: 8px;
      padding: 8px 18px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      outline: none;
      transition: background 0.2s;
    }
    .chip.selected, .chip:hover {
      background: #22397A;
      color: #fff;
    }
    .materias-card .guardar-btn {
      width: 220px;
      background: #22397A;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 14px 0;
      font-size: 1.1rem;
      font-weight: 700;
      margin-top: 24px;
      cursor: pointer;
      transition: background 0.2s;
      align-self: center;
    }
    .materias-card .guardar-btn:hover {
      background: #1a2c5a;
    }
    @media (max-width: 1000px) {
      .materias-card { width: 98vw; padding: 24px 8px; }
      .materias-form { flex-direction: column; gap: 16px; }
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
  <div class="materias-bg">
    <section class="materias-card">
      <h2>Añadir Nueva Materia</h2>
      <form class="materias-form">
        <div class="materias-form-col">
          <label>Nombre del docente asignado</label>
          <input type="text" placeholder="Ingrese el nombre completo">
        </div>
        <div class="materias-form-col">
          <label>Nombre de la materia</label>
          <div class="chips">
            <button type="button" class="chip">Matemáticas</button>
            <button type="button" class="chip">Programación</button>
            <button type="button" class="chip">Base de datos</button>
            <button type="button" class="chip">Filosofía</button>
          </div>
          <input type="text" placeholder="Ingrese el nombre completo">
        </div>
      </form>
      <button class="guardar-btn">Guardar Materia</button>
    </section>
  </div>
</body>
</html> 