<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Horarios — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#1f366d',
                        bg: '#f8f9fa',
                        card: '#e9ecef',
                        muted: '#7f8c8d',
                        darktext: '#2c3e50',
                        border: '#b8b8b8',
                        hover: '#b8b8b8',
                        lightborder: '#e0e0e0',
                        lightbg: '#f5f7f9',
                        darkblue: '#142852',
                        available: '#4CAF50',
                        notavailable: '#F44336',
                        sidebar: '#e9ecef',
                        sidebarHover: '#dee2e6'
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'sans-serif'],
                    },
                }
            }
        }
    </script>
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
        <aside class="w-64 bg-sidebar border-r border-border">
            <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
                <img src="/upload/LogoScuola.png" alt="Scuola Italiana de Montevideo" class="h-9 w-auto">
                <span class="text-white font-semibold text-lg">Scuola Italiana</span>
            </div>

            <ul class="py-5 list-none">
                <li>
                    <a href="index.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        Docentes
                    </a>
                </li>
                <li>
                    <a href="admin-coordinadores.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        Coordinadores
                    </a>
                </li>
                <li>
                    <a href="admin-materias.php" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        Materias
                    </a>
                </li>
                <li>
                    <a href="admin-horarios.php" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
                        Horarios
                    </a>
                </li>
            </ul>
        </aside>

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="text-white text-xl font-semibold">Bienvenido (ADMIN)</div>
                
                <div class="flex items-center">
                    <select class="mr-4 p-1 bg-navy text-white border border-gray-600 rounded text-sm">
                        <option value="es">ES</option>
                        <option value="en">EN</option>
                    </select>
                    
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="Notificaciones">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                    
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                            AD
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block" id="userMenu">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium">Administrador</div>
                                <div class="text-gray-500">Administrador</div>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Perfil
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Configuración
                            </a>
                            <div class="border-t"></div>
                            <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Cerrar sesión
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <section class="flex-1 px-6 py-8 flex justify-center">
                <div class="w-full max-w-6xl flex flex-col">
                    <div class="mb-8 text-center">
                        <h1 class="text-darktext text-3xl font-bold mb-2.5">DISPONIBILIDAD HORARIA</h1>
                        <p class="text-muted text-lg">Seleccione sus horas disponibles.</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse mb-6">
                                <thead>
                                    <tr>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Hora</th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Lunes</th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Martes</th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Miércoles</th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Jueves</th>
                                        <th class="bg-darkblue text-white p-3 text-center font-semibold border border-gray-300">Viernes</th>
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
                                Siguiente
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
        });
    </script>
</body>
</html>