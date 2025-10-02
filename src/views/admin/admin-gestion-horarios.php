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
            $scheduleGrid[$dia][$bloque['id_bloque']] = null;
        }
    }
    
    // Fill the schedule grid with current assignments
    foreach ($horarios as $horario) {
        if (isset($scheduleGrid[$horario['dia']][$horario['id_bloque']])) {
            $scheduleGrid[$horario['dia']][$horario['id_bloque']] = $horario;
        }
    }
    
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
    <title><?php _e('app_name'); ?> — <?php _e('schedule_management'); ?></title>
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
        
        /* Schedule Grid Styles */
        .schedule-grid {
            display: grid;
            grid-template-columns: 120px repeat(5, 1fr);
            gap: 1px;
            background-color: #e5e7eb;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .schedule-header {
            background-color: #1f366d;
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .schedule-time {
            background-color: #374151;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: 500;
            font-size: 12px;
        }
        
        .schedule-cell {
            background-color: white;
            min-height: 80px;
            padding: 4px;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .schedule-cell:hover {
            background-color: #f9fafb;
            border-color: #3b82f6;
        }
        
        .schedule-cell.occupied {
            background-color: #dbeafe;
            border-color: #3b82f6;
        }
        
        .schedule-cell.conflict {
            background-color: #fef2f2;
            border-color: #ef4444;
            animation: pulse 2s infinite;
        }
        
        .schedule-cell.warning {
            background-color: #fef3c7;
            border-color: #f59e0b;
        }
        
        .schedule-assignment {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 6px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            margin-bottom: 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .schedule-assignment .group-name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .schedule-assignment .subject-name {
            opacity: 0.9;
            font-size: 10px;
        }
        
        .schedule-assignment .teacher-name {
            opacity: 0.8;
            font-size: 10px;
            margin-top: 2px;
        }
        
        .schedule-actions {
            position: absolute;
            top: 2px;
            right: 2px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .schedule-cell:hover .schedule-actions {
            opacity: 1;
        }
        
        .schedule-actions button {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 3px;
            padding: 2px 4px;
            margin-left: 2px;
            font-size: 10px;
            cursor: pointer;
            color: #374151;
        }
        
        .schedule-actions button:hover {
            background: white;
            color: #1f366d;
        }
        
        .schedule-actions .edit-btn {
            color: #3b82f6;
        }
        
        .schedule-actions .delete-btn {
            color: #ef4444;
        }
        
        /* Conflict Indicators */
        .conflict-indicator {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ef4444;
        }
        
        .warning-indicator {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #f59e0b;
        }
        
        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal.hidden {
            display: none;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .conflict-panel {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
        }
        
        .conflict-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .conflict-item:last-child {
            margin-bottom: 0;
        }
        
        .conflict-icon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 8px;
            flex-shrink: 0;
        }
        
        .conflict-icon.error {
            background-color: #ef4444;
        }
        
        .conflict-icon.warning {
            background-color: #f59e0b;
        }
        
        .conflict-icon.info {
            background-color: #3b82f6;
        }
        
        .conflict-message {
            font-size: 14px;
            color: #374151;
        }
        
        .anep-conflicts {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
        }
        
        .anep-conflicts h4 {
            color: #92400e;
            font-size: 16px;
        }
        
        .anep-conflicts .conflict-detail {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .anep-conflicts .severity-error {
            border-left: 4px solid #dc2626;
        }
        
        .anep-conflicts .severity-warning {
            border-left: 4px solid #d97706;
        }
        
        .anep-conflicts .severity-info {
            border-left: 4px solid #2563eb;
        }
        
        .suggestion-panel {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 12px;
            margin-top: 16px;
        }
        
        .suggestion-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            padding: 8px;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .suggestion-item:hover {
            background-color: #f8fafc;
        }
        
        .suggestion-item:last-child {
            margin-bottom: 0;
        }
        
        .suggestion-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            color: #3b82f6;
        }
        
        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #3b82f6;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .schedule-grid {
                grid-template-columns: 80px repeat(5, 1fr);
                font-size: 12px;
            }
            
            .schedule-cell {
                min-height: 60px;
                padding: 2px;
            }
            
            .schedule-assignment {
                padding: 4px 6px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('schedule_management'); ?></div>
                
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

            <!-- Contenido principal -->
            <section class="flex-1 px-6 py-8">
                <div class="max-w-7xl mx-auto">
                    <!-- Header con controles -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="text-darktext text-2xl font-semibold mb-2"><?php _e('schedule_management'); ?></h2>
                                <p class="text-muted text-base"><?php _e('schedule_management_description'); ?></p>
                            </div>
                            
                            <div class="flex gap-3">
                                <select id="viewMode" class="px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-darkblue focus:border-darkblue">
                                    <option value="group" selected><?php _e('by_group'); ?></option>
                                    <option value="teacher"><?php _e('by_teacher'); ?></option>
                                </select>
                                
                                <select id="groupFilter" class="px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-darkblue focus:border-darkblue">
                                    <option value=""><?php _e('select_group'); ?></option>
                                    <?php foreach ($grupos as $index => $grupo): ?>
                                        <option value="<?php echo $grupo['id_grupo']; ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['nivel']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <select id="teacherFilter" class="px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-darkblue focus:border-darkblue hidden">
                                    <option value=""><?php _e('select_teacher'); ?></option>
                                    <?php foreach ($docentes as $docente): ?>
                                        <option value="<?php echo $docente['id_docente']; ?>">
                                            <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <button onclick="openScheduleModal()" class="px-4 py-2 bg-darkblue text-white rounded-md text-sm font-medium hover:bg-navy transition-colors">
                                    <span class="mr-1">+</span>
                                    <?php _e('add_schedule'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Conflict Summary -->
                        <div id="conflictSummary" class="hidden bg-red-50 border border-red-200 rounded-md p-4 mb-4">
                            <div class="flex items-center">
                                <div class="w-5 h-5 text-red-400 mr-3">⚠️</div>
                                <div>
                                    <h3 class="text-red-800 font-medium"><?php _e('conflicts_detected'); ?></h3>
                                    <p class="text-red-600 text-sm" id="conflictCount">0 <?php _e('conflicts_found'); ?></p>
                                </div>
                                <button onclick="showConflictDetails()" class="ml-auto text-red-600 hover:text-red-800 text-sm font-medium">
                                    <?php _e('view_details'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Grid -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder">
                        <div class="schedule-grid">
                            <!-- Headers -->
                            <div class="schedule-header"><?php _e('time'); ?></div>
                            <div class="schedule-header"><?php _e('monday'); ?></div>
                            <div class="schedule-header"><?php _e('tuesday'); ?></div>
                            <div class="schedule-header"><?php _e('wednesday'); ?></div>
                            <div class="schedule-header"><?php _e('thursday'); ?></div>
                            <div class="schedule-header"><?php _e('friday'); ?></div>
                            
                            <!-- Time slots and cells -->
                            <?php foreach ($bloques as $bloque): ?>
                                <div class="schedule-time">
                                    <?php echo date('H:i', strtotime($bloque['hora_inicio'])) . ' - ' . date('H:i', strtotime($bloque['hora_fin'])); ?>
                                </div>
                                
                                <?php foreach ($dias as $dia): ?>
                                    <div class="schedule-cell" 
                                         data-bloque="<?php echo $bloque['id_bloque']; ?>" 
                                         data-dia="<?php echo $dia; ?>"
                                         onclick="openScheduleModal(<?php echo $bloque['id_bloque']; ?>, '<?php echo $dia; ?>')">
                                        
                                        <?php 
                                        $assignment = $scheduleGrid[$dia][$bloque['id_bloque']] ?? null;
                                        if ($assignment): ?>
                                            <div class="schedule-assignment">
                                                <div class="group-name"><?php echo htmlspecialchars($assignment['grupo_nombre']); ?></div>
                                                <div class="subject-name"><?php echo htmlspecialchars($assignment['materia_nombre']); ?></div>
                                                <div class="teacher-name"><?php echo htmlspecialchars($assignment['docente_nombre'] . ' ' . $assignment['docente_apellido']); ?></div>
                                            </div>
                                            
                                            <div class="schedule-actions">
                                                <button class="edit-btn" onclick="event.stopPropagation(); editSchedule(<?php echo $assignment['id_horario']; ?>)">
                                                    ✏️
                                                </button>
                                                <button class="delete-btn" onclick="event.stopPropagation(); deleteSchedule(<?php echo $assignment['id_horario']; ?>)">
                                                    🗑️
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-gray-400 text-xs text-center mt-4">
                                                <?php _e('available'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal hidden">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 id="scheduleModalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_schedule'); ?></h3>
                <button onclick="closeScheduleModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="text-xl">×</span>
                </button>
            </div>
            
            <!-- Conflict Panel -->
            <div id="conflictPanel" class="conflict-panel hidden">
                <h4 class="text-red-800 font-medium mb-3"><?php _e('conflicts_detected'); ?></h4>
                <div id="conflictList"></div>
            </div>
            
            <!-- Suggestion Panel -->
            <div id="suggestionPanel" class="suggestion-panel hidden">
                <h4 class="text-blue-800 font-medium mb-3"><?php _e('suggestions'); ?></h4>
                <div id="suggestionList"></div>
            </div>
            
            <form id="scheduleForm" onsubmit="handleScheduleFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="schedule_id" name="id">
                <input type="hidden" id="schedule_id_bloque" name="id_bloque">
                <input type="hidden" id="schedule_dia" name="dia">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="id_grupo" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('group'); ?></label>
                        <select id="id_grupo" name="id_grupo" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                            <option value=""><?php _e('select_group'); ?></option>
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?php echo $grupo['id_grupo']; ?>">
                                    <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['nivel']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="id_grupoError" class="text-red-500 text-xs mt-1"></div>
                    </div>
                    
                    <div>
                        <label for="id_materia" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('subject'); ?></label>
                        <select id="id_materia" name="id_materia" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                            <option value=""><?php _e('select_subject'); ?></option>
                            <?php foreach ($materias as $materia): ?>
                                <option value="<?php echo $materia['id_materia']; ?>">
                                    <?php echo htmlspecialchars($materia['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="id_materiaError" class="text-red-500 text-xs mt-1"></div>
                    </div>
                </div>
                
                <div>
                    <label for="id_docente" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('teacher'); ?></label>
                    <select id="id_docente" name="id_docente" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm">
                        <option value=""><?php _e('select_teacher'); ?></option>
                        <?php foreach ($docentes as $docente): ?>
                            <option value="<?php echo $docente['id_docente']; ?>">
                                <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="id_docenteError" class="text-red-500 text-xs mt-1"></div>
                </div>
                
                <div id="scheduleInfo" class="bg-gray-50 p-3 rounded text-sm text-gray-600"></div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeScheduleModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('cancel'); ?>
                    </button>
                    <button type="submit" id="saveButton"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-darkblue hover:bg-navy focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('save'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="/js/toast.js"></script>
    <script>
        let isEditMode = false;
        let currentConflicts = [];
        let currentSuggestions = [];
        
        // View mode handling
        document.getElementById('viewMode').addEventListener('change', function() {
            const mode = this.value;
            const groupFilter = document.getElementById('groupFilter');
            const teacherFilter = document.getElementById('teacherFilter');
            
            groupFilter.classList.add('hidden');
            teacherFilter.classList.add('hidden');
            
            if (mode === 'group') {
                groupFilter.classList.remove('hidden');
            } else if (mode === 'teacher') {
                teacherFilter.classList.remove('hidden');
            }
            
            loadScheduleGrid();
        });
        
        // Initialize view mode on page load
        document.addEventListener('DOMContentLoaded', function() {
            const mode = document.getElementById('viewMode').value;
            const groupFilter = document.getElementById('groupFilter');
            const teacherFilter = document.getElementById('teacherFilter');
            
            if (mode === 'group') {
                groupFilter.classList.remove('hidden');
            } else if (mode === 'teacher') {
                teacherFilter.classList.remove('hidden');
            }
            
            // Load initial data
            loadScheduleGrid();
        });
        
        document.getElementById('groupFilter').addEventListener('change', loadScheduleGrid);
        document.getElementById('teacherFilter').addEventListener('change', loadScheduleGrid);
        
        // Modal functions
        function openScheduleModal(idBloque = null, dia = null) {
            isEditMode = false;
            document.getElementById('scheduleModalTitle').textContent = '<?php _e('add_schedule'); ?>';
            document.getElementById('scheduleForm').reset();
            document.getElementById('conflictPanel').classList.add('hidden');
            document.getElementById('suggestionPanel').classList.add('hidden');
            
            if (idBloque && dia) {
                document.getElementById('schedule_id_bloque').value = idBloque;
                document.getElementById('schedule_dia').value = dia;
                
                // Find block time info
                const bloques = <?php echo json_encode($bloques); ?>;
                const bloque = bloques.find(b => b.id_bloque == idBloque);
                
                if (bloque) {
                    document.getElementById('scheduleInfo').innerHTML = 
                        `<strong><?php _e('schedule_time'); ?>:</strong> ${dia} ${bloque.hora_inicio.substring(0,5)} - ${bloque.hora_fin.substring(0,5)}`;
                }
            }
            
            document.getElementById('scheduleModal').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('id_grupo').focus();
            }, 100);
        }
        
        function closeScheduleModal() {
            document.getElementById('scheduleModal').classList.add('hidden');
            clearErrors();
            currentConflicts = [];
            currentSuggestions = [];
        }
        
        // Edit schedule
        function editSchedule(id) {
            isEditMode = true;
            document.getElementById('scheduleModalTitle').textContent = '<?php _e('edit_schedule'); ?>';
            
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('/src/controllers/gestion_horarios_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('schedule_id').value = data.data.id_horario;
                    document.getElementById('schedule_id_bloque').value = data.data.id_bloque;
                    document.getElementById('schedule_dia').value = data.data.dia;
                    document.getElementById('id_grupo').value = data.data.id_grupo;
                    document.getElementById('id_materia').value = data.data.id_materia;
                    document.getElementById('id_docente').value = data.data.id_docente;
                    
                    document.getElementById('scheduleInfo').innerHTML = 
                        `<strong><?php _e('schedule_time'); ?>:</strong> ${data.data.dia} ${data.data.hora_inicio.substring(0,5)} - ${data.data.hora_fin.substring(0,5)}`;
                    
                    document.getElementById('scheduleModal').classList.remove('hidden');
                    
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
        
        // Delete schedule
        function deleteSchedule(id) {
            const confirmMessage = `¿Está seguro de que desea eliminar esta asignación de horario?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('/src/controllers/gestion_horarios_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast('Horario eliminado exitosamente', 'success');
                        loadScheduleGrid();
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
        function handleScheduleFormSubmit(e) {
            e.preventDefault();
            
            clearErrors();
            
            const formData = new FormData(e.target);
            formData.append('action', isEditMode ? 'update_schedule' : 'create_schedule');
            
            // Check for conflicts first
            checkConflicts(formData).then(conflictData => {
                if (conflictData.anep_conflicts || (conflictData.regular_conflicts && conflictData.regular_conflicts.length > 0)) {
                    showConflicts(conflictData);
                    return;
                }
                
                // Proceed with creation/update
                submitSchedule(formData);
            });
        }
        
        // Check conflicts
        function checkConflicts(formData) {
            const checkData = new FormData();
            checkData.append('action', 'check_conflicts');
            checkData.append('id_grupo', formData.get('id_grupo'));
            checkData.append('id_docente', formData.get('id_docente'));
            checkData.append('id_materia', formData.get('id_materia'));
            checkData.append('id_bloque', formData.get('id_bloque'));
            checkData.append('dia', formData.get('dia'));
            
            if (isEditMode) {
                checkData.append('exclude_id', formData.get('id'));
            }
            
            return fetch('/src/controllers/gestion_horarios_handler.php', {
                method: 'POST',
                body: checkData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    return data.data;
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error checking conflicts:', error);
                return [];
            });
        }
        
        // Show conflicts
        function showConflicts(conflicts) {
            currentConflicts = conflicts;
            
            const conflictPanel = document.getElementById('conflictPanel');
            const conflictList = document.getElementById('conflictList');
            
            conflictList.innerHTML = '';
            
            // Handle ANEP conflicts specially
            if (conflicts.anep_conflicts && conflicts.anep_conflicts.conflicts) {
                const anepDiv = document.createElement('div');
                anepDiv.className = 'anep-conflicts mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg';
                
                let conflictsHtml = `
                    <h4 class="font-semibold text-yellow-800 mb-3 flex items-center">
                        ⚠️ Conflictos ANEP - ${conflicts.anep_conflicts.materia}
                    </h4>
                    <div class="text-sm text-yellow-700 mb-3">
                        <strong>Pauta:</strong> ${conflicts.anep_conflicts.pauta}<br>
                        <strong>Días asignados:</strong> ${conflicts.anep_conflicts.resumen.dias_asignados} 
                        (mín: ${conflicts.anep_conflicts.resumen.dias_minimos}, máx: ${conflicts.anep_conflicts.resumen.dias_maximos})<br>
                        <strong>Horas asignadas:</strong> ${conflicts.anep_conflicts.resumen.horas_asignadas} / ${conflicts.anep_conflicts.resumen.horas_semanales}
                    </div>
                    <div class="space-y-2">
                `;
                
                conflicts.anep_conflicts.conflicts.forEach(conflict => {
                    const severityClass = conflict.severity === 'error' ? 'text-red-600' : 
                                        conflict.severity === 'warning' ? 'text-yellow-600' : 'text-blue-600';
                    const icon = conflict.severity === 'error' ? '❌' : 
                               conflict.severity === 'warning' ? '⚠️' : 'ℹ️';
                    
                    conflictsHtml += `
                        <div class="p-2 bg-white rounded border-l-4 ${conflict.severity === 'error' ? 'border-red-400' : 
                                                                          conflict.severity === 'warning' ? 'border-yellow-400' : 'border-blue-400'}">
                            <div class="font-medium ${severityClass}">
                                ${icon} ${conflict.message}
                            </div>
                            ${conflict.suggestion ? `<div class="text-xs text-gray-600 mt-1">💡 ${conflict.suggestion}</div>` : ''}
                        </div>
                    `;
                });
                
                conflictsHtml += '</div>';
                anepDiv.innerHTML = conflictsHtml;
                conflictList.appendChild(anepDiv);
            }
            
            // Handle regular conflicts
            if (conflicts.regular_conflicts) {
                conflicts.regular_conflicts.forEach(conflict => {
                    const conflictItem = document.createElement('div');
                    conflictItem.className = 'conflict-item';
                    
                    const icon = document.createElement('div');
                    icon.className = `conflict-icon ${conflict.severity}`;
                    
                    const message = document.createElement('div');
                    message.className = 'conflict-message';
                    message.textContent = conflict.message;
                    
                    conflictItem.appendChild(icon);
                    conflictItem.appendChild(message);
                    conflictList.appendChild(conflictItem);
                });
            }
            
            conflictPanel.classList.remove('hidden');
            
            // Show suggestions if available
            if (conflicts.some(c => c.suggestions)) {
                showSuggestions(conflicts.flatMap(c => c.suggestions || []));
            }
        }
        
        // Show suggestions
        function showSuggestions(suggestions) {
            currentSuggestions = suggestions;
            
            const suggestionPanel = document.getElementById('suggestionPanel');
            const suggestionList = document.getElementById('suggestionList');
            
            suggestionList.innerHTML = '';
            
            suggestions.forEach(suggestion => {
                const suggestionItem = document.createElement('div');
                suggestionItem.className = 'suggestion-item';
                suggestionItem.onclick = () => executeSuggestion(suggestion);
                
                const icon = document.createElement('div');
                icon.className = 'suggestion-icon';
                icon.innerHTML = '💡';
                
                const message = document.createElement('div');
                message.textContent = suggestion.message;
                
                suggestionItem.appendChild(icon);
                suggestionItem.appendChild(message);
                suggestionList.appendChild(suggestionItem);
            });
            
            suggestionPanel.classList.remove('hidden');
        }
        
        // Execute suggestion
        function executeSuggestion(suggestion) {
            switch (suggestion.action) {
                case 'find_alternative_times':
                    findAlternativeTimes();
                    break;
                case 'find_alternative_teachers':
                    findAlternativeTeachers();
                    break;
                case 'update_teacher_availability':
                    updateTeacherAvailability();
                    break;
            }
        }
        
        // Submit schedule
        function submitSchedule(formData) {
            const saveButton = document.getElementById('saveButton');
            saveButton.disabled = true;
            saveButton.textContent = '<?php _e('saving'); ?>...';
            
            fetch('/src/controllers/gestion_horarios_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeScheduleModal();
                    loadScheduleGrid();
                } else {
                    if (data.data && typeof data.data === 'object') {
                        // Show validation errors
                        Object.keys(data.data).forEach(field => {
                            const errorElement = document.getElementById(field + 'Error');
                            if (errorElement) {
                                errorElement.textContent = data.data[field];
                            }
                        });
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error procesando solicitud', 'error');
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.textContent = '<?php _e('save'); ?>';
            });
        }
        
        // Load schedule grid
        function loadScheduleGrid() {
            const viewMode = document.getElementById('viewMode').value;
            const groupId = document.getElementById('groupFilter').value;
            const teacherId = document.getElementById('teacherFilter').value;
            
            let url = '/src/controllers/gestion_horarios_handler.php?action=get_schedule_grid';
            
            if (viewMode === 'group' && groupId) {
                url += '&grupo_id=' + groupId;
            } else if (viewMode === 'teacher' && teacherId) {
                url += '&docente_id=' + teacherId;
            }
            
            fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos del servidor:', data);
                if (data.success) {
                    console.log('Grid data:', data.data.grid);
                    console.log('Grid LUNES:', data.data.grid.LUNES);
                    console.log('Grid MARTES:', data.data.grid.MARTES);
                    updateScheduleGrid(data.data.grid);
                    console.log('Horarios cargados:', data.data.horarios_count, 'Bloques:', data.data.bloques_count);
                } else {
                    showToast('Error cargando horarios: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error cargando horarios', 'error');
            });
        }
        
        // Update schedule grid
        function updateScheduleGrid(gridData) {
            console.log('Updating schedule grid with data:', gridData);
            
            // Clear existing assignments
            document.querySelectorAll('.schedule-cell').forEach(cell => {
                cell.innerHTML = '<div class="text-gray-400 text-xs text-center mt-4"><?php _e('available'); ?></div>';
                cell.classList.remove('occupied', 'conflict', 'warning');
            });
            
            // Update with new data
            if (gridData && typeof gridData === 'object') {
                Object.keys(gridData).forEach(dia => {
                    console.log('Processing day:', dia);
                    if (gridData[dia] && typeof gridData[dia] === 'object') {
                        Object.keys(gridData[dia]).forEach(bloqueId => {
                            const assignment = gridData[dia][bloqueId];
                            console.log(`Looking for cell: data-dia="${dia}" data-bloque="${bloqueId}"`);
                            const cell = document.querySelector(`[data-dia="${dia}"][data-bloque="${bloqueId}"]`);
                            
                            if (cell && assignment) {
                                console.log('Found cell and assignment:', assignment);
                                cell.innerHTML = `
                                    <div class="schedule-assignment">
                                        <div class="group-name">${assignment.grupo_nombre || ''}</div>
                                        <div class="subject-name">${assignment.materia_nombre || ''}</div>
                                        <div class="teacher-name">${assignment.docente_nombre || ''} ${assignment.docente_apellido || ''}</div>
                                    </div>
                                    <div class="schedule-actions">
                                        <button class="edit-btn" onclick="event.stopPropagation(); editSchedule(${assignment.id_horario})">
                                            ✏️
                                        </button>
                                        <button class="delete-btn" onclick="event.stopPropagation(); deleteSchedule(${assignment.id_horario})">
                                            🗑️
                                        </button>
                                    </div>
                                `;
                                cell.classList.add('occupied');
                            } else if (cell) {
                                console.log('Found cell but no assignment for:', dia, bloqueId);
                            } else {
                                console.log('Cell not found for:', dia, bloqueId);
                            }
                        });
                    }
                });
            }
        }
        
        // Clear validation errors
        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
        }
        
        // Helper functions for suggestions
        function findAlternativeTimes() {
            showToast('Buscando horarios alternativos...', 'info');
            // Implementation for finding alternative times
        }
        
        function findAlternativeTeachers() {
            showToast('Buscando docentes alternativos...', 'info');
            // Implementation for finding alternative teachers
        }
        
        function updateTeacherAvailability() {
            showToast('Abriendo gestión de disponibilidad...', 'info');
            // Implementation for updating teacher availability
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadScheduleGrid();
        });
    </script>
</body>
</html>
