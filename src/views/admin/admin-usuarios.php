<?php
// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Usuario.php';

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

// Load database configuration
$dbConfig = require __DIR__ . '/../../config/database.php';
$database = new Database($dbConfig);

// Get users and roles
$usuarioModel = new Usuario($database->getConnection());
$usuarios = $usuarioModel->getAllUsuarios();
$roles = $usuarioModel->getAllRoles();

// Function to get user initials
function getUserInitials($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('users'); ?></title>
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
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 10000;
      padding: 16px 20px;
      border-radius: 8px;
      color: white;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      transform: translateX(100%);
      transition: transform 0.3s ease-in-out;
      max-width: 400px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .toast.show {
      transform: translateX(0);
    }
    .toast-success {
      background: linear-gradient(135deg, #10b981, #059669);
    }
    .toast-error {
      background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    .toast-warning {
      background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    .toast-info {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
    }
    #toastContainer {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 10000;
    }
    
    /* Modal styles */
    #usuarioModal {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      bottom: 0 !important;
      z-index: 10000 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      background-color: rgba(0, 0, 0, 0.2) !important;
      backdrop-filter: blur(8px) !important;
      -webkit-backdrop-filter: blur(8px) !important;
    }
    
    #usuarioModal.hidden {
      display: none !important;
    }
    
    #usuarioModal .modal-content {
      position: relative !important;
      z-index: 10001 !important;
      background: white !important;
      border-radius: 8px !important;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }
    
    #usuarioModal button[type="submit"], 
    #usuarioModal button[type="button"] {
      z-index: 10002 !important;
      position: relative !important;
      background-color: #1f366d !important;
      color: white !important;
    }
  </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
  <div class="flex min-h-screen">
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
          <a href="admin-usuarios.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
            <?php _e('users'); ?>
          </a>
        </li>
        <li>
          <a href="admin-docentes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
            <?php _e('teachers'); ?>
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
                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <?php _e('profile'); ?>
              </a>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <?php _e('settings'); ?>
              </a>
              <div class="border-t"></div>
              <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
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
            <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('users_management'); ?></h2>
            <p class="text-muted mb-6 text-base"><?php _e('users_management_description'); ?></p>
          </div>

          <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
            <!-- Header de la tabla -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
              <h3 class="font-medium text-darktext"><?php _e('users'); ?></h3>
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
                <button onclick="openUsuarioModal()" class="py-2 px-4 border-none rounded cursor-pointer font-medium transition-all text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  <?php _e('add_user'); ?>
                </button>
              </div>
            </div>

            <!-- Lista de usuarios -->
            <div class="divide-y divide-gray-200">
              <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $usuario): ?>
                  <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg">
                    <div class="flex items-center">
                      <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                        <?php echo getUserInitials($usuario['nombre'], $usuario['apellido']); ?>
                      </div>
                      <div class="meta">
                        <div class="font-semibold text-darktext mb-1">
                          <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                        </div>
                        <div class="text-muted text-sm">
                          <?php echo htmlspecialchars($usuario['email']); ?> • 
                          CI: <?php echo htmlspecialchars($usuario['cedula']); ?> • 
                          <?php _e('roles'); ?>: <?php echo htmlspecialchars($usuario['roles'] ?? 'Sin roles'); ?>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
                      <button onclick="editUsuario(<?php echo $usuario['id_usuario']; ?>)" 
                              class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                        <?php _e('edit'); ?>
                      </button>
                      <button onclick="deleteUsuario(<?php echo $usuario['id_usuario']; ?>, '<?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>')" 
                              class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                        <?php _e('delete'); ?>
                      </button>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="p-8 text-center">
                  <div class="text-gray-500 text-lg mb-2"><?php _e('no_users_found'); ?></div>
                  <div class="text-gray-400 text-sm"><?php _e('add_first_user'); ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- Modal para agregar/editar usuario -->
  <div id="usuarioModal" class="hidden">
    <div class="modal-content p-8 w-full max-w-md mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_user'); ?></h3>
        <button onclick="closeUsuarioModal()" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <form id="usuarioForm" class="space-y-4">
        <input type="hidden" id="id_usuario" name="id_usuario">
        
        <div>
          <label for="cedula" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('cedula'); ?></label>
          <input type="text" id="cedula" name="cedula" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
        </div>

        <div>
          <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('name'); ?></label>
          <input type="text" id="nombre" name="nombre" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
        </div>

        <div>
          <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('lastname'); ?></label>
          <input type="text" id="apellido" name="apellido" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('email'); ?></label>
          <input type="email" id="email" name="email" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
        </div>

        <div>
          <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('phone'); ?></label>
          <input type="text" id="telefono" name="telefono"
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
        </div>

        <div>
          <label for="contrasena" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('password'); ?></label>
          <input type="password" id="contrasena" name="contrasena"
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
          <p class="text-xs text-gray-500 mt-1"><?php _e('password_leave_blank'); ?></p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2"><?php _e('roles'); ?></label>
          <div class="space-y-2">
            <?php foreach ($roles as $role): ?>
              <label class="flex items-center">
                <input type="checkbox" name="roles[]" value="<?php echo $role['nombre_rol']; ?>" 
                       class="h-4 w-4 text-darkblue focus:ring-darkblue border-gray-300 rounded">
                <span class="ml-2 text-sm text-gray-900"><?php echo htmlspecialchars($role['nombre_rol']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="flex items-center">
          <input type="checkbox" id="activo" name="activo" checked
                 class="h-4 w-4 text-darkblue focus:ring-darkblue border-gray-300 rounded">
          <label for="activo" class="ml-2 block text-sm text-gray-900">
            <?php _e('active'); ?>
          </label>
        </div>

        <div class="flex justify-end space-x-3 pt-4">
          <button type="button" onclick="closeUsuarioModal()" 
                  class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
            <?php _e('cancel'); ?>
          </button>
          <button type="submit" 
                  class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-darkblue hover:bg-navy focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
            <?php _e('save'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast Container -->
  <div id="toastContainer"></div>

  <script>
    let isEditMode = false;

    // Toast notification functions
    function showToast(message, type = 'info') {
      const container = document.getElementById('toastContainer');
      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;
      
      const icon = getToastIcon(type);
      toast.innerHTML = `
        <div class="flex items-center">
          ${icon}
          <span>${message}</span>
        </div>
        <button onclick="hideToast(this)" class="ml-4 text-white hover:text-gray-200">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      `;
      
      container.appendChild(toast);
      
      // Trigger animation
      setTimeout(() => toast.classList.add('show'), 100);
      
      // Auto hide after 5 seconds
      setTimeout(() => hideToast(toast), 5000);
    }

    function getToastIcon(type) {
      const icons = {
        success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
        error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
        warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
        info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
      };
      return icons[type] || icons.info;
    }

    function hideToast(toast) {
      if (toast && toast.parentNode) {
        toast.classList.remove('show');
        setTimeout(() => {
          if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
          }
        }, 300);
      }
    }

    // Modal functions
    function openUsuarioModal() {
      isEditMode = false;
      document.getElementById('modalTitle').textContent = '<?php _e('add_user'); ?>';
      document.getElementById('usuarioForm').reset();
      document.getElementById('id_usuario').value = '';
      
      // Limpiar checkboxes de roles
      document.querySelectorAll('input[name="roles[]"]').forEach(checkbox => {
        checkbox.checked = false;
      });
      
      const modal = document.getElementById('usuarioModal');
      modal.classList.remove('hidden');
      
      // Focus on first input
      setTimeout(() => {
        document.getElementById('cedula').focus();
      }, 100);
    }

    function closeUsuarioModal() {
      const modal = document.getElementById('usuarioModal');
      modal.classList.add('hidden');
    }

    function editUsuario(id) {
      isEditMode = true;
      document.getElementById('modalTitle').textContent = '<?php _e('edit_user'); ?>';
      
      const formData = new FormData();
      formData.append('action', 'get');
      formData.append('id', id);
      
      fetch('/src/controllers/user_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('id_usuario').value = data.data.id_usuario;
          document.getElementById('cedula').value = data.data.cedula;
          document.getElementById('nombre').value = data.data.nombre;
          document.getElementById('apellido').value = data.data.apellido;
          document.getElementById('email').value = data.data.email;
          document.getElementById('telefono').value = data.data.telefono || '';
          document.getElementById('activo').checked = data.data.activo;
          
          // Limpiar checkboxes de roles
          document.querySelectorAll('input[name="roles[]"]').forEach(checkbox => {
            checkbox.checked = false;
          });
          
          // Marcar roles del usuario
          if (data.data.role_names && data.data.role_names.length > 0) {
            data.data.role_names.forEach(roleName => {
              const checkbox = document.querySelector(`input[name="roles[]"][value="${roleName}"]`);
              if (checkbox) {
                checkbox.checked = true;
              }
            });
          }
          
          const modal = document.getElementById('usuarioModal');
          modal.classList.remove('hidden');
          
          // Focus on first input
          setTimeout(() => {
            document.getElementById('cedula').focus();
          }, 100);
        } else {
          showToast('Error: ' + data.message, 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Error al cargar los datos del usuario', 'error');
      });
    }

    function deleteUsuario(id, nombre) {
      const confirmMessage = `¿Está seguro de que desea eliminar al usuario "${nombre}"?`;
      if (confirm(confirmMessage)) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('/src/controllers/user_handler.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showToast('Usuario eliminado exitosamente', 'success');
            setTimeout(() => location.reload(), 1000);
          } else {
            showToast('Error: ' + data.message, 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('Error al eliminar el usuario', 'error');
        });
      }
    }

    // Form submission
    document.getElementById('usuarioForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      formData.append('action', isEditMode ? 'update' : 'create');
      
      fetch('/src/controllers/user_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast(isEditMode ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente', 'success');
          closeUsuarioModal();
          setTimeout(() => location.reload(), 1000);
        } else {
          showToast('Error: ' + data.message, 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Error al procesar la solicitud', 'error');
      });
    });

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
          const confirmMessage = '<?php _e('confirm_logout'); ?>';
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
  </script>
</body>
</html>
