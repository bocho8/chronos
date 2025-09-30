<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidad Horaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos para el texto en las celdas */
        .cell-text {
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        <aside class="w-64 bg-white flex flex-col shadow-lg">
            <div class="h-16 flex items-center justify-center bg-[#002366] border-b border-blue-950">
                <div class="flex items-center space-x-2">
                    <img src="/assets/images/LogoScuola.png" alt="Logo" class="h-8 w-auto">
                    <h1 class="text-lg font-bold text-white">Scuola Italiana</h1>
                </div>
            </div>
            <nav class="flex-1 px-4 py-4">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center p-2 text-base font-normal text-gray-600 rounded-lg hover:bg-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            <span class="ml-3">Inicio</span>
                        </a>
                    </li>
                    <li>
                        <a href="coordinador-docentes.php" class="flex items-center p-2 text-base font-normal text-gray-600 rounded-lg hover:bg-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197m0 0A6.995 6.995 0 0012 12a6.995 6.995 0 00-3-5.197M15 21a9 9 0 00-9-9"></path></svg>
                            <span class="ml-3">Docentes</span>
                        </a>
                    </li>
                    <li>
                        <a href="coordinador-calendario.php" class="flex items-center p-2 text-base font-normal text-gray-600 rounded-lg hover:bg-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="ml-3">Calendario</span>
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
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
            </header>

            <main class="flex-1 p-8 overflow-y-auto flex flex-col items-center">
                <div class="max-w-4xl w-full text-center">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-6">Seleccione sus horas disponibles.</h2>
                    
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
        // Obtener todas las celdas con la clase 'cell'
        const cells = document.querySelectorAll('.cell');

        // Agregar un event listener a cada celda
        cells.forEach(cell => {
            cell.addEventListener('click', () => {
                // Comprobar si la celda es "Disponible" (verde)
                if (cell.classList.contains('bg-green-200')) {
                    // Cambiar a "No disponible" (rojo)
                    cell.classList.remove('bg-green-200', 'text-green-800');
                    cell.classList.add('bg-red-200', 'text-red-800');
                    cell.textContent = 'No disponible';
                } else {
                    // Si es "No disponible" (rojo), cambiar a "Disponible" (verde)
                    cell.classList.remove('bg-red-200', 'text-red-800');
                    cell.classList.add('bg-green-200', 'text-green-800');
                    cell.textContent = 'Disponible';
                }
            });
        });
    </script>

</body>
</html>