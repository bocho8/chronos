<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Dashboard de Horarios Semanales del director
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
    <title>Horarios Semanales (Director)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans text-gray-800">

    <div class="flex h-screen">
        <aside class="w-56 md:w-64 bg-white shadow-lg flex flex-col items-center">
            
            <div class="flex items-center space-x-2 w-full pl-4 md:pl-6 bg-blue-900 text-white h-16 md:h-20">
                <img src="/assets/images/LogoScuola.png" alt="Logo" class="h-6 md:h-8 w-auto"> 
                <span class="text-lg md:text-xl font-bold leading-tight hidden sm:block">Scuola Italiana<br>di Montevideo</span>
                <span class="text-sm font-bold sm:hidden">SIM</span>
            </div>
            
            <nav class="w-full p-6">
                <ul>
                    <li class="mb-4">
                        <a href="dashboard.php" class="flex items-center p-2 text-gray-600 hover:bg-gray-200 rounded-md 
                            <?php echo ($current_page == 'dashboard.php') ? 'bg-blue-100 text-blue-800 font-semibold' : ''; ?>">
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
                        <a href="director-asignacion.php" class="flex items-center p-2 text-gray-600 hover:bg-gray-200 rounded-md">
                            Asignación Docentes
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-center items-center px-8 shadow-md bg-blue-900 h-20">
                <h1 class="text-3xl font-bold text-white">Horarios Semanales</h1>
                <button class="absolute right-8 text-white hover:text-gray-300 h-full flex items-center"> 
                    <span class="text-sm">📋</span>
                </button>
            </header>
            <main class="flex-1 p-8 overflow-y-auto">
                <div class="max-w-7xl mx-auto">
                    <h2 class="text-4xl font-bold text-gray-800 mb-8 text-center">Horarios Semanales</h2>

                    <div class="bg-white p-6 rounded-lg shadow-xl border border-gray-200 overflow-x-auto">
                        <div class="min-w-full inline-block align-middle">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-3 bg-blue-50 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r">Hora</th>
                                        <th class="px-3 py-3 bg-blue-50 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r">Lunes</th>
                                        <th class="px-3 py-3 bg-blue-50 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r">Martes</th>
                                        <th class="px-3 py-3 bg-blue-50 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r">Miércoles</th>
                                        <th class="px-3 py-3 bg-blue-50 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r">Jueves</th>
                                        <th class="px-3 py-3 bg-blue-50 text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Viernes</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php 
                                    $horas = ["8:00 - 8:45", "8:45 - 9:30", "9:30 - 10:15", "10:30 - 11:15", "11:15 - 12:00"];
                                    $materias = [
                                        ["Matemáticas", "Historia", "Física", "Lengua", "Geografía"],
                                        ["Lengua", "Matemáticas", "Física", "Historia", "Dibujo"],
                                        ["Biología", "Química", "Filosofía", "Música", "Matemáticas"],
                                        ["Ed. Física", "Inglés", "Italiano", "Taller", "Ed. Física"],
                                        ["Informática", "Taller", "Lengua", "Biología", "Historia"]
                                    ];
                                    
                                    foreach ($horas as $i => $hora) { ?>
                                        <tr>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900 bg-gray-50 border-r text-center"><?php echo $hora; ?></td>
                                            <?php foreach ($materias[$i] as $j => $materia) { ?>
                                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 border-r">
                                                    <div class="p-1 bg-blue-50 rounded-md text-center">
                                                        <?php echo $materia; ?>
                                                    </div>
                                                </td>
                                            <?php } ?>
                                        </tr>
                                    <?php } ?>
                                    
                                    <tr class="bg-yellow-50">
                                        <td colspan="6" class="px-3 py-2 text-center text-sm font-semibold text-gray-700">
                                            RECREO / ALMUERZO
                                        </td>
                                    </tr>
                                    
                                    <?php 
                                    $horas_tarde = ["13:00 - 13:45", "13:45 - 14:30", "14:30 - 15:15", "15:30 - 16:15", "16:15 - 17:00"];
                                    $materias_tarde = [
                                        ["Taller", "Taller", "Italiano", "Inglés", "Ed. Cívica"],
                                        ["Inglés", "Italiano", "Taller", "Taller", "Informática"],
                                        ["Programación", "Programación", "Música", "Artes", "Artes"],
                                        ["Lengua", "Matemáticas", "Geografía", "Física", "Química"],
                                        ["Historia", "Filosofía", "Biología", "Lengua", "Matemáticas"]
                                    ];
                                    
                                    foreach ($horas_tarde as $i => $hora) { ?>
                                        <tr>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900 bg-gray-50 border-r text-center"><?php echo $hora; ?></td>
                                            <?php foreach ($materias_tarde[$i] as $j => $materia) { ?>
                                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 border-r">
                                                    <div class="p-1 bg-blue-50 rounded-md text-center">
                                                        <?php echo $materia; ?>
                                                    </div>
                                                </td>
                                            <?php } ?>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>