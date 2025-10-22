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

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-estudiantes.php');

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
    $query = "SELECT u.id_usuario as id, u.nombre, u.apellido, u.email, u.cedula, 
                     'Sin Grupo Asignado' as grupo, 'Sin Nivel' as nivel
              FROM usuario u 
              INNER JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario 
              WHERE ur.nombre_rol = 'PADRE' 
              ORDER BY u.apellido, u.nombre";
    
    $stmt = $database->getConnection()->prepare($query);
    $stmt->execute();
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($estudiantes)) {
        $estudiantes = [];
    }
    
} catch (Exception $e) {
    error_log("Error cargando estudiantes: " . $e->getMessage());
    $grupos = [];
    $estudiantes = [];
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
    <title><?php _e('app_name'); ?> — <?php _e('students'); ?></title>
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
        .student-card {
            transition: transform 0.2s;
        }
        .student-card:hover {
            transform: translateY(-2px);
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
            <section class="flex-1 px-4 md:px-6 py-6 md:py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-6 md:mb-8">
                        <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('students_management'); ?></h2>
                        <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('students_management_description'); ?></p>
                    </div>

                    <!-- Filtros por grupo -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('filter_by_group'); ?></h3>
                        <div class="flex gap-2 flex-wrap">
                            <button onclick="filterByGroup('')" class="filter-btn active px-3 py-2 text-sm border border-gray-300 rounded-md bg-darkblue text-white">
                                <?php _e('all_groups'); ?>
                            </button>
                            <?php foreach ($grupos as $grupo): ?>
                                <button onclick="filterByGroup('<?php echo $grupo['nombre']; ?>')" 
                                        class="filter-btn px-3 py-2 text-sm border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">
                                    <?php echo htmlspecialchars($grupo['nombre']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Lista de estudiantes -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder">
                        <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-medium text-darktext"><?php _e('students_list'); ?></h3>
                            <div class="flex gap-2">
                                <button class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50">
                                    <?php _e('export'); ?>
                                </button>
                                <button onclick="showAddEstudianteInfo()" class="py-2 px-4 border-none rounded cursor-pointer font-medium transition-all text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                                    <span class="mr-1 text-sm">+</span>
                                    <?php _e('add_student'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6" id="studentsContainer">
                            <?php foreach ($estudiantes as $estudiante): ?>
                                <div class="student-card bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all" 
                                     data-grupo="<?php echo $estudiante['grupo']; ?>">
                                    <div class="flex items-center mb-3">
                                        <div class="w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center text-white font-semibold">
                                            <?php echo getUserInitials($estudiante['nombre'], $estudiante['apellido']); ?>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?>
                                            </h4>
                                            <p class="text-sm text-gray-600">CI: <?php echo htmlspecialchars($estudiante['cedula']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2 mb-3">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500"><?php _e('email'); ?>:</span>
                                            <span class="text-gray-900"><?php echo htmlspecialchars($estudiante['email'] ?: 'No especificado'); ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500"><?php _e('group'); ?>:</span>
                                            <span class="text-gray-900"><?php echo htmlspecialchars($estudiante['grupo']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <?php _e('parent_role'); ?>
                                        </span>
                                        <div class="flex space-x-2">
                                            <button onclick="viewStudentSchedule('<?php echo $estudiante['grupo']; ?>')" 
                                                    class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                                                <?php _e('view_schedule'); ?>
                                            </button>
                                            <button onclick="viewStudentInfo(<?php echo $estudiante['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-800 text-sm font-medium transition-colors">
                                                <?php _e('details'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        function filterByGroup(grupo) {
            const cards = document.querySelectorAll('.student-card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            buttons.forEach(btn => {
                btn.classList.remove('active', 'bg-darkblue', 'text-white');
                btn.classList.add('bg-white', 'text-gray-700');
            });
            event.target.classList.add('active', 'bg-darkblue', 'text-white');
            event.target.classList.remove('bg-white', 'text-gray-700');

            cards.forEach(card => {
                if (grupo === '' || card.dataset.grupo === grupo) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function viewStudentSchedule(grupo) {
            window.location.href = `admin-horarios-estudiante.php?grupo=${encodeURIComponent(grupo)}`;
        }

        function viewStudentInfo(studentId) {
            alert(`Ver información detallada del estudiante ID: ${studentId}\n\nEsta funcionalidad será implementada en una versión futura.`);
        }

        function showAddEstudianteInfo() {
            alert('Agregar nuevo estudiante\n\nEsta funcionalidad será implementada en una versión futura.');
        }

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
