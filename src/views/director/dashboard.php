<?php
/**
 * Dashboard principal del director
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../config/translations.php';

// Initialize secure session first
initSecureSession();

// Require authentication and director role
AuthHelper::requireRole('DIRECTOR');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Get user data
$user = $_SESSION['user'];
$translation = new Translation();
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('app_name'); ?> — <?php _e('dashboard_title'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../components/Sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900"><?php _e('dashboard_title'); ?></h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600"><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></span>
                        <a href="/src/views/logout.php" class="text-sm text-red-600 hover:text-red-800"><?php _e('logout'); ?></a>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <main class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Welcome Card -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-2"><?php _e('welcome'); ?></h2>
                            <p class="text-gray-600"><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></p>
                            <p class="text-sm text-gray-500 mt-1"><?php _e('role_director'); ?></p>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php _e('quick_actions'); ?></h2>
                            <div class="space-y-2">
                                <a href="#" class="block text-blue-600 hover:text-blue-800"><?php _e('view_reports'); ?></a>
                                <a href="#" class="block text-blue-600 hover:text-blue-800"><?php _e('manage_schedules'); ?></a>
                                <a href="#" class="block text-blue-600 hover:text-blue-800"><?php _e('view_statistics'); ?></a>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php _e('recent_activity'); ?></h2>
                            <p class="text-gray-600 text-sm"><?php _e('no_recent_activity'); ?></p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>

