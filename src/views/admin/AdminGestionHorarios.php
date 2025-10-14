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
                                <div class="text-sm text-gray-600">
                                    <?php _e('click_available_slot'); ?>
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

    <script src="/js/toast.js"></script>
    <script>
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
        let currentBloque = null;
        let currentDia = null;

        function openHorarioModal() {
            isEditMode = false;
            document.getElementById('horarioModalTitle').textContent = '<?php _e('add_class_to_slot'); ?>';
            document.getElementById('horarioForm').reset();
            document.getElementById('horario_id').value = '';
            
            document.getElementById('grupo_search').value = '';
            document.getElementById('materia_search').value = '';
            document.getElementById('docente_search').value = '';
            
            // Hide teacher selection and info message
            document.getElementById('teacher_selection_container').style.display = 'none';
            document.getElementById('teacher_info_message').classList.add('hidden');
            
            resetSelectOptions('id_grupo');
            resetSelectOptions('id_materia');
            resetSelectOptions('id_docente');
            
            clearErrors();
            document.getElementById('horarioModal').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('grupo_search').focus();
            }, 100);
        }

        function openScheduleModal(idBloque, dia) {
            currentBloque = idBloque;
            currentDia = dia;
            
            document.getElementById('horario_id_bloque').value = idBloque;
            document.getElementById('horario_dia').value = dia;
                
                const bloques = <?php echo json_encode($bloques); ?>;
                const bloque = bloques.find(b => b.id_bloque == idBloque);
                
                if (bloque) {
                    document.getElementById('scheduleInfo').innerHTML = 
                        `<strong><?php _e('schedule_time'); ?>:</strong> ${dia} ${bloque.hora_inicio.substring(0,5)} - ${bloque.hora_fin.substring(0,5)}`;
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
        
        function handleHorarioFormSubmit(e) {
            e.preventDefault();
            
            if (!validateHorarioForm()) {
                showToast('<?php _e('please_correct_errors'); ?>', 'error');
                    return;
                }
                
            const url = '/src/controllers/HorarioHandler.php';
            const method = 'POST';
            
            let requestBody;
            let contentType;
            
            const formData = new FormData(e.target);
            formData.append('action', isEditMode ? 'update' : 'create');
            requestBody = formData;
            contentType = null;
            
            const fetchOptions = {
                method: method,
                body: requestBody
            };
            
            if (contentType) {
                fetchOptions.headers = {
                    'Content-Type': contentType
                };
            }
            
            // Debug: Log what we're sending
            console.log('Sending request to:', url);
            console.log('Method:', method);
            console.log('Body:', requestBody);
            console.log('Content-Type:', contentType);
            
            fetch(url, fetchOptions)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
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
            document.getElementById('filter_grupo').value = '';
            document.getElementById('filter_materia').value = '';
            document.getElementById('filter_docente').value = '';
            document.getElementById('filterResults').textContent = '';
            
            const cells = document.querySelectorAll('.horario-cell');
            cells.forEach(cell => {
                cell.style.display = 'table-cell';
                const assignment = cell.querySelector('.bg-blue-100');
                if (assignment) {
                    assignment.style.display = 'block';
                }
            });
        }

        function setupSearchFunctionality() {

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

        document.addEventListener('DOMContentLoaded', function() {
            setupSearchFunctionality();
            
            setupFilterFunctionality();
            
            // Add event listener for subject change
            const materiaSelect = document.getElementById('id_materia');
            if (materiaSelect) {
                materiaSelect.addEventListener('change', handleSubjectChange);
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