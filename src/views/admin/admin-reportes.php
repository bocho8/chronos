<?php
/**
 * Página de reportes para administradores
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';

// Initialize secure session
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-reportes.php');

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

// Load database configuration and get statistics
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $pdo = $database->getConnection();
    
    // Get comprehensive statistics
    $stats = [];
    
    // Total counts
    $stats['total_usuarios'] = $database->queryCount("SELECT COUNT(*) FROM usuario");
    $stats['total_docentes'] = $database->queryCount("SELECT COUNT(*) FROM docente");
    $stats['total_materias'] = $database->queryCount("SELECT COUNT(*) FROM materia");
    $stats['total_grupos'] = $database->queryCount("SELECT COUNT(*) FROM grupo");
    $stats['total_horarios'] = $database->queryCount("SELECT COUNT(*) FROM horario");
    
    // Docentes by availability
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_docente) as count FROM disponibilidad WHERE disponible = true");
    $stats['docentes_disponibles'] = $stmt->fetch()['count'] ?? 0;
    
    // Materias by program
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM materia WHERE es_programa_italiano = true");
    $stats['materias_italiano'] = $stmt->fetch()['count'] ?? 0;
    
    // Recent activity
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
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
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('reports'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('reports_description'); ?></p>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="stat-card bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 515 0z"></path>
                                        </svg>
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
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
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
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
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
                                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
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
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Quick Reports -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('quick_reports'); ?></h3>
                            <div class="space-y-3">
                                <button onclick="generateReport('teachers')" class="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php _e('teachers_report'); ?></p>
                                        <p class="text-sm text-gray-500"><?php _e('teachers_report_description'); ?></p>
                                    </div>
                                </button>

                                <button onclick="generateReport('schedules')" class="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php _e('schedules_report'); ?></p>
                                        <p class="text-sm text-gray-500"><?php _e('schedules_report_description'); ?></p>
                                    </div>
                                </button>

                                <button onclick="generateReport('subjects')" class="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <svg class="w-5 h-5 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php _e('subjects_report'); ?></p>
                                        <p class="text-sm text-gray-500"><?php _e('subjects_report_description'); ?></p>
                                    </div>
                                </button>

                                <button onclick="generateReport('availability')" class="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                    <svg class="w-5 h-5 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php _e('availability_report'); ?></p>
                                        <p class="text-sm text-gray-500"><?php _e('availability_report_description'); ?></p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- System Overview -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('system_overview'); ?></h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600"><?php _e('total_groups'); ?>:</span>
                                    <span class="font-semibold"><?php echo $stats['total_grupos'] ?? 0; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600"><?php _e('available_teachers'); ?>:</span>
                                    <span class="font-semibold"><?php echo $stats['docentes_disponibles'] ?? 0; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600"><?php _e('italian_subjects'); ?>:</span>
                                    <span class="font-semibold"><?php echo $stats['materias_italiano'] ?? 0; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600"><?php _e('active_schedules'); ?>:</span>
                                    <span class="font-semibold"><?php echo $stats['total_horarios'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('recent_activity'); ?></h3>
                        <?php if (!empty($recentActivity)): ?>
                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                <?php foreach ($recentActivity as $activity): ?>
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900">
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
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
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
            alert(`Generando reporte: ${reportName}\n\nEsta funcionalidad será implementada en una versión futura.`);
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
