<?php
/**
 * Página de gestión de disponibilidad de docentes para administradores
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';
require_once __DIR__ . '/../../models/Docente.php';

// Initialize secure session
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-disponibilidad.php');

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

// Load database configuration and get data
try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    // Get models
    $horarioModel = new Horario($database->getConnection());
    $docenteModel = new Docente($database->getConnection());
    
    // Get data
    $docentes = $docenteModel->getAllDocentes();
    $bloques = $horarioModel->getAllBloques();
    
    // Get selected docente if any
    $selectedDocenteId = $_GET['docente'] ?? null;
    $selectedDocente = null;
    $disponibilidad = [];
    
    if ($selectedDocenteId) {
        foreach ($docentes as $docente) {
            if ($docente['id_docente'] == $selectedDocenteId) {
                $selectedDocente = $docente;
                break;
            }
        }
        if ($selectedDocente) {
            $disponibilidad = $horarioModel->getDocenteDisponibilidad($selectedDocenteId);
        }
    }
    
} catch (Exception $e) {
    error_log("Error cargando disponibilidad: " . $e->getMessage());
    $docentes = [];
    $bloques = [];
    $disponibilidad = [];
    $error_message = 'Error interno del servidor';
}

// Function to get user initials
function getUserInitials($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('teacher_availability'); ?></title>
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
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                
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
                                <div class="text-gray-500"><?php _e('role_admin'); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                                <span class="inline mr-2 text-xs">👤</span>
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                                <span class="inline mr-2 text-xs">👤</span>
                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php _e('no_teacher_selected'); ?></h3>
                        <p class="text-gray-500"><?php _e('select_teacher_to_manage_availability'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        function loadDocenteDisponibilidad() {
            const docenteId = document.getElementById('docenteSelect').value;
            if (docenteId) {
                window.location.href = `admin-disponibilidad.php?docente=${docenteId}`;
            }
        }

        function toggleDisponibilidad(cell) {
            const docente = cell.dataset.docente;
            const bloque = cell.dataset.bloque;
            const dia = cell.dataset.dia;
            const currentState = cell.dataset.disponible === 'true';
            const newState = !currentState;
            
            // Update UI immediately
            cell.dataset.disponible = newState.toString();
            cell.className = cell.className.replace(currentState ? 'disponible' : 'no-disponible', 
                                                   newState ? 'disponible' : 'no-disponible');
            cell.textContent = newState ? 'Disponible' : 'No disponible';
            
            // Save to database
            const formData = new FormData();
            formData.append('action', 'update_disponibilidad');
            formData.append('id_docente', docente);
            formData.append('id_bloque', bloque);
            formData.append('dia', dia);
            formData.append('disponible', newState);
            
            fetch('/src/controllers/horario_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert UI changes if save failed
                    cell.dataset.disponible = currentState.toString();
                    cell.className = cell.className.replace(newState ? 'disponible' : 'no-disponible', 
                                                           currentState ? 'disponible' : 'no-disponible');
                    cell.textContent = currentState ? 'Disponible' : 'No disponible';
                    alert('Error actualizando disponibilidad: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert UI changes
                cell.dataset.disponible = currentState.toString();
                cell.className = cell.className.replace(newState ? 'disponible' : 'no-disponible', 
                                                       currentState ? 'disponible' : 'no-disponible');
                cell.textContent = currentState ? 'Disponible' : 'No disponible';
                alert('Error de conexión');
            });
        }

        function saveAllDisponibilidad() {
            alert('Cambios guardados automáticamente al hacer clic en cada celda');
        }

        // Logout functionality
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });
    </script>
</body>
</html>
