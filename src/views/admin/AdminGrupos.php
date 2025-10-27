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
$sidebar = new Sidebar('admin-grupos.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    require_once __DIR__ . '/../../models/Grupo.php';
    $grupoModel = new Grupo($database->getConnection());
    $grupos = $grupoModel->getAllGrupos();
    
    if ($grupos === false) {
        $grupos = [];
    }
} catch (Exception $e) {
    error_log("Error cargando grupos: " . $e->getMessage());
    $grupos = [];
    $error_message = 'Error interno del servidor';
}

function getGroupInitials($nombre) {
    $words = explode(' ', $nombre);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($nombre, 0, 2));
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('groups_management'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <script src="/js/multiple-selection.js"></script>
    <script src="/js/status-labels.js"></script>
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
        .bg-white.rounded-lg.shadow-sm {
            display: flex;
            flex-direction: column;
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

        #grupoModal {
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
        
        #grupoModal.hidden {
            display: none !important;
        }
        
        #grupoModal .modal-content {
            position: relative !important;
            z-index: 10001 !important;
            background: white !important;
            border-radius: 8px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }
        
        #grupoModal button[type="submit"], 
        #grupoModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
            background-color: #1f366d !important;
            color: white !important;
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <!-- Main -->
        <main class="flex-1 flex flex-col main-content">
            <!-- Header -->
            <header class="bg-darkblue px-4 md:px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <!-- Espacio para el botón de menú hamburguesa -->
                <div class="w-8"></div>
                
                <!-- Título centrado -->
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                <div class="text-white text-sm font-semibold text-center sm:hidden"><?php _e('welcome'); ?></div>
                
                <!-- Contenedor de iconos a la derecha -->
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-2 md:mr-4'); ?>
                    <button class="mr-2 md:mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
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
            <section class="flex-1 px-4 md:px-6 py-6 md:py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-6 md:mb-8">
                        <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('groups_management'); ?></h2>
                        <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('groups_management_description'); ?></p>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8" style="display: flex; flex-direction: column;" data-default-labels='["Estados"]'>
                        <!-- Header de la tabla -->
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-3 md:p-4 border-b border-gray-200 bg-gray-50 gap-3 md:gap-0">
                            <div class="flex items-center">
                                <div class="select-all-container">
                                    <input type="checkbox" id="selectAll" class="item-checkbox">
                                    <label for="selectAll" class="text-sm md:text-base"><?php _e('select_all'); ?></label>
                                </div>
                                <h3 class="font-medium text-darktext ml-3 md:ml-4 text-sm md:text-base"><?php _e('groups'); ?></h3>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                                <div class="relative w-full sm:w-auto">
                                    <input type="text" id="searchInput" placeholder="<?php _e('search_groups'); ?>" 
                                           class="w-full py-2 px-3 md:px-4 pr-10 border border-gray-300 rounded text-xs md:text-sm focus:ring-darkblue focus:border-darkblue"
                                           onkeyup="searchGrupos(this.value)">
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="exportGrupos()" class="py-2 px-3 md:px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-xs md:text-sm bg-white text-gray-700 hover:bg-gray-50">
                                        <?php _e('export'); ?>
                                    </button>
                                    <button onclick="showAddGrupoModal()" class="py-2 px-3 md:px-4 border-none rounded cursor-pointer font-medium transition-all text-xs md:text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                                        <span class="mr-1 text-sm">+</span>
                                    <?php _e('add_group'); ?>
                                </button>
                                </div>
                            </div>
                        </div>

                        <!-- Bulk Actions Bar -->
                        <div id="bulkActions" class="bulk-actions hidden">
                            <div class="flex items-center justify-between">
                                <div class="selection-info">
                                    <span data-selection-count>0</span> <?php _e('selected_items'); ?>
                                </div>
                                <div class="action-buttons">
                                    <button data-bulk-action="export" class="btn-export">
                                        <?php _e('bulk_export'); ?>
                                    </button>
                                    <button data-bulk-action="delete" class="btn-delete">
                                        <?php _e('bulk_delete'); ?>
                                    </button>
                                </div>
                            </div>
                            <!-- Statistics Container -->
                            <div id="statisticsContainer"></div>
                        </div>

                        <!-- Lista de grupos -->
                        <div class="divide-y divide-gray-200">
                            <?php if (!empty($grupos)): ?>
                                <?php foreach ($grupos as $grupo): ?>
                                    <article class="item-row flex items-center justify-between p-4 transition-colors hover:bg-lightbg" 
                                             data-item-id="<?php echo $grupo['id_grupo']; ?>"
                                             data-original-text=""
                                             data-available-labels="<?php 
                                                 $labels = [];
                                                 $labels[] = 'Level: ' . htmlspecialchars($grupo['nivel']);
                                                 $tieneHorario = isset($grupo['tiene_horario']) ? $grupo['tiene_horario'] : false;
                                                 $labels[] = $tieneHorario ? 'Estado: Con horario' : 'Estado: Sin horario';
                                                 echo implode('|', $labels);
                                             ?>"
                                             data-label-mapping="<?php 
                                                 $mapping = [];
                                                 $tieneHorario = isset($grupo['tiene_horario']) ? $grupo['tiene_horario'] : false;
                                                 $mapping['Estados'] = $tieneHorario ? 'Estado: Con horario' : 'Estado: Sin horario';
                                                 $mapping['Niveles'] = 'Level: ' . htmlspecialchars($grupo['nivel']);
                                                 echo htmlspecialchars(json_encode($mapping));
                                             ?>">
                                        <div class="flex items-center">
                                            <div class="checkbox-container">
                                                <input type="checkbox" class="item-checkbox" data-item-id="<?php echo $grupo['id_grupo']; ?>">
                                            </div>
                                            <div class="w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                                                <?php echo getGroupInitials($grupo['nombre']); ?>
                                            </div>
                                            <div class="meta">
                                                <div class="font-semibold text-darktext mb-1">
                                                    <?php echo htmlspecialchars($grupo['nombre']); ?>
                                                </div>
                                                <div class="text-muted text-sm">
                                                    <?php 
                                                    $tieneHorario = isset($grupo['tiene_horario']) ? $grupo['tiene_horario'] : false;
                                                    echo $tieneHorario ? 'Estado: Con horario' : 'Estado: Sin horario';
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button onclick="viewGroupSchedule(<?php echo $grupo['id_grupo']; ?>)" 
                                                    class="text-green-600 hover:text-green-800 text-sm font-medium transition-colors">
                                                <?php _e('view_schedule'); ?>
                                            </button>
                                            <button onclick="editGrupo(<?php echo $grupo['id_grupo']; ?>)" 
                                                    class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                                                <?php _e('edit'); ?>
                                            </button>
                                            <button onclick="deleteGrupo(<?php echo $grupo['id_grupo']; ?>, '<?php echo htmlspecialchars($grupo['nombre']); ?>')" 
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                                                <?php _e('delete'); ?>
                                            </button>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-8 text-center">
                                    <div class="text-gray-500 text-lg mb-2"><?php _e('no_groups_found'); ?></div>
                                    <div class="text-gray-400 text-sm"><?php _e('add_first_group'); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal para agregar/editar grupo -->
    <div id="grupoModal" class="hidden">
        <div class="modal-content p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_group'); ?></h3>
                <button onclick="closeGrupoModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="text-sm">×</span>
                </button>
            </div>

            <form id="grupoForm" onsubmit="handleGrupoFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="grupoId" name="id" value="">
                
                <div>
                    <label for="nombre_grupo" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('group_name'); ?></label>
                    <input type="text" id="nombre_grupo" name="nombre" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="Ej: 1º Año A">
                </div>
                
                <div>
                    <label for="nivel" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('level'); ?></label>
                    <select id="nivel" name="nivel" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                        <option value=""><?php _e('select_level'); ?></option>
                        <option value="1º Año">1º Año</option>
                        <option value="2º Año">2º Año</option>
                        <option value="3º Año">3º Año</option>
                        <option value="4º Año">4º Año</option>
                        <option value="5º Año">5º Año</option>
                        <option value="6º Año">6º Año</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeGrupoModal()" 
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

        function showAddGrupoModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = '<?php _e('add_group'); ?>';
            document.getElementById('grupoForm').reset();
            document.getElementById('grupoId').value = '';
            
            clearErrors();
            document.getElementById('grupoModal').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('nombre_grupo').focus();
            }, 100);
        }

        function editGrupo(id) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = '<?php _e('edit_group'); ?>';
            
            fetch(`/src/controllers/GrupoHandler.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('grupoId').value = data.data.id_grupo;
                        document.getElementById('nombre_grupo').value = data.data.nombre;
                        document.getElementById('nivel').value = data.data.nivel;
                        
                        clearErrors();
                        document.getElementById('grupoModal').classList.remove('hidden');
                        
                        setTimeout(() => {
                            document.getElementById('nombre_grupo').focus();
                        }, 100);
                    } else {
                        showToast(data.message || 'Error al cargar el grupo', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error al cargar el grupo', 'error');
                });
        }

        function deleteGrupo(id, nombre) {
            const confirmMessage = `<?php _e('confirm_delete_group'); ?> "${nombre}"?`;
            if (confirm(confirmMessage)) {
                // Show loading state on delete button
                const deleteButton = event.target;
                const originalText = deleteButton.textContent;
                deleteButton.disabled = true;
                deleteButton.textContent = '<?php _e('deleting'); ?>...';
                
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('/src/controllers/GrupoHandler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('<?php _e('group_deleted_successfully'); ?>', 'success');
                        // Remove group from UI without page reload
                        removeGroupFromList(id);
                    } else {
                        showToast(data.message || 'Error al eliminar el grupo', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error al eliminar el grupo', 'error');
                })
                .finally(() => {
                    // Restore button state
                    deleteButton.disabled = false;
                    deleteButton.textContent = originalText;
                });
            }
        }

        function viewGroupSchedule(id) {
            // Navigate to the admin schedule management page with the group ID
            window.location.href = `/src/views/admin/AdminHorarios.php?grupo=${id}`;
        }

        function closeGrupoModal() {
            const modal = document.getElementById('grupoModal');
            modal.classList.add('hidden');
            clearErrors();
        }

        function handleGrupoFormSubmit(e) {
            e.preventDefault();
            
            clearErrors();
            
            const formData = new FormData(e.target);
            const action = isEditMode ? 'update' : 'create';
            formData.append('action', action);
            
            const nombre = formData.get('nombre').trim();
            const nivel = formData.get('nivel').trim();
            
            if (!nombre) {
                showToast('<?php _e('group_name_required'); ?>', 'error');
                return;
            }
            
            if (!nivel) {
                showToast('<?php _e('level_required'); ?>', 'error');
                return;
            }
            
            if (nombre.length > 100) {
                showToast('<?php _e('group_name_too_long'); ?>', 'error');
                return;
            }
            
            if (nivel.length > 50) {
                showToast('<?php _e('level_too_long'); ?>', 'error');
                return;
            }
            
            // Show loading state
            const submitButton = e.target.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = '<?php _e('saving'); ?>...';
            
            fetch('/src/controllers/GrupoHandler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const message = isEditMode ? 
                        '<?php _e('group_updated_successfully'); ?>' : 
                        '<?php _e('group_created_successfully'); ?>';
                    showToast(message, 'success');
                    closeGrupoModal();
                    
                    // Update UI without page reload
                    if (isEditMode) {
                        updateGroupInList(data.data);
                    } else {
                        addGroupToList(data.data);
                    }
                } else {
                    showToast(data.message || 'Error al procesar la solicitud', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al procesar la solicitud', 'error');
            })
            .finally(() => {
                // Restore button state
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        }

        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
        }

        // Helper functions for dynamic UI updates
        function addGroupToList(groupData) {
            const groupsContainer = document.querySelector('.divide-y.divide-gray-200');
            
            // Create new group article element
            const groupArticle = document.createElement('article');
            groupArticle.className = 'item-row flex items-center justify-between p-4 transition-colors hover:bg-lightbg';
            groupArticle.setAttribute('data-item-id', groupData.id_grupo);
            groupArticle.setAttribute('data-original-text', '');
            groupArticle.setAttribute('data-available-labels', `Level: ${groupData.nivel}|Estado: Sin horario`);
            groupArticle.setAttribute('data-label-mapping', JSON.stringify({
                'Estados': 'Estado: Sin horario',
                'Niveles': `Level: ${groupData.nivel}`
            }));
            
            groupArticle.innerHTML = `
                <div class="flex items-center">
                    <div class="checkbox-container">
                        <input type="checkbox" class="item-checkbox" data-item-id="${groupData.id_grupo}">
                    </div>
                    <div class="w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                        ${getGroupInitials(groupData.nombre)}
                    </div>
                    <div class="meta">
                        <div class="font-semibold text-darktext mb-1">
                            ${escapeHtml(groupData.nombre)}
                        </div>
                        <div class="text-muted text-sm">
                            Estado: Sin horario
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="viewGroupSchedule(${groupData.id_grupo})" 
                            class="text-green-600 hover:text-green-800 text-sm font-medium transition-colors">
                        <?php _e('view_schedule'); ?>
                    </button>
                    <button onclick="editGrupo(${groupData.id_grupo})" 
                            class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                        <?php _e('edit'); ?>
                    </button>
                    <button onclick="deleteGrupo(${groupData.id_grupo}, '${escapeHtml(groupData.nombre)}')" 
                            class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                        <?php _e('delete'); ?>
                    </button>
                </div>
            `;
            
            // Add to the beginning of the list
            groupsContainer.insertBefore(groupArticle, groupsContainer.firstChild);
            
            // Update statistics
            updateStatistics();
        }

        function updateGroupInList(groupData) {
            if (!groupData || !groupData.id_grupo) {
                console.error('updateGroupInList: Invalid group data');
                return;
            }
            
            const groupElement = document.querySelector(`[data-item-id="${groupData.id_grupo}"]`);
            if (groupElement) {
                // Update group name
                const nameElement = groupElement.querySelector('.font-semibold.text-darktext');
                if (nameElement) {
                    nameElement.textContent = groupData.nombre;
                }
                
                // Update group initials
                const initialsElement = groupElement.querySelector('.w-10.h-10.rounded-full');
                if (initialsElement) {
                    initialsElement.textContent = getGroupInitials(groupData.nombre);
                }
                
                // Update data attributes
                groupElement.setAttribute('data-available-labels', `Level: ${groupData.nivel}|Estado: Sin horario`);
                groupElement.setAttribute('data-label-mapping', JSON.stringify({
                    'Estados': 'Estado: Sin horario',
                    'Niveles': `Level: ${groupData.nivel}`
                }));
                
                // Update delete button onclick
                const deleteButton = groupElement.querySelector('button[onclick*="deleteGrupo"]');
                if (deleteButton) {
                    deleteButton.setAttribute('onclick', `deleteGrupo(${groupData.id_grupo}, '${escapeHtml(groupData.nombre)}')`);
                }
            }
        }

        function removeGroupFromList(groupId) {
            const groupElement = document.querySelector(`[data-item-id="${groupId}"]`);
            if (groupElement) {
                groupElement.remove();
                updateStatistics();
            }
        }

        function getGroupInitials(nombre) {
            const words = nombre.split(' ');
            if (words.length >= 2) {
                return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
            }
            return nombre.substring(0, 2).toUpperCase();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function updateStatistics() {
            // Update the statistics display if it exists
            const statsContainer = document.getElementById('statisticsContainer');
            if (statsContainer) {
                const totalGroups = document.querySelectorAll('.item-row').length;
                const conHorario = document.querySelectorAll('.item-row').length; // This would need to be calculated based on actual data
                const sinHorario = 0; // This would need to be calculated based on actual data
                
                statsContainer.innerHTML = `
                    <div class="flex justify-between text-sm text-gray-600 mt-2">
                        <span>Con Horario: ${conHorario}</span>
                        <span>Sin Horario: ${sinHorario}</span>
                    </div>
                `;
            }
        }

        document.getElementById('grupoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeGrupoModal();
            }
        });

        function searchGrupos(searchTerm) {
            const grupos = document.querySelectorAll('.item-row');
            const searchLower = searchTerm.toLowerCase().trim();
            
            if (searchLower === '') {
                // Show all groups
                grupos.forEach(grupo => {
                    grupo.style.display = '';
                });
                removeNoResultsMessage();
                return;
            }

            // Client-side search for immediate feedback
            let visibleCount = 0;
            grupos.forEach(grupo => {
                const nombre = grupo.querySelector('.font-semibold.text-darktext')?.textContent || '';
                const nivel = grupo.querySelector('.text-muted.text-sm')?.textContent || '';
                
                if (nombre.toLowerCase().includes(searchLower) || nivel.toLowerCase().includes(searchLower)) {
                    grupo.style.display = '';
                    visibleCount++;
                } else {
                    grupo.style.display = 'none';
                }
            });

            // Show no results message if needed
            if (visibleCount === 0) {
                showNoResultsMessage(searchTerm);
            } else {
                removeNoResultsMessage();
            }

            // Optional: Server-side search for more comprehensive results
            if (searchLower.length >= 2) {
                performServerSearch(searchTerm);
            }
        }

        function performServerSearch(searchTerm) {
            // Debounce server search to avoid too many requests
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => {
                fetch(`/src/controllers/GrupoHandler.php?action=search&q=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            // Update the UI with server search results
                            updateSearchResults(data.data);
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                    });
            }, 300);
        }

        function updateSearchResults(searchResults) {
            // This would update the UI with server search results
            // For now, we'll keep the client-side search as primary
        }

        function showNoResultsMessage(searchTerm) {
            removeNoResultsMessage();
            const gruposContainer = document.querySelector('.divide-y.divide-gray-200');
            const messageDiv = document.createElement('div');
            messageDiv.id = 'noResultsMessage';
            messageDiv.className = 'p-8 text-center';
            messageDiv.innerHTML = `
                <div class="text-gray-500 text-lg mb-2">No se encontraron grupos que coincidan con "${searchTerm}"</div>
                <div class="text-gray-400 text-sm">Intente con un término de búsqueda diferente</div>
            `;
            gruposContainer.appendChild(messageDiv);
        }

        function removeNoResultsMessage() {
            const noResultsMessage = document.getElementById('noResultsMessage');
            if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }

        // Export and bulk operations
        function exportGrupos(selectedIds = null) {
            if (!selectedIds) {
                selectedIds = getSelectedIds();
            }
            
            if (selectedIds.length === 0) {
                showToast('<?php _e('select_groups_to_export'); ?>', 'error');
                return;
            }
            
            // Show loading state
            const exportButton = event?.target || document.querySelector('[data-bulk-action="export"]');
            if (exportButton) {
                const originalText = exportButton.textContent;
                exportButton.disabled = true;
                exportButton.textContent = '<?php _e('exporting'); ?>...';
            }
            
            const params = new URLSearchParams();
            selectedIds.forEach(id => params.append('ids[]', id));
            params.append('action', 'export');
            
            fetch(`/src/controllers/GrupoHandler.php?${params.toString()}`)
                .then(response => {
                    if (response.ok) {
                        return response.blob();
                    }
                    throw new Error('Export failed');
                })
                .then(blob => {
                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `grupos_export_${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    showToast('<?php _e('export_successful'); ?>', 'success');
                })
                .catch(error => {
                    console.error('Export error:', error);
                    showToast('<?php _e('export_failed'); ?>', 'error');
                })
                .finally(() => {
                    // Restore button state
                    if (exportButton) {
                        exportButton.disabled = false;
                        exportButton.textContent = originalText;
                    }
                });
        }

        function bulkDeleteGrupos(selectedIds) {
            if (selectedIds.length === 0) {
                showToast('<?php _e('select_groups_to_delete'); ?>', 'error');
                return;
            }
            
            const confirmMessage = `<?php _e('confirm_bulk_delete_groups'); ?> ${selectedIds.length} <?php _e('groups'); ?>?`;
            if (confirm(confirmMessage)) {
                // Show loading state
                const deleteButton = document.querySelector('[data-bulk-action="delete"]');
                const originalText = deleteButton.textContent;
                deleteButton.disabled = true;
                deleteButton.textContent = '<?php _e('deleting'); ?>...';
                
                const formData = new FormData();
                formData.append('action', 'bulk_delete');
                selectedIds.forEach(id => formData.append('ids[]', id));
                
                fetch('/src/controllers/GrupoHandler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`<?php _e('groups_deleted_successfully'); ?>: ${data.deleted_count || selectedIds.length}`, 'success');
                        // Remove groups from UI
                        selectedIds.forEach(id => removeGroupFromList(id));
                    } else {
                        showToast(data.message || '<?php _e('error_deleting_groups'); ?>', 'error');
                    }
                })
                .catch(error => {
                    console.error('Bulk delete error:', error);
                    showToast('<?php _e('error_deleting_groups'); ?>', 'error');
                })
                .finally(() => {
                    // Restore button state
                    deleteButton.disabled = false;
                    deleteButton.textContent = originalText;
                });
            }
        }

        function getSelectedIds() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            return Array.from(checkboxes).map(checkbox => checkbox.dataset.itemId);
        }

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });

        const multipleSelection = new MultipleSelection({
            container: document.querySelector('.bg-white.rounded-lg.shadow-sm'),
            itemSelector: '.item-row',
            checkboxSelector: '.item-checkbox',
            selectAllSelector: '#selectAll',
            bulkActionsSelector: '#bulkActions',
            entityType: 'grupos',
            onSelectionChange: function(selectedItems) {

            },
            onBulkAction: function(action, selectedIds) {
                if (action === 'export') {
                    exportGrupos(selectedIds);
                } else if (action === 'delete') {
                    bulkDeleteGrupos(selectedIds);
                }
            }
        });

        const statusLabels = new StatusLabels({
            container: document.querySelector('.bg-white.rounded-lg.shadow-sm'),
            itemSelector: '.item-row',
            metaSelector: '.meta .text-muted',
            entityType: 'grupos'
        });
    </script>
</body>
</html>
