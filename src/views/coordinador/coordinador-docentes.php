<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docentes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Puedes añadir aquí pequeñas personalizaciones si es necesario */
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
                        <a href="coordinador-docentes.php" class="flex items-center p-2 text-base font-normal text-blue-800 bg-blue-100 rounded-lg">
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
                    Bienvenido (COORDINADOR)
                </div>
                <div class="absolute top-1/2 right-6 transform -translate-y-1/2">
                    <button class="focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
            </header>

            <main class="flex-1 p-8 overflow-y-auto">
                <div class="max-w-4xl mx-auto">
                    <h2 class="text-3xl font-bold text-gray-800">Registros de Docentes</h2>
                    <p class="text-gray-500 mt-1">Lista de todos los docentes registrados.</p>
                    
                    <div class="mt-6 flex space-x-4">
                        <button class="px-6 py-3 font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Eliminar Seleccionados</button>
                        <button class="px-6 py-3 font-medium text-white bg-[#002366] rounded-md hover:bg-blue-900">Agregar Docente</button>
                    </div>

                    <div class="mt-8 space-y-4">
                        <div class="bg-white p-4 rounded-lg shadow-sm flex items-center">
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center text-blue-700">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-bold text-gray-800">Juan Pérez</h3>
                                <p class="text-sm text-gray-500">Matemáticas</p>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-lg shadow-sm flex items-center">
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center text-blue-700">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-bold text-gray-800">Ana Gómez</h3>
                                <p class="text-sm text-gray-500">Historia</p>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-lg shadow-sm flex items-center">
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center text-blue-700">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-bold text-gray-800">Luis Rodríguez</h3>
                                <p class="text-sm text-gray-500">Biología</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>