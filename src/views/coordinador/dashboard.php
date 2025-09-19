<?php
/**
 * Dashboard del Coordinador - Using BaseView component
 */

require_once __DIR__ . '/../../components/BaseView.php';

// Page-specific logic for coordinador dashboard
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // Get coordinator-specific statistics
    $totalDocentes = $database->queryCount("SELECT COUNT(*) FROM docente");
    $totalMaterias = $database->queryCount("SELECT COUNT(*) FROM materia");
    
} catch (Exception $e) {
    error_log("Error cargando estadísticas: " . $e->getMessage());
    $totalDocentes = 0;
    $totalMaterias = 0;
}

// Prepare main content
$content = '
<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e(\'coordinator_dashboard\'); ?></h2>
        <p class="text-muted mb-6 text-base"><?php _e(\'coordinator_dashboard_description\'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500"><?php _e(\'total_teachers\'); ?></p>
                    <p class="text-2xl font-semibold text-gray-900">' . $totalDocentes . '</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500"><?php _e(\'total_subjects\'); ?></p>
                    <p class="text-2xl font-semibold text-gray-900">' . $totalMaterias . '</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Coordinator Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e(\'coordinator_actions\'); ?></h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="/src/views/admin/admin-disponibilidad.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 712-2h2a2 2 0 712 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900"><?php _e(\'teacher_availability\'); ?></p>
                    <p class="text-sm text-gray-500"><?php _e(\'manage_teacher_availability\'); ?></p>
                </div>
            </a>
            
            <a href="/src/views/admin/admin-asignaciones.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 712-2h2a2 2 0 712 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900"><?php _e(\'subject_assignments\'); ?></p>
                    <p class="text-sm text-gray-500"><?php _e(\'assign_subjects_to_teachers\'); ?></p>
                </div>
            </a>
        </div>
    </div>
</div>
';

// Render the complete page using BaseView
$view = new BaseView('dashboard.php', 'Dashboard Coordinador', 'COORDINADOR');
echo $view->renderPage(
    '<?php _e(\'app_name\'); ?> — Dashboard Coordinador',
    '<section class="flex-1 px-6 py-8">' . $content . '</section>',
    ['toast' => true, 'modal' => false, 'logout' => true, 'userMenu' => true]
);
?>
