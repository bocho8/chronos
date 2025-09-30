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

function generate_class_row() {
    $row = '';
    // Genera 5 celdas: Lunes a Viernes
    for ($i = 0; $i < 5; $i++) {
        $row .= '<div class="py-2 px-1 border-r border-b border-gray-200 text-gray-700 bg-gray-50">Text</div>';
    }
    return $row;
}

// Generar 4 filas pre-almuerzo
$pre_lunch_rows = '';
for ($i = 0; $i < 4; $i++) {
    $pre_lunch_rows .= generate_class_row();
}

// Generar 4 filas post-almuerzo
$post_lunch_rows = '';
for ($i = 0; $i < 4; $i++) {
    $post_lunch_rows .= generate_class_row();
}
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
                                <div class="grid grid-cols-5 gap-0 border border-gray-200 rounded-lg text-center text-sm font-medium text-white shadow-md">
                                    
                                    <div class="py-3 px-1 bg-blue-600 rounded-tl-lg">Lunes</div>
                                    <div class="py-3 px-1 bg-blue-600">Martes</div>
                                    <div class="py-3 px-1 bg-blue-600">Miércoles</div>
                                    <div class="py-3 px-1 bg-blue-600">Jueves</div>
                                    <div class="py-3 px-1 bg-blue-600 rounded-tr-lg">Viernes</div>

                                    <?php echo $pre_lunch_rows; ?>

                                    <div class="py-3 px-1 border-b border-gray-200 text-yellow-800 font-bold bg-yellow-100 col-span-5">
                                        ALMUERZO
                                    </div>

                                    <?php echo $post_lunch_rows; ?>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-6">
                            Próximas Evaluaciones
                        </h3>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4 text-red-600 font-bold text-lg">
                                        17
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Matemáticas</p>
                                        <p class="text-sm text-gray-500">Examen Final</p>
                                    </div>
                                </div>
                                <div class="text-gray-700 font-medium">
                                    Fecha: <span class="text-blue-600">25/10/2023</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4 text-red-600 font-bold text-lg">
                                        17
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Programación</p>
                                        <p class="text-sm text-gray-500">Trabajo Práctico</p>
                                    </div>
                                </div>
                                <div class="text-gray-700 font-medium">
                                    Fecha: <span class="text-blue-600">27/10/2023</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4 text-red-600 font-bold text-lg">
                                        17
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Sistemas Operativos</p>
                                        <p class="text-sm text-gray-500">Presentación de Proyecto</p>
                                    </div>
                                </div>
                                <div class="text-gray-700 font-medium">
                                    Fecha: <span class="text-blue-600">30/10/2023</span>
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