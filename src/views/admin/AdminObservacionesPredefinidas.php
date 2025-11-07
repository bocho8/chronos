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

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-observaciones-predefinidas.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole(['ADMIN', 'DIRECTOR', 'COORDINADOR']);

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $db = $database->getConnection();
    
    // Load predefined observaciones
    $query = "SELECT id_observacion_predefinida, texto, es_sistema, activa 
              FROM observacion_predefinida 
              ORDER BY es_sistema DESC, texto ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $observacionesPredefinidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($observacionesPredefinidas === false) {
        $observacionesPredefinidas = [];
    }
} catch (Exception $e) {
    error_log("Error cargando observaciones predefinidas: " . $e->getMessage());
    $observacionesPredefinidas = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('observaciones_predefinidas'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <script src="/js/toast.js?v=<?php echo time(); ?>"></script>
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
        #observacionModal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 10000 !important;
            display: none !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            padding: 1rem !important;
        }
        
        #observacionModal.show {
            display: flex !important;
        }
        
        #observacionModal .modal-content {
            position: relative !important;
            z-index: 10001 !important;
            background: white !important;
            border-radius: 12px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            max-width: 90vw !important;
            width: 100% !important;
            max-height: 90vh !important;
            overflow-y: auto !important;
            animation: modalSlideIn 0.3s ease-out !important;
        }
        
        @media (min-width: 640px) {
            #observacionModal .modal-content {
                max-width: 500px !important;
            }
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
        
        #observacionModal button[type="submit"], 
        #observacionModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
            background-color: #1f366d !important;
            color: white !important;
            transition: all 0.2s ease !important;
        }
        
        #observacionModal button[type="button"]:not([onclick*="closeModal"]) {
            background-color: #6b7280 !important;
        }
        
        #observacionModal button[type="submit"]:hover, 
        #observacionModal button[type="button"]:hover {
            background-color: #1a2d5a !important;
            transform: translateY(-1px) !important;
        }
        
        #observacionModal button[type="button"]:not([onclick*="closeModal"]):hover {
            background-color: #4b5563 !important;
        }

        #observacionModal input:focus,
        #observacionModal select:focus,
        #observacionModal textarea:focus,
        #observacionModal button:focus {
            outline: 2px solid #1f366d !important;
            outline-offset: 2px !important;
        }
        
        .badge-system {
            background-color: #3b82f6;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-active {
            background-color: #10b981;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-inactive {
            background-color: #6b7280;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .observacion-item {
            transition: all 0.2s ease;
        }
        .observacion-item:hover {
            background-color: #f9fafb;
        }
        
        #toastContainer {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 10000 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
            pointer-events: none !important;
        }
        
        #toastContainer .toast {
            pointer-events: auto !important;
        }
        
        @media (max-width: 640px) {
            #observacionModal {
                padding: 0.5rem !important;
            }
            
            #observacionModal .modal-content {
                max-height: 95vh !important;
                border-radius: 8px !important;
            }
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
                
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block">
                    <?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> 
                    (<?php _e('role_' . strtolower(AuthHelper::getCurrentUserRole() ?? 'admin')); ?>)
                </div>
                <div class="text-white text-sm font-semibold text-center sm:hidden"><?php _e('welcome'); ?></div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <span class="text-white text-sm">🔔</span>
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block" id="userMenu">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                                <div class="text-gray-500"><?php _e('role_' . strtolower(AuthHelper::getCurrentUserRole() ?? 'admin')); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                                <span class="inline mr-2 text-xs">👤</span>
                                <?php _e('profile'); ?>
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

            <!-- Contenido principal -->
            <section class="flex-1 p-3 sm:p-4 md:p-6 w-full overflow-x-hidden">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-6 md:mb-8">
                        <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('observaciones_predefinidas'); ?></h2>
                        <p class="text-muted mb-4 md:mb-6 text-sm md:text-base">Gestionar observaciones predefinidas que pueden ser seleccionadas por los docentes</p>
                    </div>

                    <!-- Container con header y botón -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-3 md:p-4 border-b border-lightborder bg-gray-50 gap-3 md:gap-0">
                            <h3 class="font-medium text-darktext text-sm md:text-base"><?php _e('observaciones_predefinidas'); ?></h3>
                            <div class="flex gap-2">
                                <button onclick="openModal()" 
                                        class="py-2 px-3 md:px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-xs md:text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                                    <span class="mr-1 text-sm">+</span>
                                    Agregar Observación
                                </button>
                            </div>
                        </div>

                        <!-- Lista de observaciones -->
                        <div class="divide-y divide-gray-200">
                            <?php if (empty($observacionesPredefinidas)): ?>
                                <div class="p-8 text-center">
                                    <div class="text-gray-500 text-lg mb-2">No hay observaciones predefinidas registradas</div>
                                    <div class="text-gray-400 text-sm">Haga clic en el botón "+" para agregar la primera observación predefinida</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($observacionesPredefinidas as $obs): ?>
                                    <article class="observacion-item flex flex-col md:flex-row items-start md:items-center justify-between p-3 md:p-4 transition-colors hover:bg-lightbg">
                                        <div class="flex items-center flex-1 min-w-0">
                                            <div class="w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                                                <?php echo strtoupper(substr($obs['texto'], 0, 1)); ?>
                                            </div>
                                            <div class="meta flex-1 min-w-0">
                                                <div class="font-semibold text-darktext mb-1 text-sm md:text-base">
                                                    <?php echo htmlspecialchars($obs['texto']); ?>
                                                </div>
                                                <div class="text-muted text-xs md:text-sm flex items-center gap-3 flex-wrap">
                                                    <span>ID: <?php echo htmlspecialchars($obs['id_observacion_predefinida']); ?></span>
                                                    <span>|</span>
                                                    <span>
                                                        Sistema: 
                                                        <?php if ($obs['es_sistema']): ?>
                                                            <span class="badge-system mr-1"><?php _e('yes'); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-gray-400"><?php _e('no'); ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span>|</span>
                                                    <span>
                                                        <?php if ($obs['activa']): ?>
                                                            <span class="badge-active"><?php _e('active'); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge-inactive"><?php _e('inactive'); ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <?php 
                                                    $obsTextLower = strtolower(trim($obs['texto']));
                                                    $isProtected = ($obsTextLower === 'otro' || $obsTextLower === 'otro liceo');
                                                    if ($isProtected): 
                                                    ?>
                                                        <span>|</span>
                                                        <span class="text-xs text-gray-400 italic">(Protegida)</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2 mt-2 md:mt-0">
                                            <?php 
                                            $obsTextLower = strtolower(trim($obs['texto']));
                                            $isProtected = ($obsTextLower === 'otro' || $obsTextLower === 'otro liceo');
                                            ?>
                                            <?php if (!$isProtected): ?>
                                                <button onclick="editObservacion(<?php echo htmlspecialchars($obs['id_observacion_predefinida']); ?>)" 
                                                        class="text-darkblue hover:text-navy text-xs md:text-sm font-medium transition-colors">
                                                    <?php _e('edit'); ?>
                                                </button>
                                                <span class="text-gray-300">|</span>
                                            <?php endif; ?>
                                            <?php if (!$isProtected): ?>
                                                <button onclick="deleteObservacion(<?php echo htmlspecialchars($obs['id_observacion_predefinida']); ?>, '<?php echo htmlspecialchars(addslashes($obs['texto'])); ?>')" 
                                                        class="text-red-600 hover:text-red-800 text-xs md:text-sm font-medium transition-colors">
                                                    <?php _e('delete'); ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400 italic">(Protegida)</span>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($observacionesPredefinidas)): ?>
                        <div class="bg-gray-50 border-t border-gray-200 px-4 py-3">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-sm">
                                <div class="text-gray-600">
                                    <strong>Total:</strong> <?php echo count($observacionesPredefinidas); ?> observaciones predefinidas
                                </div>
                                <?php 
                                $activas = count(array_filter($observacionesPredefinidas, function($o) { return $o['activa']; }));
                                $sistema = count(array_filter($observacionesPredefinidas, function($o) { return $o['es_sistema']; }));
                                ?>
                                <div class="text-gray-600">
                                    <strong><?php _e('active'); ?>:</strong> <?php echo $activas; ?> | 
                                    <strong>Sistema:</strong> <?php echo $sistema; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <!-- Modal para agregar/editar observación -->
    <div id="observacionModal" class="hidden" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDescription">
        <div class="modal-content p-4 md:p-8 w-full max-w-sm md:max-w-md mx-auto">
            <div class="flex justify-between items-center mb-4 md:mb-6">
                <h3 id="modalTitle" class="text-base md:text-lg font-semibold text-gray-900">Agregar Observación Predefinida</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600" aria-label="<?php _e('close_modal'); ?>" tabindex="0">
                    <span class="text-sm" aria-hidden="true">×</span>
                </button>
            </div>
            <p id="modalDescription" class="text-xs md:text-sm text-gray-600 mb-4 md:mb-6 sr-only">Formulario para agregar o editar una observación predefinida</p>
            
            <form id="observacionForm" class="space-y-4">
                <input type="hidden" id="observacionId" name="id_observacion_predefinida" value="">
                
                <div>
                    <label for="observacionTexto" class="block text-sm font-medium text-gray-700 mb-2">
                        Texto <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="observacionTexto" name="texto" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="Ingrese el texto de la observación"
                           minlength="1"
                           pattern=".*\S.*"
                           title="El texto no puede estar vacío o contener solo espacios">
                    <p id="observacionTextoError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" id="observacionActiva" name="activa" checked
                               class="mr-2 rounded border-gray-300 text-darkblue focus:ring-darkblue">
                        <span class="text-sm text-gray-700"><?php _e('active'); ?></span>
                    </label>
                </div>
                
                <div class="flex gap-2 pt-4">
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-darkblue text-white rounded-md hover:bg-navy transition-colors">
                        <?php _e('save'); ?>
                    </button>
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        <?php _e('cancel'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(editId = null) {
            const modal = document.getElementById('observacionModal');
            const form = document.getElementById('observacionForm');
            const title = document.getElementById('modalTitle');
            const textoInput = document.getElementById('observacionTexto');
            const activaCheckbox = document.getElementById('observacionActiva');
            
            if (editId) {
                title.textContent = 'Editar Observación Predefinida';
                loadObservacion(editId);
            } else {
                title.textContent = 'Agregar Observación Predefinida';
                form.reset();
                document.getElementById('observacionId').value = '';
                // Ensure fields are enabled for new observations
                if (textoInput) {
                    textoInput.disabled = false;
                    textoInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
                if (activaCheckbox) {
                    activaCheckbox.disabled = false;
                    activaCheckbox.classList.remove('cursor-not-allowed');
                }
            }
            
            modal.classList.add('show');
        }

        function closeModal() {
            const modal = document.getElementById('observacionModal');
            modal.classList.remove('show');
            document.getElementById('observacionForm').reset();
            document.getElementById('observacionId').value = '';
        }

        function loadObservacion(id) {
            if (!id || !Number.isInteger(parseInt(id))) {
                if (typeof showToast === 'function') {
                    showToast('ID de observación inválido', 'error');
                } else {
                    alert('ID de observación inválido');
                }
                return;
            }
            
            fetch('/api/observaciones-predefinidas?action=get&id_observacion_predefinida=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error HTTP: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.observacion) {
                        const obs = data.observacion;
                        const obsTextLower = (obs.texto || '').toLowerCase().trim();
                        const isProtected = (obsTextLower === 'otro' || obsTextLower === 'otro liceo');
                        
                        document.getElementById('observacionId').value = obs.id_observacion_predefinida;
                        const textoInput = document.getElementById('observacionTexto');
                        const activaCheckbox = document.getElementById('observacionActiva');
                        
                        textoInput.value = obs.texto || '';
                        activaCheckbox.checked = obs.activa !== false;
                        
                        // Disable editing for protected observations
                        if (isProtected) {
                            textoInput.disabled = true;
                            textoInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                            activaCheckbox.disabled = true;
                            activaCheckbox.classList.add('cursor-not-allowed');
                        } else {
                            textoInput.disabled = false;
                            textoInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                            activaCheckbox.disabled = false;
                            activaCheckbox.classList.remove('cursor-not-allowed');
                        }
                    } else {
                        const errorMsg = data.message || 'Error cargando observación';
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorMsg = error.message && error.message.includes('HTTP') 
                        ? 'Error del servidor. Por favor, intente nuevamente.' 
                        : 'Error de conexión. Verifique su conexión a internet.';
                    if (typeof showToast === 'function') {
                        showToast(errorMsg, 'error');
                    } else {
                        alert(errorMsg);
                    }
                });
        }

        function editObservacion(id) {
            openModal(id);
        }

        async function deleteObservacion(id, texto) {
            let confirmed = false;
            
            if (typeof showConfirmModal === 'function') {
                confirmed = await showConfirmModal(
                    'Confirmar Eliminación',
                    '¿Está seguro de que desea eliminar la observación "' + texto + '"?\n\nEsta acción no se puede deshacer.',
                    'Eliminar',
                    'Cancelar'
                );
            } else {
                confirmed = confirm('¿Está seguro de que desea eliminar la observación "' + texto + '"?\n\nEsta acción no se puede deshacer.');
            }
            
            if (!confirmed) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id_observacion_predefinida', id);

            fetch('/api/observaciones-predefinidas', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Error HTTP: ' + response.status);
                    }).catch(() => {
                        throw new Error('Error del servidor: ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Observación eliminada exitosamente', 'success');
                    } else {
                        alert('Observación eliminada exitosamente');
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const errorMsg = data.message || 'Error desconocido';
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + errorMsg, 'error');
                    } else {
                        alert('Error: ' + errorMsg);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = error.message || 'Error de conexión. Verifique su conexión a internet.';
                if (typeof showToast === 'function') {
                    showToast(errorMsg, 'error');
                } else {
                    alert(errorMsg);
                }
            });
        }

        document.getElementById('observacionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            const errorElement = document.getElementById('observacionTextoError');
            if (errorElement) {
                errorElement.textContent = '';
            }
            
            // Validate before submitting
            const texto = document.getElementById('observacionTexto').value.trim();
            if (!texto || texto.length === 0) {
                if (errorElement) {
                    errorElement.textContent = 'El texto es requerido';
                }
                if (typeof showToast === 'function') {
                    showToast('El texto es requerido', 'error');
                } else {
                    alert('El texto es requerido');
                }
                document.getElementById('observacionTexto').focus();
                return false;
            }
            
            if (texto.length > 500) {
                if (errorElement) {
                    errorElement.textContent = 'El texto no puede exceder 500 caracteres';
                }
                if (typeof showToast === 'function') {
                    showToast('El texto no puede exceder 500 caracteres', 'error');
                } else {
                    alert('El texto no puede exceder 500 caracteres');
                }
                document.getElementById('observacionTexto').focus();
                return false;
            }
            
            saveObservacion();
        });

        function saveObservacion() {
            const form = document.getElementById('observacionForm');
            const formData = new FormData(form);
            const observacionId = document.getElementById('observacionId').value;
            
            formData.append('action', observacionId ? 'update' : 'create');
            formData.append('activa', document.getElementById('observacionActiva').checked ? '1' : '0');

            const saveBtn = form.querySelector('button[type="submit"]');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Guardando...';

            // Client-side validation
            const texto = document.getElementById('observacionTexto').value.trim();
            if (!texto) {
                if (typeof showToast === 'function') {
                    showToast('El texto es requerido', 'error');
                } else {
                    alert('El texto es requerido');
                }
                saveBtn.disabled = false;
                saveBtn.textContent = 'Guardar';
                return;
            }
            
            if (texto.length > 500) {
                if (typeof showToast === 'function') {
                    showToast('El texto no puede exceder 500 caracteres', 'error');
                } else {
                    alert('El texto no puede exceder 500 caracteres');
                }
                saveBtn.disabled = false;
                saveBtn.textContent = 'Guardar';
                return;
            }
            
            fetch('/api/observaciones-predefinidas', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Error HTTP: ' + response.status);
                    }).catch(() => {
                        throw new Error('Error del servidor: ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Observación guardada exitosamente', 'success');
                    } else {
                        alert('Observación guardada exitosamente');
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const errorMsg = data.message || 'Error desconocido';
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + errorMsg, 'error');
                    } else {
                        alert('Error: ' + errorMsg);
                    }
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Guardar';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = error.message || 'Error de conexión. Verifique su conexión a internet.';
                if (typeof showToast === 'function') {
                    showToast(errorMsg, 'error');
                } else {
                    alert(errorMsg);
                }
                saveBtn.disabled = false;
                saveBtn.textContent = 'Guardar';
            });
        }

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });

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

