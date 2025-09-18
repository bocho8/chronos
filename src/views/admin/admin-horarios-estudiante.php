<?php
/**
 * Vista de horarios de estudiantes para administradores (funcionalidad de padre)
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';

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

// Load database configuration and get data
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // Get models
    $horarioModel = new Horario($database->getConnection());
    
    // Get data
    $grupos = $horarioModel->getAllGrupos();
    $bloques = $horarioModel->getAllBloques();
    
    // Get selected grupo
    $selectedGrupo = $_GET['grupo'] ?? null;
    $selectedGrupoData = null;
    $horarios = [];
    
    if ($selectedGrupo) {
        foreach ($grupos as $grupo) {
            if ($grupo['nombre'] == $selectedGrupo) {
                $selectedGrupoData = $grupo;
                break;
            }
        }
        if ($selectedGrupoData) {
            $horarios = $horarioModel->getHorariosByGrupo($selectedGrupoData['id_grupo']);
        }
    }
    
    // Organize schedule by day and time
    $scheduleGrid = [];
    $dias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
    
    foreach ($dias as $dia) {
        $scheduleGrid[$dia] = [];
        foreach ($bloques as $bloque) {
            $scheduleGrid[$dia][$bloque['id_bloque']] = null;
        }
    }
    
    // Fill the schedule grid with current assignments
    foreach ($horarios as $horario) {
        if (isset($scheduleGrid[$horario['dia']][$horario['id_bloque']])) {
            $scheduleGrid[$horario['dia']][$horario['id_bloque']] = $horario;
        }
    }
    
} catch (Exception $e) {
    error_log("Error cargando horarios de estudiantes: " . $e->getMessage());
    $grupos = [];
    $bloques = [];
    $horarios = [];
    $scheduleGrid = [];
    $error_message = 'Error interno del servidor';
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('student_schedules'); ?></title>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 515 0z"></path>
                        </svg>
                        <?php _e('users'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-docentes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
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
                    <a href="admin-horarios-estudiante.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <?php _e('student_schedules'); ?>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                                </svg>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 713-3h4a3 3 0 713 3v1"></path>
                                </svg>
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenido principal -->
            <section class="flex-1 px-6 py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('student_schedules'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('student_schedules_description'); ?></p>
                    </div>

                    <!-- Selector de grupo -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('select_group'); ?></h3>
                        <div class="flex gap-4">
                            <select id="grupoSelect" class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                <option value=""><?php _e('select_group'); ?></option>
                                <?php foreach ($grupos as $grupo): ?>
                                    <option value="<?php echo htmlspecialchars($grupo['nombre']); ?>" 
                                            <?php echo ($selectedGrupo == $grupo['nombre']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['nivel']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button onclick="loadGrupoHorario()" 
                                    class="px-4 py-2 bg-darkblue text-white rounded-md hover:bg-navy transition-colors">
                                <?php _e('load'); ?>
                            </button>
                        </div>
                    </div>

                    <?php if ($selectedGrupoData): ?>
                    <!-- Información del grupo seleccionado -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6 mb-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-darkblue mr-4 flex items-center justify-center text-white font-semibold">
                                <?php echo strtoupper(substr($selectedGrupoData['nombre'], 0, 2)); ?>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($selectedGrupoData['nombre']); ?>
                                </h3>
                                <p class="text-gray-600">
                                    <?php _e('level'); ?>: <?php echo htmlspecialchars($selectedGrupoData['nivel']); ?>
                                </p>
                            </div>
                            <div class="ml-auto">
                                <button onclick="printSchedule()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                    <?php _e('print'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Horario del grupo -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder">
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-medium text-darktext"><?php _e('group_schedule'); ?></h3>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('time'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('monday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('tuesday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('wednesday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('thursday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('friday'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $dias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
                                    foreach ($bloques as $bloque): 
                                    ?>
                                        <tr>
                                            <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">
                                                <?php echo date('H:i', strtotime($bloque['hora_inicio'])) . ' – ' . date('H:i', strtotime($bloque['hora_fin'])); ?>
                                            </th>
                                            <?php foreach ($dias as $dia): ?>
                                                <td class="text-center font-medium p-3 border border-gray-300">
                                                    <?php 
                                                    $assignment = $scheduleGrid[$dia][$bloque['id_bloque']] ?? null;
                                                    if ($assignment): ?>
                                                        <div class="bg-green-100 text-green-800 p-2 rounded">
                                                            <div class="font-semibold text-sm"><?php echo htmlspecialchars($assignment['materia_nombre']); ?></div>
                                                            <div class="text-xs"><?php echo htmlspecialchars($assignment['docente_nombre'] . ' ' . $assignment['docente_apellido']); ?></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-gray-400 text-sm"><?php _e('free'); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Mensaje cuando no hay grupo seleccionado -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php _e('no_group_selected'); ?></h3>
                        <p class="text-gray-500"><?php _e('select_group_to_view_schedule'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        function loadGrupoHorario() {
            const grupo = document.getElementById('grupoSelect').value;
            if (grupo) {
                window.location.href = `admin-horarios-estudiante.php?grupo=${encodeURIComponent(grupo)}`;
            }
        }

        function printSchedule() {
            window.print();
        }

        // Logout functionality
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
