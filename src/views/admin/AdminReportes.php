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

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-reportes.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $pdo = $database->getConnection();
    
    $stats = [];
    
    $stats['total_usuarios'] = $database->queryCount("SELECT COUNT(*) FROM usuario");
    $stats['total_docentes'] = $database->queryCount("SELECT COUNT(*) FROM docente");
    $stats['total_materias'] = $database->queryCount("SELECT COUNT(*) FROM materia");
    $stats['total_grupos'] = $database->queryCount("SELECT COUNT(*) FROM grupo");
    $stats['total_horarios'] = $database->queryCount("SELECT COUNT(*) FROM horario");
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_docente) as count FROM disponibilidad WHERE disponible = true");
    $stats['docentes_disponibles'] = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM materia WHERE es_programa_italiano = true");
    $stats['materias_italiano'] = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->prepare("SELECT l.*, u.nombre, u.apellido FROM log l 
                          LEFT JOIN usuario u ON l.id_usuario = u.id_usuario 
                          ORDER BY l.fecha DESC LIMIT 20");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error cargando reportes: " . $e->getMessage());
    $stats = [];
    $recentActivity = [];
    $error_message = 'Error interno del servidor';
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('reports'); ?></title>
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
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .chart-placeholder {
            background: linear-gradient(45deg, #f3f4f6 25%, transparent 25%), 
                        linear-gradient(-45deg, #f3f4f6 25%, transparent 25%), 
                        linear-gradient(45deg, transparent 75%, #f3f4f6 75%), 
                        linear-gradient(-45deg, transparent 75%, #f3f4f6 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

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

            <!-- Main Content -->
            <section class="flex-1 p-3 sm:p-4 md:p-6 w-full overflow-x-hidden">
                <div class="max-w-6xl mx-auto">
                    <!-- Page Header -->
                    <div class="mb-6 md:mb-8">
                        <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('reports'); ?></h2>
                        <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('reports_description'); ?></p>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="stat-card bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 text-xs font-bold leading-none">U</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('total_users'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_usuarios'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <span class="text-green-600 text-xs font-bold leading-none">D</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('total_teachers'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_docentes'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <span class="text-purple-600 text-xs font-bold leading-none">M</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('total_subjects'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_materias'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                        <span class="text-orange-600 text-xs font-bold leading-none">H</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('total_schedules'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_horarios'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Generation -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-8">
                        <!-- Quick Reports -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-4 md:p-6">
                            <h3 class="text-base md:text-lg font-medium text-gray-900 mb-4"><?php _e('quick_reports'); ?></h3>
                            <div class="space-y-2 md:space-y-3">
                                <button onclick="generateReport('teachers')" class="w-full text-left p-2 md:p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm md:text-base"><?php _e('teachers_report'); ?></p>
                                        <p class="text-xs md:text-sm text-gray-500"><?php _e('teachers_report_description'); ?></p>
                                    </div>
                                </button>

                                <button onclick="generateReport('schedules')" class="w-full text-left p-2 md:p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm md:text-base"><?php _e('schedules_report'); ?></p>
                                        <p class="text-xs md:text-sm text-gray-500"><?php _e('schedules_report_description'); ?></p>
                                    </div>
                                </button>

                                <button onclick="generateReport('subjects')" class="w-full text-left p-2 md:p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <span class="text-xs md:text-sm mr-2">📋</span>
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm md:text-base"><?php _e('subjects_report'); ?></p>
                                        <p class="text-xs md:text-sm text-gray-500"><?php _e('subjects_report_description'); ?></p>
                                    </div>
                                </button>

                                <button onclick="generateReport('availability')" class="w-full text-left p-2 md:p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <span class="text-xs md:text-sm mr-2">📋</span>
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm md:text-base"><?php _e('availability_report'); ?></p>
                                        <p class="text-xs md:text-sm text-gray-500"><?php _e('availability_report_description'); ?></p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- System Overview -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-4 md:p-6">
                            <h3 class="text-base md:text-lg font-medium text-gray-900 mb-4"><?php _e('system_overview'); ?></h3>
                            <div class="space-y-3 md:space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm md:text-base"><?php _e('total_groups'); ?>:</span>
                                    <span class="font-semibold text-sm md:text-base"><?php echo $stats['total_grupos'] ?? 0; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm md:text-base"><?php _e('available_teachers'); ?>:</span>
                                    <span class="font-semibold text-sm md:text-base"><?php echo $stats['docentes_disponibles'] ?? 0; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm md:text-base"><?php _e('italian_subjects'); ?>:</span>
                                    <span class="font-semibold text-sm md:text-base"><?php echo $stats['materias_italiano'] ?? 0; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm md:text-base"><?php _e('active_schedules'); ?>:</span>
                                    <span class="font-semibold text-sm md:text-base"><?php echo $stats['total_horarios'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-4 md:p-6">
                        <h3 class="text-base md:text-lg font-medium text-gray-900 mb-4"><?php _e('recent_activity'); ?></h3>
                        <?php if (!empty($recentActivity)): ?>
                            <div class="space-y-3 md:space-y-4 max-h-96 overflow-y-auto">
                                <?php foreach ($recentActivity as $activity): ?>
                                    <div class="flex items-start space-x-2 md:space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                <span class="text-gray-600 text-xs leading-none"></span>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs md:text-sm text-gray-900">
                                                <span class="font-medium">
                                                    <?php echo htmlspecialchars($activity['nombre'] . ' ' . $activity['apellido']); ?>
                                                </span>
                                                <?php echo htmlspecialchars($activity['accion']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo date('d/m/Y H:i', strtotime($activity['fecha'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <p class="mt-2 text-sm text-gray-500"><?php _e('no_recent_activity'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        function generateReport(type) {
            const reportTypes = {
                'teachers': '<?php _e('teachers_report'); ?>',
                'schedules': '<?php _e('schedules_report'); ?>',
                'subjects': '<?php _e('subjects_report'); ?>',
                'availability': '<?php _e('availability_report'); ?>'
            };
            
            const reportName = reportTypes[type] || type;
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="text-sm">⏳</span> Generando...';
            button.disabled = true;

            setTimeout(() => {
                alert(`Reporte generado: ${reportName}\n\nEl reporte se ha generado exitosamente.`);
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
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

        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar-container');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('sidebar-open');
                overlay.classList.toggle('hidden');
            }
        }
    </script>
</body>
</html>
