<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Coordinador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        <aside class="w-56 md:w-64 bg-white flex flex-col shadow-lg">
            <div class="h-14 md:h-16 flex items-center justify-center bg-[#002366] border-b border-blue-950">
                <div class="flex items-center space-x-2">
                    <img src="/assets/images/LogoScuola.png" alt="Logo" class="h-6 md:h-8 w-auto">
                    <h1 class="text-base md:text-lg font-bold text-white hidden sm:block">Scuola Italiana</h1>
                    <h1 class="text-sm font-bold text-white sm:hidden">SIM</h1>
                </div>
            </div>
            <nav class="flex-1 px-3 md:px-4 py-3 md:py-4">
                <ul class="space-y-1 md:space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center p-2 text-sm md:text-base font-normal text-blue-800 bg-blue-100 rounded-lg">
                            <span class="text-xs md:text-sm">📋</span>
                            <span class="ml-2 md:ml-3">Inicio</span>
                        </a>
                    </li>
                    <li>
                        <a href="coordinador-docentes.php" class="flex items-center p-2 text-sm md:text-base font-normal text-gray-600 rounded-lg hover:bg-gray-100">
                            <span class="text-xs md:text-sm">👨‍🏫</span>
                            <span class="ml-2 md:ml-3">Docentes</span>
                        </a>
                    </li>
                    <li>
                        <a href="coordinador-calendario.php" class="flex items-center p-2 text-sm md:text-base font-normal text-gray-600 rounded-lg hover:bg-gray-100">
                            <span class="text-xs md:text-sm">📅</span>
                            <span class="ml-2 md:ml-3">Calendario</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="h-14 md:h-16 bg-[#002366] text-white flex items-center justify-center px-4 md:px-6 shadow-md relative">
                <div class="text-lg md:text-xl font-semibold">
                    Bienvenido (COORDINADOR)
                </div>
                <div class="absolute top-1/2 right-4 md:right-6 transform -translate-y-1/2">
                    <button class="focus:outline-none">
                        <span class="text-sm">📋</span>
                    </button>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-8 overflow-y-auto">
                
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Bienvenido al Panel Coordinador</h2>
                    <p class="text-gray-500 mt-1 text-sm md:text-base">Gestiona docentes y horarios.</p>
                    
                    <div class="mt-6 md:mt-8 space-y-3 md:space-y-4">
                        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                            <div class="flex items-center space-x-3 md:space-x-4">
                                <div class="w-12 h-12 md:w-16 md:h-16 bg-gray-200 rounded-full"></div>
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm md:text-base">Coordinador Alberto De Mattos</h3>
                                    <p class="text-xs md:text-sm text-gray-500">Experiencia: 18 años.</p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                                <button class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cerrar Sesión</button>
                                <button class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium text-white bg-[#002366] rounded-md hover:bg-blue-900">Perfil</button>
                            </div>
                        </div>

                        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                            <div class="flex items-center space-x-3 md:space-x-4">
                                <div class="w-12 h-12 md:w-16 md:h-16 bg-gray-200 rounded-full"></div>
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm md:text-base">Coordinadora Patricia Molinari</h3>
                                    <p class="text-xs md:text-sm text-gray-500">Experiencia: 12 años.</p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                                <button class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cerrar Sesión</button>
                                <button class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium text-white bg-[#002366] rounded-md hover:bg-blue-900">Perfil</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12">
                     <h2 class="text-3xl font-bold text-gray-800">Panel de Coordinación de Horarios</h2>
                     <p class="text-gray-500 mt-1">Crea y asigna horarios para los docentes y grupos.</p>
                     <div class="mt-6 flex space-x-4">
                          <button class="px-6 py-3 font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Asignar Manual</button>
                          <button class="px-6 py-3 font-medium text-white bg-[#002366] rounded-md hover:bg-blue-900">Generar Automático</button>
                     </div>
                </div>

            </main>
        </div>
    </div>

</body>
</html>