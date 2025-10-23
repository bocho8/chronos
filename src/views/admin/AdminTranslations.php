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
    <script src="/js/auto-save-manager.js"></script>
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
        .status-spanish-error {
            background-color: #fed7aa;
            color: #c2410c;
        }
        .spanish-error {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .spanish-error-cell {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
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
        <main class="flex-1 flex flex-col main-content">
            <!-- Header -->
            <header class="bg-darkblue px-4 md:px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <!-- Espacio para el botón de menú hamburguesa -->
                <div class="w-8"></div>
                
                <!-- Título centrado -->
                <div class="text-white text-lg md:text-xl font-semibold text-center hidden sm:block"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
                <div class="text-white text-sm font-semibold text-center sm:hidden"><?php _e('welcome'); ?></div>
                
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
            <section class="flex-1 px-4 md:px-6 py-6 md:py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-6 md:mb-8">
                        <h2 class="text-darktext text-xl md:text-2xl font-semibold mb-2 md:mb-2.5"><?php _e('translations_management'); ?></h2>
                        <p class="text-muted mb-4 md:mb-6 text-sm md:text-base"><?php _e('translations_management_description'); ?></p>
                        
                        <!-- Export Buttons -->
                        <div class="flex items-center space-x-3 mb-6">
                            <button onclick="exportTranslations('json')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <?php _e('export_json'); ?>
                            </button>
                            <button onclick="exportTranslations('csv')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <?php _e('export_csv'); ?>
                            </button>
                            <div class="flex-1"></div>
                            <button onclick="openAddKeyModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                                + Add Key
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
                                        <option value="spanish-error">Spanish Errors</option>
                                    </select>
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


    <!-- Add Key Modal -->
    <div id="addKeyModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Add Translation Key</h3>
                <button onclick="closeAddKeyModal()" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>
            <div class="space-y-3">
                <label for="addKeyInput" class="block text-sm font-medium text-gray-700">Key name</label>
                <input id="addKeyInput" type="text" placeholder="new_key_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                <p id="addKeyError" class="text-sm text-red-600 hidden"></p>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button onclick="closeAddKeyModal()" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"><?php _e('cancel'); ?></button>
                <button onclick="submitAddKey()" id="addKeySubmit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Create</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        let translations = {};
        let filteredTranslations = {};
        let editingKey = null;
        let spanishErrors = {};

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
            // Modal Enter key
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('addKeyModal');
                if (!modal.classList.contains('hidden') && e.key === 'Enter') {
                    e.preventDefault();
                    submitAddKey();
                }
            });
        }

        async function loadTranslations() {
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
            }
        }

        // Add Key Modal controls
        function openAddKeyModal() {
            const modal = document.getElementById('addKeyModal');
            const input = document.getElementById('addKeyInput');
            const err = document.getElementById('addKeyError');
            err.classList.add('hidden');
            err.textContent = '';
            input.value = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => input.focus(), 0);
        }

        function closeAddKeyModal() {
            const modal = document.getElementById('addKeyModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function submitAddKey() {
            const input = document.getElementById('addKeyInput');
            const err = document.getElementById('addKeyError');
            const submitBtn = document.getElementById('addKeySubmit');
            let key = (input.value || '').trim();

            // Basic checks
            if (!key) {
                err.textContent = 'Key is required';
                err.classList.remove('hidden');
                return;
            }

            // Client-side format check to avoid unnecessary request
            const formatRegex = /^[a-zA-Z_][a-zA-Z0-9_]*$/;
            if (!formatRegex.test(key)) {
                err.textContent = 'Invalid key format. Use letters, numbers, underscores; cannot start with number.';
                err.classList.remove('hidden');
                return;
            }

            // Check duplicates from loaded data
            if (translations[key]) {
                err.textContent = 'Key already exists';
                err.classList.remove('hidden');
                return;
            }

            try {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creating...';

                // Server-side format validation (authoritative)
                const validateRes = await fetch('/admin/translations/validate-key', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key })
                });
                const validateData = await validateRes.json();
                if (!validateData.success || !validateData.data || !validateData.data.valid) {
                    err.textContent = 'Key format is invalid';
                    err.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Create';
                    return;
                }

                // Create empty Spanish entry
                const createRes = await fetch('/admin/translations/update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key: key, language: 'es', value: '' })
                });
                const createData = await createRes.json();

                if (createData.success) {
                    closeAddKeyModal();
                    showToast('Key created');
                    await loadTranslations();
                } else {
                    err.textContent = 'Failed to create key: ' + (createData.message || 'Unknown error');
                    err.classList.remove('hidden');
                }
            } catch (e) {
                err.textContent = 'Error: ' + e.message;
                err.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create';
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
            const spanishErrors = getSpanishErrorStatus(translation);
            const statusClass = getStatusClass(status);

            // Check if this row has Spanish errors
            const hasSpanishErrors = spanishErrors && spanishErrors.length > 0;
            const finalStatus = hasSpanishErrors ? 'Spanish Error' : status;
            const finalStatusClass = hasSpanishErrors ? 'status-spanish-error' : statusClass;

            // Determine which cells have Spanish errors
            const enHasSpanish = spanishErrors && spanishErrors.includes('en');
            const itHasSpanish = spanishErrors && spanishErrors.includes('it');

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
                           data-autosave-key="${key}_es"
                           oninput="handleTranslationInput('${key}', 'es', this)"
                           onkeypress="handleKeyPress(event, '${key}', 'es', this.value)">
                </td>
                <td class="px-4 py-3 translation-cell ${enHasSpanish ? 'spanish-error-cell' : ''}">
                    <input type="text" 
                           class="translation-input" 
                           value="${escapeHtml(translation.en || '')}" 
                           data-key="${key}" 
                           data-lang="en"
                           data-autosave-key="${key}_en"
                           oninput="handleTranslationInput('${key}', 'en', this)"
                           onkeypress="handleKeyPress(event, '${key}', 'en', this.value)">
                    ${enHasSpanish ? '<span class="text-orange-600 text-xs font-semibold">⚠️ Spanish</span>' : ''}
                </td>
                <td class="px-4 py-3 translation-cell ${itHasSpanish ? 'spanish-error-cell' : ''}">
                    <input type="text" 
                           class="translation-input" 
                           value="${escapeHtml(translation.it || '')}" 
                           data-key="${key}" 
                           data-lang="it"
                           data-autosave-key="${key}_it"
                           oninput="handleTranslationInput('${key}', 'it', this)"
                           onkeypress="handleKeyPress(event, '${key}', 'it', this.value)">
                    ${itHasSpanish ? '<span class="text-orange-600 text-xs font-semibold">⚠️ Spanish</span>' : ''}
                </td>
                <td class="px-4 py-3">
                    <span class="status-badge ${finalStatusClass}">${finalStatus}</span>
                </td>
                <td class="px-4 py-3">
                    ${hasSpanishErrors ? 
                        `<button onclick="clearSpanishFromRow('${key}')" 
                                class="text-orange-600 hover:text-orange-800 text-sm mr-2">
                            Clear Spanish
                        </button>` : ''
                    }
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

            // Add Spanish error highlighting
            if (hasSpanishErrors) {
                row.classList.add('spanish-error');
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

        function getSpanishErrorStatus(translation) {
            const esValue = translation.es || '';
            const enValue = translation.en || '';
            const itValue = translation.it || '';

            if (!esValue || esValue.trim() === '') return false;

            const errors = [];
            // Check if field matches Spanish exactly
            if (enValue === esValue) errors.push('en');
            if (itValue === esValue) errors.push('it');

            return errors.length > 0 ? errors : false;
        }

        function getStatusClass(status) {
            switch (status) {
                case 'Complete': return 'status-complete';
                case 'Missing': return 'status-missing';
                case 'Partial': return 'status-partial';
                case 'Spanish Error': return 'status-spanish-error';
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
                const spanishErrors = getSpanishErrorStatus(translation);
                const hasSpanishErrors = spanishErrors && spanishErrors.length > 0;
                const finalStatus = hasSpanishErrors ? 'Spanish Error' : status;
                
                const matchesStatus = !statusFilter || 
                                    (statusFilter === 'complete' && finalStatus === 'Complete') ||
                                    (statusFilter === 'missing' && finalStatus === 'Missing') ||
                                    (statusFilter === 'partial' && finalStatus === 'Partial') ||
                                    (statusFilter === 'spanish-error' && finalStatus === 'Spanish Error');

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

        // Handle translation input with debounced auto-save
        function handleTranslationInput(key, language, inputElement) {
            const value = inputElement.value;
            const saveKey = `${key}_${language}`;
            
            // Mark as unsaved
            window.autoSaveManager.markUnsaved(saveKey);
            
            // Debounced save
            window.autoSaveManager.save(saveKey, async () => {
                return await saveTranslation(key, language, value);
            }, {
                indicator: inputElement,
                debounceDelay: 1500,
                onSuccess: (result) => {
                    // Update local data
                    if (!translations[key]) {
                        translations[key] = {};
                    }
                    translations[key][language] = value;
                    
                    // Mark as saved to prevent re-triggering
                    window.autoSaveManager.markSaved(saveKey);
                    
                    // Refresh the table to show updated data
                    renderTranslations();
                    
                    // Refresh statistics
                    updateStatistics();
                },
                onError: (error) => {
                    console.error('Translation save error:', error);
                }
            });
        }

        async function fillMissingTranslations() {
            if (!confirm('This will fill missing translations from Spanish. Continue?')) {
                return;
            }

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

        async function detectSpanishErrors() {
            try {
                const response = await fetch('/admin/translations/detect-spanish');
                const data = await response.json();
                
                if (data.success) {
                    spanishErrors = data.data.errors;
                    showToast(`Detected ${data.data.count} Spanish errors`, 'info');
                    renderTranslations(); // Re-render to show highlighted errors
                } else {
                    showToast('Error detecting Spanish errors: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error detecting Spanish errors: ' + error.message, 'error');
            }
        }

        async function clearSpanishFromRow(key) {
            if (!confirm('Are you sure you want to clear Spanish text from this row?')) {
                return;
            }

            try {
                const translation = translations[key];
                const esValue = translation.es || '';
                const updates = {};

                // Clear EN field if it matches Spanish
                if (translation.en === esValue) {
                    updates.en = '';
                }

                // Clear IT field if it matches Spanish
                if (translation.it === esValue) {
                    updates.it = '';
                }

                // Update each field that needs clearing
                for (const [lang, value] of Object.entries(updates)) {
                    await saveTranslation(key, lang, value);
                }

                showToast('Spanish text cleared successfully', 'success');
                renderTranslations(); // Re-render to update display
            } catch (error) {
                showToast('Error clearing Spanish text: ' + error.message, 'error');
            }
        }

        async function clearAllSpanishErrors() {
            try {
                // Show loading indicator
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Clearing...';
                button.disabled = true;

                // Call the bulk clear endpoint
                const response = await fetch('/admin/translations/clear-all-spanish', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    // Reload translations to get updated data
                    await loadTranslations();
                } else {
                    showToast('Error clearing Spanish text: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error clearing Spanish text: ' + error.message, 'error');
            } finally {
                // Restore button state
                const button = event.target;
                button.textContent = 'Clear All Spanish';
                button.disabled = false;
            }
        }
    </script>
</body>
</html>
