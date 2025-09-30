<?php
/**
 * Dashboard del Padre
 * Panel de control para seguimiento académico
 */

require_once __DIR__ . '/../../config/session.php'; 
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';

// Initialize secure session
initSecureSession();

// Initialize components
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('dashboard.php');

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and padre role
AuthHelper::requireRole('PADRE'); 

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Load current user info
$currentUser = AuthHelper::getCurrentUser();

// Load database configuration and get data
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // Get models
    $horarioModel = new Horario($database->getConnection());
    
    // Get grupos (to show as students for parents)
    $grupos = $horarioModel->getAllGrupos();
    
    // Get sample horarios for demonstration (first grupo if exists)
    $sampleHorarios = [];
    if (!empty($grupos)) {
        $firstGrupo = $grupos[0];
        $sampleHorarios = $horarioModel->getHorariosByGrupo($firstGrupo['id_grupo']);
    }
    
    // Get bloques horarios
    $bloques = $horarioModel->getAllBloques();
    
} catch (Exception $e) {
    error_log("Error loading dashboard data: " . $e->getMessage());
    $grupos = [];
    $sampleHorarios = [];
    $bloques = [];
}

function generate_class_row($horarios, $bloqueId, $dia) {
    // Buscar horario para este bloque y día
    $horarioEncontrado = null;
    foreach ($horarios as $horario) {
        if ($horario['id_bloque'] == $bloqueId && $horario['dia'] == $dia) {
            $horarioEncontrado = $horario;
            break;
        }
    }
    
    if ($horarioEncontrado) {
        $materia = htmlspecialchars($horarioEncontrado['materia_nombre'] ?? 'Sin asignar');
        return '<div class="py-2 px-1 border-r border-b border-gray-200 text-gray-700 bg-blue-50 text-xs" title="' . $materia . '">' . substr($materia, 0, 8) . '</div>';
    } else {
        return '<div class="py-2 px-1 border-r border-b border-gray-200 text-gray-700 bg-gray-50 text-xs">Libre</div>';
    }
}

function generate_weekly_schedule($horarios, $bloques) {
    $dias = [1, 2, 3, 4, 5]; // Lunes a Viernes
    $rows = '';
    
    foreach ($bloques as $bloque) {
        $rows .= '<div class="grid grid-cols-5 gap-0 text-center text-xs text-gray-600 border-b border-gray-200">';
        
        foreach ($dias as $dia) {
            $rows .= generate_class_row($horarios, $bloque['id_bloque'], $dia);
        }
        
        $rows .= '</div>';
    }
    
    return $rows;
}

// Generate schedule rows
$schedule_rows = generate_weekly_schedule($sampleHorarios, $bloques);
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — Dashboard Padre</title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center">
                    <?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_parent'); ?>)
                </div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <img src="/assets/images/icons/bell.png" class="h-6 w-6" alt="<?php _e('notifications'); ?>" />
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block" id="userMenu">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                                <div class="text-gray-500"><?php _e('role_parent'); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                                <img src="/assets/images/icons/user.png" class="inline w-4 h-4 mr-2" alt="<?php _e('profile'); ?>" />
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                                <img src="/assets/images/icons/gear.png" class="inline w-4 h-4 mr-2" alt="<?php _e('settings'); ?>" />
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <img src="/assets/images/icons/logout.png" class="inline w-4 h-4 mr-2" alt="<?php _e('logout'); ?>" />
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <section class="flex-1 px-6 py-8">
                <div class="max-w-7xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('parent_dashboard'); ?></h2>
                        <p class="text-muted mb-6 text-base">Aquí puedes monitorear el progreso académico de tu hijo.</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6 mb-10 border border-gray-100">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-6">
                            Calendario Semanal
                        </h3>

                        <div class="overflow-x-auto">
                            <div class="min-w-full inline-block align-middle">
                                <div class="border border-gray-200 rounded-lg text-center text-sm font-medium shadow-md">
                                    
                                    <!-- Header with days -->
                                    <div class="grid grid-cols-5 gap-0">
                                        <div class="py-3 px-2 bg-blue-600 text-white rounded-tl-lg"><?php _e('monday'); ?></div>
                                        <div class="py-3 px-2 bg-blue-600 text-white"><?php _e('tuesday'); ?></div>
                                        <div class="py-3 px-2 bg-blue-600 text-white"><?php _e('wednesday'); ?></div>
                                        <div class="py-3 px-2 bg-blue-600 text-white"><?php _e('thursday'); ?></div>
                                        <div class="py-3 px-2 bg-blue-600 text-white rounded-tr-lg"><?php _e('friday'); ?></div>
                                    </div>

                                    <!-- Schedule rows -->
                                    <?php if (!empty($bloques)): ?>
                                        <?php foreach ($bloques as $bloque): ?>
                                            <div class="grid grid-cols-5 gap-0 border-b border-gray-200">
                                                <?php for ($dia = 1; $dia <= 5; $dia++): ?>
                                                    <?php
                                                    // Buscar horario para este bloque y día
                                                    $horarioEncontrado = null;
                                                    foreach ($sampleHorarios as $horario) {
                                                        if ($horario['id_bloque'] == $bloque['id_bloque'] && $horario['dia'] == $dia) {
                                                            $horarioEncontrado = $horario;
                                                            break;
                                                        }
                                                    }
                                                    ?>
                                                    
                                                    <?php if ($horarioEncontrado): ?>
                                                        <div class="py-2 px-1 border-r border-gray-200 text-gray-700 bg-blue-50 text-xs" title="<?php echo htmlspecialchars($horarioEncontrado['materia_nombre'] ?? 'Sin asignar') . ' (' . $bloque['hora_inicio'] . '-' . $bloque['hora_fin'] . ')'; ?>">
                                                            <?php echo htmlspecialchars(substr($horarioEncontrado['materia_nombre'] ?? 'Sin asignar', 0, 10)); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="py-2 px-1 border-r border-gray-200 text-gray-700 bg-gray-50 text-xs" title="<?php echo $bloque['hora_inicio'] . '-' . $bloque['hora_fin']; ?>">
                                                            <?php _e('free'); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="py-8 text-center text-gray-500">
                                            <?php _e('no_schedule_available'); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Logout functionality
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>