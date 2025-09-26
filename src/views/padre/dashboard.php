<?php
/**
 * Dashboard del Padre - Using BaseView component
 */

require_once __DIR__ . '/../../components/BaseView.php';

// Page-specific logic for padre dashboard
$currentUser = AuthHelper::getCurrentUser();

// Prepare main content
$content = '
<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e(\'parent_dashboard\'); ?></h2>
        <p class="text-muted mb-6 text-base"><?php _e(\'parent_dashboard_description\'); ?></p>
    </div>

    <!-- Parent Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e(\'parent_actions\'); ?></h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="/src/views/admin/admin-estudiantes.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900"><?php _e(\'students\'); ?></p>
                    <p class="text-sm text-gray-500"><?php _e(\'view_my_children\'); ?></p>
                </div>
            </a>
            
            <a href="/src/views/admin/admin-horarios-estudiante.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900"><?php _e(\'student_schedules\'); ?></p>
                    <p class="text-sm text-gray-500"><?php _e(\'view_children_schedules\'); ?></p>
                </div>
            </a>
        </div>
    </div>
</div>
';

// Render the complete page using BaseView
$view = new BaseView('dashboard.php', 'Dashboard Padre', 'PADRE');
echo $view->renderPage(
    '<?php _e(\'app_name\'); ?> — Dashboard Padre',
    '<section class="flex-1 px-6 py-8">' . $content . '</section>',
    ['toast' => false, 'modal' => false, 'logout' => true, 'userMenu' => true]
);
?>