<?php
// Include translation system
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();

// Handle language change
$languageSwitcher->handleLanguageChange();
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('teachers'); ?></title>
  <link rel="stylesheet" href="/css/styles.css">
  <style type="text/css">
    .hamburger span {
      width: 25px;
      height: 3px;
      background-color: white;
      margin: 3px 0;
      border-radius: 2px;
      transition: all 0.3s;
    }
    .avatar::after {
      color: white;
      font-weight: bold;
      font-size: 0.9rem;
    }
    .list-item:nth-child(1) .avatar::after {
      content: "JP";
    }
    .list-item:nth-child(2) .avatar::after {
      content: "AG";
    }
    .list-item:nth-child(3) .avatar::after {
      content: "LR";
    }
    body {
      overflow-x: hidden;
    }
    .sidebar-link {
      position: relative;
      transition: all 0.3s;
    }
    .sidebar-link.active {
      background-color: #e4e6eb;
      font-weight: 600;
    }
    .sidebar-link.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 4px;
      background-color: #1f366d;
    }
  </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-sidebar border-r border-border">
      <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
        <img src="LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
        <span class="text-white font-semibold text-lg"><?php _e('scuola_italiana'); ?></span>
      </div>

      <ul class="py-5 list-none">
        <li>
          <a href="admin-docentes.html" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <?php _e('teachers'); ?>
          </a>
        </li>
        <li>
          <a href="admin-coordinadores.html" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <?php _e('coordinators'); ?>
          </a>
        </li>
        <li>
          <a href="admin-materias.html" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <?php _e('subjects'); ?>
          </a>
        </li>
        <li>
          <a href="admin-horarios.html" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?php _e('schedules'); ?>
          </a>
        </li>
      </ul>
    </aside>

    <!-- Main -->
    <main class="flex-1 flex flex-col">
      <!-- Header modificado con texto centrado -->
      <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
        <!-- Espacio para el botón de menú hamburguesa (oculto por ahora) -->
        <div class="w-8"></div>
        
        <!-- Título centrado -->
        <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?> (<?php _e('role_admin'); ?>)</div>
        
        <!-- Contenedor de iconos a la derecha -->
        <div class="flex items-center">
          <?php echo $languageSwitcher->render('', 'mr-4'); ?>
          <button class="mr-4 p-2 rounded-full hover:bg-navy">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </button>
          <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold">A</div>
        </div>
      </header>

      <!-- Contenido principal - Centrado -->
      <section class="flex-1 px-6 py-8">
        <div class="max-w-6xl mx-auto">
          <div class="mb-8">
            <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('teacher_records'); ?></h2>
            <p class="text-muted mb-6 text-base"><?php _e('teacher_list_description'); ?></p>
          </div>

          <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
            <!-- Header de la tabla -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
              <h3 class="font-medium text-darktext"><?php _e('registered_teachers'); ?></h3>
              <div class="flex gap-2">
                <button class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                  <?php _e('filter'); ?>
                </button>
                <button class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50">
                  <?php _e('export'); ?>
                </button>
                <button class="py-2 px-4 border border-red-300 rounded cursor-pointer font-medium transition-all text-sm bg-red-50 text-red-600 hover:bg-red-100 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  <?php _e('delete_selected'); ?>
                </button>
                <button class="py-2 px-4 border-none rounded cursor-pointer font-medium transition-all text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  <?php _e('add_teacher'); ?>
                </button>
              </div>
            </div>

            <!-- Lista de docentes -->
            <div class="divide-y divide-gray-200">
              <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg">
                <div class="flex items-center">
                  <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">JP</div>
                  <div class="meta">
                    <div class="font-semibold text-darktext mb-1">Juan Pérez</div>
                    <div class="text-muted text-sm"><?php _e('subject_mathematics'); ?></div>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button class="p-2 text-blue-600 hover:bg-blue-50 rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button class="p-2 text-red-600 hover:bg-red-50 rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </article>

              <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg">
                <div class="flex items-center">
                  <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">AG</div>
                  <div class="meta">
                    <div class="font-semibold text-darktext mb-1">Ana Gómez</div>
                    <div class="text-muted text-sm"><?php _e('subject_history'); ?></div>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button class="p-2 text-blue-600 hover:bg-blue-50 rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button class="p-2 text-red-600 hover:bg-red-50 rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v极
m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </article>

              <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg">
                <div class="flex items-center">
                  <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-极 text-white font-semibold">LR</div>
                  <div class="meta">
                    <div class="font-semibold text-darktext mb-1">Luís Rodríguez</div>
                    <div class="text-muted text-sm"><?php _e('subject_biology'); ?></div>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button class="p-2 text-blue-600 hover:bg-blue-50 rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0极 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2极v11a2 2 0 002 2h11a2 2 极 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button class="p-2 text-red-600 hover:bg-red-50 rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1极h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    // Funcionalidad para la barra lateral
    document.addEventListener('DOMContentLoaded', function() {
      // Obtener todos los enlaces de la barra lateral
      const sidebarLinks = document.querySelectorAll('.sidebar-link');
      
      // Función para manejar el clic en los enlaces
      function handleSidebarClick(event) {
        // Remover la clase active de todos los enlaces
        sidebarLinks.forEach(link => {
          link.classList.remove('active');
        });
        
        // Agregar la clase active al enlace clickeado
        this.classList.add('active');
        
        // Aquí puedes agregar lógica de redirección si es necesario
        // window.location.href = this.getAttribute('href');
      }
      
      // Agregar event listener a cada enlace
      sidebarLinks.forEach(link => {
        link.addEventListener('click', handleSidebarClick);
      });
      
      // También puedes agregar funcionalidad para el botón hamburguesa si es necesario
      const hamburger = document.querySelector('.hamburger');
      if (hamburger) {
        hamburger.addEventListener极('click', function() {
          // Aquí puedes agregar la funcionalidad para expandir/contraer el sidebar
          document.querySelector('aside').classList.toggle('hidden');
        });
      }
    });
  </script>
</body>
</html>