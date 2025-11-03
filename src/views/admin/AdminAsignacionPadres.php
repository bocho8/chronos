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
require_once __DIR__ . '/../../models/Padre.php';
require_once __DIR__ . '/../../models/Grupo.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-asignacion-padres.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole(['ADMIN', 'DIRECTOR', 'COORDINADOR']);

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /login?message=session_expired");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $padreModel = new Padre($database->getConnection());
    $grupoModel = new Grupo($database->getConnection());
    
    $padres = $padreModel->getAllParentsWithGroups();
    $grupos = $grupoModel->getAllGrupos();
    
    if ($padres === false) {
        $padres = [];
    }
    if ($grupos === false) {
        $grupos = [];
    }
} catch (Exception $e) {
    error_log("Error cargando datos de asignación: " . $e->getMessage());
    $padres = [];
    $grupos = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('parent_group_assignment'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <style>
        .assignment-card {
            transition: all 0.2s ease;
        }
        .assignment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .group-tag {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .group-tag:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }

        /* Modal styles consistent with other admin pages */
        #editModal {
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
            width: 100vw !important;
            height: 100vh !important;
        }
        
        #editModal.hidden {
            display: none !important;
        }
        
        #editModal .modal-content {
            position: relative !important;
            z-index: 10001 !important;
            background: white !important;
            border-radius: 8px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            max-width: 90vw !important;
            width: calc(100% - 2rem) !important;
            margin: 1rem !important;
            max-height: calc(100vh - 2rem) !important;
            overflow-y: auto !important;
            animation: modalSlideIn 0.3s ease-out !important;
        }
        
        @media (min-width: 640px) {
            #editModal .modal-content {
                border-radius: 12px !important;
                width: 100% !important;
                margin: 0 !important;
                max-width: 600px !important;
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
        
        #editModal button[type="submit"], 
        #editModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
        }

        /* Multiple select styling */
        #edit_groupSelect, #groupSelect {
            background-color: #f8fafc !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 12px !important;
            font-size: 14px !important;
            line-height: 1.5 !important;
            transition: all 0.2s ease !important;
        }

        #edit_groupSelect:focus, #groupSelect:focus {
            border-color: #1f366d !important;
            box-shadow: 0 0 0 3px rgba(31, 54, 109, 0.1) !important;
            background-color: white !important;
        }

        #edit_groupSelect option, #groupSelect option {
            padding: 8px 12px !important;
            background-color: white !important;
            color: #374151 !important;
        }

        #edit_groupSelect option:checked, #groupSelect option:checked {
            background-color: #1f366d !important;
            color: white !important;
            font-weight: 600 !important;
        }

        #edit_groupSelect option:hover, #groupSelect option:hover {
            background-color: #f1f5f9 !important;
        }

        #edit_groupSelect option:checked:hover, #groupSelect option:checked:hover {
            background-color: #1a2d5a !important;
        }

        /* Make multiple select behave like checkboxes - no Ctrl needed */
        #edit_groupSelect, #groupSelect {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }

        /* Custom styling for better UX */
        #edit_groupSelect option, #groupSelect option {
            position: relative !important;
            padding-left: 30px !important;
        }

        #edit_groupSelect option:checked::before, #groupSelect option:checked::before {
            content: "✓" !important;
            position: absolute !important;
            left: 8px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: white !important;
            font-weight: bold !important;
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
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block flex-1"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                <div class="text-white text-sm font-semibold text-center sm:hidden flex-1"><?php _e('welcome'); ?></div>
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
                                <div class="text-gray-500"><?php _e('role_admin'); ?></div>
                            </div>
                            <a href="/src/views/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><?php _e('logout'); ?></a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <div class="flex-1 p-3 sm:p-4 md:p-6">
                <div class="max-w-7xl mx-auto">
                    <div class="mb-4 sm:mb-6 md:mb-8">
                        <h2 class="text-darktext text-xl sm:text-2xl font-semibold mb-2 sm:mb-2.5"><?php _e('parent_group_assignment'); ?></h2>
                        <p class="text-muted mb-4 sm:mb-6 text-sm sm:text-base"><?php _e('parent_group_assignment_description'); ?></p>
                    </div>

                    <!-- Assignment Form -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 sm:mb-6">
                        <div class="p-3 sm:p-4 border-b border-gray-200">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900"><?php _e('assign_groups_to_parent'); ?></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <form id="assignmentForm" class="space-y-3 sm:space-y-4">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
                                    <div>
                                        <label for="parentSelect" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('select_parent'); ?> <span class="text-red-500">*</span></label>
                                        <select id="parentSelect" name="id_padre" required class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                            <option value=""><?php _e('select_parent'); ?></option>
                                            <?php foreach ($padres as $padre): ?>
                                                <option value="<?php echo $padre['id_padre']; ?>">
                                                    <?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="groupSelect" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('select_groups'); ?> <span class="text-red-500">*</span></label>
                                        <select id="groupSelect" name="id_grupos[]" multiple required class="w-full min-h-[150px] sm:min-h-[200px] text-sm sm:text-base">
                                            <?php foreach ($grupos as $grupo): ?>
                                                <option value="<?php echo $grupo['id_grupo']; ?>">
                                                    <?php echo htmlspecialchars($grupo['nombre'] . ' (' . $grupo['nivel'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mt-2 gap-2">
                                            <p class="text-xs text-gray-500 flex items-start sm:items-center">
                                                <svg class="w-4 h-4 mr-1 mt-0.5 sm:mt-0 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="hidden sm:inline">Haz clic en los grupos para seleccionarlos (selección múltiple automática)</span>
                                                <span class="sm:hidden">Clic para seleccionar grupos</span>
                                            </p>
                                            <span id="groupCount" class="text-xs font-medium text-darkblue bg-blue-100 px-2 py-1 rounded-full whitespace-nowrap">
                                                0 grupos seleccionados
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="w-full sm:w-auto px-4 py-2 text-sm sm:text-base bg-darkblue text-white rounded-md hover:bg-navy transition-colors">
                                        <?php _e('assign_groups'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Parents List -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-3 sm:p-4 border-b border-gray-200">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900"><?php _e('parents_and_groups'); ?></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <div class="space-y-3 sm:space-y-4">
                                <?php if (!empty($padres)): ?>
                                    <?php foreach ($padres as $padre): ?>
                                        <div class="assignment-card p-3 sm:p-4 border border-gray-200 rounded-lg">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-medium text-gray-900 text-sm sm:text-base truncate"><?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?></h4>
                                                    <p class="text-xs sm:text-sm text-gray-600 truncate mt-1"><?php echo htmlspecialchars($padre['email']); ?></p>
                                                    <div class="mt-2 flex flex-wrap gap-1.5 sm:gap-2">
                                                        <?php if (!empty($padre['grupos_asignados'])): ?>
                                                            <?php $gruposAsignados = explode(', ', $padre['grupos_asignados']); ?>
                                                            <?php foreach ($gruposAsignados as $grupo): ?>
                                                                <span class="group-tag text-xs"><?php echo htmlspecialchars(trim($grupo)); ?></span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-gray-500 text-xs sm:text-sm"><?php _e('no_groups_assigned'); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="flex gap-2 sm:ml-4 flex-shrink-0">
                                                    <button onclick="editParentAssignments(<?php echo $padre['id_padre']; ?>, '<?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?>')" 
                                                            class="px-3 py-1.5 sm:py-1 text-xs sm:text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors whitespace-nowrap">
                                                        <?php _e('edit'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-8 text-sm sm:text-base"><?php _e('no_parents_found'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden">
        <div class="modal-content">
            <div class="p-4 sm:p-6">
                <div class="flex justify-between items-center mb-4 sm:mb-6">
                    <h3 id="editModalTitle" class="text-lg sm:text-xl font-semibold text-gray-900 pr-2">Editar Grupos del Padre</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="editForm" class="space-y-4 sm:space-y-6">
                    <input type="hidden" id="edit_id_padre" name="id_padre">
                    
                    <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Padre Seleccionado</label>
                        <p id="edit_padre_nombre" class="text-base sm:text-lg font-semibold text-darkblue break-words"></p>
                    </div>
                    
                    <div>
                        <label for="edit_groupSelect" class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
                            Grupos Asignados <span class="text-red-500">*</span>
                        </label>
                        <select id="edit_groupSelect" name="id_grupos[]" multiple required 
                                class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue min-h-[200px] sm:min-h-[250px] text-sm">
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?php echo $grupo['id_grupo']; ?>">
                                    <?php echo htmlspecialchars($grupo['nombre'] . ' (' . $grupo['nivel'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mt-2 gap-2">
                            <p class="text-xs text-gray-500 flex items-start sm:items-center">
                                <svg class="w-4 h-4 mr-1 mt-0.5 sm:mt-0 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="hidden sm:inline">Haz clic en los grupos para seleccionarlos (selección múltiple automática)</span>
                                <span class="sm:hidden">Clic para seleccionar grupos</span>
                            </p>
                            <span id="editGroupCount" class="text-xs font-medium text-darkblue bg-blue-100 px-2 py-1 rounded-full whitespace-nowrap">
                                0 grupos seleccionados
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeEditModal()" 
                                class="w-full sm:w-auto px-4 sm:px-6 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="w-full sm:w-auto px-4 sm:px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-darkblue hover:bg-navy transition-colors">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script src="/js/toast.js?v=<?php echo time(); ?>"></script>
    <script>
        // Function to update group count
        function updateGroupCount(selectId, countId) {
            const select = document.getElementById(selectId);
            const countElement = document.getElementById(countId);
            if (select && countElement) {
                const selectedCount = select.selectedOptions.length;
                countElement.textContent = `${selectedCount} grupo${selectedCount !== 1 ? 's' : ''} seleccionado${selectedCount !== 1 ? 's' : ''}`;
                
                // Update styling based on selection
                if (selectedCount === 0) {
                    countElement.className = 'text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full';
                } else {
                    countElement.className = 'text-xs font-medium text-darkblue bg-blue-100 px-2 py-1 rounded-full';
                }
            }
        }

        // Wait for DOM and toast system to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for group count updates
            const groupSelect = document.getElementById('groupSelect');
            const editGroupSelect = document.getElementById('edit_groupSelect');
            
            if (groupSelect) {
                groupSelect.addEventListener('change', function() {
                    updateGroupCount('groupSelect', 'groupCount');
                });
                
                // Make clicking work without Ctrl
                groupSelect.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    const option = e.target;
                    if (option.tagName === 'OPTION') {
                        option.selected = !option.selected;
                        this.dispatchEvent(new Event('change'));
                    }
                });
                
                // Initial count
                updateGroupCount('groupSelect', 'groupCount');
            }
            
            if (editGroupSelect) {
                editGroupSelect.addEventListener('change', function() {
                    updateGroupCount('edit_groupSelect', 'editGroupCount');
                });
                
                // Make clicking work without Ctrl
                editGroupSelect.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    const option = e.target;
                    if (option.tagName === 'OPTION') {
                        option.selected = !option.selected;
                        this.dispatchEvent(new Event('change'));
                    }
                });
            }

            // Assignment form
            document.getElementById('assignmentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'assign_groups');
                
                fetch('/api/parent-assignments', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showToast === 'function') {
                            showToast('Grupos asignados exitosamente', 'success');
                        }
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error: ' + data.message, 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error procesando solicitud', 'error');
                    }
                });
            });

            // Edit form
            const editForm = document.getElementById('editForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'assign_groups');
                    formData.append('replace', 'true');
                    
                    fetch('/api/parent-assignments', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof showToast === 'function') {
                                showToast('Grupos actualizados exitosamente', 'success');
                            }
                            closeEditModal();
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            if (typeof showToast === 'function') {
                                showToast('Error: ' + data.message, 'error');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (typeof showToast === 'function') {
                            showToast('Error procesando solicitud', 'error');
                        }
                    });
                });
            }
        });

        function editParentAssignments(id_padre, nombre) {
            // Set the parent info
            document.getElementById('edit_id_padre').value = id_padre;
            document.getElementById('edit_padre_nombre').textContent = nombre;
            
            // Get current assignments for this parent
            fetch('/api/parent-assignments?action=get_parent_groups&id_padre=' + id_padre)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('edit_groupSelect');
                        const assignedIds = data.data.map(g => g.id_grupo.toString());
                        
                        // Clear all selections
                        Array.from(select.options).forEach(option => {
                            option.selected = false;
                        });
                        
                        // Select assigned groups
                        Array.from(select.options).forEach(option => {
                            if (assignedIds.includes(option.value)) {
                                option.selected = true;
                            }
                        });
                        
                        // Update group count
                        updateGroupCount('edit_groupSelect', 'editGroupCount');
                        
                        // Show modal
                        document.getElementById('editModal').classList.remove('hidden');
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error cargando grupos del padre', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error de conexión', 'error');
                    }
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
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
        
        // Close sidebar when clicking outside on mobile
        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            toggleSidebar();
        });
    </script>
</body>
</html>
