<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Materia.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-agregar-materias.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$message = '';
$messageType = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $dbConfig = require __DIR__ . '/../../config/database.php';
        $database = new Database($dbConfig);
        $materiaModel = new Materia($database->getConnection());
        
        if ($_POST['action'] === 'add_subject') {
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $horasSemanales = intval($_POST['horas_semanales'] ?? 0);
            $idPautaAnep = !empty($_POST['id_pauta_anep']) ? intval($_POST['id_pauta_anep']) : null;
            $compartidaConOtra = isset($_POST['compartida_con_otra']) ? 1 : 0;
            $idGrupoCompartido = !empty($_POST['id_grupo_compartido']) ? intval($_POST['id_grupo_compartido']) : null;

            if (empty($nombre)) {
                $errors['nombre'] = 'El nombre de la materia es requerido';
            }
            
            if ($horasSemanales <= 0) {
                $errors['horas_semanales'] = 'Las horas semanales deben ser mayor a 0';
            }
            
            if ($compartidaConOtra && empty($idGrupoCompartido)) {
                $errors['id_grupo_compartido'] = 'Debe seleccionar un grupo cuando la materia es compartida';
            }

            if (empty($errors)) {
                try {
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

                        $database->query("INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())", [
                            $_SESSION['user']['id_usuario'],
                            "Agregó nueva materia: $nombre ($horasSemanales horas semanales)"
                        ]);

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
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-xl font-semibold text-center"><?php _e('add_new_subjects'); ?></div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <span class="text-white text-sm">🔔</span>
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
                                <span class="inline mr-2 text-xs">👤</span>
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <span class="inline mr-2 text-xs">⚙</span>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" onclick="logout()">
                                <span class="inline mr-2 text-xs">🚪</span>
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
                                        <span class="text-gray-400 text-2xl">•</span>
                                    <?php else: ?>
                                        <span class="text-sm">📋</span>
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
                                        <span class="text-sm">📋</span>
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
                                    <span class="text-gray-400 text-2xl">•</span>
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

        document.addEventListener('DOMContentLoaded', function() {
            toggleSharedGroup();
        });
    </script>
</body>
</html>
