<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Vista de horarios de estudiantes para administradores (funcionalidad de padre)
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-horarios-estudiante.php');

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
    
    $grupos = $horarioModel->getAllGrupos();
    $bloques = $horarioModel->getAllBloques();
    
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
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                <div class="text-white text-sm font-semibold text-center sm:hidden"><?php _e('welcome'); ?></div>
                
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

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
