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
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';

initSecureSession();

    // Initialize page

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-view-schedules.php');

$languageSwitcher->handleLanguageChange();

// Check authentication and appropriate role
if (!AuthHelper::isLoggedIn()) {
    header("Location: /src/views/login.php?message=not_authenticated");
    exit();
}
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Check if user has permission to view schedules
$userRole = AuthHelper::getCurrentUserRole();
if (!in_array($userRole, ['ADMIN', 'DIRECTOR', 'COORDINADOR', 'DOCENTE', 'PADRE'])) {
    header("Location: /src/views/errors/403.php");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $horarioModel = new Horario($database->getConnection());
    
    // Get all groups for dropdown
    require_once __DIR__ . '/../../models/Grupo.php';
    $grupoModel = new Grupo($database->getConnection());
    $grupos = $grupoModel->getAllGrupos();
    
    // Get selected group (from URL parameter or default to first)
    $selectedGroupId = $_GET['group'] ?? ($grupos[0]['id_grupo'] ?? null);
    $selectedGroup = null;
    
    if ($selectedGroupId) {
        foreach ($grupos as $grupo) {
            if ($grupo['id_grupo'] == $selectedGroupId) {
                $selectedGroup = $grupo;
                break;
            }
        }
    }
    
    // Get schedules for selected group
    $schedules = [];
    if ($selectedGroup) {
        $schedules = $horarioModel->getHorariosByGrupo($selectedGroupId);
    }
    
    // Get time blocks
    $bloques = $horarioModel->getAllBloques();
    
    // Build schedule grid
    $scheduleGrid = [];
    $dias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
    
    foreach ($dias as $dia) {
        $scheduleGrid[$dia] = [];
        foreach ($bloques as $bloque) {
            $id_bloque = (int)$bloque['id_bloque'];
            $scheduleGrid[$dia][$id_bloque] = null;
        }
    }
    
    // Create a simple lookup array for schedules by day and time block
    $scheduleLookup = [];
    if (!empty($schedules)) {
        foreach ($schedules as $schedule) {
            $dia = $schedule['dia'];
            $id_bloque = (int)$schedule['id_bloque'];
            $scheduleLookup[$dia][$id_bloque] = $schedule;
        }
    }
    
    
    // Check if schedules are published
    $publishStatus = $horarioModel->getPublishRequestStatus();
    $isPublished = $publishStatus === 'publicado';
    
} catch (Exception $e) {
    $grupos = [];
    $selectedGroup = null;
    $schedules = [];
    $bloques = [];
    $scheduleGrid = [];
    $isPublished = false;
    $error_message = 'Error interno del servidor: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('schedule_viewer'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <style>
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
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .schedule-table th,
        .schedule-table td {
            border: 1px solid #e5e7eb;
            padding: 0.75rem;
            text-align: center;
            vertical-align: top;
        }
        
        .schedule-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .schedule-table .time-column {
            background-color: #f3f4f6;
            font-weight: 500;
            width: 80px;
        }
        
        .schedule-cell {
            min-height: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0.5rem;
        }
        
        .subject-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .teacher-name {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .empty-cell {
            background-color: #f9fafb;
            color: #9ca3af;
        }
        
        .published-badge {
            background-color: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .unpublished-badge {
            background-color: #f59e0b;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .print-button {
            background-color: #6b7280;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .print-button:hover {
            background-color: #4b5563;
        }
        
        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .sidebar,
            .action-buttons,
            .group-selector {
                display: none !important;
            }
            
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .schedule-table {
                page-break-inside: avoid;
                font-size: 12px;
            }
            
            .schedule-table th,
            .schedule-table td {
                padding: 0.5rem;
            }
            
            .schedule-cell {
                min-height: 40px;
            }
        }
        
        @media (max-width: 768px) {
            .schedule-table {
                font-size: 0.75rem;
            }
            
            .schedule-table th,
            .schedule-table td {
                padding: 0.5rem 0.25rem;
            }
            
            .schedule-cell {
                min-height: 50px;
            }
            
            .subject-name {
                font-size: 0.75rem;
            }
            
            .teacher-name {
                font-size: 0.625rem;
            }
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col main-content">
            <!-- Header -->
            <header class="bg-darkblue px-4 md:px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block"><?php _e('schedule_viewer'); ?></div>
                <div class="text-white text-sm font-semibold text-center sm:hidden"><?php _e('viewer'); ?></div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <span class="text-white text-sm">🔔</span>
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                                <div class="text-gray-500"><?php _e('role_' . strtolower($userRole)); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <span class="inline mr-2 text-xs">👤</span>
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <span class="inline mr-2 text-xs">⚙</span>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" onclick="logout()">
                                <span class="inline mr-2 text-xs">🚪</span>
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <section class="flex-1 px-4 md:px-6 py-6 md:py-8">
                <div class="max-w-7xl mx-auto">

            <!-- Page Header -->
            <div class="mb-6 md:mb-8">
                <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('schedule_viewer'); ?></h2>
                <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('schedule_viewer_description'); ?></p>
            </div>

                    <!-- Group Selector and Actions -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-6 border border-lightborder">
                        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                            <div class="flex-1">
                                <label for="group-selector" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php _e('select_group'); ?>
                                </label>
                                <select id="group-selector" class="group-selector w-full md:w-auto px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue text-sm" onchange="changeGroup()">
                                    <?php if (!empty($grupos)): ?>
                                        <?php foreach ($grupos as $grupo): ?>
                                            <option value="<?php echo $grupo['id_grupo']; ?>" <?php echo ($selectedGroup && $grupo['id_grupo'] == $selectedGroup['id_grupo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['nivel']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value=""><?php _e('no_groups_available'); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="action-buttons no-print">
                                <button onclick="printSchedule()" class="print-button">
                                    <span class="mr-1">🖨️</span>
                                    <?php _e('print_schedule'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Publication Status -->
                        <div class="mt-4 flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700"><?php _e('published_status'); ?>:</span>
                            <?php if ($isPublished): ?>
                                <span class="published-badge"><?php _e('schedule_published'); ?></span>
                            <?php else: ?>
                                <span class="unpublished-badge"><?php _e('schedule_not_published'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Schedule Display -->
                    <?php if ($selectedGroup): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder">
                            <div class="p-4 md:p-6 border-b border-lightborder bg-gray-50">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?php _e('schedule_for_group'); ?>: <?php echo htmlspecialchars($selectedGroup['nombre'] . ' - ' . $selectedGroup['nivel']); ?>
                                </h3>
                            </div>
                            
                            <div class="p-4 md:p-6">
                                <?php if (empty($schedules)): ?>
                                    <div class="text-center py-12">
                                        <span class="text-gray-400 text-4xl">📅</span>
                                        <p class="mt-4 text-gray-500"><?php _e('no_schedules_found'); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="schedule-table">
                                            <thead>
                                                <tr>
                                                    <th class="time-column"><?php _e('time'); ?></th>
                                                    <?php foreach ($dias as $dia): ?>
                                                        <th><?php _e(strtolower($dia)); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bloques as $bloque): ?>
                                                    <tr>
                                                        <td class="time-column">
                                                            <div class="font-medium">
                                                                <?php echo date('H:i', strtotime($bloque['hora_inicio'])); ?>
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                <?php echo date('H:i', strtotime($bloque['hora_fin'])); ?>
                                                            </div>
                                                        </td>
                                                        <?php foreach ($dias as $dia): ?>
                                                            <td>
                                                                <?php 
                                                                $schedule = $scheduleLookup[$dia][(int)$bloque['id_bloque']] ?? null;
                                                                if ($schedule): 
                                                                ?>
                                                                    <div class="schedule-cell">
                                                                        <div class="subject-name">
                                                                            <?php echo htmlspecialchars($schedule['materia_nombre']); ?>
                                                                        </div>
                                                                        <div class="teacher-name">
                                                                            <?php echo htmlspecialchars($schedule['docente_nombre'] . ' ' . $schedule['docente_apellido']); ?>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="schedule-cell empty-cell">
                                                                        <span class="text-gray-400">—</span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-sm p-8 text-center border border-lightborder">
                            <span class="text-gray-400 text-4xl">📅</span>
                            <p class="mt-4 text-gray-500"><?php _e('no_groups_available'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        function changeGroup() {
            const groupId = document.getElementById('group-selector').value;
            if (groupId) {
                window.location.href = '/admin/view-schedules?group=' + groupId;
            }
        }
        
        function printSchedule() {
            window.print();
        }
        
        function logout() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        }
    </script>
</body>
</html>
