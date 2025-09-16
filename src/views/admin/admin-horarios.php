<?php
// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();

// Handle language change
$languageSwitcher->handleLanguageChange();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('admin_panel'); ?> · <?php _e('schedules'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <style type="text/css">
        body {
            overflow-x: hidden;
        }
        .sidebar-link {
            position: relative;
            transition: all 0.3s;
        }
        .sidebar-link.active {
            background-color: #dee2e6;
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
            gap: 12px;
        }
        .toast.show {
            transform: translateX(0);
        }
        .toast-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .toast-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .toast-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .toast-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        #toastContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }
        .horario-cell {
            cursor: pointer;
            transition: all 0.2s;
            min-width: 100px;
        }
        .horario-cell:hover {
            opacity: 0.9;
            transform: scale(0.97);
        }
        @media (max-width: 768px) {
            .horario-cell {
                min-width: 80px;
                font-size: 0.75rem;
                padding: 6px 4px;
            }
        }
        @media (max-width: 480px) {
            .horario-cell {
                min-width: 60px;
                font-size: 0.65rem;
            }
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
                <li>
                    <a href="index.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('dashboard'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-usuarios.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('users'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-docentes.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('teachers'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-materias.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('subjects'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin-horarios.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
                        <?php _e('schedules'); ?>
                    </a>
                </li>
            </ul>
        </aside>

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
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
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
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <?php _e('profile'); ?>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <?php _e('settings'); ?>
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <?php _e('logout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenido principal - Centrado -->
            <section class="flex-1 px-6 py-8">
                <div class="max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('schedules_management'); ?></h2>
                        <p class="text-muted mb-6 text-base"><?php _e('schedules_management_description'); ?></p>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse mb-6">
                                <thead>
                                    <tr>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('time'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('monday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('tuesday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('wednesday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('thursday'); ?></th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300"><?php _e('friday'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">08:00 – 08:45</th>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">08:45 – 09:30</th>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">09:30 – 10:15</th>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">10:15 – 11:00</th>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">11:00 – 11:45</th>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">11:45 – 12:30</th>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-available text-white text-center font-medium p-2 border border-gray-300" data-state="yes">Disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">12:30 – 13:15</th>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">13:15 – 14:00</th>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">14:00 – 14:45</th>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">14:45 – 15:30</th>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">15:30 – 16:15</th>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-[#34495e] text-white p-2 text-center font-semibold border border-gray-300">16:15 – 16:45</th>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                        <td class="horario-cell bg-notavailable text-white text-center font-medium p-2 border border-gray-300" data-state="no">No disponible</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-center">
                            <button class="py-3 px-8 border-none rounded cursor-pointer font-semibold transition-all text-base bg-darkblue text-white hover:bg-navy">
                                <?php _e('next'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Toggle Disponible / No disponible
        document.querySelectorAll('.horario-cell').forEach(td => {
            td.tabIndex = 0;
            td.addEventListener('click', toggle);
            td.addEventListener('keydown', e => {
                if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); toggle.call(td); }
            });
        });

        function toggle() {
            const state = this.getAttribute('data-state');
            if (state === 'yes') {
                this.setAttribute('data-state', 'no');
                this.textContent = 'No disponible';
                this.classList.remove('bg-available');
                this.classList.add('bg-notavailable');
            } else {
                this.setAttribute('data-state', 'yes');
                this.textContent = 'Disponible';
                this.classList.remove('bg-notavailable');
                this.classList.add('bg-available');
            }
        }

        // Toast notification functions
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = getToastIcon(type);
            toast.innerHTML = `
                <div class="flex items-center">
                    ${icon}
                    <span>${message}</span>
                </div>
                <button onclick="hideToast(this)" class="ml-4 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Auto hide after 5 seconds
            setTimeout(() => hideToast(toast), 5000);
        }

        function getToastIcon(type) {
            const icons = {
                success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
                info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };
            return icons[type] || icons.info;
        }

        function hideToast(toast) {
            if (toast && toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }

        // Funcionalidad para la barra lateral
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener todos los enlaces de la barra lateral
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            
            // Función para manejar el clic en los enlaces
            function handleSidebarClick(event) {
                // Remover la clase active de todos los enlaces
                sidebarLinks.forEach(link => {
                    link.classList.remove('active');
                });
                
                // Agregar la clase active al enlace clickeado
                this.classList.add('active');
            }
            
            // Agregar event listener a cada enlace
            sidebarLinks.forEach(link => {
                link.addEventListener('click', handleSidebarClick);
            });
            
            // Logout functionality
            const logoutButton = document.getElementById('logoutButton');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Show confirmation dialog
                    const confirmMessage = '<?php _e('confirm_logout'); ?>';
                    if (confirm(confirmMessage)) {
                        // Create form and submit logout request
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/src/controllers/LogoutController.php';
                        
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'logout';
                        
                        form.appendChild(actionInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
            
            // User menu toggle
            const userMenuButton = document.getElementById('userMenuButton');
            const userMenu = document.getElementById('userMenu');
            
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    <!-- Toast Container -->
    <div id="toastContainer"></div>
</body>
</html>