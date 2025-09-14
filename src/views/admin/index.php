<?php
// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Docente.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Load database configuration and get teachers
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $docenteModel = new Docente($database->getConnection());
    $docentes = $docenteModel->getAllDocentes();
    
    if ($docentes === false) {
        $docentes = [];
        $error_message = 'Error cargando lista de docentes';
    }
} catch (Exception $e) {
    error_log("Error cargando docentes: " . $e->getMessage());
    $docentes = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('teachers'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <style type="text/css">
        .hamburger span {
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 3px 0;
            border-radius: 2px;
            transition: all 0.3s;
        }
        .avatar::after {
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .list-item:nth-child(1) .avatar::after {
            content: "JP";
        }
        .list-item:nth-child(2) .avatar::after {
            content: "AG";
        }
        .list-item:nth-child(3) .avatar::after {
            content: "LR";
        }
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
        
        /* Modal animations */
        #docenteModal {
            transition: all 0.3s ease-in-out;
            display: flex !important;
            align-items: center;
            justify-content: center;
            z-index: 9999 !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            background-color: rgba(0, 0, 0, 0.2) !important;
        }
        
        /* Ensure modal content is visible and above everything */
        #docenteModal .modal-content {
            background: white !important;
            z-index: 10000 !important;
            position: relative !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        }
        
        /* Ensure all elements inside modal are visible */
        #docenteModal * {
            z-index: 10001 !important;
            position: relative !important;
        }
        
        /* Specific styles for buttons to ensure visibility */
        #docenteModal button[type="submit"],
        #docenteModal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
            background-color: #3b82f6 !important;
            color: white !important;
            border: none !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            font-weight: 500 !important;
        }
        
        #docenteModal button[type="button"] {
            background-color: #6b7280 !important;
        }
        
        #docenteModal button[type="button"]:hover {
            background-color: #4b5563 !important;
        }
        
        #docenteModal button[type="submit"]:hover {
            background-color: #2563eb !important;
        }
        
        #docenteModal.hidden {
            display: none !important;
            opacity: 0;
            visibility: hidden;
        }
        
        #docenteModal:not(.hidden) {
            opacity: 1;
            visibility: visible;
        }
        
        #docenteModal .modal-content {
            transform: scale(0.9) translateY(-20px);
            transition: all 0.3s ease-in-out;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        #docenteModal:not(.hidden) .modal-content {
            transform: scale(1) translateY(0);
        }
        
        /* Form focus styles */
        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Ensure modal is above everything */
        #docenteModal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }
        
        /* Remove pseudo-element that might be causing issues */
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-sidebar border-r border-border">
    <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
        <img src="/upload/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
        <span class="text-white font-semibold text-lg"><?php _e('scuola_italiana'); ?></span>
    </div>

    <ul class="py-5 list-none">
        <li>
            <a href="index.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
                <span class="w-2 h-2 bg-gray-500 rounded-full mr-3"></span>
                <?php _e('teachers'); ?>
            </a>
        </li>
        <li>
            <a href="admin-coordinadores.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                <span class="w-2 h-2 bg-gray-500 rounded-full mr-3"></span>
                <?php _e('coordinators'); ?>
            </a>
        </li>
        <li>
            <a href="admin-materias.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                <span class="w-2 h-2 bg-gray-500 rounded-full mr-3"></span>
                <?php _e('subjects'); ?>
            </a>
        </li>
        <li>
            <a href="admin-horarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                <span class="w-2 h-2 bg-gray-500 rounded-full mr-3"></span>
                <?php _e('schedules'); ?>
            </a>
        </li>
    </ul>
