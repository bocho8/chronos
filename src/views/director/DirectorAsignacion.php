<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Módulo de Asignación de Docentes para el director
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../helpers/Translation.php';

initSecureSession();

$translation = Translation::getInstance();

AuthHelper::requireRole('DIRECTOR');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$user = AuthHelper::getCurrentUser();

$current_page = basename($_SERVER['PHP_SELF']); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Docentes (Director)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans text-gray-800">

    <div class="flex h-screen">
        <aside class="w-64 bg-white shadow-lg flex flex-col items-center">
            
            <div class="flex items-center space-x-2 w-full pl-6 bg-blue-900 text-white h-20">
                <img src="assets/images/LogoScuola.png" alt="Logo" class="h-10 w-10">
                <span class="text-xl font-bold leading-tight">Scuola Italiana<br>di Montevideo</span>
            </div>
            
            <nav class="w-full p-6">
                <ul>
                    <li class="mb-4">
                        <a href="dashboard.php" class="flex items-center p-2 text-gray-600 hover:bg-gray-200 rounded-md 
                            <?php echo ($current_page == 'dashboard.php' || $current_page == 'index.php') ? 'bg-blue-100 text-blue-800 font-semibold' : ''; ?>">
                            Inicio
                        </a>
                    </li>
                    <li class="mb-4">
                        <a href="director-horarios.php" class="flex items-center p-2 text-gray-600 hover:bg-gray-200 rounded-md 
                            <?php echo ($current_page == 'director-horarios.php') ? 'bg-blue-100 text-blue-800 font-semibold' : ''; ?>">
                            Horarios Semanales
                        </a>
                    </li>
                    <li class="mb-4">
                        <a href="director-asignacion.php" class="flex items-center p-2 text-gray-600 hover:bg-gray-200 rounded-md
                            <?php echo ($current_page == 'director-asignacion.php') ? 'bg-blue-100 text-blue-800 font-semibold' : ''; ?>">
                            Asignación Docentes
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-center items-center px-8 shadow-md bg-blue-900 h-20">
                <h1 class="text-3xl font-bold text-white">Bienvenido (Director)</h1>
                <button class="absolute right-8 text-white hover:text-gray-300 h-full flex items-center"> 
                    <span class="text-sm">📋</span>
                </button>
            </header>
            <main class="flex-1 p-8 overflow-y-auto">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-4xl font-bold text-gray-800 mb-2 text-center">Módulo de Asignación de Docentes</h2>
                    <p class="text-gray-500 mb-8 text-center">Asigna grupos y materias a los docentes utilizando drag & drop</p>
                    
                    <div class="flex justify-center space-x-4 mb-12">
                        <button class="px-6 py-2 bg-blue-900 text-white font-medium rounded-md hover:bg-blue-800">Docentes</button>
                        <button class="px-6 py-2 bg-gray-200 text-gray-800 font-medium rounded-md hover:bg-gray-300">Carga Horaria</button>
                    </div>

                    <h3 class="text-3xl font-bold text-gray-800 mb-8 text-center">Listado de Docentes</h3>
                    <div class="flex justify-center space-x-12 mb-16">
                        
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-white border border-gray-300 rounded-full flex items-center justify-center mb-2">
                                <span class="text-gray-400 text-2xl">•</span>
                            </div>
                            <p class="font-semibold text-gray-800">Mario Gubenio</p>
                            <p class="text-sm text-gray-500">Química</p>
                            <p class="text-md font-bold text-gray-700 mt-1">10 horas asignadas</p>
                        </div>
                        
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-white border border-gray-300 rounded-full flex items-center justify-center mb-2">
                                <span class="text-gray-400 text-2xl">•</span>
                            </div>
                            <p class="font-semibold text-gray-800">Marcelo Sime</p>
                            <p class="text-sm text-gray-500">Física/Electrónica</p>
                            <p class="text-md font-bold text-gray-700 mt-1">8 horas asignadas</p>
                        </div>

                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-white border border-gray-300 rounded-full flex items-center justify-center mb-2">
                                <span class="text-gray-400 text-2xl">•</span>
                            </div>
                            <p class="font-semibold text-gray-800">Lourdes Canflore</p>
                            <p class="text-sm text-gray-500">Italiano</p>
                            <p class="text-md font-bold text-gray-700 mt-1">12 horas asignadas</p>
                        </div>
                    </div>

                    <h3 class="text-3xl font-bold text-gray-800 mb-6 text-center">Asignar Docente</h3>
                    <div class="bg-white p-8 rounded-lg shadow-xl border border-gray-200 max-w-4xl mx-auto">
                        <div class="flex flex-wrap justify-center space-x-6 space-y-4 md:space-y-0">
                            
                            <div class="flex flex-col items-center">
                                <label class="text-sm font-medium text-gray-700 mb-2">Seleccionar Docente</label>
                                <div class="flex flex-wrap justify-center gap-2">
                                    <span class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full cursor-pointer">Diego Rivero</span>
                                    <span class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-full cursor-pointer hover:bg-gray-200">Mary Cortavido</span>
                                    <span class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-full cursor-pointer hover:bg-gray-200">Bastian Farias</span>
                                </div>
                            </div>
                            
                            <div class="flex flex-col items-center">
                                <label class="text-sm font-medium text-gray-700 mb-2">Seleccionar Grupo/Materia</label>
                                <div class="flex flex-wrap justify-center gap-2">
                                    <span class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-full cursor-pointer hover:bg-gray-200">Matemáticas</span>
                                    <span class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full cursor-pointer">Programación</span>
                                    <span class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-full cursor-pointer hover:bg-gray-200">Física</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center space-x-4 mt-8">
                            <button class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-100">Revertir Cambios</button>
                            <button class="px-6 py-2 bg-blue-900 text-white font-medium rounded-md hover:bg-blue-800">Asignar</button>
                        </div>
                    </div>
                    
                </div>
            </main>
        </div>
    </div>
</body>
</html>