<?php
/**
 * Dashboard principal del director
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../helpers/Translation.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();

// Require authentication and director role
AuthHelper::requireRole('DIRECTOR');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Get user data
$user = AuthHelper::getCurrentUser();

// Definimos la variable para saber en qué archivo estamos.
// Usaremos esta variable para resaltar el botón activo.
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
        <aside class="w-64 bg-white shadow-lg flex flex-col items-center">
            
            <div class="flex items-center space-x-2 w-full pl-6 bg-blue-900 text-white h-20">
                <img src="/assets/images/LogoScuola.png" alt="Logo" class="h-8 w-auto">
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
                        <a href="director-horarios.php" class="flex items-center p-2 text-gray-600 hover:bg-gray-200 rounded-md">
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
                <h1 class="text-3xl font-bold text-white">Bienvenido (Director)</h1>
                <button class="absolute right-8 text-white hover:text-gray-300 h-full flex items-center"> 
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </header>
            <main class="flex-1 p-8 overflow-y-auto">
                <div class="max-w-6xl mx-auto">
                    <div class="flex flex-col items-center mb-12">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">DIRECTORA: <?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></h2>
                        <div class="flex items-center space-x-4">
                            <input type="text" class="px-4 py-2 border rounded-md text-gray-700" placeholder="Julio 2025" readonly>
                            <button class="px-6 py-2 bg-blue-900 text-white font-medium rounded-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-opacity-50">
                                Firma Digital
                            </button>
                        </div>
                    </div>

                    <div class="mb-12">
                        <h3 class="text-xl font-semibold text-gray-700 mb-6">Resumen de Estado</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 text-center">
                                <p class="text-gray-500 text-sm">Horarios por Aprobar</p>
                                <p class="text-4xl font-bold text-gray-800 mt-2">3</p>
                            </div>
                            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 text-center">
                                <p class="text-gray-500 text-sm">Docentes sin Grupo</p>
                                <p class="text-4xl font-bold text-gray-800 mt-2">2</p>
                            </div>
                            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 text-center">
                                <p class="text-gray-500 text-sm">Próximos Vencimientos</p>
                                <p class="text-4xl font-bold text-gray-800 mt-2">3</p>
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