<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sistema de Horarios SIM — Admin · Materias</title>
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
            darkblue: '#142852'
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
          <a href="admin-materias.html" class="flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-hover hover:text-gray-800 bg-hover text-darkblue font-medium">
            <span class="dot"></span> Materias
          </a>
        </li>
        <li>
          <a href="admin-horarios.html" class="flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-hover hover:text-gray-800">
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
        <div class="w-full max-w-4xl flex flex-col">
          <div class="mb-8">
            <h2 class="text-darktext text-2xl font-semibold mb-2.5">Registros de Materias</h2>
            <p class="text-muted mb-6 text-base">Lista de todas las materias registradas.</p>
          </div>

          <div class="flex gap-4 mb-8">
            <button class="py-2.5 px-5 border-none rounded cursor-pointer font-medium transition-all text-sm bg-black text-white hover:bg-gray-800">
              Eliminar Seleccionados
            </button>
            <button class="py-2.5 px-5 border-none rounded cursor-pointer font-medium transition-all text-sm bg-darkblue text-white hover:bg-navy">
              Agregar Materia
            </button>
          </div>

          <div class="border-t border-gray-300 mb-8"></div>

          <!-- Lista de materias -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder">
            <article class="flex items-center p-4 border-b border-gray-200 transition-colors hover:bg-lightbg">
              <div class="w-3 h-3 rounded-full bg-darkblue mr-4 flex-shrink-0"></div>
              <div class="meta">
                <div class="font-semibold text-darktext">Matemáticas</div>
              </div>
            </article>

            <article class="flex items-center p-4 border-b border-gray-200 transition-colors hover:bg-lightbg">
              <div class="w-3 h-3 rounded-full bg-darkblue mr-4 flex-shrink-0"></div>
              <div class="meta">
                <div class="font-semibold text-darktext">Programación</div>
              </div>
            </article>

            <article class="flex items-center p-4 transition-colors hover:bg-lightbg">
              <div class="w-3 h-3 rounded-full bg-darkblue mr-4 flex-shrink-0"></div>
              <div class="meta">
                <div class="font-semibold text-darktext">Sistemas Operativos</div>
              </div>
            </article>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html>