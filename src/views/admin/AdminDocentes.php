<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Docente.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../components/Breadcrumb.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-docentes.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

$dbConfig = require __DIR__ . '/../../config/database.php';
$database = new Database($dbConfig);

$docenteModel = new Docente($database->getConnection());
$docentes = $docenteModel->getAllDocentes();

// Load subjects and assignments data
require_once __DIR__ . '/../../models/Materia.php';
require_once __DIR__ . '/../../app/Models/Assignment.php';

$materiaModel = new Materia($database->getConnection());
$materias = $materiaModel->getAllMaterias();

$assignmentModel = new \App\Models\Assignment($database->getConnection());
$assignments = $assignmentModel->getAllAssignments();

function getUserInitials($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}

function getTeacherAssignments($teacherId, $assignments) {
    return array_filter($assignments, function($assignment) use ($teacherId) {
        return $assignment['id_docente'] == $teacherId;
    });
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('teachers_management'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <script src="/js/multiple-selection.js"></script>
    <script src="/js/status-labels.js"></script>
    <script src="/js/pagination.js"></script>
    <script src="/js/filter-manager.js"></script>
  <style type="text/css">
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
    
    /* Modal styles - removed conflicting general styles */

    #docenteModal {
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
      padding: 1rem !important;
      width: 100vw !important;
      height: 100vh !important;
    }
    
    #docenteModal.hidden {
      display: none !important;
    }
    
    
#docenteModal .modal-content {
  position: relative !important;
  z-index: 10001 !important;
  background: white !important;
  border-radius: 12px !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
  max-height: 90vh !important;
  max-width: 90vw !important;
  width: 100% !important;
  overflow-y: auto !important;
  margin: 0 auto !important;
  animation: modalSlideIn 0.3s ease-out !important;
  transform: none !important;
  top: auto !important;
  left: auto !important;
}

@media (min-width: 640px) {
  #docenteModal .modal-content {
    max-width: 500px !important;
  }
}

#docenteModal button[type="submit"],
#docenteModal button[type="button"],
#assignmentModal button[type="submit"],
#assignmentModal button[type="button"] {
  z-index: 10002 !important;
  position: relative !important;
  background-color: #1f366d !important;
  color: white !important;
}

/* Mejoras específicas para el modal de asignaciones */
#assignmentModal {
  backdrop-filter: blur(4px) !important;
}

#assignmentModal .modal-content {
  animation: modalSlideIn 0.3s ease-out !important;
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

