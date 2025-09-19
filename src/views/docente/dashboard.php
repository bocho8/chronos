<?php
/**
 * Dashboard del Docente - Using BaseView component
 */

require_once __DIR__ . '/../../components/BaseView.php';

// Page-specific logic for docente dashboard
$currentUser = AuthHelper::getCurrentUser();
$docenteId = $currentUser['id_usuario'] ?? null;

// Prepare main content
$content = '
<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e(\'teacher_dashboard\'); ?></h2>
        <p class="text-muted mb-6 text-base"><?php _e(\'teacher_dashboard_description\'); ?></p>
    </div>

    <!-- Teacher Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e(\'teacher_actions\'); ?></h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="/src/views/admin/admin-mi-horario.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 712-2h4a2 2 0 712 2v4m-6 0V3a2 2 0 712-2h4a2 2 0 712 2v4M7 7h10l4 10v4a1 1 0 71-1 1H4a1 1 0 71-1-1v-4L7 7z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900"><?php _e(\'my_schedule\'); ?></p>
                    <p class="text-sm text-gray-500"><?php _e(\'view_my_teaching_schedule\'); ?></p>
                </div>
            </a>
            
            <a href="/src/views/admin/admin-mi-disponibilidad.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900"><?php _e(\'my_availability\'); ?></p>
                    <p class="text-sm text-gray-500"><?php _e(\'manage_my_availability\'); ?></p>
                </div>
            </a>
        </div>
    </div>
</div>
';

// Render the complete page using BaseView
$view = new BaseView('dashboard.php', 'Dashboard Docente', 'DOCENTE');
echo $view->renderPage(
    '<?php _e(\'app_name\'); ?> — Dashboard Docente',
    '<section class="flex-1 px-6 py-8">' . $content . '</section>',
    ['toast' => false, 'modal' => false, 'logout' => true, 'userMenu' => true]
);
?>
