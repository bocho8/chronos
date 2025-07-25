<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Docente | SIM</title>
  <link rel="stylesheet" href="../../public/css/estilos.css">
  <script src="../../public/js/menu.js" defer></script>
  <style>
    .registro-bg {
      min-height: calc(100vh - 70px);
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .registro-card {
      background: #E5E5E5;
      border-radius: 32px;
      padding: 48px 40px 32px 40px;
      width: 1100px;
      box-shadow: 0 2px 16px rgba(34,57,122,0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .registro-card h2 {
      color: #22397A;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 32px;
      text-align: center;
    }
    .registro-form {
      width: 100%;
      display: flex;
      gap: 32px;
      margin-bottom: 24px;
    }
    .registro-form-col {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .registro-form label {
      font-size: 1rem;
      color: #22397A;
      font-weight: 500;
      margin-bottom: 8px;
      margin-top: 10px;
    }
    .registro-form input {
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
    .registro-form input:focus {
      border-color: #22397A;
    }
    .registro-form .asignadas {
      flex: 2;
    }
    .registro-form-bajo {
      width: 100%;
      display: flex;
      gap: 32px;
      align-items: center;
      margin-bottom: 24px;
    }
    .registro-form-bajo label {
      margin-bottom: 8px;
      margin-top: 10px;
    }
    .registro-form-bajo .si-no {
      display: flex;
      gap: 10px;
      margin-bottom: 0;
    }
    .registro-form-bajo .btn-si {
      background: #6EFF6E;
      color: #22397A;
      border: none;
      border-radius: 8px;
      padding: 10px 32px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
      outline: none;
    }
    .registro-form-bajo .btn-no {
      background: #D9D9D9;
      color: #22397A;
      border: none;
      border-radius: 8px;
      padding: 10px 32px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
      outline: none;
    }
    .registro-form-bajo .btn-si.selected, .registro-form-bajo .btn-si:hover {
      background: #4be84b;
    }
    .registro-form-bajo .btn-no.selected, .registro-form-bajo .btn-no:hover {
      background: #bcbcbc;
    }
    .registro-card .siguiente-btn {
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
    .registro-card .siguiente-btn:hover {
      background: #1a2c5a;
    }
    @media (max-width: 1200px) {
      .registro-card { width: 98vw; padding: 24px 8px; }
      .registro-form, .registro-form-bajo { flex-direction: column; gap: 16px; }
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
    <div class="header-title">REGISTRO DOCENTE</div>
    <div class="menu-icon">
      <span></span><span></span><span></span>
    </div>
  </header>
  <div class="registro-bg">
    <section class="registro-card">
      <h2>Información personal del docente</h2>
      <form>
        <div class="registro-form">
          <div class="registro-form-col">
            <label>Nombre Completo</label>
            <input type="text" placeholder="Marcos Mendez">
          </div>
          <div class="registro-form-col">
            <label>Número Celular</label>
            <input type="text" placeholder="123456789">
          </div>
          <div class="registro-form-col asignadas">
            <label>Materias Asignadas</label>
            <input type="text" placeholder="Programación, Tutoría UTU LAB">
          </div>
        </div>
        <div class="registro-form-bajo">
          <div class="registro-form-col">
            <label>Trabaja en otros liceos</label>
            <div class="si-no">
              <button type="button" class="btn-si selected">Sí</button>
              <button type="button" class="btn-no">No</button>
            </div>
          </div>
          <div class="registro-form-col asignadas">
            <label>Nombres de otros liceos</label>
            <input type="text" placeholder="UTU Buceo">
          </div>
        </div>
        <button class="siguiente-btn">Siguiente</button>
      </form>
    </section>
  </div>
</body>
</html> 