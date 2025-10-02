<?php
// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Materia.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-materias.php');

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Load database configuration and get materias
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // Get materias and related data
    $materiaModel = new Materia($database->getConnection());
    $materias = $materiaModel->getAllMaterias();
    $pautasAnep = $materiaModel->getAllPautasAnep();
    $grupos = $materiaModel->getAllGrupos();
    
    if ($materias === false) {
        $materias = [];
    }
} catch (Exception $e) {
    error_log("Error cargando materias: " . $e->getMessage());
    $materias = [];
    $pautasAnep = [];
    $grupos = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('subjects'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <style type="text/css">
        .hamburger span {
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 3px 0;
            border-radius: 2px;
            transition: all 0.3s;
        }
        body {
            overflow-x: hidden;
        }
        .sidebar-link {
            position: relative;
            transition: all 0.3s;
        }
        .sidebar-link.active {
            background-color: #dee2e6;
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
        
        /* Modal styles - Improved for accessibility and responsiveness */
        #materiaModal {
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
        
        #materiaModal.hidden {
            display: none !important;
        }
        
        #materiaModal .modal-content {
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
        
        #materiaModal button[type="submit"], 
        #materiaModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
            background-color: #1f366d !important;
            color: white !important;
            transition: all 0.2s ease !important;
        }
        
        #materiaModal button[type="submit"]:hover, 
        #materiaModal button[type="button"]:hover {
            background-color: #1a2d5a !important;
            transform: translateY(-1px) !important;
        }
        
        /* Focus styles for accessibility */
        #materiaModal input:focus,
        #materiaModal select:focus,
        #materiaModal textarea:focus,
        #materiaModal button:focus {
            outline: 2px solid #1f366d !important;
            outline-offset: 2px !important;
        }
        
        /* Error state styles */
        .error-input {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }
        
        /* Screen reader only class */
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
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            #materiaModal {
                padding: 0.5rem !important;
            }
            
            #materiaModal .modal-content {
                max-height: 95vh !important;
                border-radius: 8px !important;
            }
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

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
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('subjects_management'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('subjects_management_description'); ?></p>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-medium text-darktext"><?php _e('subjects'); ?></h3>
                            <div class="flex gap-2">
                                <button onclick="openMateriaModal()" class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                                    <span class="mr-1 text-sm">+</span>
                                    <?php _e('add_subject'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Lista de materias -->
                        <div class="divide-y divide-gray-200">
                            <?php if (!empty($materias)): ?>
                                <?php foreach ($materias as $materia): ?>
                                    <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                                                <?php echo strtoupper(substr($materia['nombre'], 0, 1)); ?>
                                            </div>
                                            <div class="meta">
                                                <div class="font-semibold text-darktext mb-1">
                                                    <?php echo htmlspecialchars($materia['nombre']); ?>
                                                </div>
                                                <div class="text-muted text-sm">
                                                    <?php echo $materia['horas_semanales']; ?> horas semanales
                                                    <?php if ($materia['pauta_anep_nombre']): ?>
                                                        • <?php echo htmlspecialchars($materia['pauta_anep_nombre']); ?>
                                                    <?php endif; ?>
                                                    <?php if ($materia['es_programa_italiano']): ?>
                                                        • Programa Italiano
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button onclick="editMateria(<?php echo $materia['id_materia']; ?>)" 
                                                    class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                                                <?php _e('edit'); ?>
                                            </button>
                                            <button onclick="deleteMateria(<?php echo $materia['id_materia']; ?>, '<?php echo htmlspecialchars($materia['nombre']); ?>')" 
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                                                <?php _e('delete'); ?>
                                            </button>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-8 text-center">
                                    <div class="text-gray-500 text-lg mb-2"><?php _e('no_subjects_found'); ?></div>
                                    <div class="text-gray-400 text-sm"><?php _e('add_first_subject'); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="/js/toast.js"></script>
    <script>

        let isEditMode = false;

        // Modal functions
        function openMateriaModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = '<?php _e('add_subject'); ?>';
            document.getElementById('materiaForm').reset();
            document.getElementById('materiaId').value = '';
            document.getElementById('horas_semanales').value = '1';
            
            clearErrors();
            document.getElementById('materiaModal').classList.remove('hidden');
            
            // Focus on first input
            setTimeout(() => {
                document.getElementById('nombre').focus();
            }, 100);
        }

        function closeMateriaModal() {
            document.getElementById('materiaModal').classList.add('hidden');
            clearErrors();
        }

        // Edit materia
        function editMateria(id) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = '<?php _e('edit_subject'); ?>';
            
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('/src/controllers/materia_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('materiaId').value = data.data.id_materia;
                    document.getElementById('nombre').value = data.data.nombre;
                    document.getElementById('horas_semanales').value = data.data.horas_semanales;
                    document.getElementById('id_pauta_anep').value = data.data.id_pauta_anep;
                    document.getElementById('en_conjunto').checked = data.data.en_conjunto;
                    document.getElementById('es_programa_italiano').checked = data.data.es_programa_italiano;
                    
                    // Handle grupo compartido
                    if (data.data.en_conjunto && data.data.id_grupo_compartido) {
                        document.getElementById('id_grupo_compartido').value = data.data.id_grupo_compartido;
                        toggleGrupoCompartido(); // Show the field
                    } else {
                        toggleGrupoCompartido(); // Hide the field
                    }
                    
                    clearErrors();
                    document.getElementById('materiaModal').classList.remove('hidden');
                    
                    // Focus on first input
                    setTimeout(() => {
                        document.getElementById('nombre').focus();
                    }, 100);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error cargando datos de la materia', 'error');
            });
        }

        // Delete materia
        function deleteMateria(id, nombre) {
            const confirmMessage = `¿Está seguro de que desea eliminar la materia "${nombre}"?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('/src/controllers/materia_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Materia eliminada exitosamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error eliminando materia', 'error');
                });
            }
        }

        // Handle form submission
        function handleMateriaFormSubmit(e) {
            e.preventDefault();
            
            if (!validateMateriaForm()) {
                showToast('<?php _e('please_correct_errors'); ?>', 'error');
                return;
            }
            
            const formData = new FormData(e.target);
            formData.append('action', isEditMode ? 'update' : 'create');
            
            fetch('/src/controllers/materia_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeMateriaModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (data.data && typeof data.data === 'object') {
                        // Show validation errors from server
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

        // Clear validation errors
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
        
        // Toggle grupo compartido visibility
        function toggleGrupoCompartido() {
            const enConjunto = document.getElementById('en_conjunto').checked;
            const grupoCompartidoDiv = document.getElementById('grupoCompartidoDiv');
            const grupoCompartidoSelect = document.getElementById('id_grupo_compartido');
            
            if (enConjunto) {
                grupoCompartidoDiv.classList.remove('hidden');
                grupoCompartidoSelect.required = true;
            } else {
                grupoCompartidoDiv.classList.add('hidden');
                grupoCompartidoSelect.required = false;
                grupoCompartidoSelect.value = '';
            }
        }
        
        // Validation functions
        function validateMateriaForm() {
            let isValid = true;
            clearErrors();
            
            // Validate nombre
            const nombre = document.getElementById('nombre').value.trim();
            if (!nombre) {
                showFieldError('nombre', '<?php _e('subject_name_required'); ?>');
                isValid = false;
            } else if (nombre.length < 2) {
                showFieldError('nombre', '<?php _e('subject_name_too_short'); ?>');
                isValid = false;
            }
            
            // Validate horas_semanales
            const horasSemanales = document.getElementById('horas_semanales').value;
            if (!horasSemanales || horasSemanales < 1 || horasSemanales > 40) {
                showFieldError('horas_semanales', '<?php _e('weekly_hours_invalid'); ?>');
                isValid = false;
            }
            
            // Validate pauta ANEP
            const pautaAnep = document.getElementById('id_pauta_anep').value;
            if (!pautaAnep) {
                showFieldError('id_pauta_anep', '<?php _e('anep_guideline_required'); ?>');
                isValid = false;
            }
            
            // Validate grupo compartido if en_conjunto is checked
            const enConjunto = document.getElementById('en_conjunto').checked;
            if (enConjunto) {
                const grupoCompartido = document.getElementById('id_grupo_compartido').value;
                if (!grupoCompartido) {
                    showFieldError('id_grupo_compartido', '<?php _e('shared_group_required'); ?>');
                    isValid = false;
                }
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

        // Funcionalidad para la barra lateral
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener todos los enlaces de la barra lateral
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            
            // Función para manejar el clic en los enlaces
            function handleSidebarClick(event) {
                // Remover la clase active de todos los enlaces
                sidebarLinks.forEach(link => {
                    link.classList.remove('active');
                });
                
                // Agregar la clase active al enlace clickeado
                this.classList.add('active');
            }
            
            // Agregar event listener a cada enlace
            sidebarLinks.forEach(link => {
                link.addEventListener('click', handleSidebarClick);
            });
            
            // Logout functionality
            const logoutButton = document.getElementById('logoutButton');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Show confirmation dialog
                    const confirmMessage = '<?php _e('confirm_logout'); ?>';
                    if (confirm(confirmMessage)) {
                        // Create form and submit logout request
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/src/controllers/LogoutController.php';
                        
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'logout';
                        
                        form.appendChild(actionInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
            
            // User menu toggle
            const userMenuButton = document.getElementById('userMenuButton');
            const userMenu = document.getElementById('userMenu');
            
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    <!-- Modal para agregar/editar materia -->
    <div id="materiaModal" class="hidden" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDescription">
        <div class="modal-content p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_subject'); ?></h3>
                <button onclick="closeMateriaModal()" class="text-gray-400 hover:text-gray-600" aria-label="<?php _e('close_modal'); ?>">
                    <span class="text-sm" aria-hidden="true">×</span>
                </button>
            </div>
            <p id="modalDescription" class="text-sm text-gray-600 mb-6 sr-only"><?php _e('modal_description'); ?></p>
            
            <form id="materiaForm" onsubmit="handleMateriaFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="materiaId" name="id">
                
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('subject_name'); ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre" name="nombre" required maxlength="200"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="<?php _e('subject_name_placeholder'); ?>" aria-describedby="nombreError">
                    <p id="nombreError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="horas_semanales" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('weekly_hours'); ?> <span class="text-red-500">*</span></label>
                    <input type="number" id="horas_semanales" name="horas_semanales" min="1" max="40" value="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           aria-describedby="horas_semanalesError">
                    <p id="horas_semanalesError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="id_pauta_anep" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('anep_guideline'); ?> <span class="text-red-500">*</span></label>
                    <select id="id_pauta_anep" name="id_pauta_anep" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                            aria-describedby="id_pauta_anepError">
                        <option value=""><?php _e('select_guideline'); ?></option>
                        <?php foreach ($pautasAnep as $pauta): ?>
                            <option value="<?php echo $pauta['id_pauta_anep']; ?>">
                                <?php echo htmlspecialchars($pauta['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="id_pauta_anepError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="en_conjunto" name="en_conjunto" value="1"
                           class="h-4 w-4 text-darkblue focus:ring-darkblue border-gray-300 rounded"
                           onchange="toggleGrupoCompartido()">
                    <label for="en_conjunto" class="ml-2 block text-sm text-gray-900">
                        <?php _e('joint_class'); ?>
                    </label>
                </div>
                
                <div id="grupoCompartidoDiv" class="hidden">
                    <label for="id_grupo_compartido" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('shared_group'); ?></label>
                    <select id="id_grupo_compartido" name="id_grupo_compartido"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                            aria-describedby="id_grupo_compartidoError">
                        <option value=""><?php _e('select_group'); ?></option>
                        <?php foreach ($grupos as $grupo): ?>
                            <option value="<?php echo $grupo['id_grupo']; ?>">
                                <?php echo htmlspecialchars($grupo['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="id_grupo_compartidoError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="es_programa_italiano" name="es_programa_italiano" value="1"
                           class="h-4 w-4 text-darkblue focus:ring-darkblue border-gray-300 rounded">
                    <label for="es_programa_italiano" class="ml-2 block text-sm text-gray-900">
                        <?php _e('italian_program'); ?>
                    </label>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeMateriaModal()" 
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
    <div id="toastContainer"></div>
</body>
</html>