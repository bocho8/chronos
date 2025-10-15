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
require_once __DIR__ . '/../../models/Padre.php';
require_once __DIR__ . '/../../models/Grupo.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-asignacion-padres.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole(['ADMIN', 'DIRECTOR', 'COORDINADOR']);

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $padreModel = new Padre($database->getConnection());
    $grupoModel = new Grupo($database->getConnection());
    
    $padres = $padreModel->getAllParentsWithGroups();
    $grupos = $grupoModel->getAllGrupos();
    
    if ($padres === false) {
        $padres = [];
    }
    if ($grupos === false) {
        $grupos = [];
    }
} catch (Exception $e) {
    error_log("Error cargando datos de asignación: " . $e->getMessage());
    $padres = [];
    $grupos = [];
    $error_message = 'Error interno del servidor';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('parent_group_assignment'); ?></title>
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
        .group-tag {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .group-tag:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
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
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('parent_group_assignment'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('parent_group_assignment_description'); ?></p>
                    </div>

                    <!-- Assignment Form -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900"><?php _e('assign_groups_to_parent'); ?></h3>
                        </div>
                        <div class="p-4">
                            <form id="assignmentForm" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="parentSelect" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('select_parent'); ?> <span class="text-red-500">*</span></label>
                                        <select id="parentSelect" name="id_padre" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                            <option value=""><?php _e('select_parent'); ?></option>
                                            <?php foreach ($padres as $padre): ?>
                                                <option value="<?php echo $padre['id_padre']; ?>">
                                                    <?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="groupSelect" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('select_groups'); ?> <span class="text-red-500">*</span></label>
                                        <select id="groupSelect" name="id_grupos[]" multiple required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue">
                                            <?php foreach ($grupos as $grupo): ?>
                                                <option value="<?php echo $grupo['id_grupo']; ?>">
                                                    <?php echo htmlspecialchars($grupo['nombre'] . ' (' . $grupo['nivel'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="px-4 py-2 bg-darkblue text-white rounded-md hover:bg-navy transition-colors">
                                        <?php _e('assign_groups'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Parents List -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900"><?php _e('parents_and_groups'); ?></h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-4">
                                <?php if (!empty($padres)): ?>
                                    <?php foreach ($padres as $padre): ?>
                                        <div class="assignment-card p-4 border border-gray-200 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?></h4>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($padre['email']); ?></p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        <?php if (!empty($padre['grupos_asignados'])): ?>
                                                            <?php $gruposAsignados = explode(', ', $padre['grupos_asignados']); ?>
                                                            <?php foreach ($gruposAsignados as $grupo): ?>
                                                                <span class="group-tag"><?php echo htmlspecialchars(trim($grupo)); ?></span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-gray-500 text-sm"><?php _e('no_groups_assigned'); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button onclick="editParentAssignments(<?php echo $padre['id_padre']; ?>, '<?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?>')" 
                                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                                                        <?php _e('edit'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-8"><?php _e('no_parents_found'); ?></p>
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
            formData.append('action', 'assign_groups');
            
            fetch('/src/controllers/ParentAssignmentHandler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('<?php _e('groups_assigned_successfully'); ?>', 'success');
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

        function editParentAssignments(id_padre, nombre) {
            // TODO: Implement edit functionality
            showToast('Edit functionality coming soon', 'info');
        }
    </script>
</body>
</html>
