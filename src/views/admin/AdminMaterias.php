<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Materia.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-materias.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
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
    <script src="/js/multiple-selection.js?v=<?php echo time(); ?>"></script>
    <script src="/js/status-labels.js"></script>
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

        #materiaModal, #pautaModal {
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
        
        #materiaModal.hidden, #pautaModal.hidden {
            display: none !important;
        }
        
        #materiaModal .modal-content, #pautaModal .modal-content {
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
        #materiaModal button[type="button"],
        #pautaModal button[type="submit"], 
        #pautaModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
            background-color: #1f366d !important;
            color: white !important;
            transition: all 0.2s ease !important;
        }
        
        #materiaModal button[type="submit"]:hover, 
        #materiaModal button[type="button"]:hover,
        #pautaModal button[type="submit"]:hover, 
        #pautaModal button[type="button"]:hover {
            background-color: #1a2d5a !important;
            transform: translateY(-1px) !important;
        }

        #materiaModal input:focus,
        #materiaModal select:focus,
        #materiaModal textarea:focus,
        #materiaModal button:focus,
        #pautaModal input:focus,
        #pautaModal select:focus,
        #pautaModal textarea:focus,
        #pautaModal button:focus {
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
            #materiaModal, #pautaModal {
                padding: 0.5rem !important;
            }
            
            #materiaModal .modal-content, #pautaModal .modal-content {
                max-height: 95vh !important;
                border-radius: 8px !important;
            }
        }

        .tab-button {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .tab-button:hover {
            background-color: #f9fafb;
        }
        .tab-button.active {
            background-color: transparent;
        }

        /* Bulk Actions Styles for Guidelines */
        #bulkActionsGuidelines {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 16px;
            margin: 0;
            transition: all 0.3s ease;
        }

        #bulkActionsGuidelines .selection-info {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        #bulkActionsGuidelines .action-buttons {
            display: flex;
            gap: 8px;
        }

        #bulkActionsGuidelines .btn-export {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #bulkActionsGuidelines .btn-export:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
        }

        #bulkActionsGuidelines .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #bulkActionsGuidelines .btn-delete:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
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

                    <!-- Tab Navigation -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder mb-6">
                        <div class="flex border-b border-lightborder">
                            <button onclick="switchTab('subjects')" id="tabSubjects" class="tab-button active px-6 py-3 font-medium text-sm border-b-2 border-darkblue text-darkblue">
                                <?php _e('subjects'); ?>
                            </button>
                            <button onclick="switchTab('guidelines')" id="tabGuidelines" class="tab-button px-6 py-3 font-medium text-sm border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                                <?php _e('anep_guidelines'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Subjects Tab Content -->
                    <div id="contentSubjects" class="tab-content">
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8" data-default-labels='["Horas semanales", "Pautas", "Programas", "Estados"]'>
                        <div class="flex justify-between items-center p-4 border-b border-lightborder bg-gray-50">
                            <div class="flex items-center">
                                <div class="select-all-container">
                                    <input type="checkbox" id="selectAll" class="item-checkbox">
                                    <label for="selectAll"><?php _e('select_all'); ?></label>
                                </div>
                                <h3 class="font-medium text-darktext ml-4"><?php _e('subjects'); ?></h3>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="openMateriaModal()" class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                                    <span class="mr-1 text-sm">+</span>
                                    <?php _e('add_subject'); ?>
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

                        <!-- Lista de materias -->
                        <div class="divide-y divide-gray-200">
                            <?php if (!empty($materias)): ?>
                                <?php foreach ($materias as $materia): ?>
                                    <article class="item-row flex items-center justify-between p-4 transition-colors hover:bg-lightbg" 
                                             data-item-id="<?php echo $materia['id_materia']; ?>"
                                             data-original-text=""
                                             data-available-labels="<?php 
                                                 $labels = [];
                                                 if ($materia['horas_semanales']) {
                                                     $labels[] = $materia['horas_semanales'] . ' horas semanales';
                                                 }
                                                 if ($materia['pauta_anep_nombre']) {
                                                     $labels[] = $materia['pauta_anep_nombre'];
                                                 }
                                                 if ($materia['es_programa_italiano']) {
                                                     $labels[] = 'Programa Italiano';
                                                 }
                                                 $labels[] = 'Estado: Sin asignar';
                                                 echo implode('|', $labels);
                                             ?>"
                                             data-label-mapping="<?php 
                                                 $mapping = [];
                                                 if ($materia['horas_semanales']) {
                                                     $mapping['Horas semanales'] = $materia['horas_semanales'] . ' horas semanales';
                                                 }
                                                 if ($materia['pauta_anep_nombre']) {
                                                     $mapping['Pautas'] = $materia['pauta_anep_nombre'];
                                                 }
                                                 if ($materia['es_programa_italiano']) {
                                                     $mapping['Programas'] = 'Programa Italiano';
                                                 }
                                                 $mapping['Estados'] = 'Estado: Sin asignar';
                                                 echo htmlspecialchars(json_encode($mapping));
                                             ?>">
                                        <div class="flex items-center">
                                            <div class="checkbox-container">
                                                <input type="checkbox" class="item-checkbox" data-item-id="<?php echo $materia['id_materia']; ?>">
                                            </div>
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

                    <!-- ANEP Guidelines Tab Content -->
                    <div id="contentGuidelines" class="tab-content hidden">
                        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <?php _e('anep_guidelines_help_text'); ?>
                            </p>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8" data-default-labels='["Días mínimos", "Días máximos", "Condiciones"]'>
                            <div class="p-4 border-b border-lightborder bg-gray-50">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center">
                                        <div class="select-all-container">
                                            <input type="checkbox" id="selectAllGuidelines" class="item-checkbox">
                                            <label for="selectAllGuidelines"><?php _e('select_all'); ?></label>
                                        </div>
                                        <h3 class="font-medium text-darktext ml-4"><?php _e('anep_guidelines'); ?></h3>
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick="openPautaModal()" class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                                            <span class="mr-1 text-sm">+</span>
                                            <?php _e('add_guideline'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Bulk Actions Bar for Guidelines -->
                            <div id="bulkActionsGuidelines" class="bulk-actions hidden">
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
                                <div id="statisticsContainerGuidelines"></div>
                            </div>
                            <div class="divide-y divide-gray-200">
                                <?php if (!empty($pautasAnep)): ?>
                                    <?php foreach ($pautasAnep as $pauta): ?>
                                        <article class="item-row flex items-center justify-between p-4 transition-colors hover:bg-lightbg" 
                                                 data-item-id="<?php echo $pauta['id_pauta_anep']; ?>"
                                                 data-original-text=""
                                                 data-available-labels="<?php 
                                                     $labels = [];
                                                     $labels[] = $pauta['dias_minimos'] . ' días mínimos';
                                                     $labels[] = $pauta['dias_maximos'] . ' días máximos';
                                                     if ($pauta['condiciones_especiales']) {
                                                         $labels[] = 'Condiciones especiales';
                                                     }
                                                     echo implode('|', $labels);
                                                 ?>"
                                                 data-label-mapping="<?php 
                                                     $mapping = [];
                                                     $mapping['Días mínimos'] = $pauta['dias_minimos'] . ' días mínimos';
                                                     $mapping['Días máximos'] = $pauta['dias_maximos'] . ' días máximos';
                                                     if ($pauta['condiciones_especiales']) {
                                                         $mapping['Condiciones'] = 'Condiciones especiales';
                                                     }
                                                     echo htmlspecialchars(json_encode($mapping));
                                                 ?>">
                                            <div class="flex items-center">
                                                <div class="checkbox-container">
                                                    <input type="checkbox" class="item-checkbox" data-item-id="<?php echo $pauta['id_pauta_anep']; ?>">
                                                </div>
                                                <div class="w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                                                    <?php echo strtoupper(substr($pauta['nombre'], 0, 1)); ?>
                                                </div>
                                                <div class="meta">
                                                    <div class="font-semibold text-darktext mb-1">
                                                        <?php echo htmlspecialchars($pauta['nombre']); ?>
                                                    </div>
                                                    <div class="text-muted text-sm">
                                                        <?php _e('min_days'); ?>: <?php echo $pauta['dias_minimos']; ?> | 
                                                        <?php _e('max_days'); ?>: <?php echo $pauta['dias_maximos']; ?>
                                                        <?php if ($pauta['condiciones_especiales']): ?>
                                                            • <?php echo htmlspecialchars($pauta['condiciones_especiales']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <button onclick="editPauta(<?php echo $pauta['id_pauta_anep']; ?>, '<?php echo htmlspecialchars($pauta['nombre']); ?>', <?php echo $pauta['dias_minimos']; ?>, <?php echo $pauta['dias_maximos']; ?>, '<?php echo htmlspecialchars($pauta['condiciones_especiales'] ?? ''); ?>')" 
                                                        class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                                                    <?php _e('edit'); ?>
                                                </button>
                                                <button onclick="deletePauta(<?php echo $pauta['id_pauta_anep']; ?>, '<?php echo htmlspecialchars($pauta['nombre']); ?>')" 
                                                        class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                                                    <?php _e('delete'); ?>
                                                </button>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-8 text-center">
                                        <div class="text-gray-500 text-lg mb-2"><?php _e('no_guidelines_found'); ?></div>
                                        <div class="text-gray-400 text-sm"><?php _e('add_first_guideline'); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="/js/toast.js"></script>
    <script>

        let isEditMode = false;

        function openMateriaModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = '<?php _e('add_subject'); ?>';
            document.getElementById('materiaForm').reset();
            document.getElementById('materiaId').value = '';
            document.getElementById('horas_semanales').value = '1';
            
            clearErrors();
            document.getElementById('materiaModal').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('nombre').focus();
            }, 100);
        }

        function closeMateriaModal() {
            document.getElementById('materiaModal').classList.add('hidden');
            clearErrors();
        }

        function editMateria(id) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = '<?php _e('edit_subject'); ?>';
            
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('/src/controllers/MateriaHandler.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleJsonResponse)
            .then(data => {
                if (data.success) {
                    document.getElementById('materiaId').value = data.data.id_materia;
                    document.getElementById('nombre').value = data.data.nombre;
                    document.getElementById('horas_semanales').value = data.data.horas_semanales;
                    document.getElementById('id_pauta_anep').value = data.data.id_pauta_anep;
                    document.getElementById('en_conjunto').checked = data.data.en_conjunto;
                    document.getElementById('es_programa_italiano').checked = data.data.es_programa_italiano;
                    
                    if (data.data.en_conjunto && data.data.id_grupo_compartido) {
                        document.getElementById('id_grupo_compartido').value = data.data.id_grupo_compartido;
                        toggleGrupoCompartido();
                    } else {
                        toggleGrupoCompartido();
                    }
                    
                    clearErrors();
                    document.getElementById('materiaModal').classList.remove('hidden');
                    
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

        function deleteMateria(id, nombre) {
            const confirmMessage = `¿Está seguro de que desea eliminar la materia "${nombre}"?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('/src/controllers/MateriaHandler.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(handleJsonResponse)
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
                    showToast(error.message || 'Error eliminando materia', 'error');
                });
            }
        }

        function handleMateriaFormSubmit(e) {
            e.preventDefault();
            
            if (!validateMateriaForm()) {
                showToast('<?php _e('please_correct_errors'); ?>', 'error');
                return;
            }
            
            const formData = new FormData(e.target);
            formData.append('action', isEditMode ? 'update' : 'create');
            
            fetch('/src/controllers/MateriaHandler.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleJsonResponse)
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeMateriaModal();
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
        
        function validateMateriaForm() {
            let isValid = true;
            clearErrors();
            
            const nombre = document.getElementById('nombre').value.trim();
            if (!nombre) {
                showFieldError('nombre', '<?php _e('subject_name_required'); ?>');
                isValid = false;
            } else if (nombre.length < 2) {
                showFieldError('nombre', '<?php _e('subject_name_too_short'); ?>');
                isValid = false;
            }
            
            const horasSemanales = document.getElementById('horas_semanales').value;
            if (!horasSemanales || horasSemanales < 1 || horasSemanales > 40) {
                showFieldError('horas_semanales', '<?php _e('weekly_hours_invalid'); ?>');
                isValid = false;
            }
            
            const pautaAnep = document.getElementById('id_pauta_anep').value;
            if (!pautaAnep) {
                showFieldError('id_pauta_anep', '<?php _e('anep_guideline_required'); ?>');
                isValid = false;
            }
            
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

        // Toast function
        function showToast(message, type = 'info', options = {}) {
            if (typeof window.toastManager !== 'undefined') {
                return window.toastManager.show(message, type, options);
            } else {
                console.error('Toast system not available:', message);
                alert(message); // Fallback to alert
            }
        }

        // Helper function to handle JSON responses safely
        function handleJsonResponse(response) {
            // First check if response is ok
            if (!response.ok) {
                if (response.status === 302) {
                    throw new Error('Authentication required. Please refresh the page and login again.');
                }
                
                // For non-ok responses, try to extract the actual error message
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    // Parse the JSON response to get the actual error message
                    return response.json().then(data => {
                        if (data && data.message) {
                            throw new Error(data.message);
                        } else {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                    }).catch((parseError) => {
                        // If this is our own error with the message, re-throw it
                        if (parseError.message && parseError.message !== `HTTP error! status: ${response.status}`) {
                            throw parseError;
                        }
                        // If JSON parsing fails, fall back to generic error
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                } else {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            }
            
            // For ok responses, check content type
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                if (contentType && contentType.includes('text/html')) {
                    throw new Error('Server returned HTML instead of JSON. This usually means authentication failed or there was a server error. Please refresh the page and try again.');
                }
                throw new Error('Response is not JSON. Content-Type: ' + contentType);
            }
            return response.json();
        }

        document.addEventListener('DOMContentLoaded', function() {

            const sidebarLinks = document.querySelectorAll('.sidebar-link');

            function handleSidebarClick(event) {

                sidebarLinks.forEach(link => {
                    link.classList.remove('active');
                });

                this.classList.add('active');
            }

            sidebarLinks.forEach(link => {
                link.addEventListener('click', handleSidebarClick);
            });

            const logoutButton = document.getElementById('logoutButton');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const confirmMessage = '<?php _e('confirm_logout'); ?>';
                    if (confirm(confirmMessage)) {
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

            const userMenuButton = document.getElementById('userMenuButton');
            const userMenu = document.getElementById('userMenu');
            
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });
                
                document.addEventListener('click', function(e) {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }

            // Initialize subjects selection (default tab)
            initializeSubjectsSelection();
            
            // Initialize guidelines selection
            initializeGuidelinesSelection();
        });

        // ANEP Guidelines Management Functions
        let isPautaEditMode = false;

        function openPautaModal() {
            isPautaEditMode = false;
            document.getElementById('pautaModalTitle').textContent = '<?php _e('add_guideline'); ?>';
            document.getElementById('pautaForm').reset();
            document.getElementById('pautaId').value = '';
            document.getElementById('diasMinimos').value = '1';
            document.getElementById('diasMaximos').value = '5';
            
            clearPautaErrors();
            document.getElementById('pautaModal').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('pautaNombre').focus();
            }, 100);
        }

        function closePautaModal() {
            document.getElementById('pautaModal').classList.add('hidden');
            clearPautaErrors();
        }

        function editPauta(id, nombre, diasMinimos, diasMaximos, condicionesEspeciales) {
            isPautaEditMode = true;
            document.getElementById('pautaModalTitle').textContent = '<?php _e('edit_guideline'); ?>';
            document.getElementById('pautaId').value = id;
            document.getElementById('pautaNombre').value = nombre;
            document.getElementById('diasMinimos').value = diasMinimos;
            document.getElementById('diasMaximos').value = diasMaximos;
            document.getElementById('condicionesEspeciales').value = condicionesEspeciales || '';
            
            clearPautaErrors();
            document.getElementById('pautaModal').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('pautaNombre').focus();
            }, 100);
        }

        function deletePauta(id, nombre) {
            if (confirm('<?php _e('confirm_delete_guideline'); ?>: ' + nombre + '?')) {
                const formData = new FormData();
                formData.append('action', 'delete_pauta');
                formData.append('id', id);
                
                fetch('/src/controllers/MateriaHandler.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(handleJsonResponse)
                .then(data => {
                    if (data.success) {
                        showToast('<?php _e('guideline_deleted_successfully'); ?>', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(error.message || '<?php _e('error_processing_request'); ?>', 'error');
                });
            }
        }

        function handlePautaFormSubmit(event) {
            event.preventDefault();
            
            if (!validatePautaForm()) {
                showToast('<?php _e('please_correct_errors'); ?>', 'error');
                return;
            }
            
            const formData = new FormData(event.target);
            formData.append('action', isPautaEditMode ? 'update_pauta' : 'create_pauta');
            
            fetch('/src/controllers/SubjectController.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleJsonResponse)
            .then(data => {
                if (data.success) {
                    showToast(isPautaEditMode ? '<?php _e('guideline_updated_successfully'); ?>' : '<?php _e('guideline_created_successfully'); ?>', 'success');
                    closePautaModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (data.data && typeof data.data === 'object') {
                        Object.keys(data.data).forEach(field => {
                            showPautaFieldError(field, data.data[field]);
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

        function validatePautaForm() {
            let isValid = true;
            clearPautaErrors();
            
            const nombre = document.getElementById('pautaNombre').value.trim();
            const diasMinimos = parseInt(document.getElementById('diasMinimos').value);
            const diasMaximos = parseInt(document.getElementById('diasMaximos').value);
            
            if (!nombre) {
                showPautaFieldError('nombre', '<?php _e('guideline_name_required'); ?>');
                isValid = false;
            }
            
            if (diasMinimos < 1 || diasMinimos > 7) {
                showPautaFieldError('dias_minimos', '<?php _e('min_days_range'); ?>');
                isValid = false;
            }
            
            if (diasMaximos < 1 || diasMaximos > 7) {
                showPautaFieldError('dias_maximos', '<?php _e('max_days_range'); ?>');
                isValid = false;
            }
            
            if (diasMaximos < diasMinimos) {
                showPautaFieldError('dias_maximos', '<?php _e('max_days_less_than_min'); ?>');
                isValid = false;
            }
            
            return isValid;
        }

        function showPautaFieldError(field, message) {
            const errorElement = document.getElementById(field + 'Error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
        }

        function clearPautaErrors() {
            const errorElements = document.querySelectorAll('#pautaModal [id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
                element.style.display = 'none';
            });
        }

        // Tab switching functionality
        function switchTab(tab) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active state from all tabs
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-darkblue', 'text-darkblue');
                button.classList.add('border-transparent', 'text-gray-600');
            });
            
            // Show selected tab content
            document.getElementById('content' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.remove('hidden');
            
            // Activate selected tab button
            const activeButton = document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1));
            activeButton.classList.add('active', 'border-darkblue', 'text-darkblue');
            activeButton.classList.remove('border-transparent', 'text-gray-600');
            
            // Note: Selection systems are already initialized and don't need reinitialization
            // Each tab has its own container and the systems work independently
        }

        // Initialize multiple selection for subjects tab
        function initializeSubjectsSelection() {
            const subjectsContainer = document.querySelector('#contentSubjects .bg-white.rounded-lg.shadow-sm');
            if (subjectsContainer && !subjectsContainer.querySelector('.status-labels-dropdown-container')) {
                const multipleSelection = new MultipleSelection({
                    container: subjectsContainer,
                    itemSelector: '.item-row',
                    checkboxSelector: '.item-checkbox',
                    selectAllSelector: '#selectAll',
                    bulkActionsSelector: '#bulkActions',
                    entityType: 'materias',
                    onSelectionChange: function(selectedItems) {
                        // Handle selection change for subjects
                    },
                    onBulkAction: function(action, selectedIds) {
                        // Handle bulk actions for subjects
                        console.log('Bulk action:', action, 'Selected IDs:', selectedIds);
                    }
                });

                // Initialize status labels for subjects
                const statusLabels = new StatusLabels({
                    container: subjectsContainer,
                    itemSelector: '.item-row',
                    metaSelector: '.meta .text-muted',
                    entityType: 'materias'
                });
            }
        }

        // Initialize multiple selection for guidelines tab
        function initializeGuidelinesSelection() {
            const guidelinesContainer = document.querySelector('#contentGuidelines .bg-white.rounded-lg.shadow-sm');
            if (guidelinesContainer && !guidelinesContainer.querySelector('.status-labels-dropdown-container')) {
                const multipleSelection = new MultipleSelection({
                    container: guidelinesContainer,
                    itemSelector: '.item-row',
                    checkboxSelector: '.item-checkbox',
                    selectAllSelector: '#selectAllGuidelines',
                    bulkActionsSelector: '#bulkActionsGuidelines',
                    entityType: 'guidelines',
                    onSelectionChange: function(selectedItems) {
                        // Handle selection change for guidelines
                    },
                    onBulkAction: function(action, selectedIds) {
                        // Handle bulk actions for guidelines
                        console.log('Bulk action:', action, 'Selected IDs:', selectedIds);
                    }
                });

                // Initialize status labels for guidelines
                const statusLabels = new StatusLabels({
                    container: guidelinesContainer,
                    itemSelector: '.item-row',
                    metaSelector: '.meta .text-muted',
                    entityType: 'guidelines'
                });
            }
        }
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
                           placeholder="<?php _e('subject_name_placeholder'); ?>" aria-describedby="nombreError" autocomplete="off">
                    <p id="nombreError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="horas_semanales" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('weekly_hours'); ?> <span class="text-red-500">*</span></label>
                    <input type="number" id="horas_semanales" name="horas_semanales" min="1" max="40" value="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           aria-describedby="horas_semanalesError" autocomplete="off">
                    <p id="horas_semanalesError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="id_pauta_anep" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('anep_guideline'); ?> <span class="text-red-500">*</span></label>
                    <select id="id_pauta_anep" name="id_pauta_anep" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                            aria-describedby="id_pauta_anepError" autocomplete="off">
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
                            aria-describedby="id_grupo_compartidoError" autocomplete="off">
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

    <!-- Modal para agregar/editar pauta ANEP -->
    <div id="pautaModal" class="hidden" role="dialog" aria-modal="true" aria-labelledby="pautaModalTitle" aria-describedby="pautaModalDescription">
        <div class="modal-content p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="pautaModalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_guideline'); ?></h3>
                <button onclick="closePautaModal()" class="text-gray-400 hover:text-gray-600" aria-label="<?php _e('close_modal'); ?>">
                    <span class="text-sm" aria-hidden="true">×</span>
                </button>
            </div>
            <p id="pautaModalDescription" class="text-sm text-gray-600 mb-6 sr-only"><?php _e('pauta_modal_description'); ?></p>
            
            <form id="pautaForm" onsubmit="handlePautaFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="pautaId" name="id">
                
                <div>
                    <label for="pautaNombre" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('guideline_name'); ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="pautaNombre" name="nombre" required maxlength="200"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="<?php _e('guideline_name_placeholder'); ?>" aria-describedby="pautaNombreError" autocomplete="off">
                    <p id="pautaNombreError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="diasMinimos" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('min_days'); ?> <span class="text-red-500">*</span></label>
                        <input type="number" id="diasMinimos" name="dias_minimos" min="1" max="7" value="1" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                               aria-describedby="diasMinimosError" autocomplete="off">
                        <p id="diasMinimosError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                    </div>
                    
                    <div>
                        <label for="diasMaximos" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('max_days'); ?> <span class="text-red-500">*</span></label>
                        <input type="number" id="diasMaximos" name="dias_maximos" min="1" max="7" value="5" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                               aria-describedby="diasMaximosError" autocomplete="off">
                        <p id="diasMaximosError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                    </div>
                </div>
                
                <div>
                    <label for="condicionesEspeciales" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('special_conditions'); ?></label>
                    <textarea id="condicionesEspeciales" name="condiciones_especiales" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                              placeholder="<?php _e('special_conditions_placeholder'); ?>" aria-describedby="condicionesEspecialesError"></textarea>
                    <p id="condicionesEspecialesError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closePautaModal()" 
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