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
    <title>Disponibilidad Horaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        
        .cell-text {
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        <aside class="w-56 md:w-64 bg-white flex flex-col shadow-lg">
            <div class="h-16 flex items-center justify-center bg-[#002366] border-b border-blue-950">
                <div class="flex items-center space-x-2">
                    <img src="/assets/images/LogoScuola.png" alt="Logo" class="h-6 md:h-8 w-auto">
                    <h1 class="text-sm md:text-lg font-bold text-white hidden sm:block">Scuola Italiana</h1>
                    <h1 class="text-xs font-bold text-white sm:hidden">SIM</h1>
                </div>
            </div>
            <nav class="flex-1 px-3 md:px-4 py-3 md:py-4">
                <ul class="space-y-1 md:space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center p-2 text-sm md:text-base font-normal text-gray-600 rounded-lg hover:bg-gray-100">
                            <span class="text-sm">📋</span>
                            <span class="ml-2 md:ml-3 hidden sm:inline">Inicio</span>
                        </a>
                    </li>
                    <li>
                        <a href="coordinador-docentes.php" class="flex items-center p-2 text-sm md:text-base font-normal text-gray-600 rounded-lg hover:bg-gray-100">
                            <span class="text-sm">📋</span>
                            <span class="ml-2 md:ml-3 hidden sm:inline">Docentes</span>
                        </a>
                    </li>
                    <li>
                        <a href="coordinador-calendario.php" class="flex items-center p-2 text-sm md:text-base font-normal text-gray-600 rounded-lg hover:bg-gray-100">
                            <span class="text-sm">📋</span>
                            <span class="ml-2 md:ml-3 hidden sm:inline">Calendario</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="h-16 bg-[#002366] text-white flex items-center justify-center px-6 shadow-md relative">
                <div class="text-xl font-semibold">
                    DISPONIBILIDAD HORARIA
                </div>
                <div class="absolute top-1/2 right-6 transform -translate-y-1/2">
                    <button class="focus:outline-none">
                        <span class="text-sm">📋</span>
                    </button>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-8 overflow-y-auto flex flex-col items-center">
                <div class="max-w-4xl w-full text-center">
                    <h2 class="text-xl md:text-2xl font-semibold text-gray-700 mb-4 md:mb-6">Seleccione sus horas disponibles.</h2>
                    
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm">
                                    <th class="py-3 px-4 text-left font-medium w-32 border-b border-r border-gray-200">Hora</th>
                                    <th class="py-3 px-4 font-medium border-b border-r border-gray-200">Lunes</th>
                                    <th class="py-3 px-4 font-medium border-b border-r border-gray-200">Martes</th>
                                    <th class="py-3 px-4 font-medium border-b border-r border-gray-200">Miércoles</th>
                                    <th class="py-3 px-4 font-medium border-b border-r border-gray-200">Jueves</th>
                                    <th class="py-3 px-4 font-medium border-b border-gray-200">Viernes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">8:00 - 8:45</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">8:45 - 9:30</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">9:45 - 10:30</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">10:30 - 11:15</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">11:30 - 12:15</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">12:15 - 13:00</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 bg-green-200 text-green-800 cell-text cursor-pointer">Disponible</td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">13:30 - 14:15</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">14:15 - 15:00</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">15:15 - 16:00</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 text-left font-medium text-gray-800 border-r border-gray-200">16:00 - 16:45</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 border-r border-gray-200 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                    <td class="cell py-3 bg-red-200 text-red-800 cell-text cursor-pointer">No disponible</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button class="mt-8 px-8 py-3 bg-blue-800 text-white font-medium rounded-md hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        Siguiente
                    </button>
                </div>
            </main>
        </div>
    </div>

    <script>

        const cells = document.querySelectorAll('.cell');

        cells.forEach(cell => {
            cell.addEventListener('click', () => {

                if (cell.classList.contains('bg-green-200')) {

                    cell.classList.remove('bg-green-200', 'text-green-800');
                    cell.classList.add('bg-red-200', 'text-red-800');
                    cell.textContent = 'No disponible';
                } else {

                    cell.classList.remove('bg-red-200', 'text-red-800');
                    cell.classList.add('bg-green-200', 'text-green-800');
                    cell.textContent = 'Disponible';
                }
            });
        });
    </script>

</body>
</html>