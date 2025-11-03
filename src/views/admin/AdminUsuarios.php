<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../components/Breadcrumb.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Usuario.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-usuarios.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$dbConfig = require __DIR__ . '/../../config/database.php';
$database = new Database($dbConfig);

$usuarioModel = new Usuario($database->getConnection());
$usuarios = $usuarioModel->getAllUsuarios();
$roles = $usuarioModel->getAllRoles();

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
    <?php echo Sidebar::getStyles(); ?>
    <script src="/js/multiple-selection.js"></script>
    <script src="/js/status-labels.js"></script>
    <script src="/js/pagination.js"></script>
    <script src="/js/filter-manager.js"></script>
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
      background-color: rgba(0, 0, 0, 0.5) !important;
      backdrop-filter: blur(8px) !important;
      -webkit-backdrop-filter: blur(8px) !important;
      padding: 1rem !important;
    }
    
    #usuarioModal {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      width: 100% !important;
      height: 100% !important;
      background: rgba(0, 0, 0, 0.5) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      z-index: 10000 !important;
    }
    
    #usuarioModal.hidden {
      display: none !important;
    }
    
    #usuarioModal .modal-content {
      position: relative !important;
      z-index: 10001 !important;
      background: white !important;
      border-radius: 12px !important;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
      max-width: 90vw !important;
      width: 100% !important;
      max-height: 90vh !important;
      overflow-y: auto !important;
      animation: modalSlideIn 0.3s ease-out !important;
    }
    
    @media (min-width: 640px) {
      #usuarioModal .modal-content {
        max-width: 500px !important;
      }
    }
    
    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    
    #usuarioModal button[type="submit"], 
    #usuarioModal button[type="button"] {
      z-index: 10002 !important;
      position: relative !important;
      background-color: #1f366d !important;
      color: white !important;
      transition: all 0.2s ease !important;
    }
    
    #usuarioModal button[type="submit"]:hover, 
    #usuarioModal button[type="button"]:hover {
      background-color: #1a2d5a !important;
      transform: translateY(-1px) !important;
    }

    #usuarioModal input:focus,
    #usuarioModal select:focus,
    #usuarioModal textarea:focus,
    #usuarioModal button:focus {
      outline: 2px solid #1f366d !important;
      outline-offset: 2px !important;
    }

    .error-input {
      border-color: #ef4444 !important;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }

    .sr-only {
      position: absolute !important;
      width: 1px !important;
      height: 1px !important;
      padding: 0 !important;
      margin: -1px !important;
      overflow: hidden !important;
      clip: rect(0, 0, 0, 0) !important;
      white-space: nowrap !important;
      border: 0 !important;
    }

    @media (max-width: 640px) {
      #usuarioModal {
        padding: 0.5rem !important;
      }
      
      #usuarioModal .modal-content {
        max-height: 95vh !important;
        border-radius: 8px !important;
      }
    }
  </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
  <div class="flex min-h-screen">
    <?php echo $sidebar->render(); ?>

    <!-- Main -->
    <main class="flex-1 flex flex-col ml-0 md:ml-56 lg:ml-64 transition-all">
      <!-- Mobile Sidebar Overlay -->
      <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden sm:hidden" onclick="toggleSidebar()"></div>
      
      <!-- Header -->
      <header class="bg-darkblue px-4 md:px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder relative z-30">
        <button id="sidebarToggle" class="sm:hidden p-2 rounded-md hover:bg-navy transition-colors text-white" onclick="toggleSidebar()" aria-label="Toggle sidebar">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
        
        <!-- Título centrado -->
        <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
        <div class="text-white text-sm font-semibold text-center sm:hidden"><?php _e('welcome'); ?></div>
        
        <!-- Contenedor de iconos a la derecha -->
        <div class="flex items-center">
          <?php echo $languageSwitcher->render('', 'mr-2 md:mr-4'); ?>
          <button class="mr-2 md:mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
            <span class="text-white text-sm">🔔</span>
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
                <span class="inline mr-2 text-xs">👤</span>
                <?php _e('profile'); ?>
              </a>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                <span class="inline mr-2 text-xs">⚙</span>
                <?php _e('settings'); ?>
              </a>
              <div class="border-t"></div>
              <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                <span class="inline mr-2 text-xs">🚪</span>
                <?php _e('logout'); ?>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Contenido principal - Centrado -->
      <section class="flex-1 p-3 sm:p-4 md:p-6 w-full overflow-x-hidden">
        <div class="max-w-6xl mx-auto">
          <!-- Breadcrumbs (RF073) -->
          <?php 
            $breadcrumb = Breadcrumb::forAdmin([
                ['label' => _e('users_management') ?? 'Users Management', 'url' => '#']
            ]);
            echo $breadcrumb->render();
          ?>
          
          <div class="mb-6 md:mb-8">
            <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('users_management'); ?></h2>
            <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('users_management_description'); ?></p>
          </div>

          <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8" data-default-labels='["Estados"]'>
            <!-- Header de la tabla -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-3 md:p-4 border-b border-gray-200 bg-gray-50 gap-3 md:gap-0">
              <div class="flex items-center">
                <div class="select-all-container">
                  <input type="checkbox" id="selectAll" class="item-checkbox">
                  <label for="selectAll" class="text-sm md:text-base"><?php _e('select_all'); ?></label>
                </div>
                <h3 class="font-medium text-darktext ml-3 md:ml-4 text-sm md:text-base"><?php _e('users'); ?></h3>
              </div>
              <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <div class="relative w-full sm:w-auto">
                  <input type="text" id="searchInput" placeholder="<?php _e('search_users'); ?>" 
                         class="w-full py-2 px-3 md:px-4 pr-10 border border-gray-300 rounded text-xs md:text-sm focus:ring-darkblue focus:border-darkblue"
                         onkeyup="searchUsuarios(this.value)">
                </div>
                <div class="flex gap-2">
                  <button class="py-2 px-3 md:px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-xs md:text-sm bg-white text-gray-700 hover:bg-gray-50">
                    <?php _e('export'); ?>
                  </button>
                  <button onclick="openUsuarioModal()" class="py-2 px-3 md:px-4 border-none rounded cursor-pointer font-medium transition-all text-xs md:text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                    <span class="mr-1 text-sm">+</span>
                    <?php _e('add_user'); ?>
                  </button>
                </div>
              </div>
            </div>

            <!-- Bulk Actions Bar -->
            <div id="bulkActions" class="bulk-actions hidden">
              <div class="flex items-center justify-between">
                <div class="selection-info">
                  <span data-selection-count>0</span> <?php _e('selected_items'); ?>
                </div>
                <div class="action-buttons">
                  <button data-bulk-action="export" class="btn-export">
                    <?php _e('bulk_export'); ?>
                  </button>
                  <button data-bulk-action="delete" class="btn-delete">
                    <?php _e('bulk_delete'); ?>
                  </button>
                </div>
              </div>
              <!-- Statistics Container -->
              <div id="statisticsContainer"></div>
            </div>

            <!-- Filter Result Count (RF080) -->
            <div id="filterResultCount" class="px-4 py-2"></div>

            <!-- Lista de usuarios -->
            <div id="usuariosList" class="divide-y divide-gray-200">
              <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $usuario): ?>
                  <article class="usuario-item item-row flex flex-col md:flex-row items-start md:items-center justify-between p-3 md:p-4 transition-colors hover:bg-lightbg" 
                           data-nombre="<?php echo htmlspecialchars(strtolower($usuario['nombre'] . ' ' . $usuario['apellido'])); ?>"
                           data-apellido="<?php echo htmlspecialchars(strtolower($usuario['apellido'])); ?>"
                           data-email="<?php echo htmlspecialchars(strtolower($usuario['email'])); ?>"
                           data-cedula="<?php echo htmlspecialchars($usuario['cedula']); ?>"
                           data-item-id="<?php echo $usuario['id_usuario']; ?>"
                           data-original-text=""
                           data-available-labels="<?php 
                               $labels = [];
                               $labels[] = 'Email: ' . htmlspecialchars($usuario['email']);
                               $labels[] = 'CI: ' . htmlspecialchars($usuario['cedula']);
                               if ($usuario['roles'] && $usuario['roles'] !== 'Sin roles') {
                                   $labels[] = 'Roles: ' . htmlspecialchars($usuario['roles']);
                                   $labels[] = 'Estado: Con roles';
                               } else {
                                   $labels[] = 'Estado: Sin roles';
                               }
                               echo implode('|', $labels);
                           ?>"
                           data-label-mapping="<?php 
                               $mapping = [];
                               $mapping['Estados'] = ($usuario['roles'] && $usuario['roles'] !== 'Sin roles') ? 'Estado: Con roles' : 'Estado: Sin roles';
                               $mapping['Información'] = 'Email: ' . htmlspecialchars($usuario['email']) . ' | CI: ' . htmlspecialchars($usuario['cedula']);
                               if ($usuario['roles'] && $usuario['roles'] !== 'Sin roles') {
                                   $mapping['Roles'] = 'Roles: ' . htmlspecialchars($usuario['roles']);
                               }
                               echo htmlspecialchars(json_encode($mapping));
                           ?>">
                    <div class="flex items-center w-full md:w-auto">
                      <div class="checkbox-container">
                        <input type="checkbox" class="item-checkbox" data-item-id="<?php echo $usuario['id_usuario']; ?>">
                      </div>
                      <div class="avatar w-8 h-8 md:w-10 md:h-10 rounded-full bg-darkblue mr-2 md:mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold text-xs md:text-sm">
                        <?php echo getUserInitials($usuario['nombre'], $usuario['apellido']); ?>
                      </div>
                      <div class="meta flex-1 min-w-0">
                        <div class="font-semibold text-darktext mb-1 text-sm md:text-base">
                          <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                        </div>
                        <div class="text-muted text-xs md:text-sm">
                          <div class="block md:hidden">
                            <?php echo htmlspecialchars($usuario['email']); ?>
                          </div>
                          <div class="hidden md:block">
                            <?php echo htmlspecialchars($usuario['email']); ?> | 
                            CI: <?php echo htmlspecialchars($usuario['cedula']); ?> | 
                            <?php _e('roles'); ?>: <?php echo htmlspecialchars($usuario['roles'] ?? 'Sin roles'); ?>
                          </div>
                          <div class="block md:hidden text-xs">
                            CI: <?php echo htmlspecialchars($usuario['cedula']); ?> | 
                            <?php _e('roles'); ?>: <?php echo htmlspecialchars($usuario['roles'] ?? 'Sin roles'); ?>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2 mt-2 md:mt-0">
                      <button onclick="editUsuario(<?php echo $usuario['id_usuario']; ?>)" 
                              class="text-darkblue hover:text-navy text-xs md:text-sm font-medium transition-colors">
                        <?php _e('edit'); ?>
                      </button>
                      <button onclick="deleteUsuario(<?php echo $usuario['id_usuario']; ?>, '<?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>')" 
                              class="text-red-600 hover:text-red-800 text-xs md:text-sm font-medium transition-colors">
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

            <!-- Table Summary (RF083) -->
            <?php if (!empty($usuarios)): ?>
            <div class="bg-gray-50 border-t border-gray-200 px-4 py-3">
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-sm">
                <div class="text-gray-600">
                  <strong>Total:</strong> <?php echo count($usuarios); ?> <?php _e('users'); ?>
                </div>
                <?php 
                  $usuariosConRoles = count(array_filter($usuarios, function($u) { 
                    return isset($u['roles']) && $u['roles'] !== 'Sin roles' && !empty($u['roles']); 
                  }));
                ?>
                <div class="text-gray-600">
                  <strong>Con roles:</strong> <?php echo $usuariosConRoles; ?> | 
                  <strong>Sin roles:</strong> <?php echo count($usuarios) - $usuariosConRoles; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Pagination Container (RF082) -->
            <div id="paginationContainer"></div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- Modal para agregar/editar usuario -->
  <div id="usuarioModal" class="hidden" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDescription">
    <div class="modal-content p-4 md:p-8 w-full max-w-sm md:max-w-md mx-auto">
      <div class="flex justify-between items-center mb-4 md:mb-6">
        <h3 id="modalTitle" class="text-base md:text-lg font-semibold text-gray-900"><?php _e('add_user'); ?></h3>
        <button onclick="closeUsuarioModal()" class="text-gray-400 hover:text-gray-600" aria-label="<?php _e('close_modal'); ?>" tabindex="0">
          <span class="text-sm" aria-hidden="true">×</span>
        </button>
      </div>
      <p id="modalDescription" class="text-xs md:text-sm text-gray-600 mb-4 md:mb-6 sr-only"><?php _e('modal_description'); ?></p>

      <form id="usuarioForm" class="space-y-4">
        <input type="hidden" id="id_usuario" name="id">
        
        <div>
          <label for="cedula" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('cedula'); ?> <span class="text-red-500">*</span></label>
          <input type="text" id="cedula" name="cedula" required maxlength="8" pattern="[0-9]{8}" 
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                 placeholder="12345678" aria-describedby="cedulaError cedulaHelp">
          <p id="cedulaHelp" class="text-xs text-gray-500 mt-1"><?php _e('cedula_help'); ?></p>
          <p id="cedulaError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
        </div>

        <div>
          <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('name'); ?> <span class="text-red-500">*</span></label>
          <input type="text" id="nombre" name="nombre" required maxlength="100" 
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                 placeholder="<?php _e('name_placeholder'); ?>" aria-describedby="nombreError">
          <p id="nombreError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
        </div>

        <div>
          <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('lastname'); ?> <span class="text-red-500">*</span></label>
          <input type="text" id="apellido" name="apellido" required maxlength="100"
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                 placeholder="<?php _e('lastname_placeholder'); ?>" aria-describedby="apellidoError">
          <p id="apellidoError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('email'); ?> <span class="text-red-500">*</span></label>
          <input type="email" id="email" name="email" required maxlength="150"
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                 placeholder="usuario@ejemplo.com" aria-describedby="emailError">
          <p id="emailError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
        </div>

        <div>
          <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('phone'); ?></label>
          <input type="tel" id="telefono" name="telefono" maxlength="20" pattern="[0-9\s\-\+\(\)]+"
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                 placeholder="099123456" aria-describedby="telefonoError telefonoHelp">
          <p id="telefonoHelp" class="text-xs text-gray-500 mt-1"><?php _e('phone_help'); ?></p>
          <p id="telefonoError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
        </div>

        <div>
          <label for="contrasena" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('password'); ?> <span id="passwordRequired" class="text-red-500" style="display: none;">*</span></label>
          <div class="relative">
            <input type="password" id="contrasena" name="contrasena" minlength="8" maxlength="255"
                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                   placeholder="<?php _e('password_placeholder'); ?>" aria-describedby="contrasenaError contrasenaHelp">
            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors" aria-label="<?php _e('toggle_password_visibility'); ?>">
              <svg id="passwordIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
            </button>
          </div>
          <p id="contrasenaHelp" class="text-xs text-gray-500 mt-1"><?php _e('password_help'); ?></p>
          <p id="contrasenaError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2"><?php _e('roles'); ?> <span class="text-red-500">*</span></label>
          <div class="space-y-2" role="group" aria-labelledby="roles-label">
            <?php foreach ($roles as $role): ?>
              <label class="flex items-center">
                <input type="checkbox" name="roles[]" value="<?php echo $role['nombre_rol']; ?>" 
                       class="h-4 w-4 text-darkblue focus:ring-darkblue border-gray-300 rounded"
                       aria-describedby="rolesError">
                <span class="ml-2 text-sm text-gray-900"><?php echo htmlspecialchars($role['nombre_rol']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <p id="rolesError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
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
  <script src="/js/toast.js"></script>
  <script>
    let isEditMode = false;

    function openUsuarioModal() {
      isEditMode = false;
      document.getElementById('modalTitle').textContent = '<?php _e('add_user'); ?>';
      document.getElementById('usuarioForm').reset();
      document.getElementById('id_usuario').value = '';
      
      document.querySelectorAll('input[name="roles[]"]').forEach(checkbox => {
        checkbox.checked = false;
      });
      
      document.getElementById('passwordRequired').style.display = 'inline';
      document.getElementById('contrasena').required = true;
      
      clearAllErrors();
      
      const modal = document.getElementById('usuarioModal');
      modal.classList.remove('hidden');
      setTimeout(() => {
        document.getElementById('cedula').focus();
      }, 100);
    }

    function closeUsuarioModal() {
      const modal = document.getElementById('usuarioModal');
      modal.classList.add('hidden');
      clearAllErrors();
      // RF078: Cancel button clears form fields
      const form = document.getElementById('usuarioForm');
      if (form) {
        form.reset();
        document.getElementById('id_usuario').value = '';
        document.querySelectorAll('input[name="roles[]"]').forEach(checkbox => {
          checkbox.checked = false;
        });
        isEditMode = false;
      }
    }

    // RNF008: Keyboard navigation support
    document.addEventListener('DOMContentLoaded', function() {
      // Escape key to close modal
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const usuarioModal = document.getElementById('usuarioModal');
          if (usuarioModal && !usuarioModal.classList.contains('hidden')) {
            closeUsuarioModal();
          }
        }
      });
      
      // Enter key to submit form (when focused on submit button or last input)
      const usuarioForm = document.getElementById('usuarioForm');
      if (usuarioForm) {
        usuarioForm.addEventListener('keydown', function(e) {
          if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            const submitButton = usuarioForm.querySelector('button[type="submit"]');
            if (submitButton && document.activeElement === e.target) {
              e.preventDefault();
              usuarioForm.requestSubmit();
            }
          }
        });
      }
    });

    function editUsuario(id) {
      isEditMode = true;
      document.getElementById('modalTitle').textContent = '<?php _e('edit_user'); ?>';
      
      document.getElementById('passwordRequired').style.display = 'none';
      document.getElementById('contrasena').required = false;
      
      const formData = new FormData();
      formData.append('action', 'get');
      formData.append('id', id);
      
      fetch('/src/controllers/UserHandler.php', {
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
          
          document.querySelectorAll('input[name="roles[]"]').forEach(checkbox => {
            checkbox.checked = false;
          });
          
          if (data.data.role_names && data.data.role_names.length > 0) {
            data.data.role_names.forEach(roleName => {
              const checkbox = document.querySelector(`input[name="roles[]"][value="${roleName}"]`);
              if (checkbox) {
                checkbox.checked = true;
              }
            });
          }
          
          clearAllErrors();
          
          const modal = document.getElementById('usuarioModal');
          modal.classList.remove('hidden');
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

    async function deleteUsuario(id, nombre) {
      const confirmMessage = `¿Está seguro de que desea eliminar al usuario "${nombre}"?`;
      const confirmed = await showConfirmModal(
        '<?php _e('confirm_delete'); ?>',
        confirmMessage,
        '<?php _e('confirm'); ?>',
        '<?php _e('cancel'); ?>'
      );
      if (confirmed) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('/src/controllers/UserHandler.php', {
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

    function validateForm() {
      let isValid = true;
      clearAllErrors();
      
      const cedula = document.getElementById('cedula').value.trim();
      if (!cedula) {
        showFieldError('cedula', '<?php _e('cedula_required'); ?>');
        isValid = false;
      } else if (!/^[0-9]{8}$/.test(cedula)) {
        showFieldError('cedula', '<?php _e('cedula_invalid_format'); ?>');
        isValid = false;
      }
      
      const nombre = document.getElementById('nombre').value.trim();
      if (!nombre) {
        showFieldError('nombre', '<?php _e('name_required'); ?>');
        isValid = false;
      } else if (nombre.length < 2) {
        showFieldError('nombre', '<?php _e('name_too_short'); ?>');
        isValid = false;
      }
      
      const apellido = document.getElementById('apellido').value.trim();
      if (!apellido) {
        showFieldError('apellido', '<?php _e('lastname_required'); ?>');
        isValid = false;
      } else if (apellido.length < 2) {
        showFieldError('apellido', '<?php _e('lastname_too_short'); ?>');
        isValid = false;
      }
      
      const email = document.getElementById('email').value.trim();
      if (!email) {
        showFieldError('email', '<?php _e('email_required'); ?>');
        isValid = false;
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showFieldError('email', '<?php _e('email_invalid_format'); ?>');
        isValid = false;
      }
      
      const telefono = document.getElementById('telefono').value.trim();
      if (telefono && !/^[0-9\s\-\+\(\)]+$/.test(telefono)) {
        showFieldError('telefono', '<?php _e('phone_invalid_format'); ?>');
        isValid = false;
      }
      
      const contrasena = document.getElementById('contrasena').value;
      if (!isEditMode && (!contrasena || contrasena.length < 8)) {
        showFieldError('contrasena', '<?php _e('password_required_min_length'); ?>');
        isValid = false;
      } else if (contrasena && contrasena.length < 8) {
        showFieldError('contrasena', '<?php _e('password_min_length'); ?>');
        isValid = false;
      }
      
      const roles = document.querySelectorAll('input[name="roles[]"]:checked');
      if (roles.length === 0) {
        showFieldError('roles', '<?php _e('at_least_one_role_required'); ?>');
        isValid = false;
      }
      
      return isValid;
    }
    
    function showFieldError(fieldName, message) {
      const errorElement = document.getElementById(fieldName + 'Error');
      const inputElement = document.getElementById(fieldName);
      
      if (errorElement) {
        errorElement.textContent = message;
      }
      
      if (inputElement) {
        inputElement.classList.add('error-input');
      }
    }
    
    function clearAllErrors() {
      const errorElements = document.querySelectorAll('[id$="Error"]');
      errorElements.forEach(element => {
        element.textContent = '';
      });
      
      const inputElements = document.querySelectorAll('input, select, textarea');
      inputElements.forEach(element => {
        element.classList.remove('error-input');
      });
    }

    function togglePasswordVisibility() {
      const passwordInput = document.getElementById('contrasena');
      const passwordIcon = document.getElementById('passwordIcon');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        `;
      } else {
        passwordInput.type = 'password';
        passwordIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
      }
    }

    function validateField(field) {
      const value = field.value.trim();
      const fieldName = field.id;

      field.classList.remove('error-input');
      const errorElement = document.getElementById(fieldName + 'Error');
      if (errorElement) {
        errorElement.textContent = '';
      }
      
      switch (fieldName) {
        case 'cedula':
          if (!value) {
            showFieldError('cedula', '<?php _e('cedula_required'); ?>');
          } else if (!/^[0-9]{8}$/.test(value)) {
            showFieldError('cedula', '<?php _e('cedula_invalid_format'); ?>');
          }
          break;
          
        case 'nombre':
          if (!value) {
            showFieldError('nombre', '<?php _e('name_required'); ?>');
          } else if (value.length < 2) {
            showFieldError('nombre', '<?php _e('name_too_short'); ?>');
          }
          break;
          
        case 'apellido':
          if (!value) {
            showFieldError('apellido', '<?php _e('lastname_required'); ?>');
          } else if (value.length < 2) {
            showFieldError('apellido', '<?php _e('lastname_too_short'); ?>');
          }
          break;
          
        case 'email':
          if (!value) {
            showFieldError('email', '<?php _e('email_required'); ?>');
          } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            showFieldError('email', '<?php _e('email_invalid_format'); ?>');
          }
          break;
          
        case 'telefono':
          if (value && !/^[0-9\s\-\+\(\)]+$/.test(value)) {
            showFieldError('telefono', '<?php _e('phone_invalid_format'); ?>');
          }
          break;
          
        case 'contrasena':
          if (!isEditMode && (!value || value.length < 8)) {
            showFieldError('contrasena', '<?php _e('password_required_min_length'); ?>');
          } else if (value && value.length < 8) {
            showFieldError('contrasena', '<?php _e('password_min_length'); ?>');
          }
          break;
      }
    }

    document.getElementById('usuarioForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      if (!validateForm()) {
        showToast('<?php _e('please_correct_errors'); ?>', 'error');
        return;
      }
      
      const formData = new FormData(this);
      formData.append('action', isEditMode ? 'update' : 'create');
      
      fetch('/src/controllers/UserHandler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast(isEditMode ? '<?php _e('user_updated_successfully'); ?>' : '<?php _e('user_created_successfully'); ?>', 'success');
          closeUsuarioModal();
          setTimeout(() => location.reload(), 1000);
        } else {
          if (data.data && typeof data.data === 'object') {
            Object.keys(data.data).forEach(field => {
              showFieldError(field, data.data[field]);
            });
          } else {
            showToast('Error: ' + data.message, 'error');
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('<?php _e('error_processing_request'); ?>', 'error');
      });
    });

    document.addEventListener('DOMContentLoaded', function() {

      const togglePasswordBtn = document.getElementById('togglePassword');
      if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', togglePasswordVisibility);
      }

      const formInputs = document.querySelectorAll('#usuarioForm input, #usuarioForm select');
      formInputs.forEach(input => {
        input.addEventListener('blur', function() {
          validateField(this);
        });
        
        input.addEventListener('input', function() {

          this.classList.remove('error-input');
          const errorElement = document.getElementById(this.id + 'Error');
          if (errorElement) {
            errorElement.textContent = '';
          }
        });
      });

      const sidebarLinks = document.querySelectorAll('.sidebar-link');

      function handleSidebarClick(event) {

        sidebarLinks.forEach(link => {
          link.classList.remove('active');
        });

        this.classList.add('active');
      }

      sidebarLinks.forEach(link => {
        link.addEventListener('click', handleSidebarClick);
      });

      const logoutButton = document.getElementById('logoutButton');
      if (logoutButton) {
        logoutButton.addEventListener('click', async function(e) {
          e.preventDefault();
          
          const confirmed = await showConfirmModal(
            '<?php _e('confirm_logout'); ?>',
            '<?php _e('confirm_logout_message'); ?>',
            '<?php _e('confirm'); ?>',
            '<?php _e('cancel'); ?>'
          );
          if (confirmed) {
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

      const userMenuButton = document.getElementById('userMenuButton');
      const userMenu = document.getElementById('userMenu');
      
      if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', function(e) {
          e.stopPropagation();
          userMenu.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function(e) {
          if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.add('hidden');
          }
        });
      }

      const multipleSelection = new MultipleSelection({
        container: document.querySelector('.bg-white.rounded-lg.shadow-sm'),
        itemSelector: '.item-row',
        checkboxSelector: '.item-checkbox',
        selectAllSelector: '#selectAll',
        bulkActionsSelector: '#bulkActions',
        entityType: 'usuarios',
        onSelectionChange: function(selectedItems) {

        },
        onBulkAction: function(action, selectedIds) {

        }
      });

      const statusLabels = new StatusLabels({
        container: document.querySelector('.bg-white.rounded-lg.shadow-sm'),
        itemSelector: '.item-row',
        metaSelector: '.meta .text-muted',
        entityType: 'usuarios'
      });

      // Initialize Pagination (RF082)
      const totalUsuarios = <?php echo count($usuarios); ?>;
      const paginationContainer = document.getElementById('paginationContainer');
      
      if (paginationContainer && totalUsuarios > 0) {
        window.paginationManager = new PaginationManager({
          container: paginationContainer,
          currentPage: 1,
          perPage: 10,
          totalRecords: totalUsuarios,
          onPageChange: function(page) {
            updateVisibleItems(page);
          },
          onPerPageChange: function(perPage) {
            updateVisibleItems(1);
          }
        });
        
        updateVisibleItems(1);
      }

      // Initialize Filter Manager (RF080)
      const filterContainer = document.querySelector('.bg-white.rounded-lg.shadow-sm');
      const filterResultCount = document.getElementById('filterResultCount');
      
      if (filterContainer && filterResultCount) {
        window.filterManager = new FilterManager({
          container: filterContainer,
          resultCountContainer: filterResultCount,
          totalCount: totalUsuarios,
          filteredCount: totalUsuarios,
          onFilterChange: function(filters) {
            applyFilters(filters);
          }
        });
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
          searchInput.setAttribute('data-filter', 'search');
        }
        
        window.filterManager.init();
        window.filterManager.updateResultCount(totalUsuarios, totalUsuarios);
      }
    });

    // Update visible items based on pagination
    function updateVisibleItems(page) {
      if (!window.paginationManager) return;
      
      const state = window.paginationManager.getState();
      const allItems = Array.from(document.querySelectorAll('.usuario-item'));
      
      const visibleItems = allItems.filter(item => {
        const filters = window.filterManager ? window.filterManager.getFilters() : {};
        let matches = true;
        
        if (filters.search && filters.search.trim() !== '') {
          const searchLower = filters.search.toLowerCase().trim();
          const nombre = item.dataset.nombre || '';
          const apellido = item.dataset.apellido || '';
          const email = item.dataset.email || '';
          const cedula = item.dataset.cedula || '';
          
          if (!nombre.includes(searchLower) && !apellido.includes(searchLower) && 
              !email.includes(searchLower) && !cedula.includes(searchLower)) {
            matches = false;
          }
        }
        
        return matches;
      });
      
      const startIndex = (state.currentPage - 1) * state.perPage;
      const endIndex = startIndex + state.perPage;
      
      allItems.forEach(item => {
        item.style.display = 'none';
      });
      
      visibleItems.slice(startIndex, endIndex).forEach(item => {
        item.style.display = 'flex';
      });
    }

    // Apply filters and update counts
    function applyFilters(filters) {
      const allItems = document.querySelectorAll('.usuario-item');
      let visibleCount = 0;
      
      allItems.forEach(item => {
        let matches = true;
        
        if (filters.search && filters.search.trim() !== '') {
          const searchLower = filters.search.toLowerCase().trim();
          const nombre = item.dataset.nombre || '';
          const apellido = item.dataset.apellido || '';
          const email = item.dataset.email || '';
          const cedula = item.dataset.cedula || '';
          
          if (!nombre.includes(searchLower) && !apellido.includes(searchLower) && 
              !email.includes(searchLower) && !cedula.includes(searchLower)) {
            matches = false;
          }
        }
        
        if (matches) {
          item.style.display = 'flex';
          visibleCount++;
        } else {
          item.style.display = 'none';
        }
      });
      
      if (window.filterManager) {
        window.filterManager.updateResultCount(visibleCount, allItems.length);
      }
      
      if (window.paginationManager) {
        window.paginationManager.currentPage = 1;
        window.paginationManager.updateTotalRecords(visibleCount);
        setTimeout(() => updateVisibleItems(1), 0);
      } else {
        allItems.forEach(item => {
          item.style.display = 'flex';
        });
      }
    }

    function searchUsuarios(searchTerm) {
      const usuarios = document.querySelectorAll('.usuario-item');
      const searchLower = searchTerm.toLowerCase().trim();
      
      if (searchLower === '') {
        usuarios.forEach(usuario => {
          usuario.style.display = 'flex';
        });
        return;
      }

      usuarios.forEach(usuario => {
        const nombre = usuario.dataset.nombre || '';
        const apellido = usuario.dataset.apellido || '';
        const email = usuario.dataset.email || '';
        const cedula = usuario.dataset.cedula || '';
        
        if (nombre.includes(searchLower) || apellido.includes(searchLower) || 
            email.includes(searchLower) || cedula.includes(searchLower)) {
          usuario.style.display = 'flex';
        } else {
          usuario.style.display = 'none';
        }
      });

      const visibleUsuarios = Array.from(usuarios).filter(usuario => usuario.style.display !== 'none');
      const noResultsMessage = document.getElementById('noResultsMessage');
      
      if (visibleUsuarios.length === 0 && searchLower !== '') {
        if (!noResultsMessage) {
          const usuariosList = document.getElementById('usuariosList');
          const messageDiv = document.createElement('div');
          messageDiv.id = 'noResultsMessage';
          messageDiv.className = 'p-8 text-center';
          messageDiv.innerHTML = `
            <div class="text-gray-500 text-lg mb-2">No se encontraron usuarios que coincidan con "${searchTerm}"</div>
            <div class="text-gray-400 text-sm">Intente con un término de búsqueda diferente</div>
          `;
          usuariosList.appendChild(messageDiv);
        }
      } else if (noResultsMessage) {
        noResultsMessage.remove();
      }
    }
  </script>
</body>
</html>
