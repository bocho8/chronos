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
$sidebar = new Sidebar('admin-publicar-horarios.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole(['ADMIN', 'DIRECTOR']);

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $dbConfig = require __DIR__ . '/../../config/database.php';
        $database = new Database($dbConfig);
        $horarioModel = new Horario($database->getConnection());
        
        if ($_POST['action'] === 'publish_schedule') {
            $userId = $_SESSION['user']['id_usuario'] ?? null;
            
            if ($userId) {
                $result = $horarioModel->publishSchedule($userId);
                
                if ($result) {
                    $message = $translation->get('schedule_published_success');
                    $messageType = 'success';

                    $database->query("INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())", [
                        $userId,
                        "Publicó horarios oficiales"
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

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $horarioModel = new Horario($database->getConnection());
    
    $unpublishedSchedules = $horarioModel->getUnpublishedSchedules();
    $publishedSchedules = $horarioModel->getPublishedSchedules();
    $pendingRequests = $horarioModel->getPendingPublishRequests();
    
    if ($unpublishedSchedules === false) {
        $unpublishedSchedules = [];
    }
    if ($publishedSchedules === false) {
        $publishedSchedules = [];
    }
    if ($pendingRequests === false) {
        $pendingRequests = [];
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
                
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block"><?php _e('schedule_publication'); ?></div>
                <div class="text-white text-sm font-semibold text-center sm:hidden"><?php _e('publication'); ?></div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <span class="text-white text-sm">🔔</span>
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                                <div class="text-gray-500"><?php _e(AuthHelper::getCurrentUserRoleTranslationKey()); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <span class="inline mr-2 text-xs">👤</span>
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <span class="inline mr-2 text-xs">⚙</span>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" onclick="logout()">
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
                        <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('schedule_publication'); ?></h2>
                        <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('publish_schedules_description'); ?></p>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <?php if ($messageType === 'success'): ?>
                                    <?php else: ?>
                                        <span class="text-sm"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Pending Publish Requests -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Solicitudes de Publicación Pendientes</h3>
                        
                        <?php if (empty($pendingRequests)): ?>
                            <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
                                <p class="mt-2 text-sm text-gray-500">No hay solicitudes de publicación pendientes</p>
                            </div>
                        <?php else: ?>
                            <div class="grid gap-4">
                                <?php foreach ($pendingRequests as $request): ?>
                                    <div class="schedule-card bg-white rounded-lg p-4 md:p-6">
                                        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-4">
                                            <div class="flex-1">
                                                <div class="flex flex-col sm:flex-row sm:items-center mb-2 gap-2 sm:gap-3">
                                                    <h4 class="text-base md:text-lg font-semibold text-gray-900">
                                                        Solicitud de Publicación
                                                    </h4>
                                                    <span class="unpublished-badge self-start sm:self-center">Pendiente de Aprobación</span>
                                                </div>
                                                <p class="text-sm md:text-base text-gray-600 mb-2">
                                                    <strong>Solicitado por:</strong> <?php echo htmlspecialchars($request['solicitante_nombre'] . ' ' . $request['solicitante_apellido']); ?>
                                                </p>
                                                <p class="text-sm md:text-base text-gray-600 mb-2">
                                                    <strong>Fecha de solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($request['fecha_solicitud'])); ?>
                                                </p>
                                                <p class="text-sm md:text-base text-gray-600 mb-4">
                                                    <strong>Descripción:</strong> Solicitud de publicación de todos los horarios del sistema
                                                </p>
                                            </div>
                                            <div class="flex flex-col sm:flex-row gap-2 sm:space-x-3">
                                                <button 
                                                    onclick="viewSchedulePreview(<?php echo $request['id_publicacion']; ?>)"
                                                    class="px-3 md:px-4 py-2 text-xs md:text-sm text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                                    <span class="text-xs md:text-sm">👁️</span>
                                                    Ver Vista Previa
                                                </button>
                                                <button 
                                                    onclick="approveRequest(<?php echo $request['id_solicitud']; ?>)"
                                                    class="px-3 md:px-4 py-2 text-xs md:text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                    <span class="text-xs md:text-sm">✅</span>
                                                    <?php _e('approve_publish_request'); ?>
                                                </button>
                                                <button 
                                                    onclick="rejectRequest(<?php echo $request['id_solicitud']; ?>)"
                                                    class="px-3 md:px-4 py-2 text-xs md:text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                                    <span class="text-xs md:text-sm">❌</span>
                                                    <?php _e('reject_publish_request'); ?>
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
                                <p class="mt-2 text-sm text-gray-500">No hay horarios publicados</p>
                            </div>
                        <?php else: ?>
                            <div class="grid gap-4">
                                <?php foreach ($publishedSchedules as $schedule): ?>
                                    <div class="schedule-card bg-white rounded-lg p-4 md:p-6">
                                        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-4">
                                            <div class="flex-1">
                                                <div class="flex flex-col sm:flex-row sm:items-center mb-2 gap-2 sm:gap-3">
                                                    <h4 class="text-base md:text-lg font-semibold text-gray-900">
                                                        Horario - <?php echo htmlspecialchars($schedule['nombre'] ?? 'Sin nombre'); ?>
                                                    </h4>
                                                    <span class="published-badge self-start sm:self-center">Publicado</span>
                                                </div>
                                                <p class="text-sm md:text-base text-gray-600 mb-2">
                                                    <strong>Publicado:</strong> <?php echo date('d/m/Y H:i', strtotime($schedule['fecha_publicacion'] ?? 'now')); ?>
                                                </p>
                                                <p class="text-sm md:text-base text-gray-600 mb-4">
                                                    <strong>Descripción:</strong> <?php echo htmlspecialchars($schedule['descripcion'] ?? 'Horario publicado'); ?>
                                                </p>
                                            </div>
                                            <div class="flex flex-col sm:flex-row gap-2 sm:space-x-3">
                                                <button 
                                                    onclick="viewPublishedSchedules()"
                                                    class="px-3 md:px-4 py-2 text-xs md:text-sm text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                                    <span class="text-xs md:text-sm">👁️</span>
                                                    Ver Horarios
                                                </button>
                                                <button 
                                                    onclick="deletePublishedSchedule(<?php echo $schedule['id_publicacion']; ?>)"
                                                    class="px-3 md:px-4 py-2 text-xs md:text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                                    <span class="text-xs md:text-sm">🗑️</span>
                                                    Eliminar
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

    <script src="/js/toast.js"></script>
    <script>
        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        async function approveRequest(requestId) {
            const confirmed = await showConfirmModal(
                '<?php _e('confirm_publish'); ?>',
                '¿Está seguro de que desea aprobar esta solicitud de publicación? Los horarios serán publicados inmediatamente.',
                '<?php _e('confirm'); ?>',
                '<?php _e('cancel'); ?>'
            );
            if (confirmed) {
                fetch('/api/publish-request/approve?action=approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('<?php _e('publish_request_approved'); ?>');
                        location.reload();
                    } else {
                        alert(data.message || 'Error al aprobar la solicitud');
                    }
                })
                .catch(error => {
                    alert('Error al aprobar la solicitud');
                });
            }
        }

        function rejectRequest(requestId) {
            const reason = prompt('¿Por qué desea rechazar esta solicitud? (opcional):');
            if (reason !== null) {
                fetch('/api/publish-request/reject?action=reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId,
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('<?php _e('publish_request_rejected'); ?>');
                        location.reload();
                    } else {
                        alert(data.message || 'Error al rechazar la solicitud');
                    }
                })
                .catch(error => {
                    alert('Error al rechazar la solicitud');
                });
            }
        }

        function viewSchedulePreview(publicationId) {
            // Redirect to read-only schedule viewer with specific publication
            window.location.href = '/view-schedules?publication_id=' + publicationId;
        }

        function viewPublishedSchedules() {
            // Redirect to schedule viewer for published schedules
            window.location.href = '/view-schedules';
        }

        async function deletePublishedSchedule(publicationId) {
            const confirmed = await showConfirmModal(
                '<?php _e('confirm_delete'); ?>',
                '¿Está seguro de que desea eliminar estos horarios publicados? Esta acción no se puede deshacer.',
                '<?php _e('confirm'); ?>',
                '<?php _e('cancel'); ?>'
            );
            if (!confirmed) {
                return;
            }
            
            fetch('/src/controllers/PublishRequestHandler.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    publication_id: publicationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Reload the page to update the list
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'Error al eliminar los horarios publicados', 'error');
                }
            })
            .catch(error => {
                showToast('Error de conexión al eliminar horarios', 'error');
            });
        }

        async function logout() {
            const confirmed = await showConfirmModal(
                '<?php _e('confirm_logout'); ?>',
                '<?php _e('confirm_logout_message'); ?>',
                '<?php _e('confirm'); ?>',
                '<?php _e('cancel'); ?>'
            );
            if (confirmed) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        }

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
