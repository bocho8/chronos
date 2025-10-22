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
require_once __DIR__ . '/../../models/Docente.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('coordinador-docentes.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('COORDINADOR');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$dbConfig = require __DIR__ . '/../../config/database.php';
$database = new Database($dbConfig);

$docenteModel = new Docente($database->getConnection());
$docentes = $docenteModel->getAllDocentes();

function getTeacherInitials($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('teachers'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <script src="/js/multiple-selection.js"></script>
    <script src="/js/status-labels.js"></script>
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
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            padding: 16px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            max-width: 400px;
            display: flex;
            align-items: center;
        }
        .toast.show {
            transform: translateX(0);
        }
        .toast.success {
            background-color: #10b981;
        }
        .toast.error {
            background-color: #ef4444;
        }
        .toast.warning {
            background-color: #f59e0b;
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="w-8"></div>
                
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block"><?php _e('teachers_management'); ?></div>
                <div class="text-white text-sm font-semibold text-center sm:hidden"><?php _e('teachers_management'); ?></div>
                
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
                                <div class="text-gray-500"><?php _e('role_coordinator'); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                                <span class="inline mr-2 text-xs">👤</span>
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                                <span class="inline mr-2 text-xs">⚙</span>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <span class="inline mr-2 text-xs">🚪</span>
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <section class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- Header Actions -->
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900"><?php _e('teachers_management'); ?></h1>
                            <p class="text-gray-600 mt-1"><?php _e('manage_teachers_description'); ?></p>
                        </div>
                        <button class="bg-darkblue text-white px-4 py-2 rounded-lg hover:bg-navy transition-colors" id="addTeacherBtn">
                            <span class="inline mr-2">➕</span>
                            <?php _e('add_teacher'); ?>
                        </button>
                    </div>

                    <!-- Teachers List -->
                    <div class="bg-white rounded-lg shadow-sm border border-lightborder">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-semibold text-gray-900"><?php _e('teachers_list'); ?></h2>
                                <div class="flex items-center space-x-2">
                                    <input type="text" id="searchInput" placeholder="<?php _e('search_teachers'); ?>" 
                                           class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-darkblue">
                                    <button class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                                        🔍
                                    </button>
                                </div>
                            </div>

                            <?php if (empty($docentes)): ?>
                                <div class="text-center py-12">
                                    <div class="text-gray-400 text-6xl mb-4">👨‍🏫</div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2"><?php _e('no_teachers_found'); ?></h3>
                                    <p class="text-gray-500 mb-4"><?php _e('no_teachers_description'); ?></p>
                                    <button class="bg-darkblue text-white px-4 py-2 rounded-lg hover:bg-navy transition-colors" id="addFirstTeacherBtn">
                                        <?php _e('add_first_teacher'); ?>
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <?php _e('teacher'); ?>
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <?php _e('email'); ?>
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <?php _e('phone'); ?>
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <?php _e('subjects'); ?>
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <?php _e('status'); ?>
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <?php _e('actions'); ?>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($docentes as $docente): ?>
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                                <span class="text-blue-600 text-sm font-bold">
                                                                    <?php echo getTeacherInitials($docente['nombre'], $docente['apellido']); ?>
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900">
                                                                    <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                                                                </div>
                                                                <div class="text-sm text-gray-500">
                                                                    ID: <?php echo htmlspecialchars($docente['id_docente']); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($docente['email'] ?? 'N/A'); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($docente['telefono'] ?? 'N/A'); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($docente['materias'] ?? 'N/A'); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                            <?php _e('active'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div class="flex space-x-2">
                                                            <button class="text-indigo-600 hover:text-indigo-900 edit-teacher" 
                                                                    data-id="<?php echo $docente['id_docente']; ?>">
                                                                <?php _e('edit'); ?>
                                                            </button>
                                                            <button class="text-red-600 hover:text-red-900 delete-teacher" 
                                                                    data-id="<?php echo $docente['id_docente']; ?>">
                                                                <?php _e('delete'); ?>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="/js/menu.js"></script>
    <script>
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/logout';
            }
        });

        // Add teacher functionality
        document.getElementById('addTeacherBtn').addEventListener('click', function() {
            // TODO: Implement add teacher modal/form
            alert('<?php _e('add_teacher'); ?> - <?php _e('feature_coming_soon'); ?>');
        });

        document.getElementById('addFirstTeacherBtn').addEventListener('click', function() {
            // TODO: Implement add teacher modal/form
            alert('<?php _e('add_teacher'); ?> - <?php _e('feature_coming_soon'); ?>');
        });

        // Edit teacher functionality
        document.querySelectorAll('.edit-teacher').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                // TODO: Implement edit teacher functionality
                alert('<?php _e('edit'); ?> ' + id + ' - <?php _e('feature_coming_soon'); ?>');
            });
        });

        // Delete teacher functionality
        document.querySelectorAll('.delete-teacher').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                if (confirm('<?php _e('confirm_delete_teacher'); ?>')) {
                    // TODO: Implement delete teacher functionality
                    alert('<?php _e('delete'); ?> ' + id + ' - <?php _e('feature_coming_soon'); ?>');
                }
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>