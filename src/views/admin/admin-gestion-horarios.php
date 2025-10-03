<?php
// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-gestion-horarios.php');

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Load database configuration and get schedule data
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // Get schedule data
    $horarioModel = new Horario($database->getConnection());
    $horarios = $horarioModel->getAllHorarios();
    $bloques = $horarioModel->getAllBloques();
    $materias = $horarioModel->getAllMaterias();
    $docentes = $horarioModel->getAllDocentes();
    
    // Get grupos using the dedicated Grupo model
    require_once __DIR__ . '/../../models/Grupo.php';
    $grupoModel = new Grupo($database->getConnection());
    $grupos = $grupoModel->getAllGrupos();
    
    
    if ($horarios === false) {
        $horarios = [];
    }
    
    // Organize schedule by day and time
    $scheduleGrid = [];
    $dias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
    
    foreach ($dias as $dia) {
        $scheduleGrid[$dia] = [];
        foreach ($bloques as $bloque) {
            $id_bloque = (int)$bloque['id_bloque']; // Force integer conversion
            $scheduleGrid[$dia][$id_bloque] = null;
        }
    }
    
    // Fill the schedule grid with current assignments
    foreach ($horarios as $horario) {
        $dia = $horario['dia'];
        $id_bloque = (int)$horario['id_bloque'];
        
        // Direct assignment without checking isset
        $scheduleGrid[$dia][$id_bloque] = $horario;
    }
    
    error_log("Schedule grid filled successfully. LUNES[1] = " . ($scheduleGrid['LUNES'][1] ? 'EXISTS' : 'NULL'));
    
} catch (Exception $e) {
    error_log("Error cargando horarios: " . $e->getMessage());
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
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('schedule_management'); ?></title>
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
        
        
        /* Conflict detection styles */
        .conflict-warning {
            background-color: #fef2f2 !important;
            border: 2px solid #ef4444 !important;
            color: #dc2626 !important;
        }
        
        .conflict-warning .bg-blue-100 {
            background-color: #fecaca !important;
            color: #dc2626 !important;
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
                                <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('schedule_management'); ?></h2>
                                <p class="text-muted mb-6 text-base"><?php _e('schedule_management_description'); ?></p>
                                
                            </div>
                            
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <!-- Header de la tabla -->
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-medium text-darktext"><?php _e('schedules'); ?></h3>
                                <div class="flex gap-2">
                                    <button onclick="openHorarioModal()" class="py-2 px-4 border-none rounded cursor-pointer font-medium transition-all text-sm bg-darkblue text-white hover:bg-navy flex items-center">
                                        <span class="mr-1 text-sm">+</span>
                                        <?php _e('add_schedule'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Filtros -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="filter_grupo" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Grupo</label>
                                    <select id="filter_grupo" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue text-sm">
                                        <option value="">Todos los grupos</option>
                                        <?php foreach ($grupos as $grupo): ?>
                                            <option value="<?php echo $grupo['id_grupo']; ?>">
                                                <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['nivel']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="filter_materia" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Materia</label>
                                    <select id="filter_materia" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue text-sm">
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
                                    <select id="filter_docente" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue text-sm">
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
                                <div class="flex gap-2">
                                    <button onclick="clearFilters()" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded hover:bg-gray-50">
                                        Limpiar filtros
                                    </button>
                                    <span id="filterResults" class="text-sm text-gray-500"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('time'); ?></th>
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
                                    foreach ($bloques as $bloque): 
                                    ?>
                                        <tr>
                                            <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">
                                                <?php echo date('H:i', strtotime($bloque['hora_inicio'])) . ' – ' . date('H:i', strtotime($bloque['hora_fin'])); ?>
                                            </th>
                                <?php foreach ($dias as $dia): ?>
                                                <td class="horario-cell text-center font-medium p-2 border border-gray-300 cursor-pointer hover:bg-gray-50" 
                                         data-bloque="<?php echo $bloque['id_bloque']; ?>" 
                                                    data-dia="<?php echo $dia; ?>">
                                        <?php 
                                        $assignment = $scheduleGrid[$dia][(int)$bloque['id_bloque']] ?? null;
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
                                                            <div class="text-gray-400 text-xs cursor-pointer hover:text-gray-600 transition-colors" onclick="openScheduleModal(<?php echo $bloque['id_bloque']; ?>, '<?php echo $dia; ?>')">
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

    <script src="/js/toast.js"></script>
    <script>
        let isEditMode = false;
        let currentBloque = null;
        let currentDia = null;

        // Modal functions
        function openHorarioModal() {
            isEditMode = false;
            document.getElementById('horarioModalTitle').textContent = '<?php _e('add_schedule'); ?>';
            document.getElementById('horarioForm').reset();
            document.getElementById('horario_id').value = '';
            
            // Clear search fields
            document.getElementById('grupo_search').value = '';
            document.getElementById('materia_search').value = '';
            document.getElementById('docente_search').value = '';
            
            // Reset all options to visible
            resetSelectOptions('id_grupo');
            resetSelectOptions('id_materia');
            resetSelectOptions('id_docente');
            
            clearErrors();
            document.getElementById('horarioModal').classList.remove('hidden');
            
            // Focus on first input
            setTimeout(() => {
                document.getElementById('grupo_search').focus();
            }, 100);
        }

        function openScheduleModal(idBloque, dia) {
            currentBloque = idBloque;
            currentDia = dia;
            
            document.getElementById('horario_id_bloque').value = idBloque;
            document.getElementById('horario_dia').value = dia;
                
                // Find block time info
                const bloques = <?php echo json_encode($bloques); ?>;
                const bloque = bloques.find(b => b.id_bloque == idBloque);
                
                if (bloque) {
                    document.getElementById('scheduleInfo').innerHTML = 
                        `<strong><?php _e('schedule_time'); ?>:</strong> ${dia} ${bloque.hora_inicio.substring(0,5)} - ${bloque.hora_fin.substring(0,5)}`;
            }
            
            openHorarioModal();
        }

        function closeHorarioModal() {
            document.getElementById('horarioModal').classList.add('hidden');
            clearErrors();
            currentBloque = null;
            currentDia = null;
        }
        
        // Edit horario
        function editHorario(id) {
            isEditMode = true;
            document.getElementById('horarioModalTitle').textContent = '<?php _e('edit_schedule'); ?>';
            
            // Use the correct API endpoint
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('/src/controllers/horario_handler.php', {
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
                    
                    // Show schedule info
                    const scheduleInfo = document.getElementById('scheduleInfo');
                    scheduleInfo.innerHTML = `
                        <strong><?php _e('schedule_time'); ?>:</strong> ${schedule.dia} ${schedule.hora_inicio.substring(0,5)} - ${schedule.hora_fin.substring(0,5)}
                    `;
                    
                    clearErrors();
                    document.getElementById('horarioModal').classList.remove('hidden');
                    
                    // Focus on first input
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
        
        // Delete horario
        function deleteHorario(id) {
            const confirmMessage = `¿Está seguro de que desea eliminar esta asignación de horario?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('/src/controllers/horario_handler.php', {
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
        
        // Handle form submission
        function handleHorarioFormSubmit(e) {
            e.preventDefault();
            
            if (!validateHorarioForm()) {
                showToast('<?php _e('please_correct_errors'); ?>', 'error');
                    return;
                }
                
            const url = isEditMode 
                ? `/admin/schedules/${document.getElementById('horario_id').value}`
                : '/admin/schedules';
            const method = isEditMode ? 'PUT' : 'POST';
            
            let requestBody;
            let contentType;
            
            if (isEditMode) {
                // For PUT requests, send as URL-encoded data
                const formData = new FormData(e.target);
                const urlEncodedData = new URLSearchParams();
                for (let [key, value] of formData.entries()) {
                    urlEncodedData.append(key, value);
                }
                requestBody = urlEncodedData.toString();
                contentType = 'application/x-www-form-urlencoded';
                } else {
                // For POST requests, use FormData
                requestBody = new FormData(e.target);
                contentType = null; // Let browser set it
            }
            
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
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeHorarioModal();
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
        
        // Validation functions
        function validateHorarioForm() {
            let isValid = true;
            clearErrors();
            
            // Validate grupo
            const grupo = document.getElementById('id_grupo').value;
            if (!grupo) {
                showFieldError('id_grupo', '<?php _e('group_required'); ?>');
                isValid = false;
            }
            
            // Validate materia
            const materia = document.getElementById('id_materia').value;
            if (!materia) {
                showFieldError('id_materia', '<?php _e('subject_required'); ?>');
                isValid = false;
            }
            
            // Validate docente
            const docente = document.getElementById('id_docente').value;
            if (!docente) {
                showFieldError('id_docente', '<?php _e('teacher_required'); ?>');
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
        
        // Toast system is now handled by /js/toast.js

        // Helper function to reset select options visibility
        function resetSelectOptions(selectId) {
            const select = document.getElementById(selectId);
            if (select) {
                const options = select.querySelectorAll('option');
                options.forEach(option => {
                    option.style.display = 'block';
                });
            }
        }

        // Conflict detection functionality
        function detectConflicts() {
            // Clear previous conflict warnings
            clearConflictWarnings();
            
            const cells = document.querySelectorAll('.horario-cell');
            const assignments = [];
            
            // Collect all assignments with their data
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
            
            // Check for conflicts
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
                
                // Check for docente conflict (same teacher, same time slot)
                if (currentAssignment.docenteId === otherAssignment.docenteId && 
                    currentAssignment.bloqueId === otherAssignment.bloqueId && 
                    currentAssignment.dia === otherAssignment.dia) {
                    conflicts.push({
                        type: 'docente',
                        message: 'El docente ya tiene una clase asignada en este horario',
                        conflictingAssignment: otherAssignment
                    });
                }
                
                // Check for grupo conflict (same group, same time slot)
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
            
            // Add conflict indicator
            const conflictIndicator = document.createElement('div');
            conflictIndicator.className = 'text-red-600 text-xs font-bold mt-1';
            conflictIndicator.innerHTML = `⚠️ ${conflicts.map(c => c.message).join(' | ')}`;
            
            assignment.assignment.appendChild(conflictIndicator);
        }
        
        function clearConflictWarnings() {
            const conflictCells = document.querySelectorAll('.conflict-warning');
            conflictCells.forEach(cell => {
                cell.classList.remove('conflict-warning');
                
                // Remove conflict indicators
                const conflictIndicators = cell.querySelectorAll('.text-red-600.text-xs.font-bold');
                conflictIndicators.forEach(indicator => {
                    indicator.remove();
                });
            });
        }

        // Filter functionality for schedule table
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
                
                // Get all schedule cells
                const cells = document.querySelectorAll('.horario-cell');
                
                cells.forEach((cell, index) => {
                    const assignment = cell.querySelector('.bg-blue-100');
                    let assignmentShouldShow = true;
                    
                    // Always show the cell (disponible)
                    cell.style.display = 'table-cell';
                    
                    if (assignment) {
                        totalCount++;
                        
                        // Get assignment data from data attributes
                        const grupoId = assignment.getAttribute('data-grupo-id');
                        const materiaId = assignment.getAttribute('data-materia-id');
                        const docenteId = assignment.getAttribute('data-docente-id');
                        
                        // Check grupo filter
                        if (selectedGrupo && grupoId !== selectedGrupo) {
                            assignmentShouldShow = false;
                        }
                        
                        // Check materia filter
                        if (selectedMateria && materiaId !== selectedMateria) {
                            assignmentShouldShow = false;
                        }
                        
                        // Check docente filter
                        if (selectedDocente && docenteId !== selectedDocente) {
                            assignmentShouldShow = false;
                        }
                        
                        if (assignmentShouldShow) {
                            visibleCount++;
                        }
                        
                        // Show/hide only the assignment, not the cell
                        assignment.style.display = assignmentShouldShow ? 'block' : 'none';
                        
                        // If assignment is hidden, show "Disponible" text
                        if (!assignmentShouldShow) {
                            // Hide the assignment
                            assignment.style.display = 'none';
                            
                            // Create or show "Disponible" text
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
                            // If assignment is shown, hide "Disponible" text
                            const disponibleDiv = cell.querySelector('.text-gray-400');
                            if (disponibleDiv) {
                                disponibleDiv.style.display = 'none';
                            }
                        }
                    } else {
                        // For empty cells, always show them as "Disponible"
                        const disponibleDiv = cell.querySelector('.text-gray-400');
                        if (disponibleDiv) {
                            disponibleDiv.style.display = 'block';
                        }
                    }
                });
                
                // Update filter results
                if (selectedGrupo || selectedMateria || selectedDocente) {
                    filterResults.textContent = `Mostrando ${visibleCount} de ${totalCount} asignaciones`;
                } else {
                    filterResults.textContent = '';
                }
            }
            
            // Add event listeners
            if (filterGrupo) filterGrupo.addEventListener('change', applyFilters);
            if (filterMateria) filterMateria.addEventListener('change', applyFilters);
            if (filterDocente) filterDocente.addEventListener('change', applyFilters);
        }
        
        function clearFilters() {
            document.getElementById('filter_grupo').value = '';
            document.getElementById('filter_materia').value = '';
            document.getElementById('filter_docente').value = '';
            document.getElementById('filterResults').textContent = '';
            
            // Show all cells and assignments
            const cells = document.querySelectorAll('.horario-cell');
            cells.forEach(cell => {
                cell.style.display = 'table-cell';
                const assignment = cell.querySelector('.bg-blue-100');
                if (assignment) {
                    assignment.style.display = 'block';
                }
            });
        }

        // Search functionality for form selects
        function setupSearchFunctionality() {
            // Grupo search
            const grupoSearch = document.getElementById('grupo_search');
            const grupoSelect = document.getElementById('id_grupo');
            
            if (grupoSearch && grupoSelect) {
                grupoSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const options = grupoSelect.querySelectorAll('option');
                    
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
            
            // Materia search
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
            
            // Docente search
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

        // Funcionalidad para la barra lateral
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize search functionality
            setupSearchFunctionality();
            
            // Initialize filter functionality
            setupFilterFunctionality();
            
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

    <!-- Modal para agregar/editar horario -->
    <div id="horarioModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="horarioModalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_schedule'); ?></h3>
                <button onclick="closeHorarioModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="inline mr-2 text-xs">•</span>
                </button>
            </div>
            
            <form id="horarioForm" onsubmit="handleHorarioFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="horario_id" name="id">
                <input type="hidden" id="horario_id_bloque" name="id_bloque">
                <input type="hidden" id="horario_dia" name="dia">
                
                <div>
                    <label for="id_grupo" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('group'); ?> <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="grupo_search" placeholder="Buscar grupo..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm mb-2">
                        <select id="id_grupo" name="id_grupo" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                                aria-describedby="id_grupoError">
                            <option value=""><?php _e('select_group'); ?></option>
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?php echo $grupo['id_grupo']; ?>" data-search="<?php echo strtolower(htmlspecialchars($grupo['nombre'] . ' ' . $grupo['nivel'])); ?>">
                                    <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['nivel']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p id="id_grupoError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
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
                                <option value="<?php echo $materia['id_materia']; ?>" data-search="<?php echo strtolower(htmlspecialchars($materia['nombre'])); ?>">
                                    <?php echo htmlspecialchars($materia['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p id="id_materiaError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
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
                
                <div id="scheduleInfo" class="bg-gray-50 p-3 rounded text-sm text-gray-600"></div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeHorarioModal()" 
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