</aside>

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                
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
                                <div class="text-gray-500"><?php _e('role_admin'); ?></div>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
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
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('teacher_records'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('teacher_list_description'); ?></p>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-medium text-darktext"><?php _e('registered_teachers'); ?></h3>
                            <div class="flex gap-2">
                                <button class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <?php _e('filter'); ?>
                                </button>
                                <button class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50">
                                    <?php _e('export'); ?>
                                </button>
                                <button class="py-2 px-4 border border-red-300 rounded cursor-pointer font-medium transition-all text-sm bg-red-50 text-red-600 hover:bg-red-100 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <?php _e('delete_selected'); ?>
                                </button>
                                <button class="py-2 px-4 border-none rounded cursor-pointer font-medium transition-all text-sm bg-darkblue text-white hover:bg-navy flex items-center" onclick="showAddDocenteModal()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <?php _e('add_teacher'); ?>
                                </button>
                            </div>
                        </div>

                        <?php if (isset($error_message)): ?>
                            <div class="p-4 text-center text-red-600 bg-red-50">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php elseif (empty($docentes)): ?>
                            <div class="p-8 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                                <p class="text-lg font-medium"><?php _e('no_teachers_found'); ?></p>
                                <p class="text-sm"><?php _e('add_first_teacher'); ?></p>
                            </div>
                        <?php else: ?>
                        <div class="divide-y divide-gray-200">
                                <?php foreach ($docentes as $docente): ?>
                                    <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg group">
                                <div class="flex items-center">
                                            <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                                                <?php echo strtoupper(substr($docente['nombre'], 0, 1) . substr($docente['apellido'], 0, 1)); ?>
                                            </div>
                                    <div class="meta">
                                                <div class="font-semibold text-darktext mb-1">
                                                    <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                                    </div>
                                                <div class="text-muted text-sm">
                                                    C.I: <?php echo htmlspecialchars($docente['cedula']); ?>
                                                    <?php if (!empty($docente['email'])): ?>
                                                        • <?php echo htmlspecialchars($docente['email']); ?>
                                                    <?php endif; ?>
                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <?php if ($docente['trabaja_otro_liceo']): ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">
                                                            <?php _e('works_other_school'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($docente['horas_asignadas'] > 0): ?>
                                                        <span class="ml-2"><?php echo $docente['horas_asignadas']; ?>h</span>
                                                    <?php endif; ?>
                                    </div>
                                </div>
                                    </div>
                                        <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors" 
                                                    onclick="editDocente(<?php echo $docente['id_docente']; ?>)" 
                                                    title="<?php _e('edit'); ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors" 
                                                    onclick="deleteDocente(<?php echo $docente['id_docente']; ?>, '<?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>')" 
                                                    title="<?php _e('delete'); ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                </div>
                            </article>
                                <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal para agregar/editar docente -->
    <div id="docenteModal" class="fixed inset-0 bg-gray-900 bg-opacity-30 h-full w-full hidden z-[9999] flex items-center justify-center p-4 backdrop-blur-md">
        <div class="modal-content relative mx-auto p-6 border w-96 shadow-xl rounded-lg bg-white max-h-[90vh] overflow-y-auto">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle"><?php _e('add_teacher'); ?></h3>
                    <button onclick="closeDocenteModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <form id="docenteForm" class="space-y-4">
                    <input type="hidden" id="docenteId" name="id_docente">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php _e('ci_label'); ?></label>
                        <input type="text" id="cedula" name="cedula" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="<?php _e('ci_placeholder'); ?>">
                        <div class="text-red-500 text-xs mt-1" id="cedulaError"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php _e('name'); ?></label>
                        <input type="text" id="nombre" name="nombre" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="<?php _e('name_placeholder'); ?>">
                        <div class="text-red-500 text-xs mt-1" id="nombreError"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php _e('lastname'); ?></label>
                        <input type="text" id="apellido" name="apellido" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="<?php _e('lastname_placeholder'); ?>">
                        <div class="text-red-500 text-xs mt-1" id="apellidoError"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php _e('email'); ?></label>
                        <input type="email" id="email" name="email" 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="<?php _e('email_placeholder'); ?>">
                        <div class="text-red-500 text-xs mt-1" id="emailError"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php _e('phone'); ?></label>
                        <input type="text" id="telefono" name="telefono" 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="<?php _e('phone_placeholder'); ?>">
                        <div class="text-red-500 text-xs mt-1" id="telefonoError"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php _e('password'); ?></label>
                        <input type="password" id="contrasena" name="contrasena" 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="<?php _e('password_placeholder'); ?>">
                        <div class="text-red-500 text-xs mt-1" id="contrasenaError"></div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="trabaja_otro_liceo" name="trabaja_otro_liceo" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="trabaja_otro_liceo" class="ml-2 block text-sm text-gray-700">
                            <?php _e('works_other_school'); ?>
                        </label>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeDocenteModal()" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <?php _e('cancel'); ?>
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span id="submitButtonText"><?php _e('add_teacher'); ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let isEditMode = false;
        let currentDocenteId = null;

        // Funcionalidad para la barra lateral
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener todos los enlaces de la barra lateral
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            
            // Función para manejar el clic en los enlaces
            function handleSidebarClick(event) {
                // Remover la clase active de todos los enlaces
                sidebarLinks.forEach(link => {
                    link.classList.remove('active');
                });
                
                // Agregar la clase active al enlace clickeado
                this.classList.add('active');
            }
            
            // Agregar event listener a cada enlace
            sidebarLinks.forEach(link => {
                link.addEventListener('click', handleSidebarClick);
            });
            
            // Logout functionality
            const logoutButton = document.getElementById('logoutButton');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Show confirmation dialog
                    const confirmMessage = '<?php _e('logout_confirm'); ?>';
                    if (confirm(confirmMessage)) {
                        // Create form and submit logout request
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/src/controllers/LogoutController.php';
                        
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'logout';
                        
                        form.appendChild(actionInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
            
            // User menu toggle
            const userMenuButton = document.getElementById('userMenuButton');
            const userMenu = document.getElementById('userMenu');
            
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
            
            // Form submission
            const docenteForm = document.getElementById('docenteForm');
            if (docenteForm) {
                docenteForm.addEventListener('submit', handleFormSubmit);
            }
        });

        // Mostrar modal para agregar docente
        function showAddDocenteModal() {
            isEditMode = false;
            currentDocenteId = null;
            
            // Reset form
            document.getElementById('docenteForm').reset();
            clearErrors();
            
            // Update modal title and button
            document.getElementById('modalTitle').textContent = '<?php _e('add_teacher'); ?>';
            document.getElementById('submitButtonText').textContent = '<?php _e('add_teacher'); ?>';
            
            // Show modal with animation
            const modal = document.getElementById('docenteModal');
            modal.style.display = 'flex';
            
            // Force reflow to ensure display change is applied
            modal.offsetHeight;
            
            // Remove hidden class for animation
            setTimeout(() => {
                modal.classList.remove('hidden');
            }, 10);
            
            // Focus on first input
            setTimeout(() => {
                document.getElementById('cedula').focus();
            }, 300);
        }

        // Mostrar modal para editar docente
        function editDocente(id) {
            isEditMode = true;
            currentDocenteId = id;
            
            // Fetch docente data
            fetch(`/src/controllers/docente_handler.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form
                        document.getElementById('docenteId').value = data.data.id_docente;
                        document.getElementById('cedula').value = data.data.cedula;
                        document.getElementById('nombre').value = data.data.nombre;
                        document.getElementById('apellido').value = data.data.apellido;
                        document.getElementById('email').value = data.data.email || '';
                        document.getElementById('telefono').value = data.data.telefono || '';
                        document.getElementById('trabaja_otro_liceo').checked = data.data.trabaja_otro_liceo;
                        
                        // Update modal title and button
                        document.getElementById('modalTitle').textContent = '<?php _e('edit_teacher'); ?>';
                        document.getElementById('submitButtonText').textContent = '<?php _e('update'); ?>';
                        
                        // Show modal
                        const modal = document.getElementById('docenteModal');
                        modal.style.display = 'flex';
                        
                        // Force reflow to ensure display change is applied
                        modal.offsetHeight;
                        
                        // Remove hidden class for animation
                        setTimeout(() => {
                            modal.classList.remove('hidden');
                        }, 10);
                        
                        // Focus on first input
                        setTimeout(() => {
                            document.getElementById('cedula').focus();
                        }, 300);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error cargando datos del docente');
                });
        }

        // Eliminar docente
        function deleteDocente(id, nombre) {
            const confirmMessage = `¿Está seguro de que desea eliminar al docente "${nombre}"?`;
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id_docente', id);
                
                fetch('/src/controllers/docente_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Docente eliminado exitosamente');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error eliminando docente');
                });
            }
        }

        // Cerrar modal
        function closeDocenteModal() {
            const modal = document.getElementById('docenteModal');
            
            // Add hidden class for animation
            modal.classList.add('hidden');
            
            // Hide modal after animation completes
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            
            clearErrors();
        }

        // Manejar envío del formulario
        function handleFormSubmit(e) {
            e.preventDefault();
            
            clearErrors();
            
            const formData = new FormData(e.target);
            formData.append('action', isEditMode ? 'update' : 'create');
            
            fetch('/src/controllers/docente_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                    
                    if (data.success) {
                        alert(data.message);
                        closeDocenteModal();
                        location.reload();
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
                            alert('Error: ' + data.message);
                        }
                    }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error procesando solicitud');
            });
        }

        // Limpiar errores de validación
        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('docenteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDocenteModal();
            }
        });

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDocenteModal();
            }
        });
    </script>
</body>
</html