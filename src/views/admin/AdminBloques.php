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
require_once __DIR__ . '/../../components/Breadcrumb.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-bloques.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole(['ADMIN', 'DIRECTOR']);

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $horarioModel = new Horario($database->getConnection());
    $bloques = $horarioModel->getAllBloques();
    
    if ($bloques === false) {
        $bloques = [];
    }
} catch (Exception $e) {
    error_log("Error cargando bloques: " . $e->getMessage());
    $bloques = [];
    $error_message = 'Error interno del servidor';
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('time_blocks_management'); ?></title>
    <link rel="stylesheet" href="/css/styles.css?v=<?php echo time(); ?>">
    <?php echo Sidebar::getStyles(); ?>
    <script src="/js/pagination.js"></script>
    <script src="/js/filter-manager.js"></script>
    <style>
        .time-display {
            font-family: var(--font-mono);
            font-weight: 600;
            color: var(--color-navy);
        }

        #bloqueModal {
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
        
        #bloqueModal.hidden {
            display: none !important;
        }
        
        #bloqueModal .modal-content {
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
            #bloqueModal .modal-content {
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
        
        #bloqueModal button[type="submit"], 
        #bloqueModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
            background-color: var(--color-darkblue) !important;
            color: white !important;
            transition: all 0.2s ease !important;
        }
        
        #bloqueModal button[type="submit"]:hover, 
        #bloqueModal button[type="button"]:hover {
            background-color: var(--color-navy) !important;
            transform: translateY(-1px) !important;
        }

        #bloqueModal input:focus,
        #bloqueModal select:focus,
        #bloqueModal textarea:focus,
        #bloqueModal button:focus {
            outline: 2px solid var(--color-darkblue) !important;
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
            #bloqueModal {
                padding: 0.5rem !important;
            }
            
            #bloqueModal .modal-content {
                max-height: 95vh !important;
                border-radius: 8px !important;
            }
        }

        /* Toast styling */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 16px 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 10000;
            min-width: 300px;
            max-width: 400px;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }
        .toast.show {
            transform: translateX(0);
        }
        .toast-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        .toast-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .toast-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        .toast-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        #toastContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php echo $sidebar->render(); ?>
        
        <!-- Main Content -->
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
            <section class="flex-1 p-3 sm:p-4 md:p-6 w-full overflow-x-hidden">
                <div class="max-w-6xl mx-auto">
                    <!-- Breadcrumbs (RF073) -->
                    <?php 
                        $breadcrumb = Breadcrumb::forAdmin([
                            ['label' => _e('time_blocks_management') ?? 'Time Blocks Management', 'url' => '#']
                        ]);
                        echo $breadcrumb->render();
                    ?>
                    
                    <div class="mb-6 md:mb-8">
                        <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('time_blocks_management'); ?></h2>
                        <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('manage_time_blocks_description'); ?></p>
                    </div>

                    <!-- Time Blocks Management -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-3 md:p-4 border-b border-lightborder bg-gray-50 gap-3 md:gap-0">
                            <div class="flex items-center">
                                <h3 class="font-medium text-darktext"><?php _e('time_blocks_list'); ?></h3>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="openCreateModal()" class="py-2 px-3 md:px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-xs md:text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                                    <span class="mr-1 text-sm">+</span>
                                    <?php _e('add_time_block'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Filter Result Count (RF080) -->
                        <div id="filterResultCount" class="px-4 py-2"></div>

                        <!-- Lista de bloques horarios -->
                        <div id="bloquesList" class="divide-y divide-gray-200">
                            <?php if (!empty($bloques)): ?>
                                <?php foreach ($bloques as $bloque): ?>
                                    <article class="bloque-item item-row flex items-center justify-between p-4 transition-colors hover:bg-lightbg" 
                                             data-item-id="<?php echo $bloque['id_bloque']; ?>"
                                             data-hora-inicio="<?php echo date('H:i', strtotime($bloque['hora_inicio'])); ?>"
                                             data-hora-fin="<?php echo date('H:i', strtotime($bloque['hora_fin'])); ?>"
                                             data-time-range="<?php echo htmlspecialchars(date('H:i', strtotime($bloque['hora_inicio'])) . '-' . date('H:i', strtotime($bloque['hora_fin']))); ?>">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                                                <?php echo $bloque['id_bloque']; ?>
                                            </div>
                                            <div class="meta">
                                                <div class="font-semibold text-darktext mb-1 time-display">
                                                    <?php echo date('H:i', strtotime($bloque['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($bloque['hora_fin'])); ?>
                                                </div>
                                                <div class="text-muted text-sm">
                                                    <?php _e('block_id'); ?>: <?php echo $bloque['id_bloque']; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button onclick="editBloque(<?php echo $bloque['id_bloque']; ?>)" 
                                                    class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                                                <?php _e('edit'); ?>
                                            </button>
                                            <button onclick="deleteBloque(<?php echo $bloque['id_bloque']; ?>, '<?php echo date('H:i', strtotime($bloque['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($bloque['hora_fin'])); ?>')" 
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                                                <?php _e('delete'); ?>
                                            </button>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-8 text-center">
                                    <div class="text-gray-500 text-lg mb-2"><?php _e('no_time_blocks_found'); ?></div>
                                    <div class="text-gray-400 text-sm"><?php _e('add_first_time_block'); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Table Summary (RF083) -->
                        <?php if (!empty($bloques)): ?>
                        <div class="bg-gray-50 border-t border-gray-200 px-4 py-3">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-sm">
                                <div class="text-gray-600">
                                    <strong>Total:</strong> <?php echo count($bloques); ?> <?php _e('time_blocks'); ?>
                                </div>
                                <?php 
                                    $totalMinutes = 0;
                                    foreach ($bloques as $bloque) {
                                        $start = strtotime($bloque['hora_inicio']);
                                        $end = strtotime($bloque['hora_fin']);
                                        $totalMinutes += ($end - $start) / 60;
                                    }
                                    $totalHours = round($totalMinutes / 60, 1);
                                    $avgMinutes = count($bloques) > 0 ? round($totalMinutes / count($bloques)) : 0;
                                ?>
                                <div class="text-gray-600">
                                    <strong>Total duración:</strong> <?php echo $totalHours; ?>h | 
                                    <strong>Promedio por bloque:</strong> <?php echo $avgMinutes; ?> min
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Pagination Container (RF082) -->
                        <div id="paginationContainer"></div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal para agregar/editar bloque horario -->
    <div id="bloqueModal" class="hidden" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDescription">
        <div class="modal-content p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_time_block'); ?></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600" aria-label="<?php _e('close_modal'); ?>">
                    <span class="text-sm" aria-hidden="true">×</span>
                </button>
            </div>
            <p id="modalDescription" class="text-sm text-gray-600 mb-6 sr-only"><?php _e('modal_description'); ?></p>
            
            <form id="bloqueForm" method="POST" onsubmit="handleBloqueFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="bloqueId" name="id_bloque">
                
                <div>
                    <label for="horaInicio" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('start_time'); ?> <span class="text-red-500">*</span></label>
                    <input type="time" 
                           id="horaInicio" 
                           name="hora_inicio" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           aria-describedby="horaInicioError" autocomplete="off">
                    <p id="horaInicioError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="horaFin" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('end_time'); ?> <span class="text-red-500">*</span></label>
                    <input type="time" 
                           id="horaFin" 
                           name="hora_fin" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           aria-describedby="horaFinError" autocomplete="off">
                    <p id="horaFinError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" 
                            onclick="closeModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('cancel'); ?>
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-darkblue border border-transparent rounded-md shadow-sm hover:bg-navy focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <span id="submitText"><?php _e('save'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="toastContainer"></div>
    <script src="/js/toast.js?v=<?php echo time(); ?>"></script>
    <script>
        let isEditMode = false;
        let currentBloqueId = null;

        // Global functions that need to be accessible from HTML onclick
        async function editBloque(id) {
            try {
                const response = await fetch(`/src/controllers/BloqueHandler.php?action=get_all`);
                const data = await response.json();
                
                if (data.success) {
                    const bloque = data.data.find(b => b.id_bloque == id);
                    if (bloque) {
                        isEditMode = true;
                        currentBloqueId = id;
                        
                        document.getElementById('horaInicio').value = bloque.hora_inicio;
                        document.getElementById('horaFin').value = bloque.hora_fin;
                        document.getElementById('bloqueId').value = id;
                        
                        document.getElementById('modalTitle').textContent = 'Editar Bloque Horario';
                        document.getElementById('submitText').textContent = 'Actualizar';
                        
                        // Open modal
                        const modal = document.getElementById('bloqueModal');
                        modal.classList.remove('hidden');
                        modal.style.display = 'flex';
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Bloque no encontrado', 'error');
                        } else {
                            alert('Bloque no encontrado');
                        }
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error al cargar datos del bloque', 'error');
                    } else {
                        alert('Error al cargar datos del bloque');
                    }
                }
            } catch (error) {
                if (typeof showToast === 'function') {
                    showToast('Error de conexión al cargar bloque', 'error');
                } else {
                    alert('Error de conexión al cargar bloque');
                }
            }
        }

        async function deleteBloque(id, timeRange) {
            const confirmMessage = `¿Estás seguro de que quieres eliminar el bloque horario "${timeRange}"?`;
            const confirmed = await showConfirmModal(
                '<?php _e('confirm_delete'); ?>',
                confirmMessage,
                '<?php _e('confirm'); ?>',
                '<?php _e('cancel'); ?>'
            );
            if (confirmed) {
                try {
                    const response = await fetch('/src/controllers/BloqueHandler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            id_bloque: id
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        if (typeof showToast === 'function') {
                            showToast(result.message, 'success');
                        } else {
                            alert(result.message);
                        }
                        loadBloques();
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error: ' + result.message, 'error');
                        } else {
                            alert('Error: ' + result.message);
                        }
                    }
                } catch (error) {
                    if (typeof showToast === 'function') {
                        showToast('Error de conexión al eliminar bloque', 'error');
                    } else {
                        alert('Error de conexión al eliminar bloque');
                    }
                }
            }
        }

        // Load all bloques
        async function loadBloques() {
            try {
                const response = await fetch('/src/controllers/BloqueHandler.php?action=get_all');
                const data = await response.json();
                
                if (data.success) {
                    renderBloques(data.data);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + data.message, 'error');
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            } catch (error) {
                if (typeof showToast === 'function') {
                    showToast('Error de conexión al cargar bloques', 'error');
                } else {
                    alert('Error de conexión al cargar bloques');
                }
            }
        }

        // Render bloques in the list
        function renderBloques(bloques) {
            const container = document.getElementById('bloquesList');
            
            if (bloques.length === 0) {
                container.innerHTML = `
                    <div class="p-8 text-center">
                        <div class="text-gray-500 text-lg mb-2">No se encontraron bloques horarios</div>
                        <div class="text-gray-400 text-sm">Agrega el primer bloque horario</div>
                    </div>
                `;
                return;
            }

            container.innerHTML = bloques.map(bloque => `
                <article class="item-row flex items-center justify-between p-4 transition-colors hover:bg-lightbg" 
                         data-item-id="${bloque.id_bloque}">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                            ${bloque.id_bloque}
                        </div>
                        <div class="meta">
                            <div class="font-semibold text-darktext mb-1 time-display">
                                ${formatTime(bloque.hora_inicio)} - ${formatTime(bloque.hora_fin)}
                            </div>
                            <div class="text-muted text-sm">
                                ID: ${bloque.id_bloque}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="editBloque(${bloque.id_bloque})" 
                                class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                            Editar
                        </button>
                        <button onclick="deleteBloque(${bloque.id_bloque}, '${formatTime(bloque.hora_inicio)} - ${formatTime(bloque.hora_fin)}')" 
                                class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                            Eliminar
                        </button>
                    </div>
                </article>
            `).join('');
        }

        // Format time for display
        function formatTime(timeString) {
            const time = new Date('2000-01-01T' + timeString);
            return time.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        }

        // Open create modal
        function openCreateModal() {
            isEditMode = false;
            currentBloqueId = null;
            
            document.getElementById('modalTitle').textContent = 'Agregar Bloque Horario';
            document.getElementById('submitText').textContent = 'Guardar';
            document.getElementById('bloqueForm').reset();
            document.getElementById('bloqueId').value = '';
            clearErrors();
            
            const modal = document.getElementById('bloqueModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('bloqueModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.getElementById('bloqueForm').reset();
            clearErrors();
        }

        // Handle form submission
        function handleBloqueFormSubmit(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            
            // Clear previous errors
            clearErrors();
            
            // Validate times
            let horaInicio = data.hora_inicio;
            let horaFin = data.hora_fin;
            
            if (!horaInicio || !horaFin) {
                if (typeof showToast === 'function') {
                    showToast('Todos los campos son requeridos', 'error');
                } else {
                    alert('Todos los campos son requeridos');
                }
                return;
            }
            
            // Convert HH:MM:SS to HH:MM format
            if (horaInicio.includes(':')) {
                horaInicio = horaInicio.substring(0, 5); // Take only HH:MM part
            }
            if (horaFin.includes(':')) {
                horaFin = horaFin.substring(0, 5); // Take only HH:MM part
            }
            
            if (horaFin <= horaInicio) {
                if (typeof showToast === 'function') {
                    showToast('La hora de fin debe ser posterior a la hora de inicio', 'error');
                } else {
                    alert('La hora de fin debe ser posterior a la hora de inicio');
                }
                return;
            }
            
            // Update data with corrected time format
            data.hora_inicio = horaInicio;
            data.hora_fin = horaFin;
            
            saveBloque(data);
        }

        // Save bloque function
        async function saveBloque(data) {
            try {
                const action = isEditMode ? 'update' : 'create';
                const requestData = {
                    action: action,
                    ...data
                };
                
                // Debug information - removed console.log for production
                
                const response = await fetch('/src/controllers/BloqueHandler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (typeof showToast === 'function') {
                        showToast(result.message, 'success');
                    } else {
                        alert(result.message);
                    }
                    closeModal();
                    loadBloques();
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + result.message, 'error');
                    } else {
                        alert('Error: ' + result.message);
                    }
                }
            } catch (error) {
                if (typeof showToast === 'function') {
                    showToast('Error de conexión al guardar bloque', 'error');
                } else {
                    alert('Error de conexión al guardar bloque');
                }
            }
        }
        
        // Clear form errors
        function clearErrors() {
            document.getElementById('horaInicioError').textContent = '';
            document.getElementById('horaFinError').textContent = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Load bloques on page load
            loadBloques();

            // Initialize Pagination (RF082)
            const totalBloques = <?php echo count($bloques); ?>;
            const paginationContainer = document.getElementById('paginationContainer');
            
            if (paginationContainer && totalBloques > 0) {
                window.paginationManager = new PaginationManager({
                    container: paginationContainer,
                    currentPage: 1,
                    perPage: 10,
                    totalRecords: totalBloques,
                    onPageChange: function(page) {
                        updateVisibleItems(page);
                    },
                    onPerPageChange: function(perPage) {
                        updateVisibleItems(1);
                    }
                });
                
                window.paginationManager.render();
                updateVisibleItems(1);
            }

            // Initialize Filter Manager (RF080)
            const filterContainer = document.querySelector('.bg-white.rounded-lg.shadow-sm');
            const filterResultCount = document.getElementById('filterResultCount');
            
            if (filterContainer && filterResultCount) {
                window.filterManager = new FilterManager({
                    container: filterContainer,
                    resultCountContainer: filterResultCount,
                    totalCount: totalBloques,
                    filteredCount: totalBloques,
                    onFilterChange: function(filters) {
                        applyFilters(filters);
                    }
                });
                
                window.filterManager.init();
                window.filterManager.updateResultCount(totalBloques, totalBloques);
            }


            // Close modal when clicking outside
            document.addEventListener('click', function(event) {
                const modal = document.getElementById('bloqueModal');
                if (event.target === modal) {
                    closeModal();
                }
            });
        }); // End of DOMContentLoaded

        // Update visible items based on pagination
        function updateVisibleItems(page) {
            if (!window.paginationManager) return;
            
            const state = window.paginationManager.getState();
            const allItems = Array.from(document.querySelectorAll('.bloque-item'));
            
            const visibleItems = allItems.filter(item => {
                const filters = window.filterManager ? window.filterManager.getFilters() : {};
                let matches = true;
                
                if (filters.search && filters.search.trim() !== '') {
                    const searchLower = filters.search.toLowerCase().trim();
                    const timeRange = item.dataset.timeRange || '';
                    
                    if (!timeRange.includes(searchLower)) {
                        matches = false;
                    }
                }
                
                return matches;
            });
            
            const startIndex = (page - 1) * state.perPage;
            const endIndex = startIndex + state.perPage;
            
            allItems.forEach(item => {
                item.style.display = 'none';
            });
            
            visibleItems.slice(startIndex, endIndex).forEach(item => {
                item.style.display = 'flex';
            });
        }

        // Apply filters and update counts
        function applyFilters(filters) {
            const allItems = document.querySelectorAll('.bloque-item');
            let visibleCount = 0;
            
            allItems.forEach(item => {
                let matches = true;
                
                if (filters.search && filters.search.trim() !== '') {
                    const searchLower = filters.search.toLowerCase().trim();
                    const timeRange = item.dataset.timeRange || '';
                    
                    if (!timeRange.includes(searchLower)) {
                        matches = false;
                    }
                }
                
                if (matches) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            if (window.filterManager) {
                window.filterManager.updateResultCount(visibleCount, allItems.length);
            }
            
            if (window.paginationManager) {
                window.paginationManager.currentPage = 1;
                window.paginationManager.updateTotalRecords(visibleCount);
                setTimeout(() => updateVisibleItems(1), 0);
            } else {
                allItems.forEach(item => {
                    item.style.display = 'flex';
                });
            }
        }
    </script>
</body>
</html>
