<?php
/**
 * Página de asignación de materias a docentes para administradores
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';
require_once __DIR__ . '/../../models/Docente.php';

// Initialize secure session
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-asignaciones.php');

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

// Load database configuration and get data
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // Get models
    $horarioModel = new Horario($database->getConnection());
    $docenteModel = new Docente($database->getConnection());
    
    // Get data
    $docentes = $docenteModel->getAllDocentes();
    $materias = $horarioModel->getAllMaterias();
    
    // Get current assignments
    $pdo = $database->getConnection();
    $stmt = $pdo->prepare("
        SELECT dm.*, d.id_docente, u.nombre, u.apellido, m.nombre as materia_nombre 
        FROM docente_materia dm
        JOIN docente d ON dm.id_docente = d.id_docente
        JOIN usuario u ON d.id_usuario = u.id_usuario
        JOIN materia m ON dm.id_materia = m.id_materia
        ORDER BY u.apellido, u.nombre, m.nombre
    ");
    $stmt->execute();
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error cargando asignaciones: " . $e->getMessage());
    $docentes = [];
    $materias = [];
    $asignaciones = [];
    $error_message = 'Error interno del servidor';
}

// Function to get user initials
function getUserInitials($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('subject_assignments'); ?></title>
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
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            padding: 16px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            max-width: 400px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toast.show {
            transform: translateX(0);
        }
        .toast-success { background: linear-gradient(135deg, #10b981, #059669); }
        .toast-error { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .toast-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .toast-info { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        #toastContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }
        
        /* Modal styles */
        #asignacionModal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 10000 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: rgba(0, 0, 0, 0.2) !important;
            backdrop-filter: blur(8px) !important;
        }
        
        #asignacionModal.hidden {
            display: none !important;
        }
        
        #asignacionModal .modal-content {
            position: relative !important;
            z-index: 10001 !important;
            background: white !important;
            border-radius: 8px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1) !important;
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
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('subject_assignments'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('subject_assignments_description'); ?></p>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <!-- Header de la tabla -->
                        <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-medium text-darktext"><?php _e('current_assignments'); ?></h3>
                            <div class="flex gap-2">
                                <button onclick="openAsignacionModal()" class="py-2 px-4 border-none rounded cursor-pointer font-medium transition-all text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <?php _e('assign_subject'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Lista de asignaciones -->
                        <div class="divide-y divide-gray-200">
                            <?php if (!empty($asignaciones)): ?>
                                <?php foreach ($asignaciones as $asignacion): ?>
                                    <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg">
                                        <div class="flex items-center">
                                            <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                                                <?php echo getUserInitials($asignacion['nombre'], $asignacion['apellido']); ?>
                                            </div>
                                            <div class="meta">
                                                <div class="font-semibold text-darktext mb-1">
                                                    <?php echo htmlspecialchars($asignacion['nombre'] . ' ' . $asignacion['apellido']); ?>
                                                </div>
                                                <div class="text-muted text-sm">
                                                    <?php _e('teaches'); ?>: <?php echo htmlspecialchars($asignacion['materia_nombre']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button onclick="removeAsignacion(<?php echo $asignacion['id_docente']; ?>, <?php echo $asignacion['id_materia']; ?>, '<?php echo htmlspecialchars($asignacion['materia_nombre']); ?>')" 
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                                                <?php _e('remove'); ?>
                                            </button>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-8 text-center">
                                    <div class="text-gray-500 text-lg mb-2"><?php _e('no_assignments_found'); ?></div>
                                    <div class="text-gray-400 text-sm"><?php _e('assign_first_subject'); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal para asignar materia -->
    <div id="asignacionModal" class="hidden">
        <div class="modal-content p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('assign_subject'); ?></h3>
                <button onclick="closeAsignacionModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="asignacionForm" onsubmit="handleAsignacionFormSubmit(event)" class="space-y-4">
                <div>
                    <label for="id_docente" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('teacher'); ?></label>
                    <select id="id_docente" name="id_docente" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                        <option value=""><?php _e('select_teacher'); ?></option>
                        <?php foreach ($docentes as $docente): ?>
                            <option value="<?php echo $docente['id_docente']; ?>">
                                <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="id_materia" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('subject'); ?></label>
                    <select id="id_materia" name="id_materia" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                        <option value=""><?php _e('select_subject'); ?></option>
                        <?php foreach ($materias as $materia): ?>
                            <option value="<?php echo $materia['id_materia']; ?>">
                                <?php echo htmlspecialchars($materia['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeAsignacionModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('cancel'); ?>
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-darkblue hover:bg-navy focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('assign'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script>
        // Modal functions
        function openAsignacionModal() {
            document.getElementById('asignacionForm').reset();
            document.getElementById('asignacionModal').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('id_docente').focus();
            }, 100);
        }

        function closeAsignacionModal() {
            document.getElementById('asignacionModal').classList.add('hidden');
        }

        // Handle form submission
        function handleAsignacionFormSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'create');
            
            fetch('/src/controllers/asignacion_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const docente = document.getElementById('id_docente').selectedOptions[0].text;
                    const materia = document.getElementById('id_materia').selectedOptions[0].text;
                    showToast(`Asignación creada: ${docente} → ${materia}`, 'success');
                    closeAsignacionModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Error al crear la asignación', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            });
        }

        // Remove assignment
        function removeAsignacion(idDocente, idMateria, materiaNombre) {
            const confirmMessage = `¿Está seguro de que desea remover la asignación de "${materiaNombre}"?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id_docente', idDocente);
                formData.append('id_materia', idMateria);
                
                fetch('/src/controllers/asignacion_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Asignación removida: ${materiaNombre}`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data.message || 'Error al eliminar la asignación', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error de conexión', 'error');
                });
            }
        }

        // Toast notification functions
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = getToastIcon(type);
            toast.innerHTML = `
                <div class="flex items-center">
                    ${icon}
                    <span>${message}</span>
                </div>
                <button onclick="hideToast(this)" class="ml-4 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => hideToast(toast), 5000);
        }

        function getToastIcon(type) {
            const icons = {
                success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
                info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };
            return icons[type] || icons.info;
        }

        function hideToast(toast) {
            if (toast && toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }

        // Logout functionality
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });

        // Close modal when clicking outside
        document.getElementById('asignacionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAsignacionModal();
            }
        });
    </script>
</body>
</html>
