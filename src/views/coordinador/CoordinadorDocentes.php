<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docentes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        
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
                            <span class="text-sm">📋</span>
                            <span class="ml-3">Inicio</span>
                        </a>
                    </li>
                    <li>
                        <a href="coordinador-docentes.php" class="flex items-center p-2 text-base font-normal text-blue-800 bg-blue-100 rounded-lg">
                            <span class="text-sm">📋</span>
                            <span class="ml-3">Docentes</span>
                        </a>
                    </li>
                    <li>
                        <a href="coordinador-calendario.php" class="flex items-center p-2 text-base font-normal text-gray-600 rounded-lg hover:bg-gray-100">
                            <span class="text-sm">📋</span>
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
                        <span class="text-sm">📋</span>
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
                                <span class="text-sm">📋</span>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-bold text-gray-800">Juan Pérez</h3>
                                <p class="text-sm text-gray-500">Matemáticas</p>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-lg shadow-sm flex items-center">
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center text-blue-700">
                                <span class="text-sm">📋</span>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-bold text-gray-800">Ana Gómez</h3>
                                <p class="text-sm text-gray-500">Historia</p>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-lg shadow-sm flex items-center">
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center text-blue-700">
                                <span class="text-sm">📋</span>
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