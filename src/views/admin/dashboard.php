<?php

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('dashboard.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $totalDocentes = $database->queryCount("SELECT COUNT(*) FROM docente");
    $totalUsuarios = $database->queryCount("SELECT COUNT(*) FROM usuario");
    $totalCoordinadores = $database->queryCount("SELECT COUNT(*) FROM usuario_rol ur JOIN rol r ON ur.nombre_rol = r.nombre_rol WHERE r.nombre_rol = 'COORDINADOR'");
    $totalPadres = $database->queryCount("SELECT COUNT(*) FROM usuario_rol ur JOIN rol r ON ur.nombre_rol = r.nombre_rol WHERE r.nombre_rol = 'PADRE'");
    
    $recentActivity = $database->query("SELECT l.*, u.nombre, u.apellido FROM log l LEFT JOIN usuario u ON l.id_usuario = u.id_usuario ORDER BY l.fecha DESC LIMIT 10");
    
    if ($recentActivity === false) {
        $recentActivity = [];
    }
} catch (Exception $e) {
    error_log("Error cargando estadísticas: " . $e->getMessage());
    $totalDocentes = 0;
    $totalUsuarios = 0;
    $totalCoordinadores = 0;
    $totalPadres = 0;
    $recentActivity = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_dashboard'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
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

            <section class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 text-xs font-bold leading-none">👨‍🏫</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('total_teachers'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalDocentes; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <span class="text-green-600 text-xs font-bold leading-none">👥</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('total_users'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalUsuarios; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <span class="text-purple-600 text-xs font-bold leading-none">👨‍💼</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('coordinators'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalCoordinadores; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                        <span class="text-orange-600 text-xs font-bold leading-none">👨‍👩‍👧‍👦</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('parents'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalPadres; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('quick_actions'); ?></h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <a href="/admin/teachers" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-blue-600 text-xs font-bold leading-none">👨‍🏫</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php _e('manage_teachers'); ?></p>
                                        <p class="text-sm text-gray-500"><?php _e('manage_teachers_description'); ?></p>
                                    </div>
                                </a>

                                <a href="/admin/coordinators" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-purple-600 text-xs font-bold leading-none">👨‍💼</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php _e('manage_coordinators'); ?></p>
                                        <p class="text-sm text-gray-500"><?php _e('manage_coordinators_description'); ?></p>
                                    </div>
                                </a>

                                <a href="/admin/subjects" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-indigo-600 text-xs font-bold leading-none">📚</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php _e('manage_subjects'); ?></p>
                                        <p class="text-sm text-gray-500"><?php _e('manage_subjects_description'); ?></p>
                                    </div>
                                </a>

                                <a href="/admin/groups" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-yellow-600 text-xs font-bold leading-none">👥</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php _e('manage_groups'); ?></p>
                                        <p class="text-sm text-gray-500"><?php _e('groups_management_description'); ?></p>
                                    </div>
                                </a>

                                <a href="/admin/schedules" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-orange-600 text-xs font-bold leading-none">📅</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php _e('manage_schedules'); ?></p>
                                        <p class="text-sm text-gray-500"><?php _e('manage_schedules_description'); ?></p>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('recent_activity'); ?></h3>
                            <?php if (empty($recentActivity)): ?>
                                <div class="text-center py-8">
                                    <span class="text-gray-400 text-2xl">•</span>
                                    <p class="mt-2 text-sm text-gray-500"><?php _e('no_recent_activity'); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                    <span class="text-gray-600 text-xs leading-none">•</span>
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
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="/js/menu.js"></script>
    <script>

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
