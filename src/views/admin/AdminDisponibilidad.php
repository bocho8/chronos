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
require_once __DIR__ . '/../../models/Docente.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-disponibilidad.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $db = $database->getConnection();
    
    $horarioModel = new Horario($db);
    $docenteModel = new Docente($db);
    
    $docentes = $docenteModel->getAllDocentes();
    $bloques = $horarioModel->getAllBloques();
    
    $selectedDocenteId = $_GET['docente'] ?? null;
    $selectedDocente = null;
    $disponibilidad = [];
    $observaciones = [];
    $observacionesPredefinidas = [];
    
    // Ensure "Otro" predefined observation exists (RF034)
    $checkOtroQuery = "SELECT id_observacion_predefinida FROM observacion_predefinida WHERE LOWER(TRIM(texto)) = 'otro'";
    $checkOtroStmt = $db->prepare($checkOtroQuery);
    $checkOtroStmt->execute();
    $otroExists = $checkOtroStmt->fetch();
    
    if (!$otroExists) {
        try {
            $db->beginTransaction();
            $insertOtroQuery = "INSERT INTO observacion_predefinida (texto, es_sistema, activa) VALUES ('Otro', TRUE, TRUE)";
            $insertOtroStmt = $db->prepare($insertOtroQuery);
            $insertOtroStmt->execute();
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log("Note: Could not create 'Otro' observation (might already exist): " . $e->getMessage());
        }
    }
    
    // Load predefined observaciones
    $predefQuery = "SELECT id_observacion_predefinida, texto, es_sistema FROM observacion_predefinida WHERE activa = TRUE ORDER BY texto";
    $predefStmt = $db->prepare($predefQuery);
    $predefStmt->execute();
    $observacionesPredefinidas = $predefStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($selectedDocenteId) {
        foreach ($docentes as $docente) {
            if ($docente['id_docente'] == $selectedDocenteId) {
                $selectedDocente = $docente;
                break;
            }
        }
        if ($selectedDocente) {
            $disponibilidad = $horarioModel->getDocenteDisponibilidad($selectedDocenteId);
            
            // Load existing observaciones for this teacher
            $obsQuery = "SELECT o.id_observacion, o.id_observacion_predefinida, o.tipo, o.descripcion, o.motivo_texto, 
                        op.texto as observacion_predefinida_texto
                        FROM observacion o
                        LEFT JOIN observacion_predefinida op ON o.id_observacion_predefinida = op.id_observacion_predefinida
                        WHERE o.id_docente = :id_docente
                        ORDER BY o.id_observacion DESC";
            $obsStmt = $db->prepare($obsQuery);
            $obsStmt->bindParam(':id_docente', $selectedDocenteId, PDO::PARAM_INT);
            $obsStmt->execute();
            $observaciones = $obsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
} catch (Exception $e) {
    error_log("Error cargando disponibilidad: " . $e->getMessage());
    $docentes = [];
    $bloques = [];
    $disponibilidad = [];
    $observaciones = [];
    $observacionesPredefinidas = [];
    $error_message = 'Error interno del servidor';
}

function getUserInitials($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('teacher_availability'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <script src="/js/toast.js?v=<?php echo time(); ?>"></script>
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
        .disponibilidad-cell {
            cursor: pointer;
            transition: all 0.2s;
            min-width: 100px;
        }
        .disponibilidad-cell:hover {
            opacity: 0.9;
            transform: scale(0.97);
        }
        .disponible {
            background-color: #10b981;
            color: white;
        }
        .no-disponible {
            background-color: #ef4444;
            color: white;
        }
        .sin-datos {
            background-color: #6b7280;
            color: white;
        }
        #toastContainer {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 10000 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
            pointer-events: none !important;
        }
        
        #toastContainer .toast {
            pointer-events: auto !important;
        }
        @media (max-width: 768px) {
            .disponibilidad-cell {
                min-width: 80px;
                font-size: 0.75rem;
                padding: 6px 4px;
            }
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

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
            <section class="flex-1 p-3 sm:p-4 md:p-6 w-full overflow-x-hidden">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-6 md:mb-8">
                        <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('teacher_availability'); ?></h2>
                        <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('teacher_availability_description'); ?></p>
                    </div>

                    <!-- Selector de docente -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-4 md:p-6 mb-6">
                        <h3 class="text-base md:text-lg font-medium text-gray-900 mb-4"><?php _e('select_teacher'); ?></h3>
                        <div class="flex flex-col sm:flex-row gap-3 md:gap-4">
                            <select id="docenteSelect" class="flex-1 px-3 py-2 text-sm md:text-base border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                <option value=""><?php _e('select_teacher'); ?></option>
                                <?php foreach ($docentes as $docente): ?>
                                    <option value="<?php echo $docente['id_docente']; ?>" 
                                            <?php echo ($selectedDocenteId == $docente['id_docente']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button onclick="loadDocenteDisponibilidad()" 
                                    class="px-4 py-2 text-sm md:text-base bg-darkblue text-white rounded-md hover:bg-navy transition-colors whitespace-nowrap">
                                <?php _e('load'); ?>
                            </button>
                        </div>
                    </div>

                    <?php if ($selectedDocente): ?>
                    <!-- Información del docente seleccionado -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-4 md:p-6 mb-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-darkblue mr-3 md:mr-4 flex items-center justify-center text-white font-semibold text-sm md:text-base flex-shrink-0">
                                <?php echo getUserInitials($selectedDocente['nombre'], $selectedDocente['apellido']); ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base md:text-lg font-semibold text-gray-900 truncate">
                                    <?php echo htmlspecialchars($selectedDocente['nombre'] . ' ' . $selectedDocente['apellido']); ?>
                                </h3>
                                <p class="text-sm md:text-base text-gray-600 break-words">
                                    <span class="block sm:inline"><?php echo htmlspecialchars($selectedDocente['email']); ?></span>
                                    <span class="hidden sm:inline"> | </span>
                                    <span class="block sm:inline">CI: <?php echo htmlspecialchars($selectedDocente['cedula']); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de disponibilidad -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder">
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-medium text-darktext"><?php _e('availability_schedule'); ?></h3>
                            <p class="text-sm text-gray-600 mt-1"><?php _e('click_to_toggle_availability'); ?></p>
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

                                    $disponibilidadGrid = [];
                                    foreach ($dias as $dia) {
                                        $disponibilidadGrid[$dia] = [];
                                        foreach ($bloques as $bloque) {
                                            $disponibilidadGrid[$dia][$bloque['id_bloque']] = true;
                                        }
                                    }

                                    foreach ($disponibilidad as $disp) {
                                        if (isset($disponibilidadGrid[$disp['dia']][$disp['id_bloque']])) {
                                            $disponibilidadGrid[$disp['dia']][$disp['id_bloque']] = $disp['disponible'];
                                        }
                                    }
                                    
                                    foreach ($bloques as $bloque): 
                                    ?>
                                        <tr>
                                            <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">
                                                <?php echo date('H:i', strtotime($bloque['hora_inicio'])) . ' – ' . date('H:i', strtotime($bloque['hora_fin'])); ?>
                                            </th>
                                            <?php foreach ($dias as $dia): ?>
                                                <?php 
                                                $isDisponible = $disponibilidadGrid[$dia][$bloque['id_bloque']];
                                                $cellClass = $isDisponible ? 'disponible' : 'no-disponible';
                                                $cellText = $isDisponible ? 'Disponible' : 'No disponible';
                                                ?>
                                                <td class="disponibilidad-cell <?php echo $cellClass; ?> text-center font-medium p-2 border border-gray-300" 
                                                    data-docente="<?php echo $selectedDocenteId; ?>"
                                                    data-bloque="<?php echo $bloque['id_bloque']; ?>" 
                                                    data-dia="<?php echo $dia; ?>"
                                                    data-disponible="<?php echo $isDisponible ? 'true' : 'false'; ?>"
                                                    onclick="toggleDisponibilidad(this)">
                                                    <?php echo $cellText; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="p-4 bg-gray-50 border-t">
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600">
                                    <span class="inline-block w-4 h-4 bg-green-500 rounded mr-2"></span><?php _e('available'); ?>
                                    <span class="inline-block w-4 h-4 bg-red-500 rounded mr-2 ml-4"></span><?php _e('not_available'); ?>
                                </div>
                                <button onclick="saveAllDisponibilidad()" 
                                        class="px-4 py-2 bg-darkblue text-white rounded-md hover:bg-navy transition-colors">
                                    <?php _e('save_changes'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Observaciones -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mt-6">
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-medium text-darktext"><?php _e('additional_observations'); ?></h3>
                            <p class="text-sm text-gray-600 mt-1"><?php _e('availability_observations_placeholder'); ?></p>
                        </div>
                        
                        <!-- Formulario para agregar observación -->
                        <div class="p-6 border-b border-gray-200">
                            <form id="observacionForm" class="space-y-4">
                                <input type="hidden" id="observacionId" name="observacion_id" value="">
                                <input type="hidden" name="id_docente" value="<?php echo htmlspecialchars($selectedDocenteId ?? ''); ?>">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="observacionPredefinida" class="block text-sm font-medium text-gray-700 mb-2">
                                            Observación Predefinida
                                        </label>
                                        <select id="observacionPredefinida" name="id_observacion_predefinida" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                            <option value="">-- Seleccionar observación predefinida --</option>
                                            <?php foreach ($observacionesPredefinidas as $obsPre): ?>
                                                <option value="<?php echo htmlspecialchars($obsPre['id_observacion_predefinida']); ?>">
                                                    <?php echo htmlspecialchars($obsPre['texto']); ?>
                                                    <?php if ($obsPre['es_sistema']): ?> (Sistema)<?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="observacionTipo" class="block text-sm font-medium text-gray-700 mb-2">
                                            Tipo
                                        </label>
                                        <input type="text" id="observacionTipo" name="tipo" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue"
                                               placeholder="Ej: Disponibilidad, Restricción, etc.">
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="observacionDescripcion" class="block text-sm font-medium text-gray-700 mb-2">
                                        Descripción
                                    </label>
                                    <textarea id="observacionDescripcion" name="descripcion" rows="2"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue"
                                              placeholder="Descripción general de la observación (opcional)"></textarea>
                                </div>
                                
                                <div id="motivoFieldContainer">
                                    <label for="observacionMotivo" class="block text-sm font-medium text-gray-700 mb-2">
                                        Motivo / Texto libre 
                                        <span id="motivoRequiredIndicator" class="text-gray-500 text-xs">(máximo 500 caracteres)</span>
                                        <span id="motivoRequiredStar" class="text-red-500 text-xs hidden">*</span>
                                    </label>
                                    <textarea id="observacionMotivo" name="motivo_texto" rows="3" maxlength="500"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue"
                                              placeholder="Ingrese el motivo o texto libre de la observación"></textarea>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span id="motivoCharCount">0</span> / 500 caracteres
                                    </div>
                                    <p id="otroHelpText" class="text-xs text-blue-600 mt-1 hidden italic">
                                        Al seleccionar "Otro", debe completar este campo con la descripción de la observación.
                                    </p>
                                </div>
                                
                                <div class="flex gap-2">
                                    <button type="submit" id="saveObservacionBtn"
                                            class="px-4 py-2 bg-darkblue text-white rounded-md hover:bg-navy transition-colors">
                                        Guardar Observación
                                    </button>
                                    <button type="button" id="cancelObservacionBtn" onclick="resetObservacionForm()"
                                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors hidden">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Lista de observaciones existentes -->
                        <div class="p-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-4">Observaciones Registradas</h4>
                            <div id="observacionesList" class="space-y-3">
                                <?php if (empty($observaciones)): ?>
                                    <p class="text-gray-500 text-sm">No hay observaciones registradas para este docente.</p>
                                <?php else: ?>
                                    <?php foreach ($observaciones as $obs): ?>
                                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50" data-obs-id="<?php echo htmlspecialchars($obs['id_observacion']); ?>">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <?php if (!empty($obs['observacion_predefinida_texto'])): ?>
                                                        <div class="font-medium text-gray-900 mb-1">
                                                            <?php echo htmlspecialchars($obs['observacion_predefinida_texto']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($obs['tipo'])): ?>
                                                        <div class="text-sm text-gray-600 mb-1">
                                                            <span class="font-medium">Tipo:</span> <?php echo htmlspecialchars($obs['tipo']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($obs['descripcion'])): ?>
                                                        <div class="text-sm text-gray-600 mb-1">
                                                            <?php echo htmlspecialchars($obs['descripcion']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($obs['motivo_texto'])): ?>
                                                        <div class="text-sm text-gray-700 mt-2 p-2 bg-white rounded border">
                                                            <span class="font-medium">Motivo:</span> <?php echo htmlspecialchars($obs['motivo_texto']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex gap-2 ml-4">
                                                    <button onclick="editObservacion(<?php echo htmlspecialchars($obs['id_observacion']); ?>)" 
                                                            class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                                                        Editar
                                                    </button>
                                                    <button onclick="deleteObservacion(<?php echo htmlspecialchars($obs['id_observacion']); ?>)" 
                                                            class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors">
                                                        Eliminar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Mensaje cuando no hay docente seleccionado -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-12 text-center">
                        <div class="text-6xl mb-4">👨‍🏫</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php _e('no_teacher_selected'); ?></h3>
                        <p class="text-gray-500"><?php _e('select_teacher_to_manage_availability'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script>
        function loadDocenteDisponibilidad() {
            const docenteId = document.getElementById('docenteSelect').value;
            if (docenteId) {
                window.location.href = `/availability?docente=${docenteId}`;
            }
        }

        function toggleDisponibilidad(cell) {
            const docente = cell.dataset.docente;
            const bloque = cell.dataset.bloque;
            const dia = cell.dataset.dia;
            const currentState = cell.dataset.disponible === 'true';
            const newState = !currentState;
            
            cell.dataset.disponible = newState.toString();
            cell.className = cell.className.replace(currentState ? 'disponible' : 'no-disponible', 
                                                   newState ? 'disponible' : 'no-disponible');
            cell.textContent = newState ? 'Disponible' : 'No disponible';
            
            const formData = new FormData();
            formData.append('action', 'update_disponibilidad');
            formData.append('id_docente', docente);
            formData.append('id_bloque', bloque);
            formData.append('dia', dia);
            formData.append('disponible', newState);
            
            fetch('/src/controllers/AdminDisponibilidadHandler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {

                    cell.dataset.disponible = currentState.toString();
                    cell.className = cell.className.replace(newState ? 'disponible' : 'no-disponible', 
                                                           currentState ? 'disponible' : 'no-disponible');
                    cell.textContent = currentState ? 'Disponible' : 'No disponible';
                    if (typeof showToast === 'function') {
                        showToast('Error actualizando disponibilidad: ' + data.message, 'error');
                    } else {
                    alert('Error actualizando disponibilidad: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);

                cell.dataset.disponible = currentState.toString();
                cell.className = cell.className.replace(newState ? 'disponible' : 'no-disponible', 
                                                       currentState ? 'disponible' : 'no-disponible');
                cell.textContent = currentState ? 'Disponible' : 'No disponible';
                if (typeof showToast === 'function') {
                    showToast('Error de conexión', 'error');
                } else {
                alert('Error de conexión');
                }
            });
        }

        function saveAllDisponibilidad() {
            if (typeof showToast === 'function') {
                showToast('Cambios guardados automáticamente al hacer clic en cada celda', 'info');
            } else {
            alert('Cambios guardados automáticamente al hacer clic en cada celda');
            }
        }

        // Observaciones management
        const motivoTextarea = document.getElementById('observacionMotivo');
        const motivoCharCount = document.getElementById('motivoCharCount');
        const observacionPredefinidaSelect = document.getElementById('observacionPredefinida');
        const motivoRequiredStar = document.getElementById('motivoRequiredStar');
        const motivoRequiredIndicator = document.getElementById('motivoRequiredIndicator');
        const otroHelpText = document.getElementById('otroHelpText');
        
        // Function to check if "Otro" is selected
        function checkIfOtroSelected() {
            if (!observacionPredefinidaSelect || !motivoTextarea) return;
            
            const selectedOption = observacionPredefinidaSelect.options[observacionPredefinidaSelect.selectedIndex];
            const selectedText = selectedOption ? selectedOption.text.trim().toLowerCase() : '';
            // Remove "(Sistema)" suffix if present for comparison
            const isOtro = selectedText === 'otro' || selectedText.startsWith('otro (sistema)');
            
            if (isOtro) {
                // Make motivo required and highlight it
                motivoTextarea.setAttribute('required', 'required');
                motivoTextarea.classList.add('border-blue-500', 'ring-2', 'ring-blue-200');
                motivoTextarea.classList.remove('border-gray-300');
                if (motivoRequiredStar) motivoRequiredStar.classList.remove('hidden');
                if (motivoRequiredIndicator) motivoRequiredIndicator.classList.add('hidden');
                if (otroHelpText) otroHelpText.classList.remove('hidden');
                motivoTextarea.placeholder = 'Este campo es requerido cuando se selecciona "Otro". Describa la observación aquí.';
            } else {
                // Make motivo optional again
                motivoTextarea.removeAttribute('required');
                motivoTextarea.classList.remove('border-blue-500', 'ring-2', 'ring-blue-200');
                motivoTextarea.classList.add('border-gray-300');
                if (motivoRequiredStar) motivoRequiredStar.classList.add('hidden');
                if (motivoRequiredIndicator) motivoRequiredIndicator.classList.remove('hidden');
                if (otroHelpText) otroHelpText.classList.add('hidden');
                motivoTextarea.placeholder = 'Ingrese el motivo o texto libre de la observación';
            }
        }
        
        // Listen for changes in the predefined observation select
        if (observacionPredefinidaSelect) {
            observacionPredefinidaSelect.addEventListener('change', checkIfOtroSelected);
        }
        
        if (motivoTextarea && motivoCharCount) {
            motivoTextarea.addEventListener('input', function() {
                motivoCharCount.textContent = this.value.length;
            });
        }

        document.getElementById('observacionForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate that at least one field has content
            const predefinidaSelect = document.getElementById('observacionPredefinida');
            const predefinida = predefinidaSelect ? predefinidaSelect.value : '';
            const tipo = document.getElementById('observacionTipo').value.trim();
            const descripcion = document.getElementById('observacionDescripcion').value.trim();
            const motivo = document.getElementById('observacionMotivo').value.trim();
            
            // Check if "Otro" is selected
            let isOtroSelected = false;
            if (predefinidaSelect && predefinida) {
                const selectedOption = predefinidaSelect.options[predefinidaSelect.selectedIndex];
                const selectedText = selectedOption ? selectedOption.text.trim().toLowerCase() : '';
                isOtroSelected = selectedText === 'otro' || selectedText.startsWith('otro (sistema)');
            }
            
            // If "Otro" is selected, motivo_texto is required
            if (isOtroSelected) {
                if (!motivo || motivo.length === 0) {
                    if (typeof showToast === 'function') {
                        showToast('Al seleccionar "Otro", debe completar el campo "Motivo / Texto libre" con la descripción de la observación', 'error');
                    } else {
                        alert('Al seleccionar "Otro", debe completar el campo "Motivo / Texto libre" con la descripción de la observación');
                    }
                    document.getElementById('observacionMotivo').focus();
                    return false;
                }
            }
            
            const hasPredefinida = !!predefinida;
            const hasTipo = tipo && tipo.length > 0 && tipo !== 'General';
            const hasDescripcion = descripcion && descripcion.length > 0;
            const hasMotivo = motivo && motivo.length > 0;
            
            if (!hasPredefinida && !hasTipo && !hasDescripcion && !hasMotivo) {
                if (typeof showToast === 'function') {
                    showToast('Debe completar al menos uno de los campos: Observación Predefinida, Tipo, Descripción o Motivo', 'error');
                } else {
                    alert('Debe completar al menos uno de los campos: Observación Predefinida, Tipo, Descripción o Motivo');
                }
                return false;
            }
            
            // Validate motivo length
            if (motivo && motivo.length > 500) {
                if (typeof showToast === 'function') {
                    showToast('El motivo no puede exceder 500 caracteres', 'error');
                } else {
                    alert('El motivo no puede exceder 500 caracteres');
                }
                document.getElementById('observacionMotivo').focus();
                return false;
            }
            
            saveObservacion();
        });

        function saveObservacion() {
            const form = document.getElementById('observacionForm');
            const formData = new FormData(form);
            const observacionId = document.getElementById('observacionId').value;
            
            formData.append('action', observacionId ? 'update_observacion' : 'create_observacion');
            if (observacionId) {
                formData.append('id_observacion', observacionId);
            }

            const saveBtn = document.getElementById('saveObservacionBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Guardando...';

            fetch('/src/controllers/AdminDisponibilidadHandler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Observación guardada exitosamente', 'success');
                    } else {
                        alert('Observación guardada exitosamente');
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + (data.message || 'Error desconocido'), 'error');
                    } else {
                        alert('Error: ' + (data.message || 'Error desconocido'));
                    }
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Guardar Observación';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof showToast === 'function') {
                    showToast('Error de conexión', 'error');
                } else {
                    alert('Error de conexión');
                }
                saveBtn.disabled = false;
                saveBtn.textContent = 'Guardar Observación';
            });
        }

        function editObservacion(id) {
            fetch('/src/controllers/AdminDisponibilidadHandler.php?action=get_observacion&id_observacion=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.observacion) {
                        const obs = data.observacion;
                        document.getElementById('observacionId').value = obs.id_observacion;
                        document.getElementById('observacionPredefinida').value = obs.id_observacion_predefinida || '';
                        document.getElementById('observacionTipo').value = obs.tipo || '';
                        document.getElementById('observacionDescripcion').value = obs.descripcion || '';
                        document.getElementById('observacionMotivo').value = obs.motivo_texto || '';
                        // Update character count
                        if (motivoCharCount) {
                            motivoCharCount.textContent = (obs.motivo_texto || '').length;
                        }
                        // Check if "Otro" is selected after loading
                        setTimeout(checkIfOtroSelected, 100);
                        
                        document.getElementById('saveObservacionBtn').textContent = 'Actualizar Observación';
                        document.getElementById('cancelObservacionBtn').classList.remove('hidden');
                        
                        document.getElementById('observacionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error cargando observación', 'error');
                        } else {
                            alert('Error cargando observación');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error de conexión', 'error');
                    } else {
                        alert('Error de conexión');
                    }
                });
        }

        async function deleteObservacion(id) {
            let confirmed = false;
            
            if (typeof showConfirmModal === 'function') {
                confirmed = await showConfirmModal(
                    'Confirmar Eliminación',
                    '¿Está seguro de que desea eliminar esta observación?',
                    'Eliminar',
                    'Cancelar'
                );
            } else {
                confirmed = confirm('¿Está seguro de que desea eliminar esta observación?');
            }
            
            if (!confirmed) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_observacion');
            formData.append('id_observacion', id);

            fetch('/src/controllers/AdminDisponibilidadHandler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Observación eliminada exitosamente', 'success');
                    } else {
                        alert('Observación eliminada exitosamente');
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + (data.message || 'Error desconocido'), 'error');
                    } else {
                        alert('Error: ' + (data.message || 'Error desconocido'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof showToast === 'function') {
                    showToast('Error de conexión', 'error');
                } else {
                    alert('Error de conexión');
                }
            });
        }

        function resetObservacionForm() {
            document.getElementById('observacionForm').reset();
            document.getElementById('observacionId').value = '';
            document.getElementById('saveObservacionBtn').textContent = 'Guardar Observación';
            document.getElementById('cancelObservacionBtn').classList.add('hidden');
            if (motivoCharCount) {
                motivoCharCount.textContent = '0';
            }
            // Reset "Otro" state
            checkIfOtroSelected();
        }

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
