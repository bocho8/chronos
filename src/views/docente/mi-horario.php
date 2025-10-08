<?php
/**
 * Visualización de Horario del Docente
 * Consulta de horarios de clases asignados
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
$sidebar = new Sidebar('mi-horario.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('DOCENTE');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$currentUser = AuthHelper::getCurrentUser();
$userId = $currentUser['id_usuario'] ?? null;
$userRole = AuthHelper::getCurrentUserRole();
$miHorario = [];
$cargaHoraria = 0;
$porcentajeMargen = 0;

// Check if user is admin accessing teacher views
$isAdminAccessingTeacherView = ($userRole === 'ADMIN');

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    if ($isAdminAccessingTeacherView) {
        // For admin users, show empty data
        $docenteInfo = null;
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
        $cargaHoraria = $docenteInfo['horas_asignadas'] ?? 0;
        $porcentajeMargen = $docenteInfo['porcentaje_margen'] ?? 0;
        
        $scheduleQuery = "SELECT h.*, m.nombre as materia_nombre, g.nombre as grupo_nombre,
                                 b.hora_inicio, b.hora_fin
                         FROM horario h
                         INNER JOIN materia m ON h.id_materia = m.id_materia
                         INNER JOIN grupo g ON h.id_grupo = g.id_grupo
                         INNER JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                         WHERE h.id_docente = :id_docente
                         ORDER BY h.dia, b.hora_inicio";
        $stmt = $database->getConnection()->prepare($scheduleQuery);
        $stmt->bindParam(':id_docente', $docenteInfo['id_docente'], PDO::PARAM_INT);
        $stmt->execute();
        $miHorario = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $blocksQuery = "SELECT id_bloque, hora_inicio, hora_fin FROM bloque_horario ORDER BY hora_inicio";
        $stmt = $database->getConnection()->prepare($blocksQuery);
        $stmt->execute();
        $timeBlocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("Error cargando horario del docente: " . $e->getMessage());
}

function generateScheduleTable($horario, $timeBlocks) {
    if (empty($horario)) {
        return '<div class="text-center py-8">
                    <span class="text-gray-400 text-2xl">•</span>
                    <p class="mt-2 text-sm text-gray-500">' . _e('no_schedules_published') . '</p>
                    <p class="text-xs text-gray-400">' . _e('schedules_will_be_visible_when_published') . '</p>
                </div>';
    }

    $html = '<div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300" style="width: 100px; min-width: 100px;">Hora</th>
                            <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Lunes</th>
                            <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Martes</th>
                            <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Miércoles</th>
                            <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Jueves</th>
                            <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Viernes</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    $dias = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES'];
    
    foreach ($timeBlocks as $bloque) {
        $html .= '<tr>
                    <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300" style="width: 100px; min-width: 100px;">
                        ' . substr($bloque['hora_inicio'], 0, 5) . ' – ' . substr($bloque['hora_fin'], 0, 5) . '
                    </th>';
        
        foreach ($dias as $dia) {
            $assignment = getAssignmentForBlock($horario, $dia, $bloque['id_bloque']);
            $html .= '<td class="border border-gray-300 p-2 text-center" style="min-width: 120px; width: 120px;">';
            
            if ($assignment) {
                $html .= '<div class="bg-blue-100 text-blue-800 p-2 rounded text-xs">
                            <div class="font-semibold">' . htmlspecialchars($assignment['grupo_nombre']) . '</div>
                            <div class="text-xs">' . htmlspecialchars($assignment['materia_nombre']) . '</div>
                          </div>';
            } else {
                $html .= '<span class="text-gray-400 text-xs">Libre</span>';
            }
            
            $html .= '</td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    return $html;
}

function getAssignmentForBlock($horario, $dia, $idBloque) {
    foreach ($horario as $assignment) {
        if (strtoupper($assignment['dia']) === $dia && $assignment['id_bloque'] == $idBloque) {
            return $assignment;
        }
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — Mi Horario</title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_teacher'); ?>)</div>
                
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

            <section class="flex-1 px-6 py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('my_schedule'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('my_schedule_description'); ?></p>
                    </div>

                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-gray-400 text-2xl">•</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('assigned_hours'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $cargaHoraria; ?> <?php _e('hours'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-bold leading-none">•</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('margin_percentage'); ?></p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $porcentajeMargen; ?>%</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 <?php echo $cargaHoraria > 16 ? 'bg-orange-100' : 'bg-green-100'; ?> rounded-full flex items-center justify-center">
                                        <span class="text-orange-600 text-xs font-bold leading-none">H</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500"><?php _e('status'); ?></p>
                                    <p class="text-lg font-semibold <?php echo $cargaHoraria > 16 ? 'text-orange-600' : 'text-green-600'; ?>">
                                        <?php echo $cargaHoraria > 16 ? _e('over_16h') : _e('normal'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule Table -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900"><?php _e('my_class_schedule'); ?></h3>
                            <button onclick="window.print()" 
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <span class="text-xs font-bold leading-none">•</span>
                                <?php _e('print'); ?>
                            </button>
                        </div>
                        <?php echo generateScheduleTable($miHorario, $timeBlocks ?? []); ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/logout';
            }
        });
    </script>
</body>
</html>
