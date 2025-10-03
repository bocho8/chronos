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
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            min-height: 100px;
            padding: 12px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .schedule-cell:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%) !important;
            border-color: #3b82f6 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15) !important;
        }
        
        .schedule-cell.occupied {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
        }
        
        .schedule-cell.occupied:hover {
            background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25) !important;
        }
        
        .schedule-cell.available:hover {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%) !important;
            border-color: #0ea5e9 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15) !important;
        }
        
        .schedule-cell.available:hover::after {
            content: '➕';
            position: absolute;
            top: 8px;
            right: 8px;
            color: #0ea5e9;
            font-size: 16px;
            z-index: 5;
        }
        
        .schedule-cell.available:hover .cell-content {
            color: #0ea5e9;
            font-weight: 600;
        }
        
        .schedule-cell.conflict {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-color: #ef4444;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
            animation: pulse-red 2s infinite;
        }
        
        .schedule-cell.warning {
            background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
            border-color: #eab308;
            box-shadow: 0 2px 8px rgba(234, 179, 8, 0.2);
            animation: pulse-yellow 2s infinite;
        }
        
        .schedule-cell.anep-warning {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-color: #0ea5e9;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
        }
        
        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        @keyframes pulse-yellow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.9; }
        }
        
        .schedule-assignment {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .schedule-assignment::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #60a5fa, #3b82f6, #1d4ed8);
        }
        
        .schedule-assignment .group-name {
            font-weight: 700;
            margin-bottom: 4px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .schedule-assignment .subject-name {
            opacity: 0.95;
            font-size: 13px;
            font-weight: 600;
            margin: 4px 0;
        }
        
        .schedule-assignment .teacher-name {
            opacity: 0.85;
            font-size: 10px;
            margin-top: 4px;
            font-style: italic;
        }
        
        .schedule-actions {
            position: absolute;
            top: 6px;
            right: 6px;
            opacity: 0;
            transition: all 0.3s ease;
            display: flex;
            gap: 4px;
            z-index: 20;
        }
        
        .schedule-cell:hover .schedule-actions {
            opacity: 1;
        }
        
        .schedule-assignment:hover .schedule-actions {
            opacity: 1;
        }
        
        /* Hide action buttons in selection mode to avoid conflicts */
        .selection-mode .schedule-actions {
            opacity: 0 !important;
            pointer-events: none;
        }
        
        .schedule-actions button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 4px;
            padding: 4px 6px;
            margin-left: 0;
            font-size: 11px;
            cursor: pointer;
            color: white;
            transition: all 0.2s;
            backdrop-filter: blur(4px);
        }
        
        .schedule-actions button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .edit-btn:hover {
            background: rgba(34, 197, 94, 0.8);
        }
        
        .delete-btn:hover {
            background: rgba(239, 68, 68, 0.8);
        }
        
        /* Tooltips */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px 8px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            font-size: 11px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        /* Indicadores de estado */
        .schedule-cell.available::after {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 8px;
            color: #10b981;
            font-weight: bold;
            font-size: 16px;
            z-index: 5;
        }
        
        /* Style for available cells */
        .schedule-cell.available {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-color: #e2e8f0;
            position: relative;
        }
        
        .cell-content {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }
        
        .schedule-cell.occupied::after {
            content: '📚';
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 14px;
            z-index: 5;
        }
        
        .schedule-cell.conflict::after {
            content: '⚠️';
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 16px;
            z-index: 5;
        }
        
        .schedule-cell.warning::after {
            content: '⚡';
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 14px;
            z-index: 5;
        }
        
        .schedule-cell.anep-warning::after {
            content: '📋';
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 14px;
            z-index: 5;
        }
        
        /* Hide status indicators in selection mode to avoid conflicts */
        .selection-mode .schedule-cell.occupied::after {
            display: none;
        }
        
        /* Bulk Selection Styles */
        .selection-mode .schedule-cell {
            cursor: pointer;
            position: relative;
        }
        
        .selection-mode .schedule-cell.occupied::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 8px;
            width: 18px;
            height: 18px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            background: white;
            z-index: 15;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Ensure available cells don't show checkbox in selection mode */
        .selection-mode .schedule-cell.available::before {
            display: none;
        }
        
        .selection-mode .schedule-cell.available .cell-content {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }
        
        .selection-mode .schedule-cell.occupied.selected::before {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        
        .selection-mode .schedule-cell.occupied.selected::after {
            content: '✓';
            position: absolute;
            top: 6px;
            left: 8px;
            color: white;
            font-size: 14px;
            font-weight: bold;
            z-index: 16;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .selection-mode .schedule-cell.occupied:hover::before {
            border-color: #3b82f6;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
        }
        
        .selection-mode .schedule-cell.occupied.selected {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .selection-mode .schedule-cell.occupied.selected:hover {
            background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25), 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .selection-mode .schedule-cell.available {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .selection-mode .schedule-cell.available:hover {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-color: #0ea5e9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15);
            opacity: 0.8;
        }
        
        /* Override hover styles in selection mode for occupied cells */
        .selection-mode .schedule-cell.occupied:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            transform: none;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
        }
        
        .selection-mode .schedule-cell.occupied.selected:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            transform: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .bulk-actions-panel {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .selected-count {
            background: #3b82f6;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
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
                            
                            <div class="flex gap-3 flex-wrap">
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
                                
                                <!-- Bulk Operations Controls -->
                                <div class="flex gap-2 items-center">
                                    <button id="toggleSelectionMode" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm transition-colors">
                                        <span class="mr-1">☑️</span> Selección Múltiple
                                    </button>
                                    
                                    <div id="bulkActions" class="hidden flex gap-2">
                                        <button id="selectAll" class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs">
                                            Seleccionar Todo
                                        </button>
                                        <button id="deselectAll" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs">
                                            Deseleccionar
                                        </button>
                                        <button id="bulkDelete" class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs">
                                            🗑️ Eliminar
                                        </button>
                                        <button id="bulkCopy" class="px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 rounded text-xs">
                                            📋 Copiar
                                        </button>
                                        <button id="bulkMove" class="px-3 py-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded text-xs">
                                            📦 Mover
                                        </button>
                                    </div>
                                </div>
                                
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
            
                        <!-- Teacher Workload Panel -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                            <div id="teacherWorkload">
                                <h4 class="font-semibold text-gray-700 mb-3">Carga Horaria por Docente</h4>
                                <p class="text-gray-500 text-sm">Selecciona un grupo o docente para ver la carga horaria</p>
                            </div>
                        </div>
                        
                        <!-- Bulk Actions Panel -->
                        <div id="bulkActionsPanel" class="bulk-actions-panel hidden">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700">Acciones en Lote:</span>
                                    <span id="selectedCount" class="selected-count">0 seleccionados</span>
                                </div>
                                <div class="flex gap-2">
                                    <button id="bulkEdit" class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs">
                                        ✏️ Editar
                                    </button>
                                    <button id="bulkDeleteConfirm" class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs">
                                        🗑️ Eliminar
                                    </button>
                                    <button id="bulkCopyConfirm" class="px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 rounded text-xs">
                                        📋 Copiar
                                    </button>
                                    <button id="bulkMoveConfirm" class="px-3 py-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded text-xs">
                                        📦 Mover
                                    </button>
                                    <button id="exitSelectionMode" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs">
                                        ✕ Salir
                                    </button>
                                </div>
                            </div>
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
        let isSelectionMode = false;
        let selectedSchedules = new Set();
        
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
            
            // Bulk operations event listeners
            const toggleBtn = document.getElementById('toggleSelectionMode');
            const selectAllBtn = document.getElementById('selectAll');
            const deselectAllBtn = document.getElementById('deselectAll');
            const bulkDeleteBtn = document.getElementById('bulkDelete');
            const bulkCopyBtn = document.getElementById('bulkCopy');
            const bulkMoveBtn = document.getElementById('bulkMove');
            const bulkEditBtn = document.getElementById('bulkEdit');
            const bulkDeleteConfirmBtn = document.getElementById('bulkDeleteConfirm');
            const bulkCopyConfirmBtn = document.getElementById('bulkCopyConfirm');
            const bulkMoveConfirmBtn = document.getElementById('bulkMoveConfirm');
            const exitSelectionBtn = document.getElementById('exitSelectionMode');
            
            // Remove existing listeners to avoid duplicates
            toggleBtn?.removeEventListener('click', toggleSelectionMode);
            selectAllBtn?.removeEventListener('click', selectAllSchedules);
            deselectAllBtn?.removeEventListener('click', deselectAllSchedules);
            bulkDeleteBtn?.removeEventListener('click', bulkDelete);
            bulkCopyBtn?.removeEventListener('click', bulkCopy);
            bulkMoveBtn?.removeEventListener('click', bulkMove);
            bulkEditBtn?.removeEventListener('click', bulkEdit);
            bulkDeleteConfirmBtn?.removeEventListener('click', bulkDelete);
            bulkCopyConfirmBtn?.removeEventListener('click', bulkCopy);
            bulkMoveConfirmBtn?.removeEventListener('click', bulkMove);
            exitSelectionBtn?.removeEventListener('click', exitSelectionMode);
            
            // Add event listeners
            toggleBtn?.addEventListener('click', toggleSelectionMode);
            selectAllBtn?.addEventListener('click', selectAllSchedules);
            deselectAllBtn?.addEventListener('click', deselectAllSchedules);
            bulkDeleteBtn?.addEventListener('click', bulkDelete);
            bulkCopyBtn?.addEventListener('click', bulkCopy);
            bulkMoveBtn?.addEventListener('click', bulkMove);
            bulkEditBtn?.addEventListener('click', bulkEdit);
            bulkDeleteConfirmBtn?.addEventListener('click', bulkDelete);
            bulkCopyConfirmBtn?.addEventListener('click', bulkCopy);
            bulkMoveConfirmBtn?.addEventListener('click', bulkMove);
            exitSelectionBtn?.addEventListener('click', exitSelectionMode);
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
                anepDiv.className = 'anep-conflicts mb-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg shadow-sm';
                
                let conflictsHtml = `
                    <h4 class="font-semibold text-blue-800 mb-3 flex items-center">
                        <span class="text-xl mr-2">📋</span>
                        Conflictos ANEP - ${conflicts.anep_conflicts.materia}
                    </h4>
                    <div class="bg-white p-3 rounded-lg border border-blue-100 mb-3">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <strong class="text-blue-700">Pauta:</strong> ${conflicts.anep_conflicts.pauta}
                            </div>
                            <div>
                                <strong class="text-blue-700">Días asignados:</strong> 
                                <span class="px-2 py-1 rounded-full text-xs ${conflicts.anep_conflicts.resumen.dias_asignados < conflicts.anep_conflicts.resumen.dias_minimos ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">
                                    ${conflicts.anep_conflicts.resumen.dias_asignados} / ${conflicts.anep_conflicts.resumen.dias_minimos}-${conflicts.anep_conflicts.resumen.dias_maximos}
                                </span>
                            </div>
                            <div>
                                <strong class="text-blue-700">Horas asignadas:</strong> 
                                <span class="px-2 py-1 rounded-full text-xs ${conflicts.anep_conflicts.resumen.horas_asignadas < conflicts.anep_conflicts.resumen.horas_semanales ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'}">
                                    ${conflicts.anep_conflicts.resumen.horas_asignadas} / ${conflicts.anep_conflicts.resumen.horas_semanales}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                `;
                
                conflicts.anep_conflicts.conflicts.forEach(conflict => {
                    const severityClass = conflict.severity === 'error' ? 'text-red-600' : 
                                        conflict.severity === 'warning' ? 'text-yellow-600' : 'text-blue-600';
                    const icon = conflict.severity === 'error' ? '❌' : 
                               conflict.severity === 'warning' ? '⚠️' : 'ℹ️';
                    const borderClass = conflict.severity === 'error' ? 'border-red-400' : 
                                      conflict.severity === 'warning' ? 'border-yellow-400' : 'border-blue-400';
                    
                    conflictsHtml += `
                        <div class="p-3 bg-white rounded-lg border-l-4 ${borderClass} shadow-sm">
                            <div class="font-medium ${severityClass} flex items-start">
                                <span class="text-lg mr-2">${icon}</span>
                                <span>${conflict.message}</span>
                            </div>
                            ${conflict.suggestion ? `
                                <div class="text-xs text-gray-600 mt-2 p-2 bg-gray-50 rounded border-l-2 border-gray-300">
                                    💡 <strong>Sugerencia:</strong> ${conflict.suggestion}
                                </div>
                            ` : ''}
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
                    updateScheduleGrid(data.data.grid);
                    updateTeacherWorkload(data.data.grid);
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
                cell.innerHTML = '<div class="cell-content">Disponible</div><span class="tooltiptext">Click para agregar horario</span>';
                cell.classList.remove('occupied', 'conflict', 'warning', 'anep-warning', 'available', 'selected', 'tooltip');
                cell.removeAttribute('data-tooltip');
                
                // Remove all event listeners
                cell.removeEventListener('click', handleCellClick);
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
                                const timeRange = `${assignment.hora_inicio} - ${assignment.hora_fin}`;
                                const tooltipText = `${assignment.materia_nombre} - ${assignment.docente_nombre} ${assignment.docente_apellido}\\n${assignment.grupo_nombre} - ${timeRange}`;
                                
                                cell.innerHTML = `
                                    <div class="schedule-assignment">
                                        <div class="group-name">${assignment.grupo_nombre || ''}</div>
                                        <div class="subject-name">${assignment.materia_nombre || ''}</div>
                                        <div class="teacher-name">${assignment.docente_nombre || ''} ${assignment.docente_apellido || ''}</div>
                                    </div>
                                    <div class="schedule-actions">
                                        <button class="edit-btn" onclick="event.stopPropagation(); editSchedule(${assignment.id_horario})" title="Editar horario">
                                            ✏️
                                        </button>
                                        <button class="delete-btn" onclick="event.stopPropagation(); deleteSchedule(${assignment.id_horario})" title="Eliminar horario">
                                            🗑️
                                        </button>
                                    </div>
                                `;
                                cell.classList.add('occupied');
                                cell.setAttribute('data-tooltip', tooltipText);
                                
                                // Remove existing event listener to avoid duplicates
                                cell.removeEventListener('click', handleCellClick);
                                cell.addEventListener('click', handleCellClick);
                            } else if (cell) {
                                console.log('Found cell but no assignment for:', dia, bloqueId);
                                cell.classList.add('available', 'tooltip');
                                // Ensure the cell has the correct content
                                if (!cell.querySelector('.cell-content')) {
                                    cell.innerHTML = '<div class="cell-content">Disponible</div><span class="tooltiptext">Click para agregar horario</span>';
                                }
                                // Add click listener for available cells
                                cell.addEventListener('click', function() {
                                    if (!isSelectionMode) {
                                        openScheduleModal();
                                        // Set the day and block for the new schedule
                                        document.getElementById('schedule_dia').value = dia;
                                        document.getElementById('schedule_id_bloque').value = bloqueId;
                                    }
                                });
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
        
        // Calculate and display teacher workload
        function updateTeacherWorkload(gridData) {
            const workload = {};
            
            if (gridData && typeof gridData === 'object') {
                Object.keys(gridData).forEach(dia => {
                    if (gridData[dia] && typeof gridData[dia] === 'object') {
                        Object.keys(gridData[dia]).forEach(bloqueId => {
                            const assignment = gridData[dia][bloqueId];
                            if (assignment && assignment.docente_nombre) {
                                const teacherKey = `${assignment.docente_nombre} ${assignment.docente_apellido}`;
                                if (!workload[teacherKey]) {
                                    workload[teacherKey] = {
                                        name: teacherKey,
                                        hours: 0,
                                        subjects: new Set(),
                                        groups: new Set()
                                    };
                                }
                                workload[teacherKey].hours += 1; // Assuming 1 hour per block
                                workload[teacherKey].subjects.add(assignment.materia_nombre);
                                workload[teacherKey].groups.add(assignment.grupo_nombre);
                            }
                        });
                    }
                });
            }
            
            // Update workload display
            const workloadContainer = document.getElementById('teacherWorkload');
            if (workloadContainer) {
                let workloadHtml = '<h4 class="font-semibold text-gray-700 mb-3">Carga Horaria por Docente</h4>';
                Object.values(workload).forEach(teacher => {
                    const subjectsList = Array.from(teacher.subjects).join(', ');
                    const groupsList = Array.from(teacher.groups).join(', ');
                    workloadHtml += `
                        <div class="bg-white p-3 rounded-lg border border-gray-200 mb-2">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-800">${teacher.name}</span>
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">
                                    ${teacher.hours} horas
                                </span>
                            </div>
                            <div class="text-xs text-gray-600 mt-1">
                                <div><strong>Materias:</strong> ${subjectsList}</div>
                                <div><strong>Grupos:</strong> ${groupsList}</div>
                            </div>
                        </div>
                    `;
                });
                workloadContainer.innerHTML = workloadHtml;
            }
        }
        
        // Bulk Operations Functions
        function toggleSelectionMode() {
            isSelectionMode = !isSelectionMode;
            const grid = document.getElementById('scheduleGrid');
            const toggleBtn = document.getElementById('toggleSelectionMode');
            const bulkActions = document.getElementById('bulkActions');
            const bulkPanel = document.getElementById('bulkActionsPanel');
            
            console.log('Toggle selection mode:', isSelectionMode);
            
            if (isSelectionMode) {
                grid.classList.add('selection-mode');
                toggleBtn.textContent = '☑️ Salir de Selección';
                toggleBtn.classList.add('bg-blue-100', 'text-blue-700');
                bulkActions.classList.remove('hidden');
                bulkPanel.classList.remove('hidden');
                
                // Clear any existing selections
                selectedSchedules.clear();
                updateSelectedCount();
                
                console.log('Selection mode activated');
            } else {
                exitSelectionMode();
            }
        }
        
        function exitSelectionMode() {
            isSelectionMode = false;
            selectedSchedules.clear();
            
            const grid = document.getElementById('scheduleGrid');
            const toggleBtn = document.getElementById('toggleSelectionMode');
            const bulkActions = document.getElementById('bulkActions');
            const bulkPanel = document.getElementById('bulkActionsPanel');
            
            grid.classList.remove('selection-mode');
            toggleBtn.textContent = '☑️ Selección Múltiple';
            toggleBtn.classList.remove('bg-blue-100', 'text-blue-700');
            bulkActions.classList.add('hidden');
            bulkPanel.classList.add('hidden');
            
            // Remove all selections
            document.querySelectorAll('.schedule-cell.selected').forEach(cell => {
                cell.classList.remove('selected');
            });
            
            updateSelectedCount();
        }
        
        function selectAllSchedules() {
            document.querySelectorAll('.schedule-cell.occupied').forEach(cell => {
                const editButton = cell.querySelector('.edit-btn');
                if (!editButton) return;
                
                const onclickAttr = editButton.getAttribute('onclick');
                const match = onclickAttr ? onclickAttr.match(/editSchedule\((\d+)\)/) : null;
                const scheduleId = match ? match[1] : null;
                
                if (scheduleId) {
                    cell.classList.add('selected');
                    selectedSchedules.add(scheduleId);
                }
            });
            updateSelectedCount();
        }
        
        function deselectAllSchedules() {
            selectedSchedules.clear();
            document.querySelectorAll('.schedule-cell.selected').forEach(cell => {
                cell.classList.remove('selected');
            });
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const count = selectedSchedules.size;
            document.getElementById('selectedCount').textContent = `${count} seleccionado${count !== 1 ? 's' : ''}`;
        }
        
        function handleCellClick(event) {
            if (!isSelectionMode) {
                console.log('Not in selection mode');
                return;
            }
            
            const cell = event.currentTarget;
            console.log('Cell clicked:', cell);
            
            // Only allow selection of occupied cells
            if (!cell.classList.contains('occupied')) {
                console.log('Cell not occupied, ignoring');
                return;
            }
            
            // Find the schedule ID from the edit button
            const editButton = cell.querySelector('.edit-btn');
            if (!editButton) {
                console.log('No edit button found');
                return;
            }
            
            const onclickAttr = editButton.getAttribute('onclick');
            const match = onclickAttr ? onclickAttr.match(/editSchedule\((\d+)\)/) : null;
            const scheduleId = match ? match[1] : null;
            
            console.log('Schedule ID found:', scheduleId);
            
            if (!scheduleId) return;
            
            if (cell.classList.contains('selected')) {
                cell.classList.remove('selected');
                selectedSchedules.delete(scheduleId);
                console.log('Deselected schedule:', scheduleId);
            } else {
                cell.classList.add('selected');
                selectedSchedules.add(scheduleId);
                console.log('Selected schedule:', scheduleId);
            }
            
            updateSelectedCount();
        }
        
        function bulkDelete() {
            if (selectedSchedules.size === 0) {
                showToast('No hay horarios seleccionados', 'warning');
                return;
            }
            
            if (confirm(`¿Estás seguro de que quieres eliminar ${selectedSchedules.size} horario(s)?`)) {
                const scheduleIds = Array.from(selectedSchedules);
                performBulkOperation('delete', scheduleIds);
            }
        }
        
        function bulkCopy() {
            if (selectedSchedules.size === 0) {
                showToast('No hay horarios seleccionados', 'warning');
                return;
            }
            
            showToast('Funcionalidad de copia en desarrollo', 'info');
        }
        
        function bulkMove() {
            if (selectedSchedules.size === 0) {
                showToast('No hay horarios seleccionados', 'warning');
                return;
            }
            
            showToast('Funcionalidad de movimiento en desarrollo', 'info');
        }
        
        function bulkEdit() {
            if (selectedSchedules.size === 0) {
                showToast('No hay horarios seleccionados', 'warning');
                return;
            }
            
            showToast('Funcionalidad de edición en lote en desarrollo', 'info');
        }
        
        async function performBulkOperation(operation, scheduleIds) {
            try {
                const response = await fetch('/src/controllers/gestion_horarios_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'bulk_' + operation,
                        schedule_ids: JSON.stringify(scheduleIds)
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(`${operation === 'delete' ? 'Eliminados' : 'Procesados'} ${scheduleIds.length} horario(s)`, 'success');
                    loadScheduleGrid();
                    exitSelectionMode();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error en la operación', 'error');
            }
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
