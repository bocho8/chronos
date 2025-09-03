<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Horarios — Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            navy: '#1f366d',
            bg: '#f8f9fa',
            card: '#d1d1d1',
            muted: '#7f8c8d',
            darktext: '#2c3e50',
            border: '#b8b8b8',
            hover: '#b8b8b8',
            lightborder: '#e0e0e0',
            lightbg: '#f5f7f9',
            darkblue: '#142852',
            available: '#4CAF50',
            notavailable: '#F44336'
          },
          fontFamily: {
            sans: ['Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'sans-serif'],
          },
        }
      }
    }
  </script>
  <style type="text/css">
    .hamburger span {
      width: 25px;
      height: 3px;
      background-color: white;
      margin: 3px 0;
      border-radius: 2px;
      transition: all 0.3s;
    }
    .dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-right: 10px;
      background-color: #142852;
    }
    body {
      overflow-x: hidden;
    }
    .horario-cell {
      cursor: pointer;
      transition: all 0.2s;
      min-width: 100px;
    }
    .horario-cell:hover {
      opacity: 0.9;
      transform: scale(0.97);
    }
    @media (max-width: 768px) {
      .horario-cell {
        min-width: 80px;
        font-size: 0.75rem;
        padding: 6px 4px;
      }
    }
    @media (max-width: 480px) {
      .horario-cell {
        min-width: 60px;
        font-size: 0.65rem;
      }
    }
  </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-card border-r border-border">
      <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
        <img src="LogoScuola.png" alt="Scuola Italiana di Montevideo" class="h-9 w-auto">
        <span class="text-white font-semibold text-lg">Scuola Italiana</span>
      </div>

      <ul class="py-5 list-none">
        <li>
          <a href="admin-docentes.html" class="flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-hover hover:text-gray-800">
            <span class="dot"></span> Docentes
          </a>
        </li>
        <li>
          <a href="admin-coordinadores.html" class="flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-hover hover:text-gray-800">
            <span class="dot"></span> Coordinadores
          </a>
        </li>
        <li>
          <a href="admin-materias.html" class="flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-hover hover:text-gray-800">
            <span class="dot"></span> Materias
          </a>
        </li>
        <li>
          <a href="admin-horarios.html" class="flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-hover hover:text-gray-800 bg-hover text-darkblue font-medium">
            <span class="dot"></span> Horarios
          </a>
        </li>
      </ul>
    </aside>

    <!-- Main -->
    <main class="flex-1 flex flex-col">
      <!-- Header -->
      <header class="bg-darkblue px-6 h-[60px] flex justify-center items-center shadow relative">
        <div class="text-white text-xl font-semibold text-center">Bienvenido (ADMIN)</div>
        <button class="hamburger flex flex-col bg-none border-none cursor-pointer p-1 absolute right-6 top-1/2 -translate-y-1/2" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </header>

      <!-- Contenido principal - Centrado -->
      <section class="flex-1 px-6 py-8 flex justify-center">
        <div class="w-full max-w-6xl flex flex-col">
          <div class="mb-8 text-center">
            <h1 class="text-darktext text-3xl font-bold mb-2.5">DISPONIBILIDAD HORARIA</h1>
            <p class="text-muted text-lg">Seleccione sus horas disponibles.</p>
          </div>

          <!-- Tarjeta de horarios -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder p-6">
            <div class="overflow-x-auto">
              <table class="w-full border-collapse mb-6">
                <thead>
                  <tr>
                    <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Hora</th>
                    <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Lunes</th>
                    <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Martes</th>
                    <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Miércoles</th>
                    <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Jueves</th>
                    <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Viernes</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">08:00 – 08:45</th>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">08:45 – 09:30</th>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">09:30 – 10:15</th>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">10:15 – 11:00</th>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">11:00 – 11:45</th>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">11:45 – 12:30</th>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">12:30 – 13:15</th>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">13:15 – 14:00</th>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">14:00 – 14:45</th>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">14:45 – 15:30</th>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">15:30 – 16:15</th>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                  <tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">16:15 – 16:45</th>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                    <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="flex justify-center">
              <button class="py-3 px-8 border-none rounded cursor-pointer font-semibold transition-all text-base bg-darkblue text-white hover:bg-navy">
                Siguiente
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    // Toggle Disponible / No disponible
    document.querySelectorAll('.horario-cell').forEach(td => {
      td.tabIndex = 0;
      td.addEventListener('click', toggle);
      td.addEventListener('keydown', e => {
        if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); toggle.call(td); }
      });
    });

    function toggle() {
      const state = this.getAttribute('data-state');
      if (state === 'yes') {
        this.setAttribute('data-state', 'no');
        this.textContent = 'No disponible';
        this.classList.remove('bg-available');
        this.classList.add('bg-notavailable');
      } else {
        this.setAttribute('data-state', 'yes');
        this.textContent = 'Disponible';
        this.classList.remove('bg-notavailable');
        this.classList.add('bg-available');
      }
    }
  </script>
</body>
</html>