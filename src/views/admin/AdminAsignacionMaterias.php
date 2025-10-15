<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Grupo.php';
require_once __DIR__ . '/../../models/Materia.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-asignacion-materias.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole(['ADMIN', 'DIRECTOR', 'COORDINADOR']);

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $grupoModel = new Grupo($database->getConnection());
    $materiaModel = new Materia($database->getConnection());
    
    $grupos = $grupoModel->getAllGroupsWithSubjects();
    $materias = $materiaModel->getAllMaterias();
    
    if ($grupos === false) {
        $grupos = [];
    }
    if ($materias === false) {
        $materias = [];
    }
} catch (Exception $e) {
    error_log("Error cargando datos de asignación: " . $e->getMessage());
    $grupos = [];
    $materias = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('group_subject_assignment'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <script src="/js/toast.js"></script>
    <style>
        .assignment-card {
            transition: all 0.2s ease;
        }
        .assignment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .subject-tag {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .subject-tag:hover {
            background: linear-gradient(135deg, #059669, #047857);
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
                            <a href="/src/views/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><?php _e('logout'); ?></a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <div class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('group_subject_assignment'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('group_subject_assignment_description'); ?></p>
                    </div>

                    <!-- Assignment Form -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900"><?php _e('assign_subjects_to_group'); ?></h3>
                        </div>
                        <div class="p-4">
                            <form id="assignmentForm" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="groupSelect" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('select_group'); ?> <span class="text-red-500">*</span></label>
                                        <select id="groupSelect" name="id_grupo" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                            <option value=""><?php _e('select_group'); ?></option>
                                            <?php foreach ($grupos as $grupo): ?>
                                                <option value="<?php echo $grupo['id_grupo']; ?>">
                                                    <?php echo htmlspecialchars($grupo['nombre'] . ' (' . $grupo['nivel'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="subjectSelect" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('select_subjects'); ?> <span class="text-red-500">*</span></label>
                                        <select id="subjectSelect" name="id_materias[]" multiple required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                            <?php foreach ($materias as $materia): ?>
                                                <option value="<?php echo $materia['id_materia']; ?>" data-hours="<?php echo $materia['horas_semanales']; ?>">
                                                    <?php echo htmlspecialchars($materia['nombre'] . ' (' . $materia['horas_semanales'] . 'h/sem)'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="px-4 py-2 bg-darkblue text-white rounded-md hover:bg-navy transition-colors">
                                        <?php _e('assign_subjects'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Groups List -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900"><?php _e('groups_and_subjects'); ?></h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-4">
                                <?php if (!empty($grupos)): ?>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <div class="assignment-card p-4 border border-gray-200 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($grupo['nombre']); ?></h4>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($grupo['nivel']); ?></p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        <?php if (!empty($grupo['materias_asignadas'])): ?>
                                                            <?php $materiasAsignadas = explode(', ', $grupo['materias_asignadas']); ?>
                                                            <?php foreach ($materiasAsignadas as $materia): ?>
                                                                <span class="subject-tag"><?php echo htmlspecialchars(trim($materia)); ?></span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-gray-500 text-sm"><?php _e('no_subjects_assigned'); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button onclick="editGroupAssignments(<?php echo $grupo['id_grupo']; ?>, '<?php echo htmlspecialchars($grupo['nombre']); ?>')" 
                                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                                                        <?php _e('edit'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-8"><?php _e('no_groups_found'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script>
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'assign_subjects');
            
            fetch('/src/controllers/GroupSubjectHandler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('<?php _e('subjects_assigned_successfully'); ?>', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('<?php _e('error_processing_request'); ?>', 'error');
            });
        });

        function editGroupAssignments(id_grupo, nombre) {
            // TODO: Implement edit functionality
            showToast('Edit functionality coming soon', 'info');
        }
    </script>
</body>
</html>
