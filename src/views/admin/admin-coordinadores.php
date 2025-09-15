<?php
// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('coordinators'); ?></title>
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
      content: "AD";
    }
    .list-item:nth-child(2) .avatar::after {
      content: "PM";
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
    <!-- Sidebar -->
<aside class="w-64 bg-sidebar border-r border-border">
  <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
    <img src="/upload/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
    <span class="text-white font-semibold text-lg"><?php _e('scuola_italiana'); ?></span>
  </div>

  <ul class="py-5 list-none">
    <li>
      <a href="index.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
        <?php _e('dashboard'); ?>
      </a>
    </li>
    <li>
      <a href="admin-usuarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
        <?php _e('users'); ?>
      </a>
    </li>
    <li>
      <a href="admin-docentes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
        <?php _e('teachers'); ?>
      </a>
    </li>
    <li>
      <a href="admin-coordinadores.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
        <?php _e('coordinators'); ?>
      </a>
    </li>
    <li>
      <a href="admin-materias.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
        <?php _e('subjects'); ?>
      </a>
    </li>
    <li>
      <a href="admin-horarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
        <?php _e('schedules'); ?>
      </a>
    </li>
  </ul>
</aside>

    <!-- Main -->
    <main class="flex-1 flex flex-col">
      <!-- Header -->
      <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
        <!-- Espacio para el botón de menú hamburguesa -->
        <div class="w-8"></div>
        
        <!-- Título centrado -->
        <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
        
        <!-- Contenedor de iconos a la derecha -->
        <div class="flex items-center">
          <?php echo $languageSwitcher->render('', 'mr-4'); ?>
          <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </button>
          
          <!-- User Menu Dropdown -->
          <div class="relative group">
            <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
              <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
            </button>
            
            <!-- Dropdown Menu -->
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block" id="userMenu">
              <div class="px-4 py-2 text-sm text-gray-700 border-b">
                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                <div class="text-gray-500"><?php _e('role_admin'); ?></div>
              </div>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="current极olor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <?php _e('profile'); ?>
              </a>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 极 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <?php _e('settings'); ?>
              </a>
              <div class="border-t"></div>
              <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3极v1"></path>
                </svg>
                <?php _e('logout'); ?>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Contenido principal - Centrado -->
      <section class="flex-1 px-6 py-8">
        <div class="max-w-6xl mx-auto">
          <div class="mb-8">
            <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('manage_coordinators'); ?></h2>
            <p class="text-muted mb-6 text-base"><?php _e('manage_coordinators_description'); ?></p>
          </div>

          <!-- Botón de agregar coordinador -->
          <div class="mb-6">
            <button onclick="openCoordinadorModal()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
              <?php _e('add_coordinator'); ?>
            </button>
          </div>

          <!-- Lista de coordinadores -->
          <div class="bg-white rounded-lg shadow-sm border border-lightborder">
            <div class="p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php _e('coordinators'); ?></h3>
              
              <!-- Filtros -->
              <div class="mb-6 flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                  <input type="text" id="searchInput" placeholder="<?php _e('search_placeholder'); ?>" 
                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-2">
                  <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                    <?php _e('export'); ?>
                  </button>
                  <button class="px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors">
                    <?php _e('delete_selected'); ?>
                  </button>
                </div>
              </div>

              <!-- Lista de coordinadores -->
              <div id="coordinadoresList">
                <!-- Los coordinadores se cargarán aquí dinámicamente -->
              </div>
            </div>
          </div>
        </div>
      </section>


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
      }
      
      // Agregar event listener a cada enlace
      sidebarLinks.forEach(link => {
        link.addEventListener('click', handleSidebarClick);
      });
      
      // Logout functionality
      const logoutButton = document.getElementById('logoutButton');
      if (logoutButton) {
        logoutButton.addEventListener('click', function(e) {
          e.preventDefault();
          
          // Show confirmation dialog
          const confirmMessage = '<?php _e('logout_confirm'); ?>';
          if (confirm(confirmMessage)) {
            // Create form and submit logout request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/src/controllers/LogoutController.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'logout';
            
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
          }
        });
      }
      
      // User menu toggle
      const userMenuButton = document.getElementById('userMenuButton');
      const userMenu = document.getElementById('userMenu');
      
      if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', function(e) {
          e.stopPropagation();
          userMenu.classList.toggle('hidden');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
          if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.add('hidden');
          }
        });
      }
    });

    // Variables globales
    let coordinadores = [];
    let isEditing = false;

    // Cargar coordinadores al inicializar la página
    document.addEventListener('DOMContentLoaded', function() {
      loadCoordinadores();
      setupEventListeners();
    });

    // Configurar event listeners
    function setupEventListeners() {
      // Búsqueda
      document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        filterCoordinadores(searchTerm);
      });

      // Formulario
      document.getElementById('coordinadorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (isEditing) {
          updateCoordinador();
        } else {
          createCoordinador();
        }
      });
    }

    // Cargar coordinadores
    async function loadCoordinadores() {
      try {
        const response = await fetch('coordinador_handler.php?action=getAll');
        const data = await response.json();
        
        if (data.success) {
          coordinadores = data.data || [];
          renderCoordinadores();
        } else {
          showToast('Error cargando coordinadores: ' + data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
      }
    }

    // Renderizar coordinadores
    function renderCoordinadores() {
      const container = document.getElementById('coordinadoresList');
      
      if (coordinadores.length === 0) {
        container.innerHTML = `
          <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2"><?php _e('no_coordinators_found'); ?></h3>
            <p class="text-gray-500 mb-4"><?php _e('add_first_coordinator'); ?></p>
            <button onclick="openCoordinadorModal()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
              <?php _e('add_coordinator'); ?>
            </button>
          </div>
        `;
        return;
      }

      let html = '<div class="space-y-4">';
      coordinadores.forEach(coordinador => {
        const initials = (coordinador.nombre.charAt(0) + coordinador.apellido.charAt(0)).toUpperCase();
        html += `
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-semibold mr-3">
                ${initials}
              </div>
              <div>
                <div class="font-semibold text-gray-900">${coordinador.nombre} ${coordinador.apellido}</div>
                <div class="text-sm text-gray-500">${coordinador.email} • CI: ${coordinador.cedula} • Roles: ${coordinador.roles}</div>
              </div>
            </div>
            <div class="flex space-x-2">
              <button onclick="editCoordinador(${coordinador.id_usuario})" 
                      class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                <?php _e('edit'); ?>
              </button>
              <button onclick="deleteCoordinador(${coordinador.id_usuario})" 
                      class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors">
                <?php _e('delete'); ?>
              </button>
            </div>
          </div>
        `;
      });
      html += '</div>';
      container.innerHTML = html;
    }

    // Filtrar coordinadores
    function filterCoordinadores(searchTerm) {
      const filtered = coordinadores.filter(coordinador => 
        coordinador.nombre.toLowerCase().includes(searchTerm) ||
        coordinador.apellido.toLowerCase().includes(searchTerm) ||
        coordinador.email.toLowerCase().includes(searchTerm) ||
        coordinador.cedula.includes(searchTerm)
      );
      
      // Crear una copia temporal para renderizar
      const originalCoordinadores = coordinadores;
      coordinadores = filtered;
      renderCoordinadores();
      coordinadores = originalCoordinadores;
    }

    // Abrir modal
    function openCoordinadorModal() {
      isEditing = false;
      document.getElementById('modalTitle').textContent = '<?php _e('add_coordinator'); ?>';
      document.getElementById('coordinadorForm').reset();
      document.getElementById('coordinadorId').value = '';
      document.getElementById('coordinadorModal').classList.remove('hidden');
    }

    // Cerrar modal
    function closeCoordinadorModal() {
      document.getElementById('coordinadorModal').classList.add('hidden');
    }

    // Editar coordinador
    async function editCoordinador(id) {
      try {
        const response = await fetch(`coordinador_handler.php?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
          const coordinador = data.data;
          document.getElementById('coordinadorId').value = coordinador.id_usuario;
          document.getElementById('cedula').value = coordinador.cedula;
          document.getElementById('nombre').value = coordinador.nombre;
          document.getElementById('apellido').value = coordinador.apellido;
          document.getElementById('email').value = coordinador.email;
          document.getElementById('telefono').value = coordinador.telefono || '';
          document.getElementById('contrasena').value = '';
          
          isEditing = true;
          document.getElementById('modalTitle').textContent = '<?php _e('edit_coordinator'); ?>';
          document.getElementById('coordinadorModal').classList.remove('hidden');
        } else {
          showToast('Error cargando coordinador: ' + data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
      }
    }

    // Crear coordinador
    async function createCoordinador() {
      const formData = new FormData(document.getElementById('coordinadorForm'));
      formData.append('action', 'create');
      
      try {
        const response = await fetch('coordinador_handler.php', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        
        if (data.success) {
          showToast(data.message, 'success');
          closeCoordinadorModal();
          loadCoordinadores();
        } else {
          showToast(data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
      }
    }

    // Actualizar coordinador
    async function updateCoordinador() {
      const formData = new FormData(document.getElementById('coordinadorForm'));
      formData.append('action', 'update');
      
      try {
        const response = await fetch('coordinador_handler.php', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        
        if (data.success) {
          showToast(data.message, 'success');
          closeCoordinadorModal();
          loadCoordinadores();
        } else {
          showToast(data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
      }
    }

    // Eliminar coordinador
    async function deleteCoordinador(id) {
      if (!confirm('¿Está seguro de que desea eliminar este coordinador?')) {
        return;
      }
      
      try {
        const response = await fetch('coordinador_handler.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=delete&id=${id}`
        });
        const data = await response.json();
        
        if (data.success) {
          showToast(data.message, 'success');
          loadCoordinadores();
        } else {
          showToast(data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
      }
    }

    // Mostrar toast
    function showToast(message, type = 'info') {
      const container = document.getElementById('toastContainer');
      const toast = document.createElement('div');
      
      const bgColor = type === 'success' ? 'bg-green-500' : 
                     type === 'error' ? 'bg-red-500' : 'bg-blue-500';
      
      toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-2 transform transition-all duration-300 translate-x-full`;
      toast.textContent = message;
      
      container.appendChild(toast);
      
      // Animar entrada
      setTimeout(() => {
        toast.classList.remove('translate-x-full');
      }, 100);
      
      // Auto eliminar después de 5 segundos
      setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
          if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
          }
        }, 300);
      }, 5000);
    }
  </script>

  <!-- Modal para agregar/editar coordinador -->
  <div id="coordinadorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900" id="modalTitle"><?php _e('add_coordinator'); ?></h3>
          <button onclick="closeCoordinadorModal()" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <form id="coordinadorForm">
          <input type="hidden" id="coordinadorId" name="id">
          
          <div class="mb-4">
            <label for="cedula" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('id_number'); ?></label>
            <input type="text" id="cedula" name="cedula" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <div class="mb-4">
            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('name'); ?></label>
            <input type="text" id="nombre" name="nombre" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <div class="mb-4">
            <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('lastname'); ?></label>
            <input type="text" id="apellido" name="apellido" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('email'); ?></label>
            <input type="email" id="email" name="email" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <div class="mb-4">
            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('phone'); ?></label>
            <input type="text" id="telefono" name="telefono"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <div class="mb-6">
            <label for="contrasena" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('password'); ?></label>
            <input type="password" id="contrasena" name="contrasena"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-gray-500 mt-1"><?php _e('password_leave_blank'); ?></p>
          </div>
          
          <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeCoordinadorModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
              <?php _e('cancel'); ?>
            </button>
            <button type="submit" 
                    class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
              <?php _e('save'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Toast notifications -->
  <div id="toastContainer" class="fixed top-4 right-4 z-50"></div>
</body>
</html>