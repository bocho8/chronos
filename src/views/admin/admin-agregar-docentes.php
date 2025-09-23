<?php
/**
 * Vista para Agregar Docentes - Funciones de Dirección
 * Según ESRE 4.3: Solo la Dirección puede añadir nuevos docentes
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Docente.php';
require_once __DIR__ . '/../../models/Usuario.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-agregar-docentes.php');

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
        $docenteModel = new Docente($database->getConnection());
        $usuarioModel = new Usuario($database->getConnection());
        
        if ($_POST['action'] === 'add_teacher') {
            // Validate input
            $cedula = trim($_POST['cedula'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            
            // Validation
            if (empty($cedula)) {
                $errors['cedula'] = 'La cédula es requerida';
            } elseif (!preg_match('/^\d{7,8}$/', $cedula)) {
                $errors['cedula'] = 'La cédula debe tener 7 u 8 dígitos';
            }
            
            if (empty($nombre)) {
                $errors['nombre'] = 'El nombre es requerido';
            }
            
            if (empty($apellido)) {
                $errors['apellido'] = 'El apellido es requerido';
            }
            
            if (empty($email)) {
                $errors['email'] = 'El email es requerido';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'El formato del email no es válido';
            }
            
            if (empty($password)) {
                $errors['password'] = 'La contraseña es requerida';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
            }
            
            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = 'Las contraseñas no coinciden';
            }
            
            // Check if user already exists
            if (empty($errors)) {
                $existingUser = $usuarioModel->getUserByCedula($cedula);
                if ($existingUser) {
                    $errors['cedula'] = 'Ya existe un usuario con esta cédula';
                }
                
                $existingEmail = $usuarioModel->getUserByEmail($email);
                if ($existingEmail) {
                    $errors['email'] = 'Ya existe un usuario con este email';
                }
            }
            
            if (empty($errors)) {
                // Create teacher
                $result = $docenteModel->createTeacher($cedula, $nombre, $apellido, $email, $telefono, $password);
                
                if ($result) {
                    $message = 'Docente agregado exitosamente';
                    $messageType = 'success';
                    
                    // Log the action
                    $database->query("INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())", [
                        $_SESSION['user']['id_usuario'],
                        "Agregó nuevo docente: $nombre $apellido (CI: $cedula)"
                    ]);
                    
                    // Clear form data
                    $_POST = [];
                } else {
                    $message = 'Error al agregar el docente';
                    $messageType = 'error';
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error adding teacher: " . $e->getMessage());
        $message = 'Error interno del servidor';
        $messageType = 'error';
    }
}

// Load recent teachers
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    $docenteModel = new Docente($database->getConnection());
    
    $recentTeachers = $docenteModel->getRecentTeachers(5);
    
    if ($recentTeachers === false) {
        $recentTeachers = [];
    }
    
} catch (Exception $e) {
    error_log("Error loading recent teachers: " . $e->getMessage());
    $recentTeachers = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('add_new_teachers'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
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
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .teacher-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .teacher-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('add_new_teachers'); ?></div>
                
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
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('add_new_teachers'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('add_teachers_description'); ?></p>
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
                        <!-- Add Teacher Form -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Agregar Nuevo Docente</h3>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="add_teacher">
                                
                                <div class="form-group">
                                    <label for="cedula" class="form-label">Cédula de Identidad *</label>
                                    <input 
                                        type="text" 
                                        id="cedula" 
                                        name="cedula" 
                                        class="form-input" 
                                        placeholder="12345678"
                                        value="<?php echo htmlspecialchars($_POST['cedula'] ?? ''); ?>"
                                        required
                                    >
                                    <?php if (isset($errors['cedula'])): ?>
                                        <div class="form-error"><?php echo htmlspecialchars($errors['cedula']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="nombre" class="form-label">Nombre *</label>
                                        <input 
                                            type="text" 
                                            id="nombre" 
                                            name="nombre" 
                                            class="form-input" 
                                            placeholder="Juan"
                                            value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                            required
                                        >
                                        <?php if (isset($errors['nombre'])): ?>
                                            <div class="form-error"><?php echo htmlspecialchars($errors['nombre']); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="apellido" class="form-label">Apellido *</label>
                                        <input 
                                            type="text" 
                                            id="apellido" 
                                            name="apellido" 
                                            class="form-input" 
                                            placeholder="Pérez"
                                            value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>"
                                            required
                                        >
                                        <?php if (isset($errors['apellido'])): ?>
                                            <div class="form-error"><?php echo htmlspecialchars($errors['apellido']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">Email *</label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        class="form-input" 
                                        placeholder="juan.perez@ejemplo.com"
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                        required
                                    >
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="form-error"><?php echo htmlspecialchars($errors['email']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input 
                                        type="text" 
                                        id="telefono" 
                                        name="telefono" 
                                        class="form-input" 
                                        placeholder="099123456"
                                        value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                                    >
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="password" class="form-label">Contraseña *</label>
                                        <input 
                                            type="password" 
                                            id="password" 
                                            name="password" 
                                            class="form-input" 
                                            placeholder="Mínimo 6 caracteres"
                                            required
                                        >
                                        <?php if (isset($errors['password'])): ?>
                                            <div class="form-error"><?php echo htmlspecialchars($errors['password']); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">Confirmar Contraseña *</label>
                                        <input 
                                            type="password" 
                                            id="confirm_password" 
                                            name="confirm_password" 
                                            class="form-input" 
                                            placeholder="Repetir contraseña"
                                            required
                                        >
                                        <?php if (isset($errors['confirm_password'])): ?>
                                            <div class="form-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                                        <?php endif; ?>
                                    </div>
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
                                        Agregar Docente
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Recent Teachers -->
                        <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Docentes Recientes</h3>
                            
                            <?php if (empty($recentTeachers)): ?>
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No hay docentes registrados</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recentTeachers as $teacher): ?>
                                        <div class="teacher-card bg-gray-50 rounded-lg p-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-blue-600 font-semibold">
                                                        <?php echo strtoupper(substr($teacher['nombre'], 0, 1) . substr($teacher['apellido'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($teacher['nombre'] . ' ' . $teacher['apellido']); ?>
                                                    </h4>
                                                    <p class="text-sm text-gray-500">
                                                        CI: <?php echo htmlspecialchars($teacher['cedula']); ?>
                                                    </p>
                                                    <p class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($teacher['email']); ?>
                                                    </p>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    <?php echo date('d/m/Y', strtotime($teacher['fecha_creacion'] ?? 'now')); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-4 text-center">
                                    <a href="admin-docentes.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Ver todos los docentes →
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
        function clearForm() {
            document.querySelector('form').reset();
        }

        function logout() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        }
    </script>
</body>
</html>
