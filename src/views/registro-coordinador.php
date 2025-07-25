<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Coordinador | SIM</title>
  <link rel="stylesheet" href="../../public/css/estilos.css">
  <script src="../../public/js/menu.js" defer></script>
  <style>
    .coordinador-bg {
      min-height: calc(100vh - 70px);
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .coordinador-card {
      background: #E5E5E5;
      border-radius: 32px;
      padding: 48px 40px 32px 40px;
      width: 700px;
      box-shadow: 0 2px 16px rgba(34,57,122,0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .coordinador-card h2 {
      color: #22397A;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 32px;
      text-align: center;
    }
    .form-section {
      width: 100%;
      margin-bottom: 32px;
    }
    .form-section h3 {
      color: #22397A;
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 18px;
      margin-top: 0;
    }
    .form-section .flex {
      gap: 18px;
      margin-bottom: 0;
    }
    .form-section label {
      font-size: 1rem;
      color: #22397A;
      font-weight: 500;
      margin-bottom: 8px;
      margin-top: 10px;
    }
    .form-section input, .form-section select {
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
    .form-section input:focus, .form-section select:focus {
      border-color: #22397A;
    }
    .coordinador-card .btn-row {
      display: flex;
      gap: 18px;
      justify-content: flex-end;
      margin-top: 8px;
    }
    .coordinador-card .btn {
      background: #22397A;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 12px 32px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
    }
    .coordinador-card .btn:hover {
      background: #1a2c5a;
    }
    @media (max-width: 800px) {
      .coordinador-card { width: 98vw; padding: 24px 8px; }
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
  <div class="coordinador-bg">
    <section class="coordinador-card">
      <h2>REGISTRO COORDINADOR</h2>
      <form>
        <div class="form-section">
          <h3>Información Personal</h3>
          <div class="flex" style="gap:18px;">
            <input type="text" placeholder="Nombre Completo">
            <input type="text" placeholder="Celular">
          </div>
          <input type="email" placeholder="Email Institucional">
          <input type="text" placeholder="Teléfono Celular">
          <div class="btn-row">
            <button type="button" class="btn">Guardar</button>
          </div>
        </div>
        <div class="form-section">
          <h3>Datos Institucionales</h3>
          <div class="flex" style="gap:18px;">
            <select>
              <option>Rol</option>
              <option>Coordinador</option>
              <option>Sub-Coordinador</option>
            </select>
            <input type="text" placeholder="Horario de trabajo">
          </div>
          <input type="text" placeholder="Premios especiales">
          <div class="btn-row">
            <button type="button" class="btn">Validar</button>
            <button type="submit" class="btn">Aceptar</button>
          </div>
        </div>
      </form>
    </section>
  </div>
</body>
</html> 