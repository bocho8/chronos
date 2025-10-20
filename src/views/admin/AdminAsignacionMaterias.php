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
require_once __DIR__ . '/../../models/Grupo.php';
require_once __DIR__ . '/../../models/Materia.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-asignacion-materias.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole(['ADMIN', 'DIRECTOR', 'COORDINADOR']);

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $grupoModel = new Grupo($database->getConnection());
    $materiaModel = new Materia($database->getConnection());
    
    $grupos = $grupoModel->getAllGroupsWithSubjects();
    $materias = $materiaModel->getAllMaterias();
    
    if ($grupos === false) {
        $grupos = [];
    }
    if ($materias === false) {
        $materias = [];
    }
} catch (Exception $e) {
    error_log("Error cargando datos de asignación: " . $e->getMessage());
    $grupos = [];
    $materias = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('group_subject_assignment'); ?></title>
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
        .subject-tag {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .subject-tag:hover {
            background: linear-gradient(135deg, #059669, #047857);
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
            border-radius: 12px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            max-width: 600px !important;
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
        
        #editModal button[type="submit"], 
        #editModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
        }

        /* Multiple select styling */
        #edit_subjectSelect, #subjectSelect {
            background-color: #f8fafc !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 12px !important;
            font-size: 14px !important;
            line-height: 1.5 !important;
            transition: all 0.2s ease !important;
        }

        #edit_subjectSelect:focus, #subjectSelect:focus {
            border-color: #1f366d !important;
            box-shadow: 0 0 0 3px rgba(31, 54, 109, 0.1) !important;
            background-color: white !important;
        }

        #edit_subjectSelect option, #subjectSelect option {
            padding: 8px 12px !important;
            background-color: white !important;
            color: #374151 !important;
        }

        #edit_subjectSelect option:checked, #subjectSelect option:checked {
            background-color: #1f366d !important;
            color: white !important;
            font-weight: 600 !important;
        }

        #edit_subjectSelect option:hover, #subjectSelect option:hover {
            background-color: #f1f5f9 !important;
        }

        #edit_subjectSelect option:checked:hover, #subjectSelect option:checked:hover {
            background-color: #1a2d5a !important;
        }

        /* Make multiple select behave like checkboxes - no Ctrl needed */
        #edit_subjectSelect, #subjectSelect {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }

        /* Custom styling for better UX */
        #edit_subjectSelect option, #subjectSelect option {
            position: relative !important;
            padding-left: 30px !important;
        }

        #edit_subjectSelect option:checked::before, #subjectSelect option:checked::before {
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

        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-4 md:px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
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
                                <div class="text-gray-500"><?php _e('role_admin'); ?></div>
                            </div>
                            <a href="/src/views/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><?php _e('logout'); ?></a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <div class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('group_subject_assignment'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('group_subject_assignment_description'); ?></p>
                    </div>

                    <!-- Assignment Form -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900"><?php _e('assign_subjects_to_group'); ?></h3>
                        </div>
                        <div class="p-4">
                            <form id="assignmentForm" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="groupSelect" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('select_group'); ?> <span class="text-red-500">*</span></label>
                                        <select id="groupSelect" name="id_grupo" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                            <option value=""><?php _e('select_group'); ?></option>
                                            <?php foreach ($grupos as $grupo): ?>
                                                <option value="<?php echo $grupo['id_grupo']; ?>">
                                                    <?php echo htmlspecialchars($grupo['nombre'] . ' (' . $grupo['nivel'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="subjectSelect" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('select_subjects'); ?> <span class="text-red-500">*</span></label>
                                        <select id="subjectSelect" name="id_materias[]" multiple required class="w-full min-h-[200px]">
                                            <?php foreach ($materias as $materia): ?>
                                                <option value="<?php echo $materia['id_materia']; ?>" data-hours="<?php echo $materia['horas_semanales']; ?>">
                                                    <?php echo htmlspecialchars($materia['nombre'] . ' (' . $materia['horas_semanales'] . 'h/sem)'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="flex justify-between items-center mt-2">
                                            <p class="text-xs text-gray-500 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Haz clic en las materias para seleccionarlas (selección múltiple automática)
                                            </p>
                                            <span id="subjectCount" class="text-xs font-medium text-darkblue bg-blue-100 px-2 py-1 rounded-full">
                                                0 materias seleccionadas
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="px-4 py-2 bg-darkblue text-white rounded-md hover:bg-navy transition-colors">
                                        <?php _e('assign_subjects'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Groups List -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900"><?php _e('groups_and_subjects'); ?></h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-4">
                                <?php if (!empty($grupos)): ?>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <div class="assignment-card p-4 border border-gray-200 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($grupo['nombre']); ?></h4>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($grupo['nivel']); ?></p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        <?php if (!empty($grupo['materias_asignadas'])): ?>
                                                            <?php $materiasAsignadas = explode(', ', $grupo['materias_asignadas']); ?>
                                                            <?php foreach ($materiasAsignadas as $materia): ?>
                                                                <span class="subject-tag"><?php echo htmlspecialchars(trim($materia)); ?></span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-gray-500 text-sm"><?php _e('no_subjects_assigned'); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button onclick="editGroupAssignments(<?php echo $grupo['id_grupo']; ?>, '<?php echo htmlspecialchars($grupo['nombre']); ?>')" 
                                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                                                        <?php _e('edit'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-8"><?php _e('no_groups_found'); ?></p>
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
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 id="editModalTitle" class="text-xl font-semibold text-gray-900">Editar Materias del Grupo</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="editForm" class="space-y-6">
                    <input type="hidden" id="edit_id_grupo" name="id_grupo">
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Grupo Seleccionado</label>
                        <p id="edit_grupo_nombre" class="text-lg font-semibold text-darkblue"></p>
                    </div>
                    
                    <div>
                        <label for="edit_subjectSelect" class="block text-sm font-medium text-gray-700 mb-2">
                            Materias Asignadas <span class="text-red-500">*</span>
                        </label>
                        <select id="edit_subjectSelect" name="id_materias[]" multiple required 
                                class="w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue min-h-[250px] text-sm">
                            <?php foreach ($materias as $materia): ?>
                                <option value="<?php echo $materia['id_materia']; ?>" data-hours="<?php echo $materia['horas_semanales']; ?>">
                                    <?php echo htmlspecialchars($materia['nombre'] . ' (' . $materia['horas_semanales'] . 'h/sem)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="flex justify-between items-center mt-2">
                            <p class="text-xs text-gray-500 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Haz clic en las materias para seleccionarlas (selección múltiple automática)
                            </p>
                            <span id="editSubjectCount" class="text-xs font-medium text-darkblue bg-blue-100 px-2 py-1 rounded-full">
                                0 materias seleccionadas
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-6 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-darkblue hover:bg-navy transition-colors">
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
        // Function to update subject count
        function updateSubjectCount(selectId, countId) {
            const select = document.getElementById(selectId);
            const countElement = document.getElementById(countId);
            if (select && countElement) {
                const selectedCount = select.selectedOptions.length;
                countElement.textContent = `${selectedCount} materia${selectedCount !== 1 ? 's' : ''} seleccionada${selectedCount !== 1 ? 's' : ''}`;
                
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
            // Add event listeners for subject count updates
            const subjectSelect = document.getElementById('subjectSelect');
            const editSubjectSelect = document.getElementById('edit_subjectSelect');
            
            if (subjectSelect) {
                subjectSelect.addEventListener('change', function() {
                    updateSubjectCount('subjectSelect', 'subjectCount');
                });
                
                // Make clicking work without Ctrl
                subjectSelect.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    const option = e.target;
                    if (option.tagName === 'OPTION') {
                        option.selected = !option.selected;
                        this.dispatchEvent(new Event('change'));
                    }
                });
                
                // Initial count
                updateSubjectCount('subjectSelect', 'subjectCount');
            }
            
            if (editSubjectSelect) {
                editSubjectSelect.addEventListener('change', function() {
                    updateSubjectCount('edit_subjectSelect', 'editSubjectCount');
                });
                
                // Make clicking work without Ctrl
                editSubjectSelect.addEventListener('mousedown', function(e) {
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
                formData.append('action', 'assign_subjects');
                
                fetch('/src/controllers/GroupSubjectHandler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showToast === 'function') {
                            showToast('Materias asignadas exitosamente', 'success');
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
                    formData.append('action', 'assign_subjects');
                    
                    fetch('/src/controllers/GroupSubjectHandler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof showToast === 'function') {
                                showToast('Materias actualizadas exitosamente', 'success');
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

        function editGroupAssignments(id_grupo, nombre) {
            // Set the group info
            document.getElementById('edit_id_grupo').value = id_grupo;
            document.getElementById('edit_grupo_nombre').textContent = nombre;
            
            // Get current assignments for this group
            fetch('/src/controllers/GroupSubjectHandler.php?action=get_group_subjects&id_grupo=' + id_grupo)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('edit_subjectSelect');
                        const assignedIds = data.data.map(m => m.id_materia.toString());
                        
                        // Clear all selections
                        Array.from(select.options).forEach(option => {
                            option.selected = false;
                        });
                        
                        // Select assigned subjects
                        Array.from(select.options).forEach(option => {
                            if (assignedIds.includes(option.value)) {
                                option.selected = true;
                            }
                        });
                        
                        // Update subject count
                        updateSubjectCount('edit_subjectSelect', 'editSubjectCount');
                        
                        // Show modal
                        document.getElementById('editModal').classList.remove('hidden');
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error cargando materias del grupo', 'error');
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
    </script>
</body>
</html>
