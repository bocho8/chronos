<?php
/**
 * Vista para Publicar Horarios - Funciones de Dirección
 * Según ESRE 4.3: Solo la Dirección puede publicar horarios generados
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-publicar-horarios.php');

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role (acting as director)
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $dbConfig = require __DIR__ . '/../../config/database.php';
        $database = new Database($dbConfig);
        $horarioModel = new Horario($database->getConnection());
        
        if ($_POST['action'] === 'publish_schedule') {
            $scheduleId = $_POST['schedule_id'] ?? null;
            
            if ($scheduleId) {
                // Update schedule status to published
                $result = $horarioModel->publishSchedule($scheduleId);
                
                if ($result) {
                    $message = $translation->get('schedule_published_success');
                    $messageType = 'success';
                    
                    // Log the action
                    $database->query("INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())", [
                        $_SESSION['user']['id_usuario'],
                        "Publicó horario ID: $scheduleId"
                    ]);
                } else {
                    $message = $translation->get('schedule_publication_failed');
                    $messageType = 'error';
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error publishing schedule: " . $e->getMessage());
        $message = $translation->get('schedule_publication_failed');
        $messageType = 'error';
    }
}

// Load schedule data
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $horarioModel = new Horario($database->getConnection());
    
    // Get unpublished schedules
    $unpublishedSchedules = $horarioModel->getUnpublishedSchedules();
    
    // Get published schedules
    $publishedSchedules = $horarioModel->getPublishedSchedules();
    
    if ($unpublishedSchedules === false) {
        $unpublishedSchedules = [];
    }
    if ($publishedSchedules === false) {
        $publishedSchedules = [];
    }
    
} catch (Exception $e) {
    error_log("Error loading schedules: " . $e->getMessage());
    $unpublishedSchedules = [];
    $publishedSchedules = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('schedule_publication'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <style>
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
        .schedule-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .schedule-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .published-badge {
            background-color: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .unpublished-badge {
            background-color: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
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
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('schedule_publication'); ?></div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                                <div class="text-gray-500"><?php _e('role_admin'); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" onclick="logout()">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <section class="flex-1 px-6 py-8">
                <div class="max-w-6xl mx-auto">
                    <!-- Page Header -->
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('schedule_publication'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('publish_schedules_description'); ?></p>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <?php if ($messageType === 'success'): ?>
                                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    <?php else: ?>
                                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Unpublished Schedules -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Horarios Pendientes de Publicación</h3>
                        
                        <?php if (empty($unpublishedSchedules)): ?>
                            <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No hay horarios pendientes de publicación</p>
                            </div>
                        <?php else: ?>
                            <div class="grid gap-4">
                                <?php foreach ($unpublishedSchedules as $schedule): ?>
                                    <div class="schedule-card bg-white rounded-lg p-6">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center mb-2">
                                                    <h4 class="text-lg font-semibold text-gray-900 mr-3">
                                                        Horario - <?php echo htmlspecialchars($schedule['nombre'] ?? 'Sin nombre'); ?>
                                                    </h4>
                                                    <span class="unpublished-badge">Pendiente</span>
                                                </div>
                                                <p class="text-gray-600 mb-2">
                                                    <strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($schedule['fecha_creacion'] ?? 'now')); ?>
                                                </p>
                                                <p class="text-gray-600 mb-4">
                                                    <strong>Descripción:</strong> <?php echo htmlspecialchars($schedule['descripcion'] ?? 'Horario generado automáticamente'); ?>
                                                </p>
                                            </div>
                                            <div class="flex space-x-3">
                                                <button 
                                                    onclick="viewSchedule(<?php echo $schedule['id_horario']; ?>)"
                                                    class="px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    Ver
                                                </button>
                                                <button 
                                                    onclick="publishSchedule(<?php echo $schedule['id_horario']; ?>)"
                                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                    </svg>
                                                    Publicar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Published Schedules -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Horarios Publicados</h3>
                        
                        <?php if (empty($publishedSchedules)): ?>
                            <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No hay horarios publicados</p>
                            </div>
                        <?php else: ?>
                            <div class="grid gap-4">
                                <?php foreach ($publishedSchedules as $schedule): ?>
                                    <div class="schedule-card bg-white rounded-lg p-6">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center mb-2">
                                                    <h4 class="text-lg font-semibold text-gray-900 mr-3">
                                                        Horario - <?php echo htmlspecialchars($schedule['nombre'] ?? 'Sin nombre'); ?>
                                                    </h4>
                                                    <span class="published-badge">Publicado</span>
                                                </div>
                                                <p class="text-gray-600 mb-2">
                                                    <strong>Publicado:</strong> <?php echo date('d/m/Y H:i', strtotime($schedule['fecha_publicacion'] ?? 'now')); ?>
                                                </p>
                                                <p class="text-gray-600 mb-4">
                                                    <strong>Descripción:</strong> <?php echo htmlspecialchars($schedule['descripcion'] ?? 'Horario publicado'); ?>
                                                </p>
                                            </div>
                                            <div class="flex space-x-3">
                                                <button 
                                                    onclick="viewSchedule(<?php echo $schedule['id_horario']; ?>)"
                                                    class="px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    Ver
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Hidden form for publishing schedules -->
    <form id="publishForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="publish_schedule">
        <input type="hidden" name="schedule_id" id="publishScheduleId">
    </form>

    <script>
        function publishSchedule(scheduleId) {
            if (confirm('¿Está seguro de que desea publicar este horario? Una vez publicado, será visible para todos los usuarios.')) {
                document.getElementById('publishScheduleId').value = scheduleId;
                document.getElementById('publishForm').submit();
            }
        }

        function viewSchedule(scheduleId) {
            // Redirect to schedule view page
            window.location.href = 'admin-horarios.php?view=' + scheduleId;
        }

        function logout() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        }
    </script>
</body>
</html>
