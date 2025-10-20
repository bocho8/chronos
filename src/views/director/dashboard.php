<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Dashboard principal del director
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
    <title>Bienvenido (Director)</title>
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
            
            <nav class="w-full p-4 md:p-6">
                <ul>
                    <li class="mb-3 md:mb-4">
                        <a href="dashboard.php" class="flex items-center p-2 text-gray-600 hover:bg-gray-200 rounded-md text-sm md:text-base
                            <?php echo ($current_page == 'dashboard.php' || $current_page == 'index.php') ? 'bg-blue-100 text-blue-800 font-semibold' : ''; ?>">
                            <span class="hidden md:inline">Inicio</span>
                            <span class="md:hidden">🏠</span>
                        </a>
                    </li>
                    <li class="mb-3 md:mb-4">
                        <a href="director-horarios.php" class="flex items-center p-2 text-gray-600 hover:bg-gray-200 rounded-md text-sm md:text-base">
                            <span class="hidden md:inline">Horarios Semanales</span>
                            <span class="md:hidden">📅</span>
                        </a>
                    </li>
                    <li class="mb-3 md:mb-4">
                        <a href="director-asignacion.php" class="flex items-center p-2 text-gray-600 hover:bg-gray-200 rounded-md text-sm md:text-base">
                            <span class="hidden md:inline">Asignación Docentes</span>
                            <span class="md:hidden">👥</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-center items-center px-4 md:px-8 shadow-md bg-blue-900 h-16 md:h-20">
                <h1 class="text-xl md:text-3xl font-bold text-white">Bienvenido (Director)</h1>
                <button class="absolute right-4 md:right-8 text-white hover:text-gray-300 h-full flex items-center"> 
                    <img class="w-6 h-6 md:w-8 md:h-8" src="/assets/images/icons/menu.png" alt="menu" />
                </button>
            </header>
            <main class="flex-1 p-4 md:p-8 overflow-y-auto">
                <div class="max-w-6xl mx-auto">
                    <div class="flex flex-col items-center mb-8 md:mb-12">
                        <h2 class="text-lg md:text-2xl font-semibold text-gray-800 mb-3 md:mb-4 text-center">DIRECTORA: <?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></h2>
                        <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                            <input type="text" class="px-3 md:px-4 py-2 border rounded-md text-gray-700 text-sm md:text-base" placeholder="Julio 2025" readonly>
                            <button class="px-4 md:px-6 py-2 bg-blue-900 text-white font-medium rounded-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-opacity-50 text-sm md:text-base">
                                Firma Digital
                            </button>
                        </div>
                    </div>

                    <div class="mb-8 md:mb-12">
                        <h3 class="text-lg md:text-xl font-semibold text-gray-700 mb-4 md:mb-6">Resumen de Estado</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                            <div class="bg-white p-4 md:p-6 rounded-lg shadow-md border border-gray-200 text-center">
                                <p class="text-gray-500 text-xs md:text-sm">Horarios por Aprobar</p>
                                <p class="text-2xl md:text-4xl font-bold text-gray-800 mt-2">3</p>
                            </div>
                            <div class="bg-white p-4 md:p-6 rounded-lg shadow-md border border-gray-200 text-center">
                                <p class="text-gray-500 text-xs md:text-sm">Docentes sin Grupo</p>
                                <p class="text-2xl md:text-4xl font-bold text-gray-800 mt-2">2</p>
                            </div>
                            <div class="bg-white p-4 md:p-6 rounded-lg shadow-md border border-gray-200 text-center">
                                <p class="text-gray-500 text-xs md:text-sm">Próximos Vencimientos</p>
                                <p class="text-2xl md:text-4xl font-bold text-gray-800 mt-2">3</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-6">Próximos Vencimientos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                                <p class="text-gray-800 font-medium text-lg">Certificado de Inscripción</p>
                                <p class="text-sm text-gray-500 mt-1">Vence en 10 días</p>
                                <p class="text-red-600 font-semibold text-xs mt-2">Urgente</p>
                            </div>
                            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                                <p class="text-gray-800 font-medium text-lg">Informe de Inspección</p>
                                <p class="text-sm text-gray-500 mt-1">Vence en 15 días</p>
                            </div>
                            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                                <p class="text-gray-800 font-medium text-lg">Documentación ANEP</p>
                                <p class="text-sm text-gray-500 mt-1">Vence en 30 días</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>