/* Responsive modal behavior */
@media (max-width: 640px) {
  #docenteModal {
    padding: 0.5rem !important;
  }
  
  #docenteModal .modal-content {
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
                ['label' => _e('teachers_management') ?? 'Teachers Management', 'url' => '#']
            ]);
            echo $breadcrumb->render();
          ?>
          
          <div class="mb-6 md:mb-8">
            <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('teachers_management'); ?></h2>
            <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('teachers_management_description'); ?></p>
          </div>

          <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8" data-default-labels='["Estados", "Disponibilidad"]'>
            <!-- Header de la tabla -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-3 md:p-4 border-b border-gray-200 bg-gray-50 gap-3 md:gap-0">
              <div class="flex items-center">
                <div class="select-all-container">
                  <input type="checkbox" id="selectAll" class="item-checkbox">
                  <label for="selectAll" class="text-sm md:text-base"><?php _e('select_all'); ?></label>
                </div>
                <h3 class="font-medium text-darktext ml-3 md:ml-4 text-sm md:text-base"><?php _e('teachers'); ?></h3>
              </div>
              <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <div class="relative w-full sm:w-auto">
                  <input type="text" id="searchInput" placeholder="<?php _e('search_teachers'); ?>" 
                         class="w-full py-2 px-3 md:px-4 pr-10 border border-gray-300 rounded text-xs md:text-sm focus:ring-darkblue focus:border-darkblue"
                         onkeyup="searchDocentes(this.value)">
                </div>
                <div class="flex gap-2">
                  <button class="py-2 px-3 md:px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-xs md:text-sm bg-white text-gray-700 hover:bg-gray-50">
                    <?php _e('export'); ?>
                  </button>
                  <button onclick="showAddDocenteModal()" class="py-2 px-3 md:px-4 border-none rounded cursor-pointer font-medium transition-all text-xs md:text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                    <span class="mr-1 text-sm">+</span>
                    <?php _e('add_teacher'); ?>
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

            <!-- Lista de docentes -->
            <div id="docentesList" class="divide-y divide-gray-200">
              <?php if (!empty($docentes)): ?>
                <?php foreach ($docentes as $docente): ?>
                  <?php 
                    $teacherAssignments = getTeacherAssignments($docente['id_docente'], $assignments);
                    $hasAssignments = count($teacherAssignments) > 0;
                    $assignmentCount = count($teacherAssignments);
                  ?>
                  <article class="docente-item item-row flex items-center justify-between p-4 transition-colors hover:bg-lightbg" 
                           data-nombre="<?php echo htmlspecialchars(strtolower($docente['nombre'] . ' ' . $docente['apellido'])); ?>"
                           data-apellido="<?php echo htmlspecialchars(strtolower($docente['apellido'])); ?>"
                           data-email="<?php echo htmlspecialchars(strtolower($docente['email'])); ?>"
                           data-cedula="<?php echo htmlspecialchars($docente['cedula']); ?>"
                           data-item-id="<?php echo $docente['id_docente']; ?>"
                           data-original-text=""
                           data-available-labels="<?php 
                               $labels = [];
                               $labels[] = 'Email: ' . htmlspecialchars($docente['email']);
                               $labels[] = 'CI: ' . htmlspecialchars($docente['cedula']);
                               if ($hasAssignments) {
                                   $labels[] = 'Materias: ' . $assignmentCount . ' asignadas';
                                   $labels[] = 'Estado: Con asignaciones';
                               } else {
                                   $labels[] = 'Estado: Sin asignaciones';
                               }
                               if ($docente['porcentaje_margen'] !== null) {
                                   $labels[] = 'Margen: ' . number_format($docente['porcentaje_margen'], 1) . '%';
                               }
                               $labels[] = 'Horas: ' . ($docente['horas_asignadas'] ?? 0) . 'h asignadas';
                               echo implode('|', $labels);
                           ?>"
                           data-label-mapping="<?php 
                               $mapping = [];
                               $mapping['Estados'] = $hasAssignments ? 'Estado: Con asignaciones' : 'Estado: Sin asignaciones';
                               $mapping['Información'] = 'Email: ' . htmlspecialchars($docente['email']) . ' | CI: ' . htmlspecialchars($docente['cedula']);
                               if ($hasAssignments) {
                                   $mapping['Materias'] = 'Materias: ' . $assignmentCount . ' asignadas';
                               }
                               if ($docente['porcentaje_margen'] !== null) {
                                   $mapping['Disponibilidad'] = 'Margen: ' . number_format($docente['porcentaje_margen'], 1) . '%';
                               }
                               $mapping['Carga'] = 'Horas: ' . ($docente['horas_asignadas'] ?? 0) . 'h asignadas';
                               echo htmlspecialchars(json_encode($mapping));
                           ?>">
                    <div class="flex items-center">
                      <div class="checkbox-container">
                        <input type="checkbox" class="item-checkbox" data-item-id="<?php echo $docente['id_docente']; ?>">
                      </div>
                      <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                        <?php echo getUserInitials($docente['nombre'], $docente['apellido']); ?>
                      </div>
                      <div class="meta">
                        <div class="font-semibold text-darktext mb-1">
                          <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                        </div>
                        <div class="text-muted text-sm">
                          <?php echo htmlspecialchars($docente['email']); ?> | 
                          CI: <?php echo htmlspecialchars($docente['cedula']); ?>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
                      <button onclick="manageAssignments(<?php echo $docente['id_docente']; ?>, '<?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>')" 
                              class="text-green-600 hover:text-green-800 text-sm font-medium transition-colors">
                        <?php _e('manage_assignments'); ?>
                      </button>
                      <button onclick="editDocente(<?php echo $docente['id_docente']; ?>)" 
                              class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                        <?php _e('edit'); ?>
                      </button>
                      <button onclick="deleteDocente(<?php echo $docente['id_docente']; ?>, '<?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>')" 
                              class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                        <?php _e('delete'); ?>
                      </button>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="p-8 text-center">
                  <div class="text-gray-500 text-lg mb-2"><?php _e('no_teachers_found'); ?></div>
                  <div class="text-gray-400 text-sm"><?php _e('add_first_teacher'); ?></div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Table Summary (RF083) -->
            <?php if (!empty($docentes)): ?>
            <div class="bg-gray-50 border-t border-gray-200 px-4 py-3">
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-sm">
                <div class="text-gray-600">
                  <strong>Total:</strong> <?php echo count($docentes); ?> <?php _e('teachers'); ?>
                </div>
                <?php 
                  $totalHoras = array_sum(array_column($docentes, 'horas_asignadas'));
                  $avgHoras = count($docentes) > 0 ? round($totalHoras / count($docentes), 1) : 0;
                  $avgMargen = count($docentes) > 0 ? round(array_sum(array_filter(array_column($docentes, 'porcentaje_margen'))) / count(array_filter(array_column($docentes, 'porcentaje_margen'), function($v) { return $v !== null; })), 1) : 0;
                ?>
                <div class="text-gray-600">
                  <strong>Total horas asignadas:</strong> <?php echo $totalHoras; ?>h | 
                  <strong>Promedio:</strong> <?php echo $avgHoras; ?>h | 
                  <strong>Margen promedio:</strong> <?php echo $avgMargen; ?>%
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

  <!-- Modal para agregar/editar docente -->
  <div id="docenteModal" class="hidden">
    <div class="modal-content p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_teacher'); ?></h3>
                <button onclick="closeDocenteModal()" class="text-gray-400 hover:text-gray-600" aria-label="<?php _e('close_modal') ?? 'Close'; ?>" tabindex="0">
                    <span class="text-sm" aria-hidden="true">×</span>
                </button>
            </div>

            <form id="docenteForm" onsubmit="handleFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="id_docente" name="id_docente" value="">
                
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
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('email'); ?></label>
                    <input type="email" id="email" name="email" maxlength="150"
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
                    <label for="confirmar_contrasena" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('confirm_password'); ?> <span id="confirmPasswordRequired" class="text-red-500" style="display: none;">*</span></label>
                    <div class="relative">
                        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" minlength="8" maxlength="255"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                               placeholder="<?php _e('confirm_password_placeholder'); ?>" aria-describedby="confirmar_contrasenaError confirmar_contrasenaHelp">
                        <button type="button" id="toggleConfirmPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors" aria-label="<?php _e('toggle_password_visibility'); ?>">
                            <svg id="confirmPasswordIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p id="confirmar_contrasenaHelp" class="text-xs text-gray-500 mt-1"><?php _e('confirm_password_help'); ?></p>
                    <p id="confirmar_contrasenaError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeDocenteModal()" 
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

  <!-- Modal para gestionar asignaciones -->
  <div id="assignmentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 8px; padding: 1rem; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;">
        <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #111827;"><?php _e('manage_assignments'); ?></h3>
        <button onclick="closeAssignmentModal()" style="background: none; border: none; font-size: 1.5rem; color: #6b7280; cursor: pointer; padding: 0.25rem;">×</button>
      </div>

      <div style="margin-bottom: 1rem;">
        <h4 style="margin: 0 0 0.75rem 0; font-size: 0.875rem; font-weight: 500; color: #374151;" id="teacherNameDisplay"></h4>
        
        <!-- Formulario para agregar nueva asignación -->
        <div style="background: #f9fafb; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; border: 1px solid #e5e7eb;">
          <h5 style="margin: 0 0 0.5rem 0; font-size: 0.75rem; font-weight: 500; color: #374151;"><?php _e('add_new_assignment'); ?></h5>
          <form id="assignmentForm" onsubmit="handleAssignmentSubmit(event)" style="display: flex; flex-direction: column; gap: 0.5rem;">
            <input type="hidden" id="assignment_teacher_id" name="teacher_id" value="">
            
            <select id="assignment_subject_id" name="subject_id" required
                    style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem;">
              <option value=""><?php _e('select_subject'); ?></option>
              <?php foreach ($materias as $materia): ?>
                <option value="<?php echo $materia['id_materia']; ?>">
                  <?php echo htmlspecialchars($materia['nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            
            <button type="submit" 
                    style="padding: 0.5rem 1rem; background: #059669; color: white; border: none; border-radius: 4px; font-size: 0.875rem; cursor: pointer; white-space: nowrap; width: 100%;">
              <?php _e('assign'); ?>
            </button>
          </form>
        </div>

        <!-- Lista de asignaciones actuales -->
        <div>
          <h5 style="margin: 0 0 0.625rem 0; font-size: 0.875rem; font-weight: 500; color: #374151;"><?php _e('current_assignments'); ?></h5>
          <div id="assignmentsList" style="display: flex; flex-direction: column; gap: 8px;">
            <!-- Las asignaciones se cargarán dinámicamente -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div id="toastContainer">    </div>

    <script src="/js/toast.js"></script>
    <script>
        let isEditMode = false;

        function showAddDocenteModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = '<?php _e('add_teacher'); ?>';
            document.getElementById('docenteForm').reset();
            
            document.getElementById('contrasena').required = true;
            document.getElementById('confirmar_contrasena').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            document.getElementById('confirmPasswordRequired').style.display = 'inline';
            
            clearErrors();
            document.getElementById('docenteModal').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('cedula').focus();
            }, 100);
        }

        function editDocente(id) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = '<?php _e('edit_teacher'); ?>';
            
            fetch(`/teachers/${id}/edit`, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const teacher = data.data.teacher;
                    document.getElementById('id_docente').value = teacher.id_docente;
                    document.getElementById('cedula').value = teacher.cedula;
                    document.getElementById('nombre').value = teacher.nombre;
                    document.getElementById('apellido').value = teacher.apellido;
                    document.getElementById('email').value = teacher.email;
                    document.getElementById('telefono').value = teacher.telefono || '';
                    
                    document.getElementById('contrasena').required = false;
                    document.getElementById('confirmar_contrasena').required = false;
                    document.getElementById('contrasena').value = '';
                    document.getElementById('confirmar_contrasena').value = '';
                    document.getElementById('passwordRequired').style.display = 'none';
                    document.getElementById('confirmPasswordRequired').style.display = 'none';
                    
                    clearErrors();
                    document.getElementById('docenteModal').classList.remove('hidden');
                    
                    setTimeout(() => {
                        document.getElementById('cedula').focus();
                    }, 100);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error cargando datos del docente', 'error');
            });
        }

        async function deleteDocente(id, nombre) {
            const confirmMessage = `¿Está seguro de que desea eliminar al docente "${nombre}"?`;
            const confirmed = await showConfirmModal(
                '<?php _e('confirm_delete'); ?>',
                confirmMessage,
                '<?php _e('confirm'); ?>',
                '<?php _e('cancel'); ?>'
            );
            
            if (confirmed) {
                fetch(`/teachers/${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Docente eliminado exitosamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error eliminando docente', 'error');
                });
            }
        }

        function closeDocenteModal() {
            const modal = document.getElementById('docenteModal');
            modal.classList.add('hidden');
            clearErrors();
            // RF078: Cancel button clears form fields
            const form = document.getElementById('docenteForm');
            if (form) {
                form.reset();
                document.getElementById('id_docente').value = '';
                isEditMode = false;
            }
        }

        // RNF008: Keyboard navigation support
        document.addEventListener('DOMContentLoaded', function() {
            // Escape key to close modals
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const docenteModal = document.getElementById('docenteModal');
                    if (docenteModal && !docenteModal.classList.contains('hidden')) {
                        closeDocenteModal();
                    }
                    const assignmentModal = document.getElementById('assignmentModal');
                    if (assignmentModal && assignmentModal.style.display !== 'none') {
                        closeAssignmentModal();
                    }
                }
            });
            
            // Enter key to submit form (when focused on submit button or last input)
            const docenteForm = document.getElementById('docenteForm');
            if (docenteForm) {
                docenteForm.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                        const submitButton = docenteForm.querySelector('button[type="submit"]');
                        if (submitButton && document.activeElement === e.target) {
                            e.preventDefault();
                            handleFormSubmit(new Event('submit'));
                        }
                    }
                });
            }
        });

        function handleFormSubmit(e) {
            e.preventDefault();
            
            if (!validateDocenteForm()) {
                showToast('<?php _e('please_correct_errors'); ?>', 'error');
                return;
            }
            
            const url = isEditMode 
                ? `/teachers/${document.getElementById('id_docente').value}`
                : '/teachers';
            const method = isEditMode ? 'PUT' : 'POST';
            
            let requestBody;
            let contentType;
            
            if (isEditMode) {
                const formData = new FormData(e.target);
                const urlEncodedData = new URLSearchParams();
                for (let [key, value] of formData.entries()) {
                    urlEncodedData.append(key, value);
                }
                requestBody = urlEncodedData.toString();
                contentType = 'application/x-www-form-urlencoded';
            } else {
                requestBody = new FormData(e.target);
                contentType = null;
            }
            
            const fetchOptions = {
                method: method,
                body: requestBody
            };
            
            if (contentType) {
                fetchOptions.headers = {
                    'Content-Type': contentType
                };
            }
            
            fetch(url, fetchOptions)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeDocenteModal();
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
        }

        function clearErrors() {
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

        function toggleConfirmPasswordVisibility() {
            const confirmPasswordInput = document.getElementById('confirmar_contrasena');
            const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');
            
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                confirmPasswordIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                confirmPasswordInput.type = 'password';
                confirmPasswordIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        function validateDocenteForm() {
            let isValid = true;
            clearErrors();
            
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
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError('email', '<?php _e('email_invalid_format'); ?>');
                isValid = false;
            }
            
            const telefono = document.getElementById('telefono').value.trim();
            if (telefono && !/^[0-9\s\-\+\(\)]+$/.test(telefono)) {
                showFieldError('telefono', '<?php _e('phone_invalid_format'); ?>');
                isValid = false;
            }
            
            const contrasena = document.getElementById('contrasena').value;
            const confirmarContrasena = document.getElementById('confirmar_contrasena').value;
            if (!isEditMode && (!contrasena || contrasena.length < 8)) {
                showFieldError('contrasena', '<?php _e('password_required_min_length'); ?>');
                isValid = false;
            } else if (contrasena && contrasena.length < 8) {
                showFieldError('contrasena', '<?php _e('password_min_length'); ?>');
                isValid = false;
            }
            
            // Validate password confirmation
            if (contrasena) {
                if (!confirmarContrasena) {
                    showFieldError('confirmar_contrasena', '<?php _e('password_required_min_length'); ?>');
                    isValid = false;
                } else if (confirmarContrasena.length < 8) {
                    showFieldError('confirmar_contrasena', '<?php _e('password_min_length'); ?>');
                    isValid = false;
                } else if (contrasena !== confirmarContrasena) {
                    showFieldError('confirmar_contrasena', '<?php _e('form_password_mismatch'); ?>');
                    isValid = false;
                }
            } else if (!isEditMode && !confirmarContrasena) {
                showFieldError('confirmar_contrasena', '<?php _e('password_required_min_length'); ?>');
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

        document.getElementById('docenteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDocenteModal();
            }
        });

        const togglePasswordBtn = document.getElementById('togglePassword');
        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', togglePasswordVisibility);
        }

        const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
        if (toggleConfirmPasswordBtn) {
            toggleConfirmPasswordBtn.addEventListener('click', toggleConfirmPasswordVisibility);
        }

        document.getElementById('logoutButton').addEventListener('click', async function() {
            const confirmed = await showConfirmModal(
                '<?php _e('confirm_logout'); ?>',
                '<?php _e('confirm_logout_message'); ?>',
                '<?php _e('confirm'); ?>',
                '<?php _e('cancel'); ?>'
            );
            if (confirmed) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });

        // Assignment Management Functions
        function manageAssignments(teacherId, teacherName) {
            document.getElementById('assignment_teacher_id').value = teacherId;
            document.getElementById('teacherNameDisplay').textContent = teacherName;
            loadTeacherAssignments(teacherId);
            
            const modal = document.getElementById('assignmentModal');
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'none';
            document.getElementById('assignmentForm').reset();
        }

        function loadTeacherAssignments(teacherId) {
            fetch(`/assignments?teacher_id=${teacherId}`)
                .then(response => response.json())
                .then(data => {
                    const assignmentsList = document.getElementById('assignmentsList');
                    if (data.success && data.data.length > 0) {
                        assignmentsList.innerHTML = data.data.map(assignment => `
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 8px;">
                                <div>
                                    <span style="font-weight: 500; color: #111827;">${assignment.materia_nombre}</span>
                                </div>
                                <button onclick="removeAssignment('${assignment.id}', ${teacherId})" 
                                        style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 4px 8px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                    <?php _e('remove'); ?>
                                </button>
                            </div>
                        `).join('');
                    } else {
                        assignmentsList.innerHTML = '<p style="color: #6b7280; font-size: 14px; text-align: center; padding: 20px;"><?php _e('no_assignments_found'); ?></p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading assignments:', error);
                    if (typeof showToast === 'function') {
                        showToast('<?php _e('error_loading_assignments'); ?>', 'error');
                    } else {
                        alert('<?php _e('error_loading_assignments'); ?>');
                    }
                });
        }

        function handleAssignmentSubmit(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const teacherId = formData.get('teacher_id');
            const subjectId = formData.get('subject_id');
            
            fetch('/assignments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    teacher_id: teacherId,
                    subject_id: subjectId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('<?php _e('assignment_created_successfully'); ?>', 'success');
                    } else {
                        alert('<?php _e('assignment_created_successfully'); ?>');
                    }
                    const teacherId = document.getElementById('assignment_teacher_id').value;
                    loadTeacherAssignments(teacherId);
                    document.getElementById('assignmentForm').reset();
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || '<?php _e('error_creating_assignment'); ?>', 'error');
                    } else {
                        alert(data.message || '<?php _e('error_creating_assignment'); ?>');
                    }
                }
            })
            .catch(error => {
                console.error('Error creating assignment:', error);
                if (typeof showToast === 'function') {
                    showToast('<?php _e('error_creating_assignment'); ?>', 'error');
                } else {
                    alert('<?php _e('error_creating_assignment'); ?>');
                }
            });
        }

        async function removeAssignment(assignmentId, teacherId) {
            const confirmed = await showConfirmModal(
                '<?php _e('confirm_delete'); ?>',
                '<?php _e('confirm_remove_assignment'); ?>',
                '<?php _e('confirm'); ?>',
                '<?php _e('cancel'); ?>'
            );
            if (confirmed) {
                fetch(`/assignments/${assignmentId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showToast === 'function') {
                            showToast('<?php _e('assignment_removed_successfully'); ?>', 'success');
                        } else {
                            alert('<?php _e('assignment_removed_successfully'); ?>');
                        }
                        loadTeacherAssignments(teacherId);
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.message || '<?php _e('error_removing_assignment'); ?>', 'error');
                        } else {
                            alert(data.message || '<?php _e('error_removing_assignment'); ?>');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error removing assignment:', error);
                    if (typeof showToast === 'function') {
                        showToast('<?php _e('error_removing_assignment'); ?>', 'error');
                    } else {
                        alert('<?php _e('error_removing_assignment'); ?>');
                    }
                });
            }
        }

        function searchDocentes(searchTerm) {
            const docentes = document.querySelectorAll('.docente-item');
            const searchLower = searchTerm.toLowerCase().trim();
            
            if (searchLower === '') {
                docentes.forEach(docente => {
                    docente.style.display = 'flex';
                });
                return;
            }

            docentes.forEach(docente => {
                const nombre = docente.dataset.nombre || '';
                const apellido = docente.dataset.apellido || '';
                const email = docente.dataset.email || '';
                const cedula = docente.dataset.cedula || '';
                
                if (nombre.includes(searchLower) || apellido.includes(searchLower) || 
                    email.includes(searchLower) || cedula.includes(searchLower)) {
                    docente.style.display = 'flex';
                } else {
                    docente.style.display = 'none';
                }
            });

            const visibleDocentes = Array.from(docentes).filter(docente => docente.style.display !== 'none');
            const noResultsMessage = document.getElementById('noResultsMessage');
            
            if (visibleDocentes.length === 0 && searchLower !== '') {
                if (!noResultsMessage) {
                    const docentesList = document.getElementById('docentesList');
                    const messageDiv = document.createElement('div');
                    messageDiv.id = 'noResultsMessage';
                    messageDiv.className = 'p-8 text-center';
                    messageDiv.innerHTML = `
                        <div class="text-gray-500 text-lg mb-2">No se encontraron docentes que coincidan con "${searchTerm}"</div>
                        <div class="text-gray-400 text-sm">Intente con un término de búsqueda diferente</div>
                    `;
                    docentesList.appendChild(messageDiv);
                }
            } else if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }

        // Initialize multiple selection and status labels
        document.addEventListener('DOMContentLoaded', function() {
            const multipleSelection = new MultipleSelection({
                container: document.querySelector('.bg-white.rounded-lg.shadow-sm'),
                itemSelector: '.item-row',
                checkboxSelector: '.item-checkbox',
                selectAllSelector: '#selectAll',
                bulkActionsSelector: '#bulkActions',
                entityType: 'docentes',
                onSelectionChange: function(selectedItems) {
                    // Handle selection change
                },
                onBulkAction: function(action, selectedIds) {
                    // Handle bulk actions
                }
            });

            const statusLabels = new StatusLabels({
                container: document.querySelector('.bg-white.rounded-lg.shadow-sm'),
                itemSelector: '.item-row',
                metaSelector: '.meta .text-muted',
                entityType: 'docentes'
            });

            // Initialize Pagination (RF082)
            const totalDocentes = <?php echo count($docentes); ?>;
            const paginationContainer = document.getElementById('paginationContainer');
            
            if (paginationContainer && totalDocentes > 0) {
                window.paginationManager = new PaginationManager({
                    container: paginationContainer,
                    currentPage: 1,
                    perPage: 10,
                    totalRecords: totalDocentes,
                    onPageChange: function(page) {
                        updateVisibleItems(page);
                    },
                    onPerPageChange: function(perPage) {
                        updateVisibleItems(1);
                    }
                });
                
                // Show first page by default
                updateVisibleItems(1);
            }

            // Initialize Filter Manager (RF080)
            const filterContainer = document.querySelector('.bg-white.rounded-lg.shadow-sm');
            const filterResultCount = document.getElementById('filterResultCount');
            
            if (filterContainer && filterResultCount) {
                window.filterManager = new FilterManager({
                    container: filterContainer,
                    resultCountContainer: filterResultCount,
                    totalCount: totalDocentes,
                    filteredCount: totalDocentes,
                    onFilterChange: function(filters) {
                        applyFilters(filters);
                    }
                });
                
                // Mark search input as filter
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.setAttribute('data-filter', 'search');
                }
                
                window.filterManager.init();
                window.filterManager.updateResultCount(totalDocentes, totalDocentes);
            }
        });

        // Update visible items based on pagination
        function updateVisibleItems(page) {
            if (!window.paginationManager) return;
            
            const state = window.paginationManager.getState();
            const allItems = Array.from(document.querySelectorAll('.docente-item'));
            
            // Get only visible items (not filtered out)
            const visibleItems = allItems.filter(item => {
                // Check if item matches current filters
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
            
            // Hide all items first
            allItems.forEach(item => {
                item.style.display = 'none';
            });
            
            // Show only items in current page range
            visibleItems.slice(startIndex, endIndex).forEach(item => {
                item.style.display = 'flex';
            });
        }

        // Apply filters and update counts
        function applyFilters(filters) {
            const allItems = document.querySelectorAll('.docente-item');
            let visibleCount = 0;
            let visibleIndex = 0;
            
            allItems.forEach(item => {
                let matches = true;
                
                // Apply search filter
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
                
                // Show/hide item
                if (matches) {
                    item.style.display = 'flex';
                    visibleCount++;
                    item.dataset.visibleIndex = visibleIndex++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Update filter count
            if (window.filterManager) {
                window.filterManager.updateResultCount(visibleCount, allItems.length);
            }
            
            // Reset pagination to page 1 when filters change
            if (window.paginationManager) {
                window.paginationManager.currentPage = 1;
                window.paginationManager.updateTotalRecords(visibleCount);
                // Update visible items will be called by pagination manager's render
                setTimeout(() => updateVisibleItems(1), 0);
            } else {
                // If no pagination, just show all matching items
                allItems.forEach(item => {
                    item.style.display = 'flex';
                });
            }
        }
    </script>
</body>
</html>
