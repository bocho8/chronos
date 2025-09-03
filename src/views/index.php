<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sistema de Horarios SIM — Inicio de Sesión</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            navy: '#1f366d',
            bg: '#f5f6f8',
            card: '#d9d9d9',
            muted: '#475569',
          },
          fontFamily: {
            sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Arial', 'sans-serif'],
          },
        }
      }
    }
  </script>
  <style type="text/css">
    .select__icon {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      width: 0;
      height: 0;
      border-left: 6px solid transparent;
      border-right: 6px solid transparent;
      border-top: 8px solid #111827;
    }
    .hamburger span {
      width: 26px;
      height: 3px;
      background: #fff;
      display: block;
      border-radius: 2px;
    }
  </style>
</head>
<body class="bg-bg font-sans text-[#0f172a]">
  <!-- BARRA SUPERIOR -->
  <header class="bg-navy text-white h-18 flex items-center justify-center">
    <div class="w-full grid grid-cols-3 items-center px-4">
      <!-- IZQUIERDA -->
      <div class="flex items-center gap-3 justify-self-start">
        <img class="h-[50px] w-auto" src="LogoScuola.png" alt="Logo Scuola Italiana di Montevideo">
        <span class="text-base font-semibold">Scuola Italiana di Montevideo</span>
      </div>

      <!-- CENTRO -->
      <h1 class="m-0 text-center text-xl md:text-[22px] font-bold">Sistema de Horarios SIM</h1>

      <!-- DERECHA -->
      <button class="w-11 h-11 grid place-content-center gap-1.5 bg-transparent border-0 cursor-pointer justify-self-end hamburger" aria-label="Menú">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- CONTENEDOR CENTRAL -->
  <main class="flex items-center justify-center min-h-[calc(100vh-72px)] p-6">
    <section class="bg-card rounded-3xl p-10 md:px-16 md:py-10 w-full max-w-[700px] shadow-lg" aria-labelledby="titulo-login">
      <h2 id="titulo-login" class="text-center text-2xl md:text-[28px] font-extrabold text-navy mb-6">Inicio de Sesión</h2>

      <form class="flex flex-col gap-5" id="formLogin" novalidate>
        <!-- C.I -->
        <label class="flex flex-col gap-1.5">
          <span class="font-semibold">C.I</span>
          <input id="ciInput" class="h-[42px] px-3 py-2.5 border border-gray-300 rounded-lg bg-white font-sans" type="text" placeholder="C.I" inputmode="numeric" required />
        </label>

        <!-- Contraseña -->
        <label class="flex flex-col gap-1.5">
          <span class="font-semibold">Contraseña</span>
          <input id="passwordInput" class="h-[42px] px-3 py-2.5 border border-gray-300 rounded-lg bg-white font-sans" type="password" placeholder="Contraseña" required />
        </label>

        <!-- Selección de Rol -->
        <label class="flex flex-col gap-1.5">
          <span class="font-semibold">Seleccione su rol:</span>
          <div class="relative">
            <select id="rolSelect" class="appearance-none w-full h-[42px] px-3 py-2.5 pr-10 border border-gray-300 rounded-lg bg-white font-sans text-gray-900" required>
              <option value="" selected disabled>Seleccione un rol</option>
              <option value="admin">Administrador</option>
              <option value="coordinador">Coordinador</option>
              <option value="docente">Docente</option>
              <option value="director">Director</option>
              <option value="padre">Padre/Madre</option>
            </select>
            <span class="select__icon" aria-hidden="true"></span>
          </div>
        </label>

        <!-- Botón -->
        <button type="submit" class="h-11 px-4 py-2.5 rounded-lg border-0 font-bold cursor-pointer bg-navy text-white w-full hover:bg-[#142852]">Iniciar Sesion</button>

        <!-- Link para olvidar contraseña -->
        <div class="mt-1.5 flex items-center justify-end">
          <a class="text-navy no-underline font-semibold text-sm hover:underline" href="#" tabindex="0">¿Olvidaste tu contraseña?</a>
        </div>
      </form>
    </section>
  </main>

  <!-- JS de redirección -->
  <script>
    document.getElementById('formLogin').addEventListener('submit', function (e) {
      e.preventDefault();
      
      // Validar campos
      const ci = document.getElementById('ciInput').value.trim();
      const password = document.getElementById('passwordInput').value.trim();
      const rol = document.getElementById('rolSelect').value;
      
      if (!ci || !password) {
        alert('Por favor, complete todos los campos.');
        return;
      }
      
      if (!rol) {
        alert('Seleccioná un rol para continuar.');
        return;
      }

      // Mapear roles a páginas
      const rutas = {
        'admin': 'admin-docentes.html',
        'coordinador': 'coordinador.html',
        'docente': 'docente.html',
        'director': 'director.html',
        'padre': 'familia.html'
      };

      // Redireccionar según el rol
      if (rutas[rol]) {
        // En un caso real, aquí verificarías las credenciales con el servidor
        // Por ahora, simulamos una verificación exitosa
        alert(`Inicio de sesión exitoso. Redirigiendo al panel de ${rol}`);
        window.location.href = rutas[rol];
      } else {
        alert('Rol no válido.');
      }
    });
  </script>
</body>
</html>