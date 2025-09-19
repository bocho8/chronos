<?php
/**
 * Dashboard del Docente
 * Panel de control para gestión de disponibilidad y visualización de horarios
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Docente.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('dashboard.php');

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and docente role
AuthHelper::requireRole('DOCENTE');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Load docente-specific data according to ESRE requirements
$currentUser = AuthHelper::getCurrentUser();
$docenteId = $currentUser['id_usuario'] ?? null;

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // Get docente information
    $docenteQuery = "SELECT d.*, u.nombre, u.apellido, u.email, u.telefono 
                     FROM docente d 
                     INNER JOIN usuario u ON d.id_usuario = u.id_usuario 
                     WHERE d.id_usuario = :id_usuario";
    $stmt = $database->getConnection()->prepare($docenteQuery);
    $stmt->bindParam(':id_usuario', $docenteId, PDO::PARAM_INT);
    $stmt->execute();
    $docenteInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $docenteIdForQuery = $docenteInfo['id_docente'] ?? 0;
    
    // Check if availability is registered
    $availabilityQuery = "SELECT COUNT(*) FROM disponibilidad WHERE id_docente = :id_docente";
    $stmt = $database->getConnection()->prepare($availabilityQuery);
    $stmt->bindParam(':id_docente', $docenteIdForQuery, PDO::PARAM_INT);
    $stmt->execute();
    $hasAvailability = $stmt->fetchColumn() > 0;
    
    // Get schedule statistics
    $scheduleQuery = "SELECT COUNT(*) FROM horario WHERE id_docente = :id_docente";
    $stmt = $database->getConnection()->prepare($scheduleQuery);
    $stmt->bindParam(':id_docente', $docenteIdForQuery, PDO::PARAM_INT);
    $stmt->execute();
    $hasPublishedSchedule = $stmt->fetchColumn() > 0;
    
} catch (Exception $e) {
    error_log("Error cargando datos del docente: " . $e->getMessage());
    $docenteInfo = null;
    $hasAvailability = false;
    $hasPublishedSchedule = false;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — Dashboard Docente</title>
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
                    <?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_teacher'); ?>)
                </div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
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
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                                </svg>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 713-3h4a3 3 0 713 3v1"></path>
                                </svg>
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <section class="flex-1 px-6 py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('teacher_dashboard'); ?></h2>
                        <p class="text-muted mb-6 text-base">Panel de control para docentes - Gestión de disponibilidad y visualización de horarios</p>
                    </div>

                    <!-- Estado de Disponibilidad y Horarios -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 712-2h2a2 2 0 712 2m-6 9l2 2 4-4"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Estado de Disponibilidad</p>
                                    <p class="text-2xl font-semibold <?php echo $hasAvailability ? 'text-green-600' : 'text-orange-600'; ?>">
                                        <?php echo $hasAvailability ? 'Registrada' : 'Pendiente'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Horario Publicado</p>
                                    <p class="text-2xl font-semibold <?php echo $hasPublishedSchedule ? 'text-green-600' : 'text-gray-600'; ?>">
                                        <?php echo $hasPublishedSchedule ? 'Disponible' : 'Pendiente'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Funciones de Docente -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6 mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Mis Funciones</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <a href="mi-disponibilidad.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 712-2h2a2 2 0 712 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Gestionar Mi Disponibilidad</p>
                                    <p class="text-sm text-gray-500">Registrar horarios disponibles y observaciones</p>
                                </div>
                            </a>
                            
                            <a href="mi-horario.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 712-2h4a2 2 0 712 2v4m-6 0V3a2 2 0 712-2h4a2 2 0 712 2v4M7 7h10l4 10v4a1 1 0 71-1 1H4a1 1 0 71-1-1v-4L7 7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Ver Mi Horario</p>
                                    <p class="text-sm text-gray-500">Consultar mis horarios de clases</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <?php if ($docenteInfo): ?>
                    <!-- Información del docente -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Mi Información</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Datos Personales:</h4>
                                <ul class="space-y-2 text-sm">
                                    <li><span class="font-medium">Nombre:</span> <?php echo htmlspecialchars($docenteInfo['nombre'] . ' ' . $docenteInfo['apellido']); ?></li>
                                    <li><span class="font-medium">Email:</span> <?php echo htmlspecialchars($docenteInfo['email']); ?></li>
                                    <li><span class="font-medium">Teléfono:</span> <?php echo htmlspecialchars($docenteInfo['telefono'] ?? 'No registrado'); ?></li>
                                </ul>
                            </div>
                            
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Información Académica:</h4>
                                <ul class="space-y-2 text-sm">
                                    <li><span class="font-medium">Horas Asignadas:</span> <?php echo $docenteInfo['horas_asignadas'] ?? 0; ?> horas</li>
                                    <li><span class="font-medium">Porcentaje de Margen:</span> 
                                        <span class="<?php echo ($docenteInfo['porcentaje_margen'] ?? 0) > 80 ? 'text-green-600' : 'text-orange-600'; ?>">
                                            <?php echo $docenteInfo['porcentaje_margen'] ?? 0; ?>%
                                        </span>
                                    </li>
                                    <li><span class="font-medium">Otro Liceo:</span> <?php echo ($docenteInfo['trabaja_otro_liceo'] ?? false) ? 'Sí' : 'No'; ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <?php if (($docenteInfo['horas_asignadas'] ?? 0) > 16): ?>
                        <div class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                            <p class="text-sm text-orange-800">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <strong>Nota:</strong> Con más de 16 horas asignadas, debe presentar constancias de trabajo en otra institución y actividades justificadas.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
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