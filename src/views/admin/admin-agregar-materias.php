<?php
/**
 * Vista para Agregar Materias - Funciones de Dirección
 * Según ESRE 4.3: Solo la Dirección puede añadir nuevas materias
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Materia.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role (acting as director)
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Handle form submission
$message = '';
$messageType = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $dbConfig = require __DIR__ . '/../../config/database.php';
        $database = new Database($dbConfig);
        $materiaModel = new Materia($database->getConnection());
        
        if ($_POST['action'] === 'add_subject') {
            // Validate input
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $horasSemanales = intval($_POST['horas_semanales'] ?? 0);
            $idPautaAnep = !empty($_POST['id_pauta_anep']) ? intval($_POST['id_pauta_anep']) : null;
            $compartidaConOtra = isset($_POST['compartida_con_otra']) ? 1 : 0;
            $idGrupoCompartido = !empty($_POST['id_grupo_compartido']) ? intval($_POST['id_grupo_compartido']) : null;
            
            // Validation
            if (empty($nombre)) {
                $errors['nombre'] = 'El nombre de la materia es requerido';
            }
            
            if ($horasSemanales <= 0) {
                $errors['horas_semanales'] = 'Las horas semanales deben ser mayor a 0';
            }
            
            if ($compartidaConOtra && empty($idGrupoCompartido)) {
                $errors['id_grupo_compartido'] = 'Debe seleccionar un grupo cuando la materia es compartida';
            }
            
            // The createMateria method will check if subject already exists
            
            if (empty($errors)) {
                try {
                    // Create subject
                    $materiaData = [
                        'nombre' => $nombre,
                        'horas_semanales' => $horasSemanales,
                        'id_pauta_anep' => $idPautaAnep,
                        'en_conjunto' => $compartidaConOtra,
                        'id_grupo_compartido' => $idGrupoCompartido
                    ];
                    $result = $materiaModel->createMateria($materiaData);
                    
                    if ($result) {
                        $message = 'Materia agregada exitosamente';
                        $messageType = 'success';
                        
                        // Log the action
                        $database->query("INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())", [
                            $_SESSION['user']['id_usuario'],
                            "Agregó nueva materia: $nombre ($horasSemanales horas semanales)"
                        ]);
                        
                        // Clear form data
                        $_POST = [];
                    }
                } catch (Exception $createException) {
                    if (strpos($createException->getMessage(), 'Ya existe') !== false) {
                        $errors['nombre'] = 'Ya existe una materia con este nombre';
                    } else {
                        $message = 'Error al agregar la materia: ' . $createException->getMessage();
                        $messageType = 'error';
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error adding subject: " . $e->getMessage());
        $message = 'Error interno del servidor';
        $messageType = 'error';
    }
}

// Load related data
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $materiaModel = new Materia($database->getConnection());
    
    $pautasAnep = $materiaModel->getAllPautasAnep();
    $grupos = $materiaModel->getAllGrupos();
    $recentSubjects = $materiaModel->getRecentMaterias(5);
    
    if ($pautasAnep === false) {
        $pautasAnep = [];
    }
    if ($grupos === false) {
        $grupos = [];
    }
    if ($recentSubjects === false) {
        $recentSubjects = [];
    }
    
} catch (Exception $e) {
    error_log("Error loading related data: " . $e->getMessage());
    $pautasAnep = [];
    $grupos = [];
    $recentSubjects = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('add_new_subjects'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
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
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .form-checkbox {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }
        .subject-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .subject-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .hours-badge {
            background-color: #3b82f6;
            color: white;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-sidebar border-r border-border">
            <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
                <img src="/assets/images/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
                <span class="text-white font-semibold text-lg"><?php _e('scuola_italiana'); ?></span>
            </div>

            <ul class="py-5 list-none">
                <!-- Dashboard Principal -->
                <li>
                    <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6a2 2 0 01-2 2H10a2 2 0 01-2-2V5z"></path>
                        </svg>
                        <?php _e('dashboard'); ?>
                    </a>
                </li>
                
                <!-- Gestión Administrativa -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('administration'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-usuarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <?php _e('users'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-docentes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <?php _e('teachers'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-coordinadores.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <?php _e('coordinators'); ?>
                    </a>
                </li>
                
                <!-- Gestión Académica -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('academic_management'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-materias.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <?php _e('subjects'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-horarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php _e('schedules'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-grupos.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <?php _e('groups'); ?>
                    </a>
                </li>
                
                <!-- Funciones de Dirección -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('director_functions'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-publicar-horarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        <?php _e('publish_schedules'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-agregar-docentes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        <?php _e('add_new_teachers'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-agregar-materias.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <?php _e('add_new_subjects'); ?>
                    </a>
                </li>
                
                <!-- Funciones de Coordinador -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('coordinator_functions'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-disponibilidad.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <?php _e('teacher_availability'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-asignaciones.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <?php _e('subject_assignments'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-reportes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <?php _e('reports'); ?>
                    </a>
                </li>
                
                <!-- Funciones de Docente -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('teacher_functions'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-mi-horario.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V3a2 2 0 012-2h4a2 2 0 012 2v4M7 7h10l4 10v4a1 1 0 01-1 1H4a1 1 0 01-1-1v-4L7 7z"></path>
                        </svg>
                        <?php _e('my_schedule'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-mi-disponibilidad.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php _e('my_availability'); ?>
                    </a>
                </li>
                
                <!-- Funciones de Padre -->
                <li class="mt-4">
                    <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php _e('parent_functions'); ?>
                    </div>
                </li>
                <li>
                    <a href="admin-estudiantes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                        </svg>
                        <?php _e('students'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-horarios-estudiante.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <?php _e('student_schedules'); ?>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('add_new_subjects'); ?></div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                                <div class="text-gray-500"><?php _e('role_admin'); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" onclick="logout()">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <section class="flex-1 px-6 py-8">
                <div class="max-w-4xl mx-auto">
                    <!-- Page Header -->
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('add_new_subjects'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('add_subjects_description'); ?></p>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <?php if ($messageType === 'success'): ?>
                                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    <?php else: ?>
                                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Add Subject Form -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Agregar Nueva Materia</h3>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="add_subject">
                                
                                <div class="form-group">
                                    <label for="nombre" class="form-label">Nombre de la Materia *</label>
                                    <input 
                                        type="text" 
                                        id="nombre" 
                                        name="nombre" 
                                        class="form-input" 
                                        placeholder="Matemática"
                                        value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                        required
                                    >
                                    <?php if (isset($errors['nombre'])): ?>
                                        <div class="form-error"><?php echo htmlspecialchars($errors['nombre']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea 
                                        id="descripcion" 
                                        name="descripcion" 
                                        class="form-textarea" 
                                        placeholder="Descripción de la materia"
                                    ><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="horas_semanales" class="form-label">Horas Semanales *</label>
                                    <input 
                                        type="number" 
                                        id="horas_semanales" 
                                        name="horas_semanales" 
                                        class="form-input" 
                                        placeholder="4"
                                        min="1"
                                        max="40"
                                        value="<?php echo htmlspecialchars($_POST['horas_semanales'] ?? ''); ?>"
                                        required
                                    >
                                    <?php if (isset($errors['horas_semanales'])): ?>
                                        <div class="form-error"><?php echo htmlspecialchars($errors['horas_semanales']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="id_pauta_anep" class="form-label">Pauta de Inspección ANEP</label>
                                    <select id="id_pauta_anep" name="id_pauta_anep" class="form-select">
                                        <option value="">Seleccionar pauta (opcional)</option>
                                        <?php foreach ($pautasAnep as $pauta): ?>
                                            <option 
                                                value="<?php echo $pauta['id_pauta_anep']; ?>"
                                                <?php echo (isset($_POST['id_pauta_anep']) && $_POST['id_pauta_anep'] == $pauta['id_pauta_anep']) ? 'selected' : ''; ?>
                                            >
                                                <?php echo htmlspecialchars($pauta['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            name="compartida_con_otra" 
                                            class="form-checkbox"
                                            <?php echo (isset($_POST['compartida_con_otra']) && $_POST['compartida_con_otra']) ? 'checked' : ''; ?>
                                            onchange="toggleSharedGroup()"
                                        >
                                        <span class="form-label mb-0">Se imparte en conjunto con otra materia</span>
                                    </label>
                                </div>

                                <div class="form-group" id="shared-group" style="display: none;">
                                    <label for="id_grupo_compartido" class="form-label">Grupo con el que se comparte</label>
                                    <select id="id_grupo_compartido" name="id_grupo_compartido" class="form-select">
                                        <option value="">Seleccionar grupo</option>
                                        <?php foreach ($grupos as $grupo): ?>
                                            <option 
                                                value="<?php echo $grupo['id_grupo']; ?>"
                                                <?php echo (isset($_POST['id_grupo_compartido']) && $_POST['id_grupo_compartido'] == $grupo['id_grupo']) ? 'selected' : ''; ?>
                                            >
                                                <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['nivel']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['id_grupo_compartido'])): ?>
                                        <div class="form-error"><?php echo htmlspecialchars($errors['id_grupo_compartido']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex justify-end space-x-3 mt-6">
                                    <button 
                                        type="button" 
                                        onclick="clearForm()"
                                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        Cancelar
                                    </button>
                                    <button 
                                        type="submit" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Agregar Materia
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Recent Subjects -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Materias Recientes</h3>
                            
                            <?php if (empty($recentSubjects)): ?>
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No hay materias registradas</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recentSubjects as $subject): ?>
                                        <div class="subject-card bg-gray-50 rounded-lg p-4">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center mb-2">
                                                        <h4 class="font-medium text-gray-900 mr-3">
                                                            <?php echo htmlspecialchars($subject['nombre']); ?>
                                                        </h4>
                                                        <span class="hours-badge">
                                                            <?php echo $subject['horas_semanales']; ?>h
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($subject['descripcion'])): ?>
                                                        <p class="text-sm text-gray-600 mb-2">
                                                            <?php echo htmlspecialchars(substr($subject['descripcion'], 0, 100)); ?>
                                                            <?php echo strlen($subject['descripcion']) > 100 ? '...' : ''; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($subject['pauta_anep_nombre'])): ?>
                                                        <p class="text-xs text-gray-500">
                                                            <strong>Pauta ANEP:</strong> <?php echo htmlspecialchars($subject['pauta_anep_nombre']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-gray-400 ml-4">
                                                    <?php echo date('d/m/Y', strtotime($subject['fecha_creacion'] ?? 'now')); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-4 text-center">
                                    <a href="admin-materias.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Ver todas las materias →
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        function toggleSharedGroup() {
            const checkbox = document.querySelector('input[name="compartida_con_otra"]');
            const sharedGroup = document.getElementById('shared-group');
            
            if (checkbox.checked) {
                sharedGroup.style.display = 'block';
            } else {
                sharedGroup.style.display = 'none';
                document.getElementById('id_grupo_compartido').value = '';
            }
        }

        function clearForm() {
            document.querySelector('form').reset();
            document.getElementById('shared-group').style.display = 'none';
        }

        function logout() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        }

        // Initialize shared group visibility on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleSharedGroup();
        });
    </script>
</body>
</html>
