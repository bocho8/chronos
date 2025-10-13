<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-translations.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('translations_management'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
    <style>
        .translation-table {
            font-size: 0.875rem;
            width: 100%;
        }
        .translation-table th {
            background-color: #f8fafc;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .translation-cell {
            min-width: 150px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .translation-table td {
            vertical-align: top;
            padding: 1.5rem 1rem;
            min-height: 60px;
        }
        .translation-table th {
            padding: 1rem;
            min-height: 50px;
        }
        .translation-input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        .translation-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .missing-translation {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
        }
        .editing {
            background-color: #fef3c7;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
        }
        .status-complete {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-missing {
            background-color: #fef2f2;
            color: #dc2626;
        }
        .status-partial {
            background-color: #fef3c7;
            color: #d97706;
        }
        .progress-bar {
            height: 0.5rem;
            background-color: #e5e7eb;
            border-radius: 0.25rem;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background-color: #3b82f6;
            transition: width 0.3s ease;
        }
        .search-container {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 20;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

        <!-- Main -->
        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <!-- Espacio para el botón de menú hamburguesa -->
                <div class="w-8"></div>
                
                <!-- Título centrado -->
                <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                
                <!-- Contenedor de iconos a la derecha -->
                <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <span class="text-white text-sm">🔔</span>
                    </button>
          
                    <!-- User Menu Dropdown -->
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                            <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                        </button>
                        
                        <!-- Dropdown Menu -->
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

            <!-- Contenido principal -->
            <section class="flex-1 px-6 py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('translations_management'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('translations_management_description'); ?></p>
                        
                        <!-- Export Buttons -->
                        <div class="flex items-center space-x-3 mb-6">
                            <button onclick="exportTranslations('json')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <?php _e('export_json'); ?>
                            </button>
                            <button onclick="exportTranslations('csv')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <?php _e('export_csv'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Statistics Dashboard -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div style="background: linear-gradient(to right, #2563eb, #1d4ed8); color: white; border-radius: 0.5rem; padding: 1rem;">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p style="color: white; font-size: 0.875rem; font-weight: 500;"><?php _e('spanish'); ?></p>
                                            <p style="color: white; font-size: 1.5rem; font-weight: bold;"><?php echo isset($statistics['es']['completion_percentage']) ? $statistics['es']['completion_percentage'] : '0'; ?>%</p>
                                            <p style="color: white; font-size: 0.75rem; opacity: 0.9;"><?php echo isset($statistics['es']['total_keys']) ? $statistics['es']['total_keys'] : '0'; ?> <?php _e('total_keys'); ?></p>
                                        </div>
                                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                            <span class="text-2xl">🇪🇸</span>
                                        </div>
                                    </div>
                                </div>
                                <div style="background: linear-gradient(to right, #dc2626, #b91c1c); color: white; border-radius: 0.5rem; padding: 1rem;">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p style="color: white; font-size: 0.875rem; font-weight: 500;"><?php _e('english'); ?></p>
                                            <p style="color: white; font-size: 1.5rem; font-weight: bold;"><?php echo isset($statistics['en']['completion_percentage']) ? $statistics['en']['completion_percentage'] : '0'; ?>%</p>
                                            <p style="color: white; font-size: 0.75rem; opacity: 0.9;"><?php echo isset($statistics['en']['total_keys']) ? $statistics['en']['total_keys'] : '0'; ?> <?php _e('total_keys'); ?></p>
                                        </div>
                                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                            <span class="text-2xl">🇺🇸</span>
                                        </div>
                                    </div>
                                </div>
                                <div style="background: linear-gradient(to right, #16a34a, #15803d); color: white; border-radius: 0.5rem; padding: 1rem;">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p style="color: white; font-size: 0.875rem; font-weight: 500;"><?php _e('italian'); ?></p>
                                            <p style="color: white; font-size: 1.5rem; font-weight: bold;"><?php echo isset($statistics['it']['completion_percentage']) ? $statistics['it']['completion_percentage'] : '0'; ?>%</p>
                                            <p style="color: white; font-size: 0.75rem; opacity: 0.9;"><?php echo isset($statistics['it']['total_keys']) ? $statistics['it']['total_keys'] : '0'; ?> <?php _e('total_keys'); ?></p>
                                        </div>
                                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                            <span class="text-2xl">🇮🇹</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Filters -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex-1">
                                    <input type="text" id="searchInput" placeholder="<?php _e('search_translations'); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div class="flex gap-2">
                                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value=""><?php _e('all_status'); ?></option>
                                        <option value="complete"><?php _e('complete'); ?></option>
                                        <option value="missing"><?php _e('missing'); ?></option>
                                        <option value="partial"><?php _e('partial'); ?></option>
                                    </select>
                                    <button onclick="fillMissingTranslations()" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                        <?php _e('fill_missing'); ?>
                                    </button>
                                    <button onclick="refreshTranslations()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                        <?php _e('refresh'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Translation Table -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder">
                        <div class="overflow-x-auto" style="max-height: 70vh;">
                            <table class="min-w-full translation-table">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-64">
                                        <?php _e('translation_key'); ?>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider translation-cell">
                                        <?php _e('spanish'); ?> (ES)
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider translation-cell">
                                        <?php _e('english'); ?> (EN)
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider translation-cell">
                                        <?php _e('italian'); ?> (IT)
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                        <?php _e('status'); ?>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                        <?php _e('actions'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="translationsTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Translations will be loaded here via JavaScript -->
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span><?php _e('loading'); ?>...</span>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        let translations = {};
        let filteredTranslations = {};
        let editingKey = null;

        // Logout functionality
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/views/logout.php';
            }
        });

        // Load translations on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTranslations();
            setupEventListeners();
        });

        function setupEventListeners() {
            document.getElementById('searchInput').addEventListener('input', filterTranslations);
            document.getElementById('statusFilter').addEventListener('change', filterTranslations);
        }

        async function loadTranslations() {
            showLoading();
            try {
                const response = await fetch('/admin/translations/all');
                const data = await response.json();
                
                if (data.success) {
                    translations = data.data;
                    filteredTranslations = translations;
                    renderTranslations();
                } else {
                    showToast('Error loading translations: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error loading translations:', error); // Debug log
                showToast('Error loading translations: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }

        function renderTranslations() {
            const tbody = document.getElementById('translationsTableBody');
            tbody.innerHTML = '';

            const keys = Object.keys(filteredTranslations);
            
            keys.forEach((key, index) => {
                const translation = filteredTranslations[key];
                const row = createTranslationRow(key, translation);
                tbody.appendChild(row);
            });
        }

        function createTranslationRow(key, translation) {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            
            const status = getTranslationStatus(translation);
            const statusClass = getStatusClass(status);

            row.innerHTML = `
                <td class="px-4 py-3 font-mono text-sm text-gray-900">
                    <div class="font-medium">${key}</div>
                </td>
                <td class="px-4 py-3 translation-cell">
                    <input type="text" 
                           class="translation-input" 
                           value="${escapeHtml(translation.es || '')}" 
                           data-key="${key}" 
                           data-lang="es"
                           onblur="saveTranslation('${key}', 'es', this.value)"
                           onkeypress="handleKeyPress(event, '${key}', 'es', this.value)">
                </td>
                <td class="px-4 py-3 translation-cell">
                    <input type="text" 
                           class="translation-input" 
                           value="${escapeHtml(translation.en || '')}" 
                           data-key="${key}" 
                           data-lang="en"
                           onblur="saveTranslation('${key}', 'en', this.value)"
                           onkeypress="handleKeyPress(event, '${key}', 'en', this.value)">
                </td>
                <td class="px-4 py-3 translation-cell">
                    <input type="text" 
                           class="translation-input" 
                           value="${escapeHtml(translation.it || '')}" 
                           data-key="${key}" 
                           data-lang="it"
                           onblur="saveTranslation('${key}', 'it', this.value)"
                           onkeypress="handleKeyPress(event, '${key}', 'it', this.value)">
                </td>
                <td class="px-4 py-3">
                    <span class="status-badge ${statusClass}">${status}</span>
                </td>
                <td class="px-4 py-3">
                    <button onclick="deleteTranslation('${key}')" 
                            class="text-red-600 hover:text-red-800 text-sm">
                        <?php _e('delete'); ?>
                    </button>
                </td>
            `;

            // Add missing translation highlighting
            if (status === 'Missing' || status === 'Partial') {
                row.classList.add('missing-translation');
            }

            return row;
        }

        function getTranslationStatus(translation) {
            const hasEs = translation.es && translation.es.trim() !== '';
            const hasEn = translation.en && translation.en.trim() !== '';
            const hasIt = translation.it && translation.it.trim() !== '';

            if (hasEs && hasEn && hasIt) return 'Complete';
            if (!hasEs && !hasEn && !hasIt) return 'Missing';
            return 'Partial';
        }

        function getStatusClass(status) {
            switch (status) {
                case 'Complete': return 'status-complete';
                case 'Missing': return 'status-missing';
                case 'Partial': return 'status-partial';
                default: return '';
            }
        }

        function filterTranslations() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;

            filteredTranslations = {};

            Object.keys(translations).forEach(key => {
                const translation = translations[key];
                const status = getTranslationStatus(translation);
                
                // Check search term
                const matchesSearch = key.toLowerCase().includes(searchTerm) ||
                                    (translation.es && translation.es.toLowerCase().includes(searchTerm)) ||
                                    (translation.en && translation.en.toLowerCase().includes(searchTerm)) ||
                                    (translation.it && translation.it.toLowerCase().includes(searchTerm));

                // Check status filter
                const matchesStatus = !statusFilter || 
                                    (statusFilter === 'complete' && status === 'Complete') ||
                                    (statusFilter === 'missing' && status === 'Missing') ||
                                    (statusFilter === 'partial' && status === 'Partial');

                if (matchesSearch && matchesStatus) {
                    filteredTranslations[key] = translation;
                }
            });

            renderTranslations();
        }

        async function saveTranslation(key, language, value) {
            try {
                const response = await fetch('/admin/translations/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        key: key,
                        language: language,
                        value: value
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    // Update local data
                    if (!translations[key]) {
                        translations[key] = {};
                    }
                    translations[key][language] = value;
                    
                    // Refresh the table to show updated data
                    renderTranslations();
                    
                    // Refresh statistics to show updated completion percentages
                    updateStatistics();
                    
                    showToast('Translation saved successfully', 'success');
                } else {
                    showToast('Error saving translation: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error saving translation: ' + error.message, 'error');
            }
        }

        function handleKeyPress(event, key, language, value) {
            if (event.key === 'Enter') {
                event.target.blur();
            }
        }

        async function fillMissingTranslations() {
            if (!confirm('This will fill missing translations from Spanish. Continue?')) {
                return;
            }

            showLoading();
            try {
                // Fill English
                const enResponse = await fetch('/admin/translations/fill-missing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        target_language: 'en',
                        source_language: 'es'
                    })
                });

                // Fill Italian
                const itResponse = await fetch('/admin/translations/fill-missing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        target_language: 'it',
                        source_language: 'es'
                    })
                });

                const enData = await enResponse.json();
                const itData = await itResponse.json();

                if (enData.success && itData.success) {
                    showToast(`Missing translations filled: ${enData.count} English, ${itData.count} Italian`, 'success');
                    loadTranslations(); // Reload to show changes
                } else {
                    showToast('Error filling missing translations', 'error');
                }
            } catch (error) {
                showToast('Error filling missing translations: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }

        function exportTranslations(format) {
            const url = `/admin/translations/export?format=${format}`;
            window.open(url, '_blank');
        }

        function refreshTranslations() {
            loadTranslations();
        }

        async function updateStatistics() {
            try {
                const response = await fetch('/admin/translations/statistics');
                const data = await response.json();
                
                if (data.success) {
                    // Update the statistics display
                    const stats = data.data;
                    
                    // Update Spanish stats
                    const esCard = document.querySelector('.bg-gradient-to-r.from-blue-600');
                    if (esCard) {
                        const percentage = esCard.querySelector('p[style*="font-size: 1.5rem"]');
                        const totalKeys = esCard.querySelector('p[style*="opacity: 0.9"]');
                        if (percentage) percentage.textContent = stats.es.completion_percentage + '%';
                        if (totalKeys) totalKeys.textContent = stats.es.total_keys + ' <?php _e('total_keys'); ?>';
                    }
                    
                    // Update English stats
                    const enCard = document.querySelector('.bg-gradient-to-r.from-red-600');
                    if (enCard) {
                        const percentage = enCard.querySelector('p[style*="font-size: 1.5rem"]');
                        const totalKeys = enCard.querySelector('p[style*="opacity: 0.9"]');
                        if (percentage) percentage.textContent = stats.en.completion_percentage + '%';
                        if (totalKeys) totalKeys.textContent = stats.en.total_keys + ' <?php _e('total_keys'); ?>';
                    }
                    
                    // Update Italian stats
                    const itCard = document.querySelector('.bg-gradient-to-r.from-green-600');
                    if (itCard) {
                        const percentage = itCard.querySelector('p[style*="font-size: 1.5rem"]');
                        const totalKeys = itCard.querySelector('p[style*="opacity: 0.9"]');
                        if (percentage) percentage.textContent = stats.it.completion_percentage + '%';
                        if (totalKeys) totalKeys.textContent = stats.it.total_keys + ' <?php _e('total_keys'); ?>';
                    }
                }
            } catch (error) {
                console.error('Error updating statistics:', error);
            }
        }

        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-500' : 
                           type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            
            toast.className = `${bgColor} text-white px-4 py-2 rounded-lg shadow-lg`;
            toast.textContent = message;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
