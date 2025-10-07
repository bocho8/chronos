<?php
require_once __DIR__ . '/../../config/session.php'; 
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Horario.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-estudiantes.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('PADRE'); 

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$currentUser = AuthHelper::getCurrentUser();

try {
    $dbConfig = require __DIR__ . '/../../config/database.php';
    $database = new Database($dbConfig);
    
    $horarioModel = new Horario($database->getConnection());
    
    $grupos = $horarioModel->getAllGrupos();
    
    $searchTerm = $_GET['search'] ?? '';
    $selectedGrupo = $_GET['grupo'] ?? '';

    $estudiantes = [];
    
    if (!empty($searchTerm) || !empty($selectedGrupo)) {
        $query = "SELECT DISTINCT g.id_grupo as id, g.nombre as nombre_grupo, g.nivel,
                         g.nivel as tipo,
                         COUNT(DISTINCT h.id_horario) as total_horarios
                  FROM grupo g 
                  LEFT JOIN horario h ON g.id_grupo = h.id_grupo 
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($searchTerm)) {
            $query .= " AND g.nombre ILIKE :search";
            $params['search'] = '%' . $searchTerm . '%';
        }
        
        if (!empty($selectedGrupo)) {
            $query .= " AND g.id_grupo = :grupo";
            $params['grupo'] = $selectedGrupo;
        }
        
        $query .= " GROUP BY g.id_grupo, g.nombre, g.nivel ORDER BY g.nivel, g.nombre";
        
        $stmt = $database->getConnection()->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $estudiantes = $stmt->fetchAll();
    } else {
        $query = "SELECT DISTINCT g.id_grupo as id, g.nombre as nombre_grupo, g.nivel,
                         g.nivel as tipo,
                         COUNT(DISTINCT h.id_horario) as total_horarios
                  FROM grupo g 
                  LEFT JOIN horario h ON g.id_grupo = h.id_grupo 
                  GROUP BY g.id_grupo, g.nombre, g.nivel 
                  ORDER BY g.nivel, g.nombre";
        
        $stmt = $database->getConnection()->prepare($query);
        $stmt->execute();
        $estudiantes = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    error_log("Error loading estudiantes data: " . $e->getMessage());
    $estudiantes = [];
    $grupos = [];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('students_management'); ?></title>
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
                    <?php _e('students_management'); ?>
                </div>
                
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <img src="/assets/images/icons/bell.png" class="h-6 w-6" alt="<?php _e('notifications'); ?>" />
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block" id="userMenu">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                                <div class="text-gray-500"><?php _e('role_parent'); ?></div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                                <img src="/assets/images/icons/user.png" class="inline w-4 h-4 mr-2" alt="<?php _e('profile'); ?>" />
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                                <img src="/assets/images/icons/gear.png" class="inline w-4 h-4 mr-2" alt="<?php _e('settings'); ?>" />
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <img src="/assets/images/icons/logout.png" class="inline w-4 h-4 mr-2" alt="<?php _e('logout'); ?>" />
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <section class="flex-1 px-6 py-8">
                <div class="max-w-7xl mx-auto">
                    <!-- Breadcrumbs -->
                    <nav class="flex mb-6" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                    <img src="/assets/images/icons/home.png" class="w-4 h-4 mr-2" alt="<?php _e('dashboard'); ?>" />
                                    <?php _e('dashboard'); ?>
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <img src="/assets/images/icons/chevron-right.png" class="w-6 h-6 text-gray-400" alt=">" />
                                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2"><?php _e('students_management'); ?></span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('students_management'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('students_management_description'); ?></p>
                    </div>

                    <!-- Filters and Search -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                        <form method="GET" class="flex flex-wrap gap-4 items-end" id="searchForm">
                            <div class="flex-1 min-w-64">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('search'); ?></label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                       placeholder="<?php _e('search_placeholder'); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       maxlength="100">
                                <div id="searchError" class="text-red-500 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div class="min-w-48">
                                <label for="grupo" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('filter_by_group'); ?></label>
                                <select id="grupo" name="grupo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value=""><?php _e('all_groups'); ?></option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?php echo $grupo['id_grupo']; ?>" <?php echo $selectedGrupo == $grupo['id_grupo'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" id="searchBtn">
                                    <span id="searchText"><?php _e('search'); ?></span>
                                    <img id="searchSpinner" src="/assets/images/icons/spinner.png" class="animate-spin -ml-1 mr-2 h-4 w-4 hidden" alt="loading" />
                                </button>
                                <a href="admin-estudiantes.php" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    <?php _e('clear'); ?>
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Students List -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900"><?php _e('students_list'); ?></h3>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <?php if (empty($estudiantes)): ?>
                                <div class="px-6 py-12 text-center">
                                    <img class="mx-auto h-12 w-12" src="/assets/images/icons/inbox.png" alt="empty" />
                                    <h3 class="mt-2 text-sm font-medium text-gray-900"><?php _e('no_students_found'); ?></h3>
                                    <p class="mt-1 text-sm text-gray-500"><?php _e('try_adjusting_your_search'); ?></p>
                                </div>
                            <?php else: ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('group'); ?></th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('level'); ?></th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('type'); ?></th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('total_schedules'); ?></th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($estudiantes as $estudiante): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($estudiante['nombre_grupo']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        <?php echo htmlspecialchars($estudiante['nivel']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($estudiante['tipo']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $estudiante['total_horarios']; ?> <?php _e('schedules'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <a href="admin-horarios-estudiante.php?grupo=<?php echo $estudiante['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900 mr-3">
                                                        <?php _e('view_schedule'); ?>
                                                    </a>
                                                    <a href="#" class="text-green-600 hover:text-green-900">
                                                        <?php _e('details'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('searchForm');
            const searchBtn = document.getElementById('searchBtn');
            const searchText = document.getElementById('searchText');
            const searchSpinner = document.getElementById('searchSpinner');
            const searchInput = document.getElementById('search');
            const searchError = document.getElementById('searchError');

            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    const searchValue = searchInput.value.trim();

                    searchError.classList.add('hidden');
                    searchInput.classList.remove('border-red-500');
                    
                    if (searchValue.length > 0 && searchValue.length < 2) {
                        e.preventDefault();
                        searchError.textContent = 'El término de búsqueda debe tener al menos 2 caracteres';
                        searchError.classList.remove('hidden');
                        searchInput.classList.add('border-red-500');
                        return false;
                    }
                    
                    const invalidChars = /[<>'"&]/;
                    if (invalidChars.test(searchValue)) {
                        e.preventDefault();
                        searchError.textContent = 'El término de búsqueda contiene caracteres no válidos';
                        searchError.classList.remove('hidden');
                        searchInput.classList.add('border-red-500');
                        return false;
                    }
                    
                    if (searchBtn) {
                        searchText.textContent = '<?php _e('loading'); ?>';
                        searchSpinner.classList.remove('hidden');
                        searchBtn.disabled = true;
                    }
                });
            }
        });
    </script>
</body>
</html>
