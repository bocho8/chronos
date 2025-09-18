<?php
/**
 * Página de gestión de docentes
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Docente.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';

// Initialize secure session
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
    header('Location: /src/views/login.php');
    exit();
}

// Load database configuration
$dbConfig = require __DIR__ . '/../../config/database.php';
$database = new Database($dbConfig);

// Get all docentes
$docenteModel = new Docente($database->getConnection());
$docentes = $docenteModel->getAllDocentes();

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
    <title><?php _e('app_name'); ?> — <?php _e('teachers_management'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
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
    
    /* Modal styles */
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
    }
    
    #docenteModal.hidden {
      display: none !important;
    }
    
    #docenteModal .modal-content {
      position: relative !important;
      z-index: 10001 !important;
      background: white !important;
      border-radius: 8px !important;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }
    
    #docenteModal button[type="submit"], 
    #docenteModal button[type="button"] {
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
                <img src="/assets/images/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
                <span class="text-white font-semibold text-lg"><?php _e('scuola_italiana'); ?></span>
            </div>

            <ul class="py-5 list-none">
                <!-- Dashboard Principal -->
                <li>
                    <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        <?php _e('dashboard'); ?>
                    </a>
                </li>
                
                <!-- Gestión Administrativa -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('administration'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-usuarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <?php _e('users'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-docentes.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <?php _e('teachers'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-coordinadores.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <?php _e('coordinators'); ?>
                    </a>
                </li>
                
                <!-- Gestión Académica -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('academic_management'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-materias.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <?php _e('subjects'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-grupos.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <?php _e('groups'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-horarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php _e('schedules'); ?>
                    </a>
                </li>
                
                <!-- Funciones de Coordinador -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('coordinator_functions'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-disponibilidad.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 712-2h2a2 2 0 712 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <?php _e('teacher_availability'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-asignaciones.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 712-2h2a2 2 0 712 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <?php _e('subject_assignments'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-reportes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 712-2h2a2 2 0 712 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 712-2h2a2 2 0 712 2v14a2 2 0 71-2 2h-2a2 2 0 71-2-2z"></path>
                        </svg>
                        <?php _e('reports'); ?>
                    </a>
                </li>
                
                <!-- Funciones de Docente -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('teacher_functions'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-mi-horario.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 712-2h4a2 2 0 712 2v4m-6 0V3a2 2 0 712-2h4a2 2 0 712 2v4M7 7h10l4 10v4a1 1 0 71-1 1H4a1 1 0 71-1-1v-4L7 7z"></path>
                        </svg>
                        <?php _e('my_schedule'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-mi-disponibilidad.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php _e('my_availability'); ?>
                    </a>
                </li>
                
                <!-- Funciones de Padre -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('parent_functions'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-estudiantes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                        </svg>
                        <?php _e('students'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-horarios-estudiante.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <?php _e('student_schedules'); ?>
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
            <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('teachers_management'); ?></h2>
            <p class="text-muted mb-6 text-base"><?php _e('teachers_management_description'); ?></p>
          </div>

          <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
            <!-- Header de la tabla -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
              <h3 class="font-medium text-darktext"><?php _e('teachers'); ?></h3>
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
                <button onclick="showAddDocenteModal()" class="py-2 px-4 border-none rounded cursor-pointer font-medium transition-all text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  <?php _e('add_teacher'); ?>
                </button>
              </div>
            </div>

            <!-- Lista de docentes -->
            <div class="divide-y divide-gray-200">
              <?php if (!empty($docentes)): ?>
                <?php foreach ($docentes as $docente): ?>
                  <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg">
                    <div class="flex items-center">
                      <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                        <?php echo getUserInitials($docente['nombre'], $docente['apellido']); ?>
                      </div>
                      <div class="meta">
                        <div class="font-semibold text-darktext mb-1">
                          <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                        </div>
                        <div class="text-muted text-sm">
                          <?php echo htmlspecialchars($docente['email']); ?> • 
                          CI: <?php echo htmlspecialchars($docente['cedula']); ?>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
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
        </div>
      </section>
    </main>
  </div>

  <!-- Modal para agregar/editar docente -->
  <div id="docenteModal" class="hidden">
    <div class="modal-content p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_teacher'); ?></h3>
                <button onclick="closeDocenteModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="docenteForm" onsubmit="handleFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="id_docente" name="id_docente" value="">
                
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

  <!-- Toast Container -->
  <div id="toastContainer"></div>

    <script>
        let isEditMode = false;

        // Mostrar modal para agregar docente
        function showAddDocenteModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = '<?php _e('add_teacher'); ?>';
            document.getElementById('docenteForm').reset();
            
            // Hacer contraseña requerida para nuevo docente
            document.getElementById('contrasena').required = true;
            
            clearErrors();
            document.getElementById('docenteModal').classList.remove('hidden');
            
            // Focus on first input
            setTimeout(() => {
                document.getElementById('cedula').focus();
            }, 100);
        }

        // Editar docente
        function editDocente(id) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = '<?php _e('edit_teacher'); ?>';
            
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('/src/controllers/docente_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('id_docente').value = data.data.id_docente;
                    document.getElementById('cedula').value = data.data.cedula;
                    document.getElementById('nombre').value = data.data.nombre;
                    document.getElementById('apellido').value = data.data.apellido;
                    document.getElementById('email').value = data.data.email;
                    document.getElementById('telefono').value = data.data.telefono || '';
                    
                    // No hacer contraseña requerida para edición
                    document.getElementById('contrasena').required = false;
                    document.getElementById('contrasena').value = '';
                    
                    document.getElementById('docenteModal').classList.remove('hidden');
                    
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
                showToast('Error cargando datos del docente', 'error');
            });
        }

        // Eliminar docente
        function deleteDocente(id, nombre) {
            const confirmMessage = `¿Está seguro de que desea eliminar al docente "${nombre}"?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id_docente', id);
                
                fetch('/src/controllers/docente_handler.php', {
                    method: 'POST',
                    body: formData
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

        // Cerrar modal
        function closeDocenteModal() {
            const modal = document.getElementById('docenteModal');
            modal.classList.add('hidden');
            clearErrors();
        }

        // Manejar envío del formulario
        function handleFormSubmit(e) {
            e.preventDefault();
            
            clearErrors();
            
            const formData = new FormData(e.target);
            formData.append('action', isEditMode ? 'update' : 'create');
            
            fetch('/src/controllers/docente_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                    
                    if (data.success) {
                        showToast(data.message, 'success');
                        closeDocenteModal();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        if (data.data && typeof data.data === 'object') {
                            // Show validation errors
                            Object.keys(data.data).forEach(field => {
                                const errorElement = document.getElementById(field + 'Error');
                                if (errorElement) {
                                    errorElement.textContent = data.data[field];
                                }
                            });
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error procesando solicitud', 'error');
            });
        }

        // Limpiar errores de validación
        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
        }

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

        // Cerrar modal al hacer clic fuera
        document.getElementById('docenteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDocenteModal();
            }
        });

        // Logout functionality
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
