<?php
/**
 * Gestión de Disponibilidad del Docente
 * Registro de disponibilidad horaria por bloques de tiempo
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('mi-disponibilidad.php');

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and docente role
AuthHelper::requireRole('DOCENTE');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Get current user info
$currentUser = AuthHelper::getCurrentUser();
$userId = $currentUser['id_usuario'] ?? null;

// Handle form submission for availability
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $dbConfig = require __DIR__ . '/../../config/database.php';
        $database = new Database($dbConfig);
        
        if ($_POST['action'] === 'save_availability') {
            // Get docente ID from user ID
            $docenteQuery = "SELECT id_docente FROM docente WHERE id_usuario = :id_usuario";
            $stmt = $database->getConnection()->prepare($docenteQuery);
            $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $docenteId = $stmt->fetchColumn();
            
            if ($docenteId) {
                // First, delete existing availability for this docente
                $deleteQuery = "DELETE FROM disponibilidad WHERE id_docente = :id_docente";
                $deleteStmt = $database->getConnection()->prepare($deleteQuery);
                $deleteStmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
                $deleteStmt->execute();
                
                // Save availability data according to database schema
                $days = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
                $insertQuery = "INSERT INTO disponibilidad (id_docente, id_bloque, dia, disponible) VALUES (:id_docente, :id_bloque, :dia, :disponible)";
                $insertStmt = $database->getConnection()->prepare($insertQuery);
                
                // Get all time blocks
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
                
                // Update docente record with submission date and other info
                $updateDocente = "UPDATE docente SET 
                                 fecha_envio_disponibilidad = CURRENT_DATE,
                                 trabaja_otro_liceo = :trabaja_otro_liceo
                                 WHERE id_docente = :id_docente";
                $updateStmt = $database->getConnection()->prepare($updateDocente);
                $trabajaOtroLiceo = isset($_POST['otro_liceo']) ? true : false;
                $updateStmt->bindParam(':trabaja_otro_liceo', $trabajaOtroLiceo, PDO::PARAM_BOOL);
                $updateStmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
                $updateStmt->execute();
                
                // Handle observations if needed
                $observaciones = $_POST['observaciones'] ?? '';
                if (!empty($observaciones)) {
                    // Delete existing observations for this docente
                    $deleteObsQuery = "DELETE FROM observacion WHERE id_docente = :id_docente AND tipo = 'DISPONIBILIDAD'";
                    $deleteObsStmt = $database->getConnection()->prepare($deleteObsQuery);
                    $deleteObsStmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
                    $deleteObsStmt->execute();
                    
                    // Insert new observation
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

// Load current availability data and time blocks
$currentAvailability = [];
$timeBlocks = [];
$docenteInfo = null;
$availabilityGrid = [];

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // Get time blocks from database (same as admin views)
    $blocksQuery = "SELECT id_bloque, hora_inicio, hora_fin FROM bloque_horario ORDER BY hora_inicio";
    $stmt = $database->getConnection()->prepare($blocksQuery);
    $stmt->execute();
    $timeBlocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get docente information
    $docenteQuery = "SELECT d.*, u.nombre, u.apellido FROM docente d 
                     INNER JOIN usuario u ON d.id_usuario = u.id_usuario 
                     WHERE d.id_usuario = :id_usuario";
    $stmt = $database->getConnection()->prepare($docenteQuery);
    $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $docenteInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($docenteInfo) {
        $docenteId = $docenteInfo['id_docente'];
        
        // Initialize availability grid
        $days = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
        foreach ($days as $day) {
            $availabilityGrid[$day] = [];
            foreach ($timeBlocks as $block) {
                $availabilityGrid[$day][$block['id_bloque']] = false;
            }
        }
        
        // Get current availability by day and block
        $availQuery = "SELECT dia, id_bloque, disponible FROM disponibilidad WHERE id_docente = :id_docente";
        $stmt = $database->getConnection()->prepare($availQuery);
        $stmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
        $stmt->execute();
        $availabilityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill the availability grid
        foreach ($availabilityData as $row) {
            if (isset($availabilityGrid[$row['dia']][$row['id_bloque']])) {
                $availabilityGrid[$row['dia']][$row['id_bloque']] = (bool)$row['disponible'];
            }
        }
        
        // Get observations
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
        .availability-cell {
            min-height: 80px;
            transition: all 0.2s;
            cursor: pointer;
            position: relative;
        }
        .availability-cell:hover {
            opacity: 0.8;
        }
        .available-cell {
            background-color: #10b981;
            color: white;
        }
        .unavailable-cell {
            background-color: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .cell-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 8px;
        }
        .disponible-text {
            font-weight: 500;
            margin-bottom: 4px;
        }
        .time-info {
            font-size: 0.75rem;
            color: #6b7280;
            text-align: center;
            line-height: 1.2;
        }
        .available-cell .time-info {
            color: #d1fae5;
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center">
                    <?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_teacher'); ?>)
                </div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                    <button class="p-2 rounded-full hover:bg-navy" title="<?php _e('user_menu'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Contenido principal - Centrado -->
            <section class="flex-1 px-6 py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5">Gestión de Disponibilidad</h2>
                        <p class="text-muted mb-6 text-base">Selecciona los horarios en los que estás disponible para dar clases</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($docenteInfo): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Información del Docente</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Nombre:</span>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($docenteInfo['nombre'] . ' ' . $docenteInfo['apellido']); ?></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Fecha de última actualización:</span>
                                    <p class="text-gray-900"><?php echo $docenteInfo['fecha_envio_disponibilidad'] ? date('d/m/Y', strtotime($docenteInfo['fecha_envio_disponibilidad'])) : 'No registrada'; ?></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Trabaja en otro centro:</span>
                                    <p class="text-gray-900"><?php echo ($docenteInfo['trabaja_otro_liceo'] ?? false) ? 'Sí' : 'No'; ?></p>
                                </div>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="save_availability">
                            
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                                <!-- Header de la tabla -->
                                <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
                                    <h3 class="font-medium text-darktext">Disponibilidad Horaria</h3>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="selectAll()" class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                                            Seleccionar Todo
                                        </button>
                                        <button type="button" onclick="clearAll()" class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                                            Limpiar Todo
                                        </button>
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
                                            foreach ($timeBlocks as $bloque): 
                                            ?>
                                                <tr>
                                                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">
                                                        <?php echo date('H:i', strtotime($bloque['hora_inicio'])) . ' – ' . date('H:i', strtotime($bloque['hora_fin'])); ?>
                                                    </th>
                                    <?php foreach ($dias as $dia): ?>
                                        <td class="availability-cell border border-gray-300
                                                   <?php echo $availabilityGrid[$dia][$bloque['id_bloque']] ? 'available-cell' : 'unavailable-cell'; ?>" 
                                            onclick="toggleAvailability('<?php echo $dia; ?>', <?php echo $bloque['id_bloque']; ?>)">
                                            <input type="hidden" 
                                                   name="disponible_<?php echo $dia; ?>_<?php echo $bloque['id_bloque']; ?>" 
                                                   id="disponible_<?php echo $dia; ?>_<?php echo $bloque['id_bloque']; ?>"
                                                   value="<?php echo $availabilityGrid[$dia][$bloque['id_bloque']] ? '1' : '0'; ?>">
                                            <div class="cell-content">
                                                <div class="disponible-text">
                                                    <?php echo $availabilityGrid[$dia][$bloque['id_bloque']] ? 'Disponible' : 'No disponible'; ?>
                                                </div>
                                                <div class="time-info">
                                                    <?php 
                                                    $dayNames = [
                                                        'LUNES' => 'Lun',
                                                        'MARTES' => 'Mar', 
                                                        'MIERCOLES' => 'Mié',
                                                        'JUEVES' => 'Jue',
                                                        'VIERNES' => 'Vie'
                                                    ];
                                                    echo $dayNames[$dia] . '<br>' . date('H:i', strtotime($bloque['hora_inicio'])) . '-' . date('H:i', strtotime($bloque['hora_fin']));
                                                    ?>
                                                </div>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información Adicional</h3>
                                
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="otro_liceo" value="1" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                               <?php echo ($docenteInfo['trabaja_otro_liceo'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="ml-2 text-sm text-gray-700">Trabajo en otro centro educativo</span>
                                    </label>
                                </div>

                                <div class="mb-6">
                                    <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-2">
                                        Observaciones adicionales:
                                    </label>
                                    <textarea name="observaciones" id="observaciones" rows="4" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="Ingresa cualquier observación sobre tu disponibilidad..."><?php echo htmlspecialchars($currentAvailability['observaciones'] ?? ''); ?></textarea>
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <button type="button" onclick="window.location.href='dashboard.php'" 
                                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Cancelar
                                    </button>
                                    <button type="submit" 
                                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Guardar Disponibilidad
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-red-800">Error: No se pudo cargar la información del docente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        function toggleAvailability(dia, bloque) {
            const hiddenInput = document.getElementById(`disponible_${dia}_${bloque}`);
            const cell = hiddenInput.closest('td');
            const textElement = cell.querySelector('.disponible-text');
            
            // Toggle the value
            const currentValue = hiddenInput.value === '1';
            const newValue = !currentValue;
            
            hiddenInput.value = newValue ? '1' : '0';
            
            // Update cell appearance
            if (newValue) {
                cell.classList.remove('unavailable-cell');
                cell.classList.add('available-cell');
                textElement.textContent = 'Disponible';
            } else {
                cell.classList.remove('available-cell');
                cell.classList.add('unavailable-cell');
                textElement.textContent = 'No disponible';
            }
        }

        function selectAll() {
            const hiddenInputs = document.querySelectorAll('input[type="hidden"][name^="disponible_"]');
            hiddenInputs.forEach(input => {
                const cell = input.closest('td');
                const textElement = cell.querySelector('.disponible-text');
                
                input.value = '1';
                cell.classList.remove('unavailable-cell');
                cell.classList.add('available-cell');
                textElement.textContent = 'Disponible';
            });
        }

        function clearAll() {
            const hiddenInputs = document.querySelectorAll('input[type="hidden"][name^="disponible_"]');
            hiddenInputs.forEach(input => {
                const cell = input.closest('td');
                const textElement = cell.querySelector('.disponible-text');
                
                input.value = '0';
                cell.classList.remove('available-cell');
                cell.classList.add('unavailable-cell');
                textElement.textContent = 'No disponible';
            });
        }
    </script>
</body>
</html>