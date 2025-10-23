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

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('mi-disponibilidad.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('DOCENTE');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$currentUser = AuthHelper::getCurrentUser();
$userId = $currentUser['id_usuario'] ?? null;
$userRole = AuthHelper::getCurrentUserRole();

$message = '';
$messageType = '';

// Check if user is admin accessing teacher views
$isAdminAccessingTeacherView = ($userRole === 'ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $dbConfig = require __DIR__ . '/../../config/database.php';
        $database = new Database($dbConfig);
        
        if ($_POST['action'] === 'save_availability') {
            $docenteQuery = "SELECT id_docente FROM docente WHERE id_usuario = :id_usuario";
            $stmt = $database->getConnection()->prepare($docenteQuery);
            $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $docenteId = $stmt->fetchColumn();
            
            if ($docenteId) {

                $deleteQuery = "DELETE FROM disponibilidad WHERE id_docente = :id_docente";
                $deleteStmt = $database->getConnection()->prepare($deleteQuery);
                $deleteStmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
                $deleteStmt->execute();
                
                $days = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
                $insertQuery = "INSERT INTO disponibilidad (id_docente, id_bloque, dia, disponible) VALUES (:id_docente, :id_bloque, :dia, :disponible)";
                $insertStmt = $database->getConnection()->prepare($insertQuery);
                
                $blocksQuery = "SELECT id_bloque FROM bloque_horario ORDER BY hora_inicio";
                $blocksStmt = $database->getConnection()->prepare($blocksQuery);
                $blocksStmt->execute();
                $allBlocks = $blocksStmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($days as $day) {
                    foreach ($allBlocks as $blockId) {
                        $cellName = 'disponible_' . $day . '_' . $blockId;
                        $isAvailable = isset($_POST[$cellName]) && $_POST[$cellName] === '1';
                        
                        $insertStmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
                        $insertStmt->bindParam(':id_bloque', $blockId, PDO::PARAM_INT);
                        $insertStmt->bindParam(':dia', $day, PDO::PARAM_STR);
                        $insertStmt->bindParam(':disponible', $isAvailable, PDO::PARAM_BOOL);
                        $insertStmt->execute();
                    }
                }
                
                $updateDocente = "UPDATE docente SET 
                                 fecha_envio_disponibilidad = CURRENT_DATE,
                                 trabaja_otro_liceo = :trabaja_otro_liceo
                                 WHERE id_docente = :id_docente";
                $updateStmt = $database->getConnection()->prepare($updateDocente);
                $trabajaOtroLiceo = isset($_POST['otro_liceo']) ? true : false;
                $updateStmt->bindParam(':trabaja_otro_liceo', $trabajaOtroLiceo, PDO::PARAM_BOOL);
                $updateStmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
                $updateStmt->execute();

                $observaciones = $_POST['observaciones'] ?? '';
                if (!empty($observaciones)) {
                    $deleteObsQuery = "DELETE FROM observacion WHERE id_docente = :id_docente AND tipo = 'DISPONIBILIDAD'";
                    $deleteObsStmt = $database->getConnection()->prepare($deleteObsQuery);
                    $deleteObsStmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
                    $deleteObsStmt->execute();

                    $obsQuery = "INSERT INTO observacion (id_docente, tipo, descripcion) VALUES (:id_docente, 'DISPONIBILIDAD', :descripcion)";
                    $obsStmt = $database->getConnection()->prepare($obsQuery);
                    $obsStmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
                    $obsStmt->bindParam(':descripcion', $observaciones, PDO::PARAM_STR);
                    $obsStmt->execute();
                }
                
                $message = 'Disponibilidad guardada exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error: No se encontró información del docente';
                $messageType = 'error';
            }
        }
    } catch (Exception $e) {
        error_log("Error procesando disponibilidad: " . $e->getMessage());
        $message = 'Error interno del servidor';
        $messageType = 'error';
    }
}

