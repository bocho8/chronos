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

    <!-- Toast Notifications -->
    <div id="toastContainer" class="fixed top-4 right-4 z-[10000] space-y-2"></div>

    <!-- Modal para agregar/editar docente -->
    <div id="docenteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle"><?php _e('add_teacher'); ?></h3>
                    <button onclick="closeDocenteModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="docenteForm" onsubmit="handleFormSubmit(event)">
                    <input type="hidden" id="id_docente" name="id_docente" value="">
                    
                    <div class="mb-4">
                        <label for="cedula" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('id_number'); ?></label>
                        <input type="text" id="cedula" name="cedula" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('name'); ?></label>
                        <input type="text" id="nombre" name="nombre" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('lastname'); ?></label>
                        <input type="text" id="apellido" name="apellido" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('email'); ?></label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('phone'); ?></label>
                        <input type="text" id="telefono" name="telefono"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-6">
                        <label for="contrasena" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('password'); ?></label>
                        <input type="password" id="contrasena" name="contrasena" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDocenteModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            <?php _e('cancel'); ?>
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
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

        // Toast notification system
        function showToast(message, type = 'info', duration = 5000) {
            const toastContainer = document.getElementById('toastContainer');
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast toast-${type} transform translate-x-full opacity-0`;
            
            // Toast content
            const icon = getToastIcon(type);
            toast.innerHTML = `
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            ${icon}
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900">${message}</p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button onclick="hideToast(this)" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <span class="sr-only">Cerrar</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add to container
            toastContainer.appendChild(toast);
            
            // Show toast with animation
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
                toast.classList.add('show');
            }, 100);
            
            // Auto hide after duration
            setTimeout(() => {
                hideToast(toast.querySelector('button'));
            }, duration);
        }

        function getToastIcon(type) {
            const icons = {
                success: `<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>`,
                error: `<svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>`,
                warning: `<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.726-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>`,
                info: `<svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>`
            };
            return icons[type] || icons.info;
        }

        function hideToast(button) {
            const toast = button.closest('.toast');
            toast.classList.remove('show');
            toast.classList.add('hide');
            
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
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
