<?php
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
$sidebar = new Sidebar('admin-gestion-horarios.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $horarioModel = new Horario($database->getConnection());
    $horarios = $horarioModel->getAllHorarios();
    $bloques = $horarioModel->getAllBloques();
    $materias = $horarioModel->getSubjectsWithTeacherCounts();
    $docentes = $horarioModel->getAllDocentes();
    
    require_once __DIR__ . '/../../models/Grupo.php';
    $grupoModel = new Grupo($database->getConnection());
    $grupos = $grupoModel->getAllGrupos();

    if ($horarios === false) {
        $horarios = [];
    }
    
    $scheduleGrid = [];
    $dias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
    
    foreach ($dias as $dia) {
        $scheduleGrid[$dia] = [];
        foreach ($bloques as $bloque) {
            $id_bloque = (int)$bloque['id_bloque'];
            $scheduleGrid[$dia][$id_bloque] = null;
        }
    }
    
    foreach ($horarios as $horario) {
        $dia = $horario['dia'];
        $id_bloque = (int)$horario['id_bloque'];
        
        $scheduleGrid[$dia][$id_bloque] = $horario;
    }
    
    
} catch (Exception $e) {
    $horarios = [];
    $bloques = [];
    $grupos = [];
    $materias = [];
    $docentes = [];
    $scheduleGrid = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('schedule_management'); ?></title>
    <link rel="stylesheet" href="/css/styles.css?v=<?php echo time(); ?>">
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
        .horario-cell {
            cursor: pointer;
            transition: all 0.2s;
            min-width: 100px;
            vertical-align: top;
        }
        
        /* Time column styling to maintain proper alignment */
        tbody tr td:first-child {
            background-color: #34495e !important;
            color: white !important;
            font-weight: 600 !important;
            text-align: center !important;
            vertical-align: middle !important;
            width: 128px;
            white-space: nowrap;
        }
        
        /* Ensure table cells are properly aligned */
        table.table-fixed {
            table-layout: fixed;
        }
        
        table.table-fixed th,
        table.table-fixed td {
            border-collapse: collapse;
        }
        .horario-cell:hover {
            opacity: 0.9;
            transform: scale(0.97);
        }
        @media (max-width: 768px) {
            .horario-cell {
                min-width: 80px;
                font-size: 0.75rem;
                padding: 6px 4px;
            }
        }
        @media (max-width: 480px) {
            .horario-cell {
                min-width: 60px;
                font-size: 0.65rem;
            }
        }

        .conflict-warning {
            background-color: #fef2f2 !important;
            border: 2px solid #ef4444 !important;
            color: #dc2626 !important;
        }
        
        .conflict-warning .bg-blue-100 {
            background-color: #fecaca !important;
            color: #dc2626 !important;
        }
        
        /* Group-centric schedule management styles */
        .group-selector-primary {
            border: 2px solid #3b82f6;
            background-color: #eff6ff;
        }
        
        .schedule-cell-disabled {
            opacity: 0.3;
            pointer-events: none;
        }
        
        .schedule-cell-conflict {
            border: 2px solid #f59e0b;
            background-color: #fef3c7;
        }
        
        .schedule-cell-comparison {
            border: 2px solid #10b981;
        }
        
        .view-button-active {
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .error-input {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
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
                <div class="max-w-7xl mx-auto">
                    <!-- Header de la página -->
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('schedule_management'); ?></h2>
                        <p class="text-muted text-base"><?php _e('schedule_management_description'); ?></p>
                    </div>
                    
                    <!-- Selector de Grupo Principal -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6 border border-lightborder">
                        <div class="max-w-md">
                            <label for="filter_grupo" class="block text-lg font-semibold text-darktext mb-3">
                                Seleccione un Grupo <span class="text-red-500">*</span>
                            </label>
                            <select id="filter_grupo" class="w-full px-4 py-3 border border-lightborder rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue text-base font-medium" required>
                                <?php if (!empty($grupos)): ?>
                                    <?php foreach ($grupos as $index => $grupo): ?>
                                        <option value="<?php echo $grupo['id_grupo']; ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['nivel']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No hay grupos disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Panel de Horarios -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <!-- Header del panel -->
                        <div class="p-4 border-b border-lightborder bg-gray-50">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-medium text-darktext"><?php _e('schedules'); ?></h3>
                                <div class="text-sm text-muted">
                                    <?php _e('click_available_slot'); ?>
                                </div>
                            </div>
                            
                            <!-- Controles de vista -->
                            <div class="flex gap-2 mb-4">
                                <button id="viewNormal" class="px-3 py-1 text-sm bg-darkblue text-white rounded hover:bg-blue-800">
                                    Vista Normal
                                </button>
                                <button id="viewConflicts" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 border border-lightborder rounded hover:bg-gray-50">
                                    Ver Conflictos
                                </button>
                                <button id="viewComparison" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 border border-lightborder rounded hover:bg-gray-50">
                                    Comparar Grupos
                                </button>
                            </div>
                            
                            <!-- Filtros adicionales -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="filter_materia" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Materia</label>
                                    <select id="filter_materia" class="w-full px-3 py-2 border border-lightborder rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue text-sm">
                                        <option value="">Todas las materias</option>
                                        <?php foreach ($materias as $materia): ?>
                                            <option value="<?php echo $materia['id_materia']; ?>">
                                                <?php echo htmlspecialchars($materia['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="filter_docente" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Docente</label>
                                    <select id="filter_docente" class="w-full px-3 py-2 border border-lightborder rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue text-sm">
                                        <option value="">Todos los docentes</option>
                                        <?php foreach ($docentes as $docente): ?>
                                            <option value="<?php echo $docente['id_docente']; ?>">
                                                <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center mt-4">
                                <button onclick="clearFilters()" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 border border-lightborder rounded hover:bg-gray-50">
                                    Limpiar filtros
                                </button>
                                <span id="filterResults" class="text-sm text-gray-500"></span>
                            </div>
                        </div>
                        
                        <!-- Tabla de horarios -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse table-fixed">
                                <thead>
                                    <tr>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300 w-32"><?php _e('time'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('monday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('tuesday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('wednesday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('thursday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('friday'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $dias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
                                    foreach ($bloques as $index => $bloque): 
                                    ?>
                                        <tr>
                                            <td class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300 w-32">
                                                <?php echo date('H:i', strtotime($bloque['hora_inicio'])) . ' – ' . date('H:i', strtotime($bloque['hora_fin'])); ?>
                                            </td>
                                <?php foreach ($dias as $dia): ?>
                                            <td class="horario-cell text-center font-medium p-2 border border-gray-300 cursor-pointer hover:bg-gray-50 min-h-[60px]" 
                                         data-bloque="<?php echo $bloque['id_bloque']; ?>" 
                                                    data-dia="<?php echo $dia; ?>"
                                                    <?php 
                                                    $assignment = $scheduleGrid[$dia][(int)$bloque['id_bloque']] ?? null;
                                                    if (!$assignment): ?>
                                                onclick="openScheduleModal(<?php echo $bloque['id_bloque']; ?>, '<?php echo $dia; ?>')"
                                                    <?php endif; ?>>
                                        <?php 
                                        if ($assignment): ?>
                                                        <div class="bg-blue-100 text-blue-800 p-1 rounded text-xs" 
                                                             data-grupo-id="<?php echo $assignment['id_grupo']; ?>"
                                                             data-materia-id="<?php echo $assignment['id_materia']; ?>"
                                                             data-docente-id="<?php echo $assignment['id_docente']; ?>">
                                                            <div class="font-semibold"><?php echo htmlspecialchars($assignment['grupo_nombre']); ?></div>
                                                            <div><?php echo htmlspecialchars($assignment['materia_nombre']); ?></div>
                                                            <div class="text-xs"><?php echo htmlspecialchars($assignment['docente_nombre'] . ' ' . $assignment['docente_apellido']); ?></div>
                                                            <div class="mt-1">
                                                                <button onclick="event.stopPropagation(); editHorario(<?php echo $assignment['id_horario']; ?>)" 
                                                                        class="text-blue-600 hover:text-blue-800 text-xs mr-1">
                                                                    <?php _e('edit'); ?>
                                                </button>
                                                                <button onclick="event.stopPropagation(); deleteHorario(<?php echo $assignment['id_horario']; ?>)" 
                                                                        class="text-red-600 hover:text-red-800 text-xs">
                                                                    <?php _e('delete'); ?>
                                                </button>
                                                            </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-gray-400 text-xs hover:text-gray-600 transition-colors">
                                                <?php _e('available'); ?>
                                            </div>
                                        <?php endif; ?>
                                                </td>
                                <?php endforeach; ?>
                                        </tr>
                            <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>


    <script src="/js/toast.js?v=<?php echo time(); ?>"></script>
    <script>
        // Cache bust: <?php echo time(); ?> - Random: <?php echo rand(1000, 9999); ?>
        
        
        // Global function to avoid any scoping issues
        window.handleScheduleFormSubmission = function(e) {
            e.preventDefault();
            
            const form = document.getElementById('horarioForm');
            if (!form) {
                return;
            }
            
            const url = '/src/controllers/HorarioHandler.php';
            
            const requestData = {
                action: window.isEditMode ? 'update' : 'create',
                id: document.getElementById('horario_id').value,
                id_bloque: document.getElementById('horario_id_bloque').value,
                dia: document.getElementById('horario_dia').value,
                id_grupo: document.getElementById('id_grupo').value,
                id_materia: document.getElementById('id_materia').value,
                id_docente: document.getElementById('id_docente').value
            };
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeHorarioModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error processing request', 'error');
            });
        };
        // Ensure showToast is available globally
        if (typeof showToast === 'undefined') {
            function showToast(message, type = 'info', options = {}) {
                if (typeof window.toastManager !== 'undefined') {
                    return window.toastManager.show(message, type, options);
                } else {
                    console.error('Toast system not available:', message);
                    alert(message); // Fallback to alert
                }
            }
        }
    </script>
    <script>
        let isEditMode = false;
        window.isEditMode = false;
        let currentBloque = null;
        let currentDia = null;
        let currentViewMode = 'normal'; // 'normal', 'conflicts', 'comparison'
        let selectedGroupId = null;
        let allSchedules = <?php echo json_encode($horarios); ?>;

        function openHorarioModal() {
            isEditMode = false;
            window.isEditMode = false;
            document.getElementById('horarioModalTitle').textContent = '<?php _e('add_class_to_slot'); ?>';
            document.getElementById('horarioForm').reset();
            document.getElementById('horario_id').value = '';
            
            // Clear search fields that still exist
            const materiaSearch = document.getElementById('materia_search');
            const docenteSearch = document.getElementById('docente_search');
            if (materiaSearch) materiaSearch.value = '';
            if (docenteSearch) docenteSearch.value = '';
            
            // Hide teacher selection and info message
            document.getElementById('teacher_selection_container').style.display = 'none';
            document.getElementById('teacher_info_message').classList.add('hidden');
            
            // Reset select options that still exist
            resetSelectOptions('id_materia');
            resetSelectOptions('id_docente');
            
            clearErrors();
            document.getElementById('horarioModal').classList.remove('hidden');
            
            // Focus on first visible field (subject selector)
            setTimeout(() => {
                const materiaSelect = document.getElementById('id_materia');
                if (materiaSelect) materiaSelect.focus();
            }, 100);
        }

        function openScheduleModal(idBloque, dia) {
            // Validate that a group is selected
            if (!selectedGroupId) {
                showToast('Debe seleccionar un grupo primero', 'error');
                return;
            }
            currentBloque = idBloque;
            currentDia = dia;
            
            document.getElementById('horario_id_bloque').value = idBloque;
            document.getElementById('horario_dia').value = dia;
            document.getElementById('id_grupo').value = selectedGroupId;
            
            // Map Spanish day names to translation keys
            const dayTranslations = {
                'LUNES': '<?php _e('monday'); ?>',
                'MARTES': '<?php _e('tuesday'); ?>',
                'MIERCOLES': '<?php _e('wednesday'); ?>',
                'JUEVES': '<?php _e('thursday'); ?>',
                'VIERNES': '<?php _e('friday'); ?>'
            };
                
                const bloques = <?php echo json_encode($bloques); ?>;
                const bloque = bloques.find(b => b.id_bloque == idBloque);
                
                if (bloque) {
                const translatedDay = dayTranslations[dia] || dia;
                const startTime = new Date('1970-01-01T' + bloque.hora_inicio).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
                const endTime = new Date('1970-01-01T' + bloque.hora_fin).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
                
                    document.getElementById('scheduleInfo').innerHTML = 
                    `<strong><?php _e('schedule_time'); ?>:</strong> ${translatedDay} ${startTime} - ${endTime}`;
            }
            
            openHorarioModal();
        }

        function handleSubjectChange() {
            const subjectId = document.getElementById('id_materia').value;
            const teacherContainer = document.getElementById('teacher_selection_container');
            const teacherSelect = document.getElementById('id_docente');
            const teacherInfoMessage = document.getElementById('teacher_info_message');
            const teacherInfoText = document.getElementById('teacher_info_text');
            
            if (!subjectId) {
                teacherContainer.style.display = 'none';
                teacherInfoMessage.classList.add('hidden');
                return;
            }
            
            // Check if subject has teachers assigned
            const selectedOption = document.querySelector(`#id_materia option[value="${subjectId}"]`);
            const teacherCount = parseInt(selectedOption.getAttribute('data-teacher-count')) || 0;
            
            if (teacherCount === 0) {
                teacherContainer.style.display = 'none';
                teacherInfoMessage.classList.remove('hidden');
                teacherInfoText.textContent = 'Esta materia no tiene docentes asignados. Debe asignar docentes a la materia primero.';
                teacherInfoMessage.className = 'bg-red-50 border border-red-200 rounded-md p-3 text-sm text-red-800';
                return;
            }
            
            // Fetch teachers for this subject
            fetch(`/src/controllers/HorarioHandler.php?action=get_teachers_by_subject&id_materia=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        // Auto-select first teacher alphabetically
                        teacherSelect.value = data.data[0].id_docente;
                        
                        if (data.data.length > 1) {
                            // Show selector for manual choice
                            populateTeacherSelect(data.data);
                            teacherContainer.style.display = 'block';
                            teacherInfoMessage.classList.add('hidden');
                        } else {
                            // Hide selector, single teacher auto-assigned
                            teacherContainer.style.display = 'none';
                            teacherInfoMessage.classList.remove('hidden');
                            teacherInfoText.textContent = `Docente seleccionado automáticamente: ${data.data[0].nombre} ${data.data[0].apellido}`;
                            teacherInfoMessage.className = 'bg-blue-50 border border-blue-200 rounded-md p-3 text-sm text-blue-800';
                        }
                    } else {
                        // No teachers assigned - show error
                        teacherContainer.style.display = 'none';
                        teacherInfoMessage.classList.remove('hidden');
                        teacherInfoText.textContent = 'No hay docentes asignados a esta materia';
                        teacherInfoMessage.className = 'bg-red-50 border border-red-200 rounded-md p-3 text-sm text-red-800';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error cargando docentes de la materia', 'error');
                });
        }

        function populateTeacherSelect(teachers) {
            const teacherSelect = document.getElementById('id_docente');
            const docenteSearch = document.getElementById('docente_search');
            
            // Clear existing options except the first one
            while (teacherSelect.children.length > 1) {
                teacherSelect.removeChild(teacherSelect.lastChild);
            }
            
            // Add teacher options
            teachers.forEach(teacher => {
                const option = document.createElement('option');
                option.value = teacher.id_docente;
                option.textContent = `${teacher.nombre} ${teacher.apellido}`;
                option.setAttribute('data-search', `${teacher.nombre.toLowerCase()} ${teacher.apellido.toLowerCase()}`);
                teacherSelect.appendChild(option);
            });
            
            // Reset search
            docenteSearch.value = '';
        }

        function closeHorarioModal() {
            document.getElementById('horarioModal').classList.add('hidden');
            clearErrors();
            currentBloque = null;
            currentDia = null;
        }
        
        function editHorario(id) {
            isEditMode = true;
            window.isEditMode = true;
            document.getElementById('horarioModalTitle').textContent = '<?php _e('edit_schedule'); ?>';
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('/src/controllers/HorarioHandler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const schedule = data.data;
                    document.getElementById('horario_id').value = schedule.id_horario;
                    document.getElementById('horario_id_bloque').value = schedule.id_bloque;
                    document.getElementById('horario_dia').value = schedule.dia;
                    document.getElementById('id_grupo').value = schedule.id_grupo;
                    document.getElementById('id_materia').value = schedule.id_materia;
                    document.getElementById('id_docente').value = schedule.id_docente;
                    
                    // Switch to the group of the schedule being edited
                    const groupFilter = document.getElementById('filter_grupo');
                    if (groupFilter) {
                        groupFilter.value = schedule.id_grupo;
                        selectedGroupId = schedule.id_grupo;
                        filterScheduleGrid(schedule.id_grupo);
                    }
                    
                    const scheduleInfo = document.getElementById('scheduleInfo');
                    scheduleInfo.innerHTML = `
                        <strong><?php _e('schedule_time'); ?>:</strong> ${schedule.dia} ${schedule.hora_inicio.substring(0,5)} - ${schedule.hora_fin.substring(0,5)}
                    `;
                    
                    // Show teacher selection container for editing
                    document.getElementById('teacher_selection_container').style.display = 'block';
                    document.getElementById('teacher_info_message').classList.add('hidden');
                    
                    // Trigger subject change to populate teachers
                    handleSubjectChange();
                    
                    clearErrors();
                    document.getElementById('horarioModal').classList.remove('hidden');
                    
                    setTimeout(() => {
                        document.getElementById('id_grupo').focus();
                    }, 100);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error cargando datos del horario', 'error');
            });
        }
        
        function deleteHorario(id) {
            const confirmMessage = `¿Está seguro de que desea eliminar esta asignación de horario?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('/src/controllers/HorarioHandler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Horario eliminado exitosamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error eliminando horario', 'error');
                });
            }
        }
        
        function handleScheduleFormSubmission(e) {
            e.preventDefault();
            
            if (!validateHorarioForm()) {
                showToast('<?php _e('please_correct_errors'); ?>', 'error');
                    return;
                }
                
            const url = '/src/controllers/HorarioHandler.php';
            const method = 'POST';
            
            let requestBody;
            let contentType;
            
            const form = document.getElementById('horarioForm');
            
            // Create a completely new request object
            const requestData = {
                action: isEditMode ? 'update' : 'create',
                id: document.getElementById('horario_id').value,
                id_bloque: document.getElementById('horario_id_bloque').value,
                dia: document.getElementById('horario_dia').value,
                id_grupo: document.getElementById('id_grupo').value,
                id_materia: document.getElementById('id_materia').value,
                id_docente: document.getElementById('id_docente').value
            };
            
            
            requestBody = JSON.stringify(requestData);
            contentType = 'application/json';
            
            const fetchOptions = {
                method: method,
                body: requestBody
            };
            
            if (contentType) {
                fetchOptions.headers = {
                    'Content-Type': contentType
                };
            }
            
            
            fetch(url, fetchOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeHorarioModal();
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
        
        function validateHorarioForm() {
            let isValid = true;
            clearErrors();
            
            const grupo = document.getElementById('id_grupo').value;
            if (!grupo) {
                showFieldError('id_grupo', '<?php _e('group_required'); ?>');
                isValid = false;
            }
            
            const materia = document.getElementById('id_materia').value;
            if (!materia) {
                showFieldError('id_materia', '<?php _e('subject_required'); ?>');
                isValid = false;
            } else {
                // Check if subject has teachers assigned
                const selectedOption = document.querySelector(`#id_materia option[value="${materia}"]`);
                const teacherCount = parseInt(selectedOption.getAttribute('data-teacher-count')) || 0;
                
                if (teacherCount === 0) {
                    showFieldError('id_materia', 'Esta materia no tiene docentes asignados. Debe asignar docentes a la materia primero.');
                    isValid = false;
                }
            }
            
            const docente = document.getElementById('id_docente').value;
            if (!docente) {
                showFieldError('id_docente', '<?php _e('teacher_required'); ?>');
                isValid = false;
            }
            
            // Check for conflicts if all required fields are filled
            if (grupo && materia && docente) {
                const bloqueId = document.getElementById('horario_id_bloque').value;
                const dia = document.getElementById('horario_dia').value;
                
                if (bloqueId && dia) {
                    const conflicts = checkScheduleConflicts(grupo, materia, docente, bloqueId, dia);
                    if (conflicts.length > 0) {
                        showToast(`Conflictos detectados: ${conflicts.map(c => c.message).join('; ')}`, 'error');
                        isValid = false;
                    }
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

        function resetSelectOptions(selectId) {
            const select = document.getElementById(selectId);
            if (select) {
                const options = select.querySelectorAll('option');
                options.forEach(option => {
                    option.style.display = 'block';
                });
            }
        }

        function detectConflicts() {

            clearConflictWarnings();
            
            const cells = document.querySelectorAll('.horario-cell');
            const assignments = [];

            cells.forEach(cell => {
                const assignment = cell.querySelector('.bg-blue-100');
                if (assignment) {
                    const grupoId = assignment.getAttribute('data-grupo-id');
                    const materiaId = assignment.getAttribute('data-materia-id');
                    const docenteId = assignment.getAttribute('data-docente-id');
                    const bloqueId = cell.getAttribute('data-bloque');
                    const dia = cell.getAttribute('data-dia');
                    
                    assignments.push({
                        element: cell,
                        assignment: assignment,
                        grupoId: grupoId,
                        materiaId: materiaId,
                        docenteId: docenteId,
                        bloqueId: bloqueId,
                        dia: dia
                    });
                }
            });
            
            assignments.forEach(assignment => {
                const conflicts = findConflicts(assignment, assignments);
                if (conflicts.length > 0) {
                    markAsConflict(assignment, conflicts); 
                }
            });
        }
        
        function findConflicts(currentAssignment, allAssignments) {
            const conflicts = [];
            
            allAssignments.forEach(otherAssignment => {
                if (currentAssignment === otherAssignment) return;
                
                if (currentAssignment.docenteId === otherAssignment.docenteId && 
                    currentAssignment.bloqueId === otherAssignment.bloqueId && 
                    currentAssignment.dia === otherAssignment.dia) {
                    conflicts.push({
                        type: 'docente',
                        message: 'El docente ya tiene una clase asignada en este horario',
                        conflictingAssignment: otherAssignment
                    });
                }
                
                if (currentAssignment.grupoId === otherAssignment.grupoId && 
                    currentAssignment.bloqueId === otherAssignment.bloqueId && 
                    currentAssignment.dia === otherAssignment.dia) {
                    conflicts.push({
                        type: 'grupo',
                        message: 'El grupo ya tiene una clase asignada en este horario',
                        conflictingAssignment: otherAssignment
                    });
                }
            });
            
            return conflicts;
        }
        
        function markAsConflict(assignment, conflicts) {
            assignment.element.classList.add('conflict-warning');
            
            const conflictIndicator = document.createElement('div');
            conflictIndicator.className = 'text-red-600 text-xs font-bold mt-1';
            conflictIndicator.innerHTML = `⚠️ ${conflicts.map(c => c.message).join(' | ')}`;
            
            assignment.assignment.appendChild(conflictIndicator);
        }
        
        function clearConflictWarnings() {
            const conflictCells = document.querySelectorAll('.conflict-warning');
            conflictCells.forEach(cell => {
                cell.classList.remove('conflict-warning');
                
                const conflictIndicators = cell.querySelectorAll('.text-red-600.text-xs.font-bold');
                conflictIndicators.forEach(indicator => {
                    indicator.remove();
                });
            });
        }
        
        function checkScheduleConflicts(groupId, materiaId, docenteId, bloqueId, dia) {
            const conflicts = [];
            
            // Check for teacher conflicts (same teacher, same time slot)
            allSchedules.forEach(schedule => {
                if (schedule.id_docente == docenteId && 
                    schedule.id_bloque == bloqueId && 
                    schedule.dia === dia) {
                    conflicts.push({
                        type: 'teacher',
                        message: `El docente ${schedule.docente_nombre} ${schedule.docente_apellido} ya tiene una clase en este horario (${schedule.grupo_nombre})`,
                        conflictingSchedule: schedule
                    });
                }
            });
            
            // Check for group conflicts (same group, same time slot)
            allSchedules.forEach(schedule => {
                if (schedule.id_grupo == groupId && 
                    schedule.id_bloque == bloqueId && 
                    schedule.dia === dia) {
                    conflicts.push({
                        type: 'group',
                        message: `El grupo ${schedule.grupo_nombre} ya tiene una clase en este horario (${schedule.materia_nombre})`,
                        conflictingSchedule: schedule
                    });
                }
            });
            
            return conflicts;
        }

        function setupFilterFunctionality() {
            const filterGrupo = document.getElementById('filter_grupo');
            const filterMateria = document.getElementById('filter_materia');
            const filterDocente = document.getElementById('filter_docente');
            const filterResults = document.getElementById('filterResults');
            
            function applyFilters() {
                const selectedGrupo = filterGrupo.value;
                const selectedMateria = filterMateria.value;
                const selectedDocente = filterDocente.value;
                
                let visibleCount = 0;
                let totalCount = 0;
                
                const cells = document.querySelectorAll('.horario-cell');
                
                cells.forEach((cell, index) => {
                    const assignment = cell.querySelector('.bg-blue-100');
                    let assignmentShouldShow = true;

                    cell.style.display = 'table-cell';
                    
                    if (assignment) {
                        totalCount++;
                        
                        const grupoId = assignment.getAttribute('data-grupo-id');
                        const materiaId = assignment.getAttribute('data-materia-id');
                        const docenteId = assignment.getAttribute('data-docente-id');
                        
                        if (selectedGrupo && grupoId !== selectedGrupo) {
                            assignmentShouldShow = false;
                        }
                        
                        if (selectedMateria && materiaId !== selectedMateria) {
                            assignmentShouldShow = false;
                        }
                        
                        if (selectedDocente && docenteId !== selectedDocente) {
                            assignmentShouldShow = false;
                        }
                        
                        if (assignmentShouldShow) {
                            visibleCount++;
                        }
                        
                        assignment.style.display = assignmentShouldShow ? 'block' : 'none';
                        
                        if (!assignmentShouldShow) {
                            assignment.style.display = 'none';
                            
                            let disponibleDiv = cell.querySelector('.text-gray-400');
                            if (!disponibleDiv) {
                                disponibleDiv = document.createElement('div');
                                disponibleDiv.className = 'text-gray-400 text-xs cursor-pointer hover:text-gray-600 transition-colors';
                                disponibleDiv.textContent = 'Disponible';
                                disponibleDiv.onclick = function() {
                                    const bloque = cell.getAttribute('data-bloque');
                                    const dia = cell.getAttribute('data-dia');
                                    openScheduleModal(bloque, dia);
                                };
                                cell.appendChild(disponibleDiv);
                            }
                            disponibleDiv.style.display = 'block';
                } else {
                            const disponibleDiv = cell.querySelector('.text-gray-400');
                            if (disponibleDiv) {
                                disponibleDiv.style.display = 'none';
                            }
                        }
                    } else {
                        const disponibleDiv = cell.querySelector('.text-gray-400');
                        if (disponibleDiv) {
                            disponibleDiv.style.display = 'block';
                        }
                    }
                });
                
                if (selectedGrupo || selectedMateria || selectedDocente) {
                    filterResults.textContent = `Mostrando ${visibleCount} de ${totalCount} asignaciones`;
                } else {
                    filterResults.textContent = '';
                }
            }
            
            if (filterGrupo) filterGrupo.addEventListener('change', applyFilters);
            if (filterMateria) filterMateria.addEventListener('change', applyFilters);
            if (filterDocente) filterDocente.addEventListener('change', applyFilters);
        }
        
        function clearFilters() {
            // Don't clear the group filter as it's required
            document.getElementById('filter_materia').value = '';
            document.getElementById('filter_docente').value = '';
            document.getElementById('filterResults').textContent = '';
            
            // Re-apply the current group filter
            if (selectedGroupId) {
                filterScheduleGrid(selectedGroupId);
            }
        }

        function setupSearchFunctionality() {
            // materia_search still exists
            const materiaSearch = document.getElementById('materia_search');
            const materiaSelect = document.getElementById('id_materia');
            
            if (materiaSearch && materiaSelect) {
                materiaSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const options = materiaSelect.querySelectorAll('option');
                    
                    options.forEach(option => {
                        if (option.value === '') {
                            option.style.display = 'block';
                            return;
                        }
                        
                        const searchData = option.getAttribute('data-search') || '';
                        if (searchData.includes(searchTerm)) {
                            option.style.display = 'block';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                });
            }

            // docente_search still exists
            const docenteSearch = document.getElementById('docente_search');
            const docenteSelect = document.getElementById('id_docente');
            
            if (docenteSearch && docenteSelect) {
                docenteSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const options = docenteSelect.querySelectorAll('option');
                    
                    options.forEach(option => {
                        if (option.value === '') {
                            option.style.display = 'block';
                            return;
                        }
                        
                        const searchData = option.getAttribute('data-search') || '';
                        if (searchData.includes(searchTerm)) {
                            option.style.display = 'block';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                });
            }
        }

        // Store original assignment content for restoration
        const originalAssignmentContent = new Map();

        // Schedule grid filtering functions
        function filterScheduleGrid(groupId) {
            selectedGroupId = groupId;
            
            // Update hidden group field in modal
            const hiddenGroupField = document.getElementById('id_grupo');
            if (hiddenGroupField) {
                hiddenGroupField.value = groupId;
            }
            
            // Get all schedule cells
            const cells = document.querySelectorAll('.horario-cell');
            
            cells.forEach((cell, index) => {
                // Reset cell display
                cell.style.display = 'table-cell';
                cell.style.opacity = '1';
                
                const isAssignment = cell.querySelector('.bg-blue-100');
                const dia = cell.getAttribute('data-dia');
                const bloque = cell.getAttribute('data-bloque');
                
                
                if (isAssignment) {
                    // This is an assignment cell - get group ID from the inner div
                    const cellGroupId = isAssignment.getAttribute('data-grupo-id');
                    const cellKey = `${cell.getAttribute('data-dia')}_${cell.getAttribute('data-bloque')}`;
                    
                    // Store original content if not already stored
                    if (!originalAssignmentContent.has(cellKey)) {
                        originalAssignmentContent.set(cellKey, {
                            content: cell.innerHTML,
                            grupoId: cellGroupId,
                            materiaId: isAssignment.getAttribute('data-materia-id'),
                            docenteId: isAssignment.getAttribute('data-docente-id')
                        });
                    }
                    
                    if (cellGroupId != groupId) {
                        // Show as available instead of hiding
                        cell.style.display = 'table-cell';
                        cell.style.opacity = '1';
                        
                        // Replace assignment content with "Available"
                        cell.innerHTML = '<div class="text-gray-400 text-xs hover:text-gray-600 transition-colors"><?php _e("available"); ?></div>';
                        
                        // Add click handler for available slot
                        cell.onclick = function() {
                            openScheduleModal(cell.getAttribute('data-bloque'), cell.getAttribute('data-dia'));
                        };
                        
                        } else {
                        cell.style.display = 'table-cell';
                        cell.style.opacity = '1';
                        
                        // Restore original assignment content
                        const original = originalAssignmentContent.get(cellKey);
                        if (original) {
                            cell.innerHTML = original.content;
                        }
                    }
                }
                // Empty slots are always shown
            });
            
            // Update click handlers for empty slots
            updateClickHandlers(groupId);
        }
        
        function updateClickHandlers(groupId) {
            const cells = document.querySelectorAll('.horario-cell');
            
            cells.forEach((cell, index) => {
                const isAssignment = cell.querySelector('.bg-blue-100');
                const bloque = cell.getAttribute('data-bloque');
                const dia = cell.getAttribute('data-dia');
                
                
                if (!isAssignment) {
                    // Remove existing click handlers
                    cell.onclick = null;
                    
                    // Add new click handler for this group
                    cell.onclick = function() {
                        openScheduleModal(bloque, dia);
                    };
                }
            });
        }
        
        // View mode functions
        function showNormalView() {
            currentViewMode = 'normal';
            updateViewButtons();
            if (selectedGroupId) {
                filterScheduleGrid(selectedGroupId);
            }
        }
        
        function showConflictView() {
            currentViewMode = 'conflicts';
            updateViewButtons();
            if (selectedGroupId) {
                filterScheduleGrid(selectedGroupId);
                detectConflicts();
            }
        }
        
        function showComparisonView() {
            currentViewMode = 'comparison';
            updateViewButtons();
            if (selectedGroupId) {
                filterScheduleGrid(selectedGroupId);
                renderComparisonView();
            }
        }
        
        function updateViewButtons() {
            const normalBtn = document.getElementById('viewNormal');
            const conflictsBtn = document.getElementById('viewConflicts');
            const comparisonBtn = document.getElementById('viewComparison');
            
            // Reset all buttons
            [normalBtn, conflictsBtn, comparisonBtn].forEach(btn => {
                btn.className = 'px-3 py-1 text-sm text-gray-600 hover:text-gray-800 border border-lightborder rounded hover:bg-gray-50';
            });
            
            // Highlight active button
            if (currentViewMode === 'normal') {
                normalBtn.className = 'px-3 py-1 text-sm bg-darkblue text-white rounded hover:bg-blue-800';
            } else if (currentViewMode === 'conflicts') {
                conflictsBtn.className = 'px-3 py-1 text-sm bg-amber-600 text-white rounded hover:bg-amber-700';
            } else if (currentViewMode === 'comparison') {
                comparisonBtn.className = 'px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700';
            }
        }
        
        function renderComparisonView() {
            // Color code different groups
            const cells = document.querySelectorAll('.horario-cell');
            const groupColors = {
                1: 'bg-blue-100 border-blue-300',
                2: 'bg-green-100 border-green-300',
                3: 'bg-yellow-100 border-yellow-300',
                4: 'bg-purple-100 border-purple-300',
                5: 'bg-pink-100 border-pink-300'
            };
            
            cells.forEach(cell => {
                const assignment = cell.querySelector('.bg-blue-100');
                if (assignment) {
                    const groupId = cell.getAttribute('data-grupo-id');
                    const colorClass = groupColors[groupId] || 'bg-gray-100 border-gray-300';
                    
                    // Remove existing color classes
                    assignment.className = assignment.className.replace(/bg-\w+-\d+|border-\w+-\d+/g, '');
                    // Add new color classes
                    assignment.className += ' ' + colorClass;
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize group filter
            const groupFilter = document.getElementById('filter_grupo');
            if (groupFilter) {
                // Auto-select first group
                if (groupFilter.options.length > 0) {
                    const firstGroupId = groupFilter.options[0].value;
                    if (firstGroupId) {
                        selectedGroupId = firstGroupId;
                        filterScheduleGrid(firstGroupId);
                    }
                }
                
                // Add change listener for group selection
                groupFilter.addEventListener('change', function() {
                    selectedGroupId = this.value;
                    if (selectedGroupId) {
                        filterScheduleGrid(selectedGroupId);
                    }
                });
            }
            
            setupSearchFunctionality();
            
            setupFilterFunctionality();
            
            // Add event listener for subject change
            const materiaSelect = document.getElementById('id_materia');
            if (materiaSelect) {
                materiaSelect.addEventListener('change', handleSubjectChange);
            }
            
            
            // Add view mode button listeners
            const viewNormalBtn = document.getElementById('viewNormal');
            const viewConflictsBtn = document.getElementById('viewConflicts');
            const viewComparisonBtn = document.getElementById('viewComparison');
            
            if (viewNormalBtn) {
                viewNormalBtn.addEventListener('click', showNormalView);
            }
            if (viewConflictsBtn) {
                viewConflictsBtn.addEventListener('click', showConflictView);
            }
            if (viewComparisonBtn) {
                viewComparisonBtn.addEventListener('click', showComparisonView);
            }

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
        });
    </script>

    <!-- Modal para agregar/editar horario -->
    <div id="horarioModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="horarioModalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_schedule'); ?></h3>
                <button onclick="closeHorarioModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="inline mr-2 text-xs">•</span>
                </button>
            </div>
            
            <form id="horarioForm" class="space-y-4">
                <input type="hidden" id="horario_id" name="id">
                <input type="hidden" id="horario_id_bloque" name="id_bloque">
                <input type="hidden" id="horario_dia" name="dia">
                
                <!-- Hidden group field - populated from filter selection -->
                <input type="hidden" id="id_grupo" name="id_grupo" required>
                
                <div>
                    <label for="id_materia" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('subject'); ?> <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="materia_search" placeholder="Buscar materia..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm mb-2">
                        <select id="id_materia" name="id_materia" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                                aria-describedby="id_materiaError">
                            <option value=""><?php _e('select_subject'); ?></option>
                            <?php foreach ($materias as $materia): ?>
                                <option value="<?php echo $materia['id_materia']; ?>" 
                                        data-search="<?php echo strtolower(htmlspecialchars($materia['nombre'])); ?>"
                                        data-teacher-count="<?php echo $materia['teacher_count']; ?>">
                                    <?php echo htmlspecialchars($materia['nombre']); ?>
                                    <?php if ($materia['teacher_count'] == 0): ?>
                                        [Sin docentes]
                                    <?php else: ?>
                                        [<?php echo $materia['teacher_count']; ?> docente<?php echo $materia['teacher_count'] > 1 ? 's' : ''; ?>]
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p id="id_materiaError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div id="teacher_selection_container" style="display: none;">
                    <label for="id_docente" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('teacher'); ?> <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="docente_search" placeholder="Buscar docente..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm mb-2">
                        <select id="id_docente" name="id_docente" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                                aria-describedby="id_docenteError">
                            <option value=""><?php _e('select_teacher'); ?></option>
                            <?php foreach ($docentes as $docente): ?>
                                <option value="<?php echo $docente['id_docente']; ?>" data-search="<?php echo strtolower(htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido'])); ?>">
                                    <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p id="id_docenteError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div id="teacher_info_message" class="hidden bg-blue-50 border border-blue-200 rounded-md p-3 text-sm text-blue-800">
                    <div class="flex items-center">
                        <span class="mr-2">ℹ️</span>
                        <span id="teacher_info_text">Docente seleccionado automáticamente</span>
                    </div>
                </div>
                
                <div id="scheduleInfo" class="bg-gray-50 p-3 rounded text-sm text-gray-600"></div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeHorarioModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('cancel'); ?>
                    </button>
                    <button type="button" onclick="handleScheduleFormSubmission(event)" 
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