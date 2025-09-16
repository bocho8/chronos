<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Materias — Admin</title>
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
    <style>
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
    </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-sidebar border-r border-lightborder">
            <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
                <img src="/upload/LogoScuola.png" alt="Scuola Italiana de Montevideo" class="h-9 w-auto">
                <span class="text-white font-semibold text-lg">Scuola Italiana</span>
            </div>

            <ul class="py-5 list-none">
                <li>
                    <a href="#" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        Docentes
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        Coordinadores
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-link active flex items-center py-3 px-5 text-gray-800 no-underline transition-all hover:bg-sidebarHover">
                        Materias
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-link flex items-center py-3 px-5 text-gray-600 no-underline transition-all hover:bg-sidebarHover">
                        Horarios
                    </a>
                </li>
            </ul>
        </aside>

        <main class="flex-1 flex flex-col">
            <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
                <div class="flex items-center">
                    <button class="hamburger flex flex-col bg-none border-none cursor-pointer p-1" aria-label="Menu">
                        <span></span><span></span><span></span>
                    </button>
                    <div class="text-white text-xl font-semibold ml-4">Bienvenido (ADMIN)</div>
                </div>
                <div class="flex items-center">
                    <div class="relative group mr-4">
                        <select class="p-1 bg-darkblue text-white border border-gray-600 rounded text-sm">
                            <option value="es">ES</option>
                            <option value="en">EN</option>
                        </select>
                    </div>
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

            <div class="flex-1 p-8">
                <div class="w-full max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h1 class="text-darktext text-3xl font-bold mb-1">Registros de Materias</h1>
                        <p class="text-muted text-lg">Lista de todas las materias registradas.</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-lightborder p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-darktext">Materias registradas</h2>
                            <div class="flex gap-4">
                                <button class="py-2 px-4 rounded border border-lightborder text-darktext bg-white hover:bg-gray-100 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM4 10h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zM4 16h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" />
                                    </svg>
                                    Filtrar
                                </button>
                                <button class="py-2 px-4 rounded border border-lightborder text-darktext bg-white hover:bg-gray-100 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Exportar
                                </button>
                                <button class="py-2 px-4 rounded border-none text-red-600 bg-red-100 hover:bg-red-200 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.035 21H7.965a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Eliminar Seleccionados
                                </button>
                                <button class="py-2 px-4 rounded border-none text-white bg-darkblue hover:bg-navy transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Agregar Materia
                                </button>
                            </div>
                        </div>

                        <ul class="space-y-4">
                            <li class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-lightborder hover:bg-gray-100 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 flex items-center justify-center bg-darkblue rounded-full text-white font-bold text-lg mr-4">M</div>
                                    <div>
                                        <div class="font-semibold text-darktext">Matemáticas</div>
                                        <div class="text-sm text-muted">Asignatura principal</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button class="p-2 text-muted hover:text-darkblue transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 5.232z" />
                                        </svg>
                                    </button>
                                    <button class="p-2 text-muted hover:text-red-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.035 21H7.965a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                            <li class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-lightborder hover:bg-gray-100 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 flex items-center justify-center bg-darkblue rounded-full text-white font-bold text-lg mr-4">P</div>
                                    <div>
                                        <div class="font-semibold text-darktext">Programación</div>
                                        <div class="text-sm text-muted">Asignatura técnica</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button class="p-2 text-muted hover:text-darkblue transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 5.232z" />
                                        </svg>
                                    </button>
                                    <button class="p-2 text-muted hover:text-red-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.035 21H7.965a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                            <li class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-lightborder hover:bg-gray-100 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 flex items-center justify-center bg-darkblue rounded-full text-white font-bold text-lg mr-4">S</div>
                                    <div>
                                        <div class="font-semibold text-darktext">Sistemas Operativos</div>
                                        <div class="text-sm text-muted">Asignatura técnica</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button class="p-2 text-muted hover:text-darkblue transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 5.232z" />
                                        </svg>
                                    </button>
                                    <button class="p-2 text-muted hover:text-red-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.035 21H7.965a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>