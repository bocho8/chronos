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
require_once __DIR__ . '/../../models/Docente.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-mi-horario.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $horarioModel = new Horario($database->getConnection());
    $docenteModel = new Docente($database->getConnection());
    
    $docentes = $docenteModel->getAllDocentes();
    $bloques = $horarioModel->getAllBloques();
    
    $selectedDocenteId = $_GET['docente'] ?? null;
    $selectedDocente = null;
    $horarios = [];
    
    if ($selectedDocenteId) {
        foreach ($docentes as $docente) {
            if ($docente['id_docente'] == $selectedDocenteId) {
                $selectedDocente = $docente;
                break;
            }
        }
        if ($selectedDocente) {
            $horarios = $horarioModel->getHorariosByDocente($selectedDocenteId);
        }
    }

    $scheduleGrid = [];
    $dias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
    
    foreach ($dias as $dia) {
        $scheduleGrid[$dia] = [];
        foreach ($bloques as $bloque) {
            $scheduleGrid[$dia][$bloque['id_bloque']] = null;
        }
    }

    foreach ($horarios as $horario) {
        if (isset($scheduleGrid[$horario['dia']][$horario['id_bloque']])) {
            $scheduleGrid[$horario['dia']][$horario['id_bloque']] = $horario;
        }
    }
    
} catch (Exception $e) {
    error_log("Error cargando mi horario: " . $e->getMessage());
    $docentes = [];
    $bloques = [];
    $horarios = [];
    $scheduleGrid = [];
    $error_message = 'Error interno del servidor';
}

function getUserInitials($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('my_schedule'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
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
        .schedule-grid {
            display: grid;
            grid-template-columns: 120px repeat(5, 1fr);
            gap: 1px;
            background-color: #e5e7eb;
            border: 1px solid #e5e7eb;
        }
        .schedule-header {
            background-color: #f3f4f6;
            padding: 12px 8px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #d1d5db;
        }
        .schedule-cell {
            background-color: white;
            padding: 8px;
            min-height: 60px;
            border: 1px solid #d1d5db;
            position: relative;
        }
        .schedule-cell.occupied {
            background-color: #dbeafe;
            border-color: #3b82f6;
        }
        .schedule-cell.occupied:hover {
            background-color: #bfdbfe;
        }
        .schedule-item {
            font-size: 12px;
            line-height: 1.2;
        }
        .schedule-item .subject {
            font-weight: 600;
            color: #1f2937;
        }
        .schedule-item .group {
            color: #6b7280;
        }
        .time-header {
            background-color: #f9fafb;
            font-weight: 500;
            color: #374151;
        }
        .day-header {
            background-color: #1f366d;
            color: white;
            font-weight: 600;
        }
        .teacher-selector {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 14px;
            min-width: 200px;
        }
        .teacher-selector:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .schedule-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .stat-item:last-child {
            margin-bottom: 0;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .stat-value {
            font-size: 16px;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <span class="text-white text-sm">🔔</span>
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

            <!-- Main Content -->
            <section class="flex-1 px-6 py-8">
                <div class="max-w-7xl mx-auto">
                    <!-- Page Header -->
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('my_schedule'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('my_schedule_description'); ?></p>
                    </div>

                    <!-- Teacher Selection -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6 mb-6">
                        <div class="flex items-center space-x-4">
                            <label for="docenteSelect" class="text-sm font-medium text-gray-700">
                                <?php _e('select_teacher'); ?>:
                            </label>
                            <select id="docenteSelect" class="teacher-selector" onchange="loadDocenteHorario()">
                                <option value=""><?php _e('choose_teacher'); ?></option>
                                <?php foreach ($docentes as $docente): ?>
                                    <option value="<?php echo $docente['id_docente']; ?>" 
                                            <?php echo ($selectedDocenteId == $docente['id_docente']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($docente['apellido'] . ', ' . $docente['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if ($selectedDocente): ?>
                        <!-- Teacher Statistics -->
                        <div class="schedule-stats">
                            <h3 class="text-lg font-semibold mb-4">
                                <?php echo htmlspecialchars($selectedDocente['apellido'] . ', ' . $selectedDocente['nombre']); ?>
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="stat-item">
                                    <span class="stat-label"><?php _e('assigned_hours'); ?>:</span>
                                    <span class="stat-value"><?php echo $selectedDocente['horas_asignadas']; ?>h</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label"><?php _e('margin_percentage'); ?>:</span>
                                    <span class="stat-value"><?php echo number_format($selectedDocente['porcentaje_margen'], 1); ?>%</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label"><?php _e('other_school'); ?>:</span>
                                    <span class="stat-value"><?php echo $selectedDocente['trabaja_otro_liceo'] ? _e('yes') : _e('no'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Grid -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder overflow-hidden">
                            <div class="schedule-grid">
                                <!-- Time column header -->
                                <div class="schedule-header time-header"><?php _e('time'); ?></div>
                                
                                <!-- Day headers -->
                                <?php foreach ($dias as $dia): ?>
                                    <div class="schedule-header day-header">
                                        <?php echo _e(strtolower($dia)); ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Schedule rows -->
                                <?php foreach ($bloques as $bloque): ?>
                                    <!-- Time column -->
                                    <div class="schedule-header time-header">
                                        <?php echo date('H:i', strtotime($bloque['hora_inicio'])); ?><br>
                                        <span class="text-xs">-</span><br>
                                        <?php echo date('H:i', strtotime($bloque['hora_fin'])); ?>
                                    </div>
                                    
                                    <!-- Day columns -->
                                    <?php foreach ($dias as $dia): ?>
                                        <?php 
                                        $scheduleItem = $scheduleGrid[$dia][$bloque['id_bloque']] ?? null;
                                        $isOccupied = $scheduleItem !== null;
                                        ?>
                                        <div class="schedule-cell <?php echo $isOccupied ? 'occupied' : ''; ?>">
                                            <?php if ($isOccupied): ?>
                                                <div class="schedule-item">
                                                    <div class="subject"><?php echo htmlspecialchars($scheduleItem['materia_nombre'] ?? ''); ?></div>
                                                    <div class="group"><?php echo htmlspecialchars($scheduleItem['grupo_nombre'] ?? ''); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Schedule Summary -->
                        <?php if (!empty($horarios)): ?>
                            <div class="mt-6 bg-white rounded-lg shadow-sm border border-lightborder p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('schedule_summary'); ?></h3>
                                <div class="space-y-2">
                                    <?php foreach ($horarios as $horario): ?>
                                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                            <div class="flex items-center space-x-4">
                                                <span class="text-sm font-medium text-gray-600">
                                                    <?php echo _e(strtolower($horario['dia'])); ?>
                                                </span>
                                                <span class="text-sm text-gray-500">
                                                    <?php echo date('H:i', strtotime($horario['hora_inicio'])); ?> - 
                                                    <?php echo date('H:i', strtotime($horario['hora_fin'])); ?>
                                                </span>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($horario['materia_nombre'] ?? ''); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($horario['grupo_nombre'] ?? ''); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mt-6 bg-white rounded-lg shadow-sm border border-lightborder p-6 text-center">
                                <div class="text-gray-400 text-4xl mb-4">📅</div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2"><?php _e('no_schedule_assigned'); ?></h3>
                                <p class="text-gray-500"><?php _e('no_schedule_assigned_description'); ?></p>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- No teacher selected -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-12 text-center">
                            <div class="text-gray-400 text-6xl mb-4">👨‍🏫</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2"><?php _e('no_teacher_selected'); ?></h3>
                            <p class="text-gray-500"><?php _e('select_teacher_to_view_schedule'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        function loadDocenteHorario() {
            const docenteId = document.getElementById('docenteSelect').value;
            if (docenteId) {
                window.location.href = `admin-mi-horario.php?docente=${docenteId}`;
            }
        }

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
</body>
</html>