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
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('groups_management'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('groups_management_description'); ?></p>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8" data-default-labels='["Estados"]'>
                        <!-- Header de la tabla -->
                        <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex items-center">
                                <div class="select-all-container">
                                    <input type="checkbox" id="selectAll" class="item-checkbox">
                                    <label for="selectAll"><?php _e('select_all'); ?></label>
                                </div>
                                <h3 class="font-medium text-darktext ml-4"><?php _e('groups'); ?></h3>
                            </div>
                            <div class="flex gap-2">
                                <div class="relative">
                                    <input type="text" id="searchInput" placeholder="<?php _e('search_groups'); ?>" 
                                           class="py-2 px-4 pr-10 border border-gray-300 rounded text-sm focus:ring-darkblue focus:border-darkblue"
                                           onkeyup="searchGrupos(this.value)">
                                    <span class="text-gray-400 text-2xl">•</span>
                                </div>
                                <button class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50">
                                    <?php _e('export'); ?>
                                </button>
                                <button onclick="showAddGrupoModal()" class="py-2 px-4 border-none rounded cursor-pointer font-medium transition-all text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                                    <span class="mr-1 text-sm">+</span>
                                    <?php _e('add_group'); ?>
                                </button>
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
                        <div id="gruposList" class="divide-y divide-gray-200">
                            <?php if (!empty($grupos)): ?>
                                <?php foreach ($grupos as $grupo): ?>
                                    <article class="grupo-item item-row flex items-center justify-between p-4 transition-colors hover:bg-lightbg" 
                                             data-nombre="<?php echo htmlspecialchars(strtolower($grupo['nombre'])); ?>" 
                                             data-nivel="<?php echo htmlspecialchars(strtolower($grupo['nivel'])); ?>"
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
                                            <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                                                <?php echo getGroupInitials($grupo['nombre']); ?>
                                            </div>
                                            <div class="meta">
                                                <div class="font-semibold text-darktext mb-1">
                                                    <?php echo htmlspecialchars($grupo['nombre']); ?>
                                                </div>
                                                <div class="text-muted text-sm">
                                                    <?php _e('level'); ?>: <?php echo htmlspecialchars($grupo['nivel']); ?>
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
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message || 'Error al eliminar el grupo', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error al eliminar el grupo', 'error');
                });
            }
        }

        function viewGroupSchedule(id) {

            window.location.href = `admin-horarios.php?grupo=${id}`;
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

                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'Error al procesar la solicitud', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al procesar la solicitud', 'error');
            });
        }

        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
        }

        document.getElementById('grupoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeGrupoModal();
            }
        });

        function searchGrupos(searchTerm) {
            const grupos = document.querySelectorAll('.grupo-item');
            const searchLower = searchTerm.toLowerCase().trim();
            
            if (searchLower === '') {

                grupos.forEach(grupo => {
                    grupo.style.display = 'flex';
                });
                return;
            }

            grupos.forEach(grupo => {
                const nombre = grupo.dataset.nombre || '';
                const nivel = grupo.dataset.nivel || '';
                
                if (nombre.includes(searchLower) || nivel.includes(searchLower)) {
                    grupo.style.display = 'flex';
                } else {
                    grupo.style.display = 'none';
                }
            });

            const visibleGrupos = Array.from(grupos).filter(grupo => grupo.style.display !== 'none');
            const noResultsMessage = document.getElementById('noResultsMessage');
            
            if (visibleGrupos.length === 0 && searchLower !== '') {
                if (!noResultsMessage) {
                    const gruposList = document.getElementById('gruposList');
                    const messageDiv = document.createElement('div');
                    messageDiv.id = 'noResultsMessage';
                    messageDiv.className = 'p-8 text-center';
                    messageDiv.innerHTML = `
                        <div class="text-gray-500 text-lg mb-2">No se encontraron grupos que coincidan con "${searchTerm}"</div>
                        <div class="text-gray-400 text-sm">Intente con un término de búsqueda diferente</div>
                    `;
                    gruposList.appendChild(messageDiv);
                }
            } else if (noResultsMessage) {
                noResultsMessage.remove();
            }
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
