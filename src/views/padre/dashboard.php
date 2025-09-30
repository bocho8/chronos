<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido (Padre/Madre)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'school-blue-dark': '#0f3c7a', // El azul oscuro de la imagen
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans text-gray-800">

    <div class="flex h-screen">
        <aside class="w-64 bg-white shadow-xl flex flex-col">
            
            <div class="flex items-center space-x-2 w-full pl-6 py-5 h-20 bg-school-blue-dark text-white border-b border-blue-900/50">
                <img src="/assets/images/LogoScuola.png" alt="Logo" class="h-8 w-auto"> 
                <span class="text-xl font-bold leading-tight">Scuola Italiana<br>di Montevideo</span>
            </div>
            
            <nav class="w-full mt-2 p-4">
                <ul>
                    <li class="mb-2">
                        <a href="dashboard.php" class="flex items-center p-3 text-school-blue-dark font-semibold rounded-md shadow-md bg-blue-100">
                             <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Horario de mi hijo
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center px-8 shadow-md bg-school-blue-dark h-20">
                <div class="flex-1 flex justify-center items-center">
                    <h1 class="text-3xl font-bold text-white">Bienvenido (Padre/Madre)</h1>
                </div>
                <button class="text-white hover:text-gray-300 h-full flex items-center absolute right-4"> 
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </header>

            <main class="flex-1 p-8 overflow-y-auto">
                <div class="max-w-6xl mx-auto flex flex-col items-center"> 
                    
                    <div class="mb-12 w-full">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">Bienvenido al Panel de Seguimiento</h2>
                        <p class="text-gray-600 mb-8 text-center">Aquí puedes monitorear el progreso académico de tu hijo.</p>
                        
                        <div class="mb-16">
                            
                            <h3 class="text-3xl font-semibold text-gray-800 mb-6 text-center">Calendario semanal</h3>
                            
                            <div class="flex justify-center w-full"> 
                                <div class="w-full max-w-sm p-4 bg-white rounded-lg shadow-lg border border-gray-200">
                                    <div class="text-center font-bold text-white bg-school-blue-dark p-2 rounded-t-lg">Horarios semanales</div>
                                    <div class="grid grid-cols-5 text-center text-sm font-semibold text-gray-700 border-b border-gray-300">
                                        <div class="py-2 border-r border-gray-200">Lunes</div>
                                        <div class="py-2 border-r border-gray-200">Martes</div>
                                        <div class="py-2 border-r border-gray-200">Miércoles</div>
                                        <div class="py-2 border-r border-gray-200">Jueves</div>
                                        <div class="py-2">Viernes</div>
                                    </div>
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <div class="grid grid-cols-5 text-center text-xs text-gray-600 border-b border-gray-200">
                                            <div class="py-2 border-r border-gray-200">Text</div>
                                            <div class="py-2 border-r border-gray-200">Text</div>
                                            <div class="py-2 border-r border-gray-200">Text</div>
                                            <div class="py-2 border-r border-gray-200">Text</div>
                                            <div class="py-2">Text</div>
                                        </div>
                                    <?php endfor; ?>
                                    
                                    <div class="grid grid-cols-5 text-center text-xs font-semibold text-white bg-gray-500">
                                        <div class="py-2 col-span-5">ALMUERZO</div>
                                    </div>

                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                        <div class="grid grid-cols-5 text-center text-xs text-gray-600 border-b border-gray-200">
                                            <div class="py-2 border-r border-gray-200">Text</div>
                                            <div class="py-2 border-r border-gray-200">Text</div>
                                            <div class="py-2 border-r border-gray-200">Text</div>
                                            <div class="py-2 border-r border-gray-200">Text</div>
                                            <div class="py-2">Text</div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center mb-8 w-full">
                            <span class="text-3xl text-gray-400">...</span>
                        </div>

                        <div class="w-full">
                            <h3 class="text-3xl font-semibold text-gray-800 mb-6">Próximas Evaluaciones</h3>
                            
                            <div class="space-y-4">
                                
                                <div class="flex items-center p-4 bg-white rounded-lg shadow-md border border-gray-200">
                                    <div class="flex flex-col items-center justify-center w-12 h-12 bg-red-500 text-white rounded-full flex-shrink-0">
                                        <span class="text-lg font-bold">17</span>
                                    </div>
                                    <div class="ml-4 flex-grow">
                                        <p class="text-lg font-medium text-gray-800">Matemáticas</p>
                                        <p class="text-sm text-gray-500">Examen Final</p>
                                    </div>
                                    <div class="text-right text-sm font-semibold text-gray-600">
                                        Fecha: 25/10/2023
                                    </div>
                                </div>

                                <div class="flex items-center p-4 bg-white rounded-lg shadow-md border border-gray-200">
                                    <div class="flex flex-col items-center justify-center w-12 h-12 bg-red-500 text-white rounded-full flex-shrink-0">
                                        <span class="text-lg font-bold">17</span>
                                    </div>
                                    <div class="ml-4 flex-grow">
                                        <p class="text-lg font-medium text-gray-800">Programación</p>
                                        <p class="text-sm text-gray-500">Trabajo Práctico</p>
                                    </div>
                                    <div class="text-right text-sm font-semibold text-gray-600">
                                        Fecha: 27/10/2023
                                    </div>
                                </div>
                                
                                <div class="flex items-center p-4 bg-white rounded-lg shadow-md border border-gray-200">
                                    <div class="flex flex-col items-center justify-center w-12 h-12 bg-red-500 text-white rounded-full flex-shrink-0">
                                        <span class="text-lg font-bold">17</span>
                                    </div>
                                    <div class="ml-4 flex-grow">
                                        <p class="text-lg font-medium text-gray-800">Sistemas Operativos</p>
                                        <p class="text-sm text-gray-500">Presentación de Proyecto</p>
                                    </div>
                                    <div class="text-right text-sm font-semibold text-gray-600">
                                        Fecha: 30/10/2023
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>