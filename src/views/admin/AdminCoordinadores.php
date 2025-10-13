<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Coordinador.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-coordinadores.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $coordinadorModel = new Coordinador($database->getConnection());
    $coordinadores = $coordinadorModel->getAllCoordinadores();
    
    if ($coordinadores === false) {
        $coordinadores = [];
    }
} catch (Exception $e) {
    error_log("Error cargando coordinadores: " . $e->getMessage());
    $coordinadores = [];
    $error_message = 'Error interno del servidor';
}

function getUserInitials($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('coordinators_management'); ?></title>
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

        #coordinadorModal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 10000 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            padding: 1rem !important;
        }
        
        #coordinadorModal.hidden {
            display: none !important;
        }
        
        #coordinadorModal .modal-content {
            position: relative !important;
            z-index: 10001 !important;
            background: white !important;
            border-radius: 12px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            max-width: 500px !important;
            width: 100% !important;
            max-height: 90vh !important;
            overflow-y: auto !important;
            animation: modalSlideIn 0.3s ease-out !important;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        #coordinadorModal button[type="submit"], 
        #coordinadorModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
            background-color: #1f366d !important;
            color: white !important;
            transition: all 0.2s ease !important;
        }
        
        #coordinadorModal button[type="submit"]:hover, 
        #coordinadorModal button[type="button"]:hover {
            background-color: #1a2d5a !important;
            transform: translateY(-1px) !important;
        }

        #coordinadorModal input:focus,
        #coordinadorModal select:focus,
        #coordinadorModal textarea:focus,
        #coordinadorModal button:focus {
            outline: 2px solid #1f366d !important;
            outline-offset: 2px !important;
        }

        .error-input {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }

        .sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        @media (max-width: 640px) {
            #coordinadorModal {
                padding: 0.5rem !important;
            }
            
            #coordinadorModal .modal-content {
                max-height: 95vh !important;
                border-radius: 8px !important;
            }
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <!-- Main -->
        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <!-- Espacio para el botón de menú hamburguesa -->
                <div class="w-8"></div>
                
                <!-- Título centrado -->
                <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                
                <!-- Contenedor de iconos a la derecha -->
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <span class="text-white text-sm">🔔</span>
                    </button>
                    
                    <!-- User Menu Dropdown -->
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <!-- Dropdown Menu -->
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

            <!-- Contenido principal - Centrado -->
            <section class="flex-1 px-6 py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('coordinators_management'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('coordinators_management_description'); ?></p>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <!-- Header de la tabla -->
                        <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-medium text-darktext"><?php _e('coordinators'); ?></h3>
                            <div class="flex gap-2">
                                <button onclick="showAddCoordinadorModal()" class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                                    <span class="mr-1 text-sm">+</span>
                                    <?php _e('add_coordinator'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Lista de coordinadores -->
                        <div class="divide-y divide-gray-200">
                            <?php if (!empty($coordinadores)): ?>
                                <?php foreach ($coordinadores as $coordinador): ?>
                                    <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg">
                                        <div class="flex items-center">
                                            <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                                                <?php echo getUserInitials($coordinador['nombre'], $coordinador['apellido']); ?>
                                            </div>
                                            <div class="meta">
                                                <div class="font-semibold text-darktext mb-1">
                                                    <?php echo htmlspecialchars($coordinador['nombre'] . ' ' . $coordinador['apellido']); ?>
                                                </div>
                                                <div class="text-muted text-sm">
                                                    <?php echo htmlspecialchars($coordinador['email']); ?> • 
                                                    CI: <?php echo htmlspecialchars($coordinador['cedula']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button onclick="editCoordinador(<?php echo $coordinador['id_usuario']; ?>)" 
                                                    class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                                                <?php _e('edit'); ?>
                                            </button>
                                            <button onclick="deleteCoordinador(<?php echo $coordinador['id_usuario']; ?>, '<?php echo htmlspecialchars($coordinador['nombre'] . ' ' . $coordinador['apellido']); ?>')" 
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                                                <?php _e('delete'); ?>
                                            </button>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-8 text-center">
                                    <div class="text-gray-500 text-lg mb-2"><?php _e('no_coordinators_found'); ?></div>
                                    <div class="text-gray-400 text-sm"><?php _e('add_first_coordinator'); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal para agregar/editar coordinador -->
    <div id="coordinadorModal" class="hidden" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDescription">
        <div class="modal-content p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_coordinator'); ?></h3>
                <button onclick="closeCoordinadorModal()" class="text-gray-400 hover:text-gray-600" aria-label="<?php _e('close_modal'); ?>">
                    <span class="text-sm" aria-hidden="true">×</span>
                </button>
            </div>
            <p id="modalDescription" class="text-sm text-gray-600 mb-6 sr-only"><?php _e('modal_description'); ?></p>

            <form id="coordinadorForm" onsubmit="handleFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="id_coordinador" name="id" value="">
                
                <div>
                    <label for="cedula" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('cedula'); ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="cedula" name="cedula" required maxlength="8" pattern="[0-9]{8}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="12345678" aria-describedby="cedulaError cedulaHelp">
                    <p id="cedulaHelp" class="text-xs text-gray-500 mt-1"><?php _e('cedula_help'); ?></p>
                    <p id="cedulaError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('name'); ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="<?php _e('name_placeholder'); ?>" aria-describedby="nombreError">
                    <p id="nombreError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('lastname'); ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="apellido" name="apellido" required maxlength="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="<?php _e('lastname_placeholder'); ?>" aria-describedby="apellidoError">
                    <p id="apellidoError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('email'); ?> <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" required maxlength="150"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="usuario@ejemplo.com" aria-describedby="emailError">
                    <p id="emailError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('phone'); ?></label>
                    <input type="tel" id="telefono" name="telefono" maxlength="20" pattern="[0-9\s\-\+\(\)]+"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="099123456" aria-describedby="telefonoError telefonoHelp">
                    <p id="telefonoHelp" class="text-xs text-gray-500 mt-1"><?php _e('phone_help'); ?></p>
                    <p id="telefonoError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="contrasena" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('password'); ?> <span id="passwordRequired" class="text-red-500" style="display: none;">*</span></label>
                    <div class="relative">
                        <input type="password" id="contrasena" name="contrasena" minlength="8" maxlength="255"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                               placeholder="<?php _e('password_placeholder'); ?>" aria-describedby="contrasenaError contrasenaHelp">
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors" aria-label="<?php _e('toggle_password_visibility'); ?>">
                            <svg id="passwordIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p id="contrasenaHelp" class="text-xs text-gray-500 mt-1"><?php _e('password_help'); ?></p>
                    <p id="contrasenaError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeCoordinadorModal()" 
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

    <!-- Toast Container -->
    <div id="toastContainer">    </div>

    <script src="/js/toast.js"></script>
    <script>
        let isEditMode = false;

        function showAddCoordinadorModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = '<?php _e('add_coordinator'); ?>';
            document.getElementById('coordinadorForm').reset();
            
            document.getElementById('contrasena').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            
            clearErrors();
            document.getElementById('coordinadorModal').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('cedula').focus();
            }, 100);
        }

        function editCoordinador(id) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = '<?php _e('edit_coordinator'); ?>';
            
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('/src/controllers/coordinador_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('id_coordinador').value = data.data.id_usuario;
                    document.getElementById('cedula').value = data.data.cedula;
                    document.getElementById('nombre').value = data.data.nombre;
                    document.getElementById('apellido').value = data.data.apellido;
                    document.getElementById('email').value = data.data.email;
                    document.getElementById('telefono').value = data.data.telefono || '';
                    
                    document.getElementById('contrasena').required = false;
                    document.getElementById('contrasena').value = '';
                    document.getElementById('passwordRequired').style.display = 'none';
                    
                    clearErrors();
                    document.getElementById('coordinadorModal').classList.remove('hidden');
                    
                    setTimeout(() => {
                        document.getElementById('cedula').focus();
                    }, 100);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error cargando datos del coordinador', 'error');
            });
        }

        function deleteCoordinador(id, nombre) {
            const confirmMessage = `¿Está seguro de que desea eliminar al coordinador "${nombre}"?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('/src/controllers/coordinador_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Coordinador eliminado exitosamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error eliminando coordinador', 'error');
                });
            }
        }

        function closeCoordinadorModal() {
            const modal = document.getElementById('coordinadorModal');
            modal.classList.add('hidden');
            clearErrors();
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            
            if (!validateCoordinadorForm()) {
                showToast('<?php _e('please_correct_errors'); ?>', 'error');
                return;
            }
            
            const formData = new FormData(e.target);
            formData.append('action', isEditMode ? 'update' : 'create');
            
            fetch('/src/controllers/coordinador_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeCoordinadorModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (data.data && typeof data.data === 'object') {
                        Object.keys(data.data).forEach(field => {
                            showFieldError(field, data.data[field]);
                        });
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('<?php _e('error_processing_request'); ?>', 'error');
            });
        }

        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
            
            const inputElements = document.querySelectorAll('input, select, textarea');
            inputElements.forEach(element => {
                element.classList.remove('error-input');
            });
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('contrasena');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                passwordInput.type = 'password';
                passwordIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
        
        function validateCoordinadorForm() {
            let isValid = true;
            clearErrors();
            
            const cedula = document.getElementById('cedula').value.trim();
            if (!cedula) {
                showFieldError('cedula', '<?php _e('cedula_required'); ?>');
                isValid = false;
            } else if (!/^[0-9]{8}$/.test(cedula)) {
                showFieldError('cedula', '<?php _e('cedula_invalid_format'); ?>');
                isValid = false;
            }
            
            const nombre = document.getElementById('nombre').value.trim();
            if (!nombre) {
                showFieldError('nombre', '<?php _e('name_required'); ?>');
                isValid = false;
            } else if (nombre.length < 2) {
                showFieldError('nombre', '<?php _e('name_too_short'); ?>');
                isValid = false;
            }
            
            const apellido = document.getElementById('apellido').value.trim();
            if (!apellido) {
                showFieldError('apellido', '<?php _e('lastname_required'); ?>');
                isValid = false;
            } else if (apellido.length < 2) {
                showFieldError('apellido', '<?php _e('lastname_too_short'); ?>');
                isValid = false;
            }
            
            const email = document.getElementById('email').value.trim();
            if (!email) {
                showFieldError('email', '<?php _e('email_required'); ?>');
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError('email', '<?php _e('email_invalid_format'); ?>');
                isValid = false;
            }
            
            const telefono = document.getElementById('telefono').value.trim();
            if (telefono && !/^[0-9\s\-\+\(\)]+$/.test(telefono)) {
                showFieldError('telefono', '<?php _e('phone_invalid_format'); ?>');
                isValid = false;
            }
            
            const contrasena = document.getElementById('contrasena').value;
            if (!isEditMode && (!contrasena || contrasena.length < 8)) {
                showFieldError('contrasena', '<?php _e('password_required_min_length'); ?>');
                isValid = false;
            } else if (contrasena && contrasena.length < 8) {
                showFieldError('contrasena', '<?php _e('password_min_length'); ?>');
                isValid = false;
            }
            
            return isValid;
        }
        
        function showFieldError(fieldName, message) {
            const errorElement = document.getElementById(fieldName + 'Error');
            const inputElement = document.getElementById(fieldName);
            
            if (errorElement) {
                errorElement.textContent = message;
            }
            
            if (inputElement) {
                inputElement.classList.add('error-input');
            }
        }

        document.getElementById('coordinadorModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCoordinadorModal();
            }
        });

        const togglePasswordBtn = document.getElementById('togglePassword');
        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', togglePasswordVisibility);
        }

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
