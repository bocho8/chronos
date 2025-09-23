<?php
/**
 * Dashboard principal del Coordinador
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../models/Database.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and COORDINADOR role
AuthHelper::requireRole('COORDINADOR');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Load database configuration and get coordinator data
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // OBTENER LISTA DE COORDINADORES (EJEMPLO)
    // Deberías ajustar esta consulta para obtener los coordinadores de tu base de datos
    // He añadido datos de ejemplo para que coincida con tu wireframe.
    $coordinadores = $database->query(
        "SELECT u.nombre, u.apellido FROM usuario u 
         JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario 
         WHERE ur.nombre_rol = 'COORDINADOR'"
    );

    // Si la consulta falla o no devuelve resultados, usamos datos de ejemplo para el diseño
    if (empty($coordinadores)) {
        $coordinadores = [
            ['nombre' => 'Alberto', 'apellido' => 'De Mattos', 'experiencia' => 15],
            ['nombre' => 'Patricia', 'apellido' => 'Molinari', 'experiencia' => 12]
        ];
    }

} catch (Exception $e) {
    error_log("Error cargando datos del coordinador: " . $e->getMessage());
    $coordinadores = [ // Datos de fallback en caso de error de DB
        ['nombre' => 'Alberto', 'apellido' => 'De Mattos', 'experiencia' => 15],
        ['nombre' => 'Patricia', 'apellido' => 'Molinari', 'experiencia' => 12]
    ];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('coordinator_dashboard'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        /* Estilo para el enlace activo en el sidebar */
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
            background-color: #1f366d; /* Color azul oscuro principal */
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
            <div class="px-5 flex items-center h-[60px] border-b border-gray-200 bg-darkblue">
                 <img src="/assets/images/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
                 <span class="text-white font-semibold text-lg ml-2.5"><?php _e('scuola_italiana'); ?></span>
            </div>

            <ul class="py-5 list-none flex-grow">
                <li>
                    <a href="coordinador-dashboard.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-gray-100">
                        <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <?php _e('home'); // Inicio ?>
                    </a>
                </li>
                <li>
                    <a href="coordinador-docentes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-gray-100">
                        <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                        <?php _e('teachers'); // Docentes ?>
                    </a>
                </li>
                <li>
                    <a href="coordinador-calendario.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-gray-100">
                        <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <?php _e('calendar'); // Calendario ?>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-gray-200">
                <div class="flex items-center">
                    <button class="text-white focus:outline-none lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h1 class="text-white text-xl font-semibold ml-4">Bienvenido (<?php _e('role_coordinator'); ?>)</h1>
                </div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    
                    <div class="relative group">
                        <button class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block" id="userMenu">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                                <div class="text-gray-500"><?php _e('role_coordinator'); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <?php _e('profile'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <section class="flex-1 px-8 py-10 bg-gray-50">
                <div class="max-w-5xl mx-auto">
                    
                    <div class="mb-12">
                        <h2 class="text-3xl font-bold text-gray-800">Bienvenido al Panel Coordinador</h2>
                        <p class="text-gray-500 mt-1">Gestione docentes y horarios.</p>
                    </div>

                    <div class="space-y-6">
                        <?php foreach ($coordinadores as $coordinador): ?>
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-16 h-16 bg-gray-200 rounded-full mr-6 flex-shrink-0">
                                    </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">
                                        Coordinador/a <?php echo htmlspecialchars($coordinador['nombre'] . ' ' . $coordinador['apellido']); ?>
                                    </h3>
                                    <p class="text-gray-500 text-sm mt-1">
                                        Experiencia: <?php echo htmlspecialchars($coordinador['experiencia']); ?> años
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Cerrar Sesión
                                </button>
                                <button class="px-4 py-2 text-sm font-medium text-white bg-darkblue border border-transparent rounded-md hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                                    Perfil
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-16 text-center bg-white p-8 rounded-lg shadow-sm border border-gray-200">
                        <h2 class="text-2xl font-bold text-gray-800">Panel de Coordinación de Horarios</h2>
                        <p class="text-gray-500 mt-2 mb-8 max-w-xl mx-auto">Gestione y asigne horarios para los docentes y grupos de forma manual o deje que el sistema los genere automáticamente.</p>
                        <div class="flex justify-center space-x-4">
                            <button class="px-8 py-3 font-semibold text-darkblue bg-white border-2 border-darkblue rounded-lg hover:bg-gray-50 transition-colors">
                                Asignar Manual
                            </button>
                            <button class="px-8 py-3 font-semibold text-white bg-darkblue rounded-lg hover:bg-opacity-90 transition-colors">
                                Generar Automático
                            </button>
                        </div>
                    </div>

                </div>
            </section>
        </main>
    </div>

    <script src="/js/menu.js"></script>
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