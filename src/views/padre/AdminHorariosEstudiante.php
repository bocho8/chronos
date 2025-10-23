<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Vista de horarios de estudiantes para padres
 * Permite a los padres ver los horarios de sus hijos
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

AuthHelper::requireRole('PADRE'); 

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$currentUser = AuthHelper::getCurrentUser();

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
        $selectedGrupoData = $horarioModel->getGrupoById($selectedGrupo);
        
        $horarios = $horarioModel->getPublishedSchedulesByGrupo($selectedGrupo);
        
        // Handle case where no published schedules exist - fallback to draft schedules
        if ($horarios === false || empty($horarios)) {
            $horarios = $horarioModel->getHorariosByGrupo($selectedGrupo);
            if ($horarios === false) {
                $horarios = [];
            }
        }

        $horariosOrganizados = [];
        foreach ($horarios as $horario) {
            $dia = $horario['dia'];
            $bloque = $horario['id_bloque'];
            $horariosOrganizados[$dia][$bloque] = $horario;
        }
    }
    
} catch (Exception $e) {
    error_log("Error loading horarios data: " . $e->getMessage());
    $grupos = [];
    $bloques = [];
    $selectedGrupoData = null;
    $horariosOrganizados = [];
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
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block">
                    <?php _e('student_schedules'); ?>
                </div>
                <div class="text-white text-sm font-semibold text-center sm:hidden">
                    <?php _e('student_schedules'); ?>
                </div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <img src="/assets/images/icons/bell.png" class="h-6 w-6" alt="<?php _e('notifications'); ?>" />
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block" id="userMenu">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                                <div class="text-gray-500"><?php _e('role_parent'); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                                <img src="/assets/images/icons/user.png" class="inline w-4 h-4 mr-2" alt="<?php _e('profile'); ?>" />
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                                <img src="/assets/images/icons/gear.png" class="inline w-4 h-4 mr-2" alt="<?php _e('settings'); ?>" />
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <img src="/assets/images/icons/logout.png" class="inline w-4 h-4 mr-2" alt="<?php _e('logout'); ?>" />
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <section class="flex-1 px-6 py-8">
                <div class="max-w-7xl mx-auto">
                    <!-- Breadcrumbs -->
                    <nav class="flex mb-6" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                    <img src="/assets/images/icons/home.png" class="w-4 h-4 mr-2" alt="<?php _e('dashboard'); ?>" />
                                    <?php _e('dashboard'); ?>
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <img src="/assets/images/icons/chevron-right.png" class="w-6 h-6 text-gray-400" alt=">" />
                                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2"><?php _e('student_schedules'); ?></span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('student_schedules'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('student_schedules_description'); ?></p>
                    </div>

                    <!-- Group Selection -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                        <form method="GET" class="flex flex-wrap gap-4 items-end">
                            <div class="flex-1 min-w-64">
                                <label for="grupo" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('select_group_to_view_schedule'); ?></label>
                                <select id="grupo" name="grupo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="this.form.submit()">
                                    <option value=""><?php _e('no_group_selected'); ?></option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?php echo $grupo['id_grupo']; ?>" <?php echo $selectedGrupo == $grupo['id_grupo'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grupo['nombre']); ?> - <?php echo htmlspecialchars($grupo['nivel']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if ($selectedGrupo && $selectedGrupoData): ?>
                                <div class="flex gap-2">
                                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                        <img class="inline w-4 h-4 mr-2" src="/assets/images/icons/printer.png" alt="print" />
                                        <?php _e('print'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Schedule Display -->
                    <?php if ($selectedGrupo && $selectedGrupoData): ?>
                        <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?php _e('group_schedule'); ?>: <?php echo htmlspecialchars($selectedGrupoData['nombre']); ?>
                                </h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($selectedGrupoData['nivel']); ?></p>
                            </div>
                            
                            <div class="p-6">
                                <?php if (empty($horariosOrganizados)): ?>
                                    <div class="text-center py-12">
                                        <img class="mx-auto h-12 w-12" src="/assets/images/icons/clock.png" alt="empty" />
                                        <h3 class="mt-2 text-sm font-medium text-gray-900"><?php _e('no_schedule_found'); ?></h3>
                                        <p class="mt-1 text-sm text-gray-500"><?php _e('no_schedule_available_for_group'); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border-collapse border border-gray-300">
                                            <thead>
                                                <tr class="bg-gray-50">
                                                    <th class="border border-gray-300 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('time'); ?></th>
                                                    <?php 
                                                    $dias = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                                                    foreach ($dias as $dia): 
                                                    ?>
                                                        <th class="border border-gray-300 px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            <?php _e($dia); ?>
                                                        </th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bloques as $bloque): ?>
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="border border-gray-300 px-4 py-3 text-sm font-medium text-gray-900 bg-gray-50">
                                                            <?php echo htmlspecialchars($bloque['hora_inicio'] . ' - ' . $bloque['hora_fin']); ?>
                                                        </td>
                                                        <?php foreach ($dias as $dia): ?>
                                                            <td class="border border-gray-300 px-4 py-3 text-sm text-center">
                                                                <?php 
                                                                $diaNum = array_search($dia, $dias) + 1;
                                                                if (isset($horariosOrganizados[$diaNum][$bloque['id_bloque']])):
                                                                    $horario = $horariosOrganizados[$diaNum][$bloque['id_bloque']];
                                                                ?>
                                                                    <div class="bg-blue-50 border border-blue-200 rounded-md p-2">
                                                                        <div class="font-medium text-blue-900">
                                                                            <?php echo htmlspecialchars($horario['nombre_materia'] ?? 'Sin asignar'); ?>
                                                                        </div>
                                                                        <div class="text-xs text-blue-700">
                                                                            <?php echo htmlspecialchars($horario['nombre_docente'] ?? 'Sin docente'); ?>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="text-gray-400 text-xs"><?php _e('free'); ?></div>
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
                    <?php elseif ($selectedGrupo): ?>
                        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                            <div class="text-center py-12">
                                <img class="mx-auto h-12 w-12" src="/assets/images/icons/clock.png" alt="empty" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900"><?php _e('no_group_selected'); ?></h3>
                                <p class="mt-1 text-sm text-gray-500"><?php _e('select_group_to_view_schedule'); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                            <div class="text-center py-12">
                                <img class="mx-auto h-12 w-12" src="/assets/images/icons/clock.png" alt="empty" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900"><?php _e('no_group_selected'); ?></h3>
                                <p class="mt-1 text-sm text-gray-500"><?php _e('select_group_to_view_schedule'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
