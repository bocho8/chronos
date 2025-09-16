<?php
/**
 * Página de gestión de docentes
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Docente.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';

// Initialize secure session
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

// Load database configuration
$dbConfig = require __DIR__ . '/../../config/database.php';
$database = new Database($dbConfig);

// Get all docentes
$docenteModel = new Docente($database->getConnection());
$docentes = $docenteModel->getAllDocentes();
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('teachers_management'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
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
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        /* Toast styles */
        .toast {
            @apply transform transition-all duration-300 ease-in-out;
            @apply max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto;
            @apply ring-1 ring-black ring-opacity-5 overflow-hidden;
        }

        .toast.show {
            @apply translate-x-0 opacity-100;
        }

        .toast.hide {
            @apply translate-x-full opacity-0;
        }

        .toast-success {
            @apply border-l-4 border-green-400;
        }

        .toast-error {
            @apply border-l-4 border-red-400;
        }

        .toast-warning {
            @apply border-l-4 border-yellow-400;
        }

        .toast-info {
            @apply border-l-4 border-blue-400;
        }
        
        /* Toast styles */
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
        .toast-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .toast-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .toast-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .toast-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        #toastContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }
        
        /* Modal styles */
        #docenteModal {
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
            -webkit-backdrop-filter: blur(8px) !important;
        }
        
        #docenteModal.hidden {
            display: none !important;
        }
        
        #docenteModal .modal-content {
            position: relative !important;
            z-index: 10001 !important;
            background: white !important;
            border-radius: 8px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }
        
        #docenteModal button[type="submit"], 
        #docenteModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
            background-color: #1f366d !important;
            color: white !important;
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-sidebar border-r border-border">
            <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
                <img src="/upload/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
                <span class="text-white font-semibold text-lg"><?php _e('scuola_italiana'); ?></span>
            </div>

            <ul class="py-5 list-none">
                <li>
                    <a href="index.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('dashboard'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-usuarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('users'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-docentes.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('teachers'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-coordinadores.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('coordinators'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-materias.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('subjects'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-horarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('schedules'); ?>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="flex-1 flex flex-col">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
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

            <section class="flex-1 px-6 py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('teachers_management'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('teachers_management_description'); ?></p>
                    </div>

                    <!-- Botón de agregar docente -->
                    <div class="mb-6">
                        <button onclick="showAddDocenteModal()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                            <?php _e('add_teacher'); ?>
                        </button>
                    </div>

                    <!-- Teachers List -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php _e('teachers'); ?></h3>
                            
                            <!-- Filtros -->
                            <div class="mb-6 flex flex-wrap gap-4">
                                <div class="flex-1 min-w-64">
                                    <input type="text" id="searchInput" placeholder="<?php _e('search_placeholder'); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="flex gap-2">
                                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                                        <?php _e('export'); ?>
                                    </button>
                                    <button class="px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors">
                                        <?php _e('delete_selected'); ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de docentes -->
                            <div id="docentesList">
                                <?php if (empty($docentes)): ?>
                                    <div class="text-center py-12">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php _e('no_teachers_found'); ?></h3>
                                        <p class="text-gray-500 mb-4"><?php _e('add_first_teacher'); ?></p>
                                        <button onclick="showAddDocenteModal()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                                            <?php _e('add_teacher'); ?>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($docentes as $docente): ?>
                                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                                        <?php echo strtoupper(substr($docente['nombre'], 0, 1) . substr($docente['apellido'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($docente['email']); ?> • CI: <?php echo htmlspecialchars($docente['cedula']); ?></div>
                                                    </div>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <button onclick="editDocente(<?php echo $docente['id_docente']; ?>)" 
                                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                                                        <?php _e('edit'); ?>
                                                    </button>
                                                    <button onclick="deleteDocente(<?php echo $docente['id_docente']; ?>, '<?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>')" 
                                                            class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors">
                                                        <?php _e('delete'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <!-- Modal para agregar/editar docente -->
    <div id="docenteModal" class="hidden">
        <div class="modal-content p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_teacher'); ?></h3>
                <button onclick="closeDocenteModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="docenteForm" onsubmit="handleFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="id_docente" name="id_docente" value="">
                
                <div>
                    <label for="cedula" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('cedula'); ?></label>
                    <input type="text" id="cedula" name="cedula" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                </div>
                
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('name'); ?></label>
                    <input type="text" id="nombre" name="nombre" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                </div>
                
                <div>
                    <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('lastname'); ?></label>
                    <input type="text" id="apellido" name="apellido" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('email'); ?></label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                </div>
                
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('phone'); ?></label>
                    <input type="text" id="telefono" name="telefono"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                </div>
                
                <div>
                    <label for="contrasena" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('password'); ?></label>
                    <input type="password" id="contrasena" name="contrasena" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                    <p class="text-xs text-gray-500 mt-1"><?php _e('password_leave_blank'); ?></p>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeDocenteModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('cancel'); ?>
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-darkblue hover:bg-navy focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('save'); ?>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script>
        let isEditMode = false;

        // Mostrar modal para agregar docente
        function showAddDocenteModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = '<?php _e('add_teacher'); ?>';
            document.getElementById('docenteForm').reset();
            clearErrors();
            document.getElementById('docenteModal').classList.remove('hidden');
        }

        // Editar docente
        function editDocente(id) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = '<?php _e('edit_teacher'); ?>';
            
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('/src/controllers/docente_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('id_docente').value = data.data.id_docente;
                    document.getElementById('cedula').value = data.data.cedula;
                    document.getElementById('nombre').value = data.data.nombre;
                    document.getElementById('apellido').value = data.data.apellido;
                    document.getElementById('email').value = data.data.email;
                    document.getElementById('telefono').value = data.data.telefono;
                    document.getElementById('trabaja_otro_liceo').checked = data.data.trabaja_otro_liceo;
                    
                    document.getElementById('docenteModal').classList.remove('hidden');
                    
                    // Focus on first input
                    setTimeout(() => {
                        document.getElementById('cedula').focus();
                    }, 300);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error cargando datos del docente', 'error');
            });
        }

        // Eliminar docente
        function deleteDocente(id, nombre) {
            const confirmMessage = `¿Está seguro de que desea eliminar al docente "${nombre}"?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id_docente', id);
                
                fetch('/src/controllers/docente_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Docente eliminado exitosamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error eliminando docente', 'error');
                });
            }
        }

        // Cerrar modal
        function closeDocenteModal() {
            const modal = document.getElementById('docenteModal');
            modal.classList.add('hidden');
            clearErrors();
        }

        // Manejar envío del formulario
        function handleFormSubmit(e) {
            e.preventDefault();
            
            clearErrors();
            
            const formData = new FormData(e.target);
            formData.append('action', isEditMode ? 'update' : 'create');
            
            fetch('/src/controllers/docente_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                    
                    if (data.success) {
                        showToast(data.message, 'success');
                        closeDocenteModal();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        if (data.data && typeof data.data === 'object') {
                            // Show validation errors
                            Object.keys(data.data).forEach(field => {
                                const errorElement = document.getElementById(field + 'Error');
                                if (errorElement) {
                                    errorElement.textContent = data.data[field];
                                }
                            });
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error procesando solicitud', 'error');
            });
        }

        // Limpiar errores de validación
        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
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
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Auto hide after 5 seconds
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

        // Cerrar modal al hacer clic fuera
        document.getElementById('docenteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDocenteModal();
            }
        });

        // Logout functionality
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