$currentAvailability = [];
$timeBlocks = [];
$docenteInfo = null;
$availabilityGrid = [];

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $blocksQuery = "SELECT id_bloque, hora_inicio, hora_fin FROM bloque_horario ORDER BY hora_inicio";
    $stmt = $database->getConnection()->prepare($blocksQuery);
    $stmt->execute();
    $timeBlocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($isAdminAccessingTeacherView) {
        // For admin users, show a message that they should use admin views
        $docenteInfo = null;
        $message = 'Como administrador, debe gestionar la disponibilidad de docentes desde la sección de administración.';
        $messageType = 'info';
    } else {
        $docenteQuery = "SELECT d.*, u.nombre, u.apellido FROM docente d 
                         INNER JOIN usuario u ON d.id_usuario = u.id_usuario 
                         WHERE d.id_usuario = :id_usuario";
        $stmt = $database->getConnection()->prepare($docenteQuery);
        $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $docenteInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($docenteInfo) {
        $docenteId = $docenteInfo['id_docente'];
        
        $days = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
        foreach ($days as $day) {
            $availabilityGrid[$day] = [];
            foreach ($timeBlocks as $block) {
                $availabilityGrid[$day][$block['id_bloque']] = false;
            }
        }
        
        $availQuery = "SELECT dia, id_bloque, disponible FROM disponibilidad WHERE id_docente = :id_docente";
        $stmt = $database->getConnection()->prepare($availQuery);
        $stmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
        $stmt->execute();
        $availabilityData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($availabilityData as $row) {
            if (isset($availabilityGrid[$row['dia']][$row['id_bloque']])) {
                $availabilityGrid[$row['dia']][$row['id_bloque']] = (bool)$row['disponible'];
            }
        }
        
        $obsQuery = "SELECT descripcion FROM observacion WHERE id_docente = :id_docente AND tipo = 'DISPONIBILIDAD' ORDER BY id_observacion DESC LIMIT 1";
        $stmt = $database->getConnection()->prepare($obsQuery);
        $stmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
        $stmt->execute();
        $observation = $stmt->fetchColumn();
        $currentAvailability['observaciones'] = $observation ?: '';
    }
} catch (Exception $e) {
    error_log("Error cargando disponibilidad: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — Mi Disponibilidad</title>
    <link rel="stylesheet" href="/css/styles.css">
    <script src="/js/auto-save-manager.js"></script>
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
            bottom: 0;
            width: 4px;
            background-color: #007bff;
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

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_teacher'); ?>)</div>
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
                                <div class="text-gray-500"><?php _e('role_teacher'); ?></div>
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
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('my_availability'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('my_availability_description'); ?></p>
                    </div>

                    <?php if ($message): ?>
                        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($docenteInfo): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php _e('teacher_information'); ?></h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500"><?php _e('name'); ?>:</span>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($docenteInfo['nombre'] . ' ' . $docenteInfo['apellido']); ?></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500"><?php _e('last_update_date'); ?>:</span>
                                    <p class="text-gray-900"><?php echo $docenteInfo['fecha_envio_disponibilidad'] ? date('d/m/Y', strtotime($docenteInfo['fecha_envio_disponibilidad'])) : _e('not_registered'); ?></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500"><?php _e('works_other_school'); ?>:</span>
                                    <p class="text-gray-900"><?php echo ($docenteInfo['trabaja_otro_liceo'] ?? false) ? _e('yes') : _e('no'); ?></p>
                                </div>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="save_availability">
                            
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
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
                                            foreach ($timeBlocks as $bloque): 
                                            ?>
                                                <tr>
                                                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">
                                                        <?php echo date('H:i', strtotime($bloque['hora_inicio'])) . ' – ' . date('H:i', strtotime($bloque['hora_fin'])); ?>
                                                    </th>
                                                    <?php foreach ($dias as $dia): ?>
                                                        <?php 
                                                        $isDisponible = $availabilityGrid[$dia][$bloque['id_bloque']];
                                                        $cellClass = $isDisponible ? 'disponible' : 'no-disponible';
                                                        $cellText = $isDisponible ? 'Disponible' : 'No disponible';
                                                        ?>
                                                        <td class="disponibilidad-cell <?php echo $cellClass; ?> text-center font-medium p-2 border border-gray-300" 
                                                            data-bloque="<?php echo $bloque['id_bloque']; ?>" 
                                                            data-dia="<?php echo $dia; ?>"
                                                            data-disponible="<?php echo $isDisponible ? 'true' : 'false'; ?>"
                                                            onclick="toggleDisponibilidad(this)">
                                                            <input type="hidden" 
                                                                   name="disponible_<?php echo $dia; ?>_<?php echo $bloque['id_bloque']; ?>" 
                                                                   id="disponible_<?php echo $dia; ?>_<?php echo $bloque['id_bloque']; ?>"
                                                                   value="<?php echo $isDisponible ? '1' : '0'; ?>">
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
                                        <div class="flex gap-2">
                                            <button type="button" onclick="selectAll()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                                <?php _e('select_all'); ?>
                                            </button>
                                            <button type="button" onclick="clearAll()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                                <?php _e('clear_all'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4"><?php _e('additional_information'); ?></h3>
                                
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="otro_liceo" value="1" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                               <?php echo ($docenteInfo['trabaja_otro_liceo'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="ml-2 text-sm text-gray-700"><?php _e('works_other_school'); ?></span>
                                    </label>
                                </div>

                                <div class="mb-6">
                                    <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php _e('additional_observations'); ?>:
                                    </label>
                                    <textarea name="observaciones" id="observaciones" rows="4" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="<?php _e('availability_observations_placeholder'); ?>"><?php echo htmlspecialchars($currentAvailability['observaciones'] ?? ''); ?></textarea>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="text-sm text-gray-600">
                                        <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        Los cambios individuales se guardan automáticamente
                                    </div>
                                    <div class="flex space-x-3">
                                        <button type="button" onclick="window.location.href='/teacher/dashboard'" 
                                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <?php _e('cancel'); ?>
                                        </button>
                                        <button type="submit" 
                                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <?php _e('save_all_changes'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-red-800"><?php _e('error_loading_teacher_info'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        function toggleDisponibilidad(cell) {
            const bloque = cell.dataset.bloque;
            const dia = cell.dataset.dia;
            const currentState = cell.dataset.disponible === 'true';
            const newState = !currentState;
            const saveKey = `disponibilidad_${dia}_${bloque}`;
            
            // Actualizar UI inmediatamente (optimistic update)
            cell.dataset.disponible = newState.toString();
            cell.className = cell.className.replace(currentState ? 'disponible' : 'no-disponible', 
                                                   newState ? 'disponible' : 'no-disponible');
            cell.textContent = newState ? 'Disponible' : 'No disponible';
            
            // Update hidden input
            const hiddenInput = document.getElementById(`disponible_${dia}_${bloque}`);
            if (hiddenInput) {
                hiddenInput.value = newState ? '1' : '0';
            }
            
            // Mark as unsaved
            window.autoSaveManager.markUnsaved(saveKey);
            
            // Debounced save with visual feedback
            window.autoSaveManager.save(saveKey, async () => {
                const formData = new FormData();
                formData.append('action', 'update_disponibilidad');
                formData.append('id_bloque', bloque);
                formData.append('dia', dia);
                formData.append('disponible', newState);
                
                const response = await fetch('/src/controllers/docente_disponibilidad_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Error actualizando disponibilidad');
                }
                
                return data;
            }, {
                indicator: cell,
                debounceDelay: 300,
                onSuccess: (result) => {
                    // Success - keep the optimistic update
                    console.log('Disponibilidad actualizada correctamente');
                },
                onError: (error) => {
                    // Revertir cambios en caso de error
                    cell.dataset.disponible = currentState.toString();
                    cell.className = cell.className.replace(newState ? 'disponible' : 'no-disponible', 
                                                           currentState ? 'disponible' : 'no-disponible');
                    cell.textContent = currentState ? 'Disponible' : 'No disponible';
                    if (hiddenInput) {
                        hiddenInput.value = currentState ? '1' : '0';
                    }
                    console.error('Error actualizando disponibilidad:', error);
                }
            });
        }

        function selectAll() {
            const cells = document.querySelectorAll('.disponibilidad-cell');
            
            cells.forEach(cell => {
                const hiddenInput = cell.querySelector('input[type="hidden"]');
                if (hiddenInput && cell.dataset.disponible === 'false') {
                    // Actualizar UI
                    cell.dataset.disponible = 'true';
                    cell.className = cell.className.replace('no-disponible', 'disponible');
                    cell.textContent = 'Disponible';
                    hiddenInput.value = '1';
                }
            });
            
            // Mostrar mensaje de que se debe guardar
            alert('Selección completada. Los cambios se guardarán cuando envíe el formulario.');
        }

        function clearAll() {
            const cells = document.querySelectorAll('.disponibilidad-cell');
            
            cells.forEach(cell => {
                const hiddenInput = cell.querySelector('input[type="hidden"]');
                if (hiddenInput && cell.dataset.disponible === 'true') {
                    // Actualizar UI
                    cell.dataset.disponible = 'false';
                    cell.className = cell.className.replace('disponible', 'no-disponible');
                    cell.textContent = 'No disponible';
                    hiddenInput.value = '0';
                }
            });
            
            // Mostrar mensaje de que se debe guardar
            alert('Limpieza completada. Los cambios se guardarán cuando envíe el formulario.');
        }

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/logout';
            }
        });
    </script>
</body>
</html>