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
    
    $horarioModel = new Horario($database->getConnection());
    $docenteModel = new Docente($database->getConnection());
    
    $docentes = $docenteModel->getAllDocentes();
    $bloques = $horarioModel->getAllBloques();
    
    $selectedDocenteId = $_GET['docente'] ?? null;
    $selectedDocente = null;
    $disponibilidad = [];
    
    if ($selectedDocenteId) {
        foreach ($docentes as $docente) {
            if ($docente['id_docente'] == $selectedDocenteId) {
                $selectedDocente = $docente;
                break;
            }
        }
        if ($selectedDocente) {
            $disponibilidad = $horarioModel->getDocenteDisponibilidad($selectedDocenteId);
        }
    }
    
} catch (Exception $e) {
    error_log("Error cargando disponibilidad: " . $e->getMessage());
    $docentes = [];
    $bloques = [];
    $disponibilidad = [];
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

        <main class="flex-1 flex flex-col main-content">
            <!-- Header -->
            <header class="bg-darkblue px-4 md:px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
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
            <section class="flex-1 px-4 md:px-6 py-6 md:py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-6 md:mb-8">
                        <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('teacher_availability'); ?></h2>
                        <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('teacher_availability_description'); ?></p>
                    </div>

                    <!-- Selector de docente -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('select_teacher'); ?></h3>
                        <div class="flex gap-4">
                            <select id="docenteSelect" class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                <option value=""><?php _e('select_teacher'); ?></option>
                                <?php foreach ($docentes as $docente): ?>
                                    <option value="<?php echo $docente['id_docente']; ?>" 
                                            <?php echo ($selectedDocenteId == $docente['id_docente']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button onclick="loadDocenteDisponibilidad()" 
                                    class="px-4 py-2 bg-darkblue text-white rounded-md hover:bg-navy transition-colors">
                                <?php _e('load'); ?>
                            </button>
                        </div>
                    </div>

                    <?php if ($selectedDocente): ?>
                    <!-- Información del docente seleccionado -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6 mb-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-darkblue mr-4 flex items-center justify-center text-white font-semibold">
                                <?php echo getUserInitials($selectedDocente['nombre'], $selectedDocente['apellido']); ?>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($selectedDocente['nombre'] . ' ' . $selectedDocente['apellido']); ?>
                                </h3>
                                <p class="text-gray-600">
                                    <?php echo htmlspecialchars($selectedDocente['email']); ?> | 
                                    CI: <?php echo htmlspecialchars($selectedDocente['cedula']); ?>
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
                    alert('Error actualizando disponibilidad: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);

                cell.dataset.disponible = currentState.toString();
                cell.className = cell.className.replace(newState ? 'disponible' : 'no-disponible', 
                                                       currentState ? 'disponible' : 'no-disponible');
                cell.textContent = currentState ? 'Disponible' : 'No disponible';
                alert('Error de conexión');
            });
        }

        function saveAllDisponibilidad() {
            alert('Cambios guardados automáticamente al hacer clic en cada celda');
        }

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
