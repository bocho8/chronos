<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/RoleMiddleware.php';

// Require Admin Controllers
require_once __DIR__ . '/../app/Controllers/Admin/TeacherController.php';
require_once __DIR__ . '/../app/Controllers/Admin/AssignmentController.php';
require_once __DIR__ . '/../app/Controllers/Admin/SubjectController.php';
require_once __DIR__ . '/../app/Controllers/Admin/GroupController.php';
require_once __DIR__ . '/../app/Controllers/Admin/UserController.php';
require_once __DIR__ . '/../app/Controllers/Admin/CoordinatorController.php';
require_once __DIR__ . '/../app/Controllers/Admin/TranslationController.php';
require_once __DIR__ . '/../app/Controllers/Admin/ParentAssignmentController.php';
require_once __DIR__ . '/../app/Controllers/Admin/GroupSubjectController.php';

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

$router = new Router();

$router->get('/', function() {
    header('Location: /login');
    exit();
});

$router->get('/login', function() {
    require __DIR__ . '/../views/login.php';
});

$router->post('/login', function() {
    require __DIR__ . '/../controllers/LoginController.php';
});

$router->get('/logout', function() {
    require __DIR__ . '/../controllers/LogoutController.php';
});

// File not found error route
$router->get('/file-not-found', function() {
    $errorController = new \App\Controllers\ErrorController();
    $filePath = $_GET['file'] ?? null;
    $errorController->showFileNotFound($filePath);
});

$router->group(['middleware' => ['auth']], function($router) {

    // Role-specific dashboards - keep prefixes
    $router->group(['prefix' => '/admin', 'middleware' => ['admin']], function($router) {
        $router->get('/dashboard', function() {
            require __DIR__ . '/../views/admin/dashboard.php';
        });
    });

    // Shared routes - accessible by admin and coordinator (no prefix)
    $router->group(['middleware' => ['adminOrCoordinator']], function($router) {
        $router->get('/availability', function() {
            require __DIR__ . '/../views/admin/AdminDisponibilidad.php';
        });

        $router->get('/assignments', 'Admin\AssignmentController@index');
        $router->get('/assignments/create', 'Admin\AssignmentController@create');
        $router->post('/assignments', 'Admin\AssignmentController@store');
        $router->get('/assignments/{id}', 'Admin\AssignmentController@show');
        $router->get('/assignments/{id}/edit', 'Admin\AssignmentController@edit');
        $router->put('/assignments/{id}', 'Admin\AssignmentController@update');
        $router->delete('/assignments/{id}', 'Admin\AssignmentController@destroy');

        $router->get('/reports', function() {
            require __DIR__ . '/../views/admin/AdminReportes.php';
        });

        // API routes for shared resources
        $router->post('/api/assignments', 'Admin\AssignmentController@handleRequest');
    });

    // Admin-only routes - no prefix but admin middleware
    $router->group(['middleware' => ['admin']], function($router) {
        $router->get('/teachers', function() {
            require __DIR__ . '/../views/admin/AdminDocentes.php';
        });
        $router->get('/teachers/create', 'Admin\TeacherController@create');
        $router->post('/teachers', 'Admin\TeacherController@store');
        $router->get('/teachers/{id}', 'Admin\TeacherController@show');
        $router->get('/teachers/{id}/edit', 'Admin\TeacherController@edit');
        $router->put('/teachers/{id}', 'Admin\TeacherController@update');
        $router->delete('/teachers/{id}', 'Admin\TeacherController@destroy');
        $router->get('/teachers/search', 'Admin\TeacherController@search');

        $router->get('/subjects', 'Admin\SubjectController@index');
        $router->get('/subjects/create', 'Admin\SubjectController@create');
        $router->post('/subjects', 'Admin\SubjectController@store');
        $router->get('/subjects/{id}', 'Admin\SubjectController@show');
        $router->get('/subjects/{id}/edit', 'Admin\SubjectController@edit');
        $router->put('/subjects/{id}', 'Admin\SubjectController@update');
        $router->delete('/subjects/{id}', 'Admin\SubjectController@destroy');

        $router->get('/groups', 'Admin\GroupController@index');
        $router->get('/groups/create', 'Admin\GroupController@create');
        $router->post('/groups', 'Admin\GroupController@store');
        $router->get('/groups/{id}', 'Admin\GroupController@show');
        $router->get('/groups/{id}/edit', 'Admin\GroupController@edit');
        $router->put('/groups/{id}', 'Admin\GroupController@update');
        $router->delete('/groups/{id}', 'Admin\GroupController@destroy');

        $router->get('/schedules', function() {
            require __DIR__ . '/../views/admin/AdminPublicarHorarios.php';
        });
        $router->get('/schedules/create', function() {
            require __DIR__ . '/../views/admin/AdminGestionHorarios.php';
        });
        $router->post('/schedules', function() {
            require __DIR__ . '/../controllers/HorarioHandler.php';
        });
        $router->get('/schedules/{id}', function() {
            require __DIR__ . '/../controllers/HorarioHandler.php';
        });
        $router->get('/schedules/{id}/edit', function() {
            require __DIR__ . '/../controllers/HorarioHandler.php';
        });
        $router->put('/schedules/{id}', function() {
            require __DIR__ . '/../controllers/HorarioHandler.php';
        });
        $router->delete('/schedules/{id}', function() {
            require __DIR__ . '/../controllers/HorarioHandler.php';
        });

        $router->get('/gestion-horarios', function() {
            require __DIR__ . '/../views/admin/AdminGestionHorarios.php';
        });
        
        $router->get('/bloques', function() {
            require __DIR__ . '/../views/admin/AdminBloques.php';
        });
        
        $router->get('/horarios', function() {
            require __DIR__ . '/../views/admin/AdminHorarios.php';
        });
        
        $router->get('/view-schedules', function() {
            require __DIR__ . '/../views/admin/AdminScheduleViewer.php';
        });
        
        $router->get('/test-schedule-viewer', function() {
            require __DIR__ . '/../views/admin/TestScheduleViewer.php';
        });
        
        $router->get('/simple-test', function() {
            require __DIR__ . '/../views/admin/SimpleTest.php';
        });

        $router->get('/users', 'Admin\UserController@index');
        $router->get('/users/create', 'Admin\UserController@create');
        $router->post('/users', 'Admin\UserController@store');
        $router->get('/users/{id}', 'Admin\UserController@show');
        $router->get('/users/{id}/edit', 'Admin\UserController@edit');
        $router->put('/users/{id}', 'Admin\UserController@update');
        $router->delete('/users/{id}', 'Admin\UserController@destroy');

        // Coordinator Management Routes
        $router->get('/coordinators', 'Admin\CoordinatorController@index');
        $router->get('/coordinators/create', 'Admin\CoordinatorController@create');
        $router->post('/coordinators', 'Admin\CoordinatorController@store');
        $router->get('/coordinators/{id}', 'Admin\CoordinatorController@show');
        $router->get('/coordinators/{id}/edit', 'Admin\CoordinatorController@edit');
        $router->put('/coordinators/{id}', 'Admin\CoordinatorController@update');
        $router->delete('/coordinators/{id}', 'Admin\CoordinatorController@destroy');

        // Parent Assignment Routes
        $router->get('/parent-assignments', 'Admin\ParentAssignmentController@index');
        $router->post('/api/parent-assignments', 'Admin\ParentAssignmentController@handleRequest');
        
        // Group Subject Assignment Routes
        $router->get('/group-subjects', 'Admin\GroupSubjectController@index');
        $router->post('/api/group-subjects', 'Admin\GroupSubjectController@handleRequest');

        // Translation Management Routes
        $router->get('/translations', 'Admin\TranslationController@index');
        $router->get('/translations-test', function() {
            echo "Translation route is working!";
        });
        $router->get('/translations-simple', function() {
            require_once __DIR__ . '/../app/Controllers/Admin/TranslationController.php';
            $controller = new \App\Controllers\Admin\TranslationController();
            $controller->index();
        });
        $router->get('/translations/all', 'Admin\TranslationController@getAll');
        $router->get('/translations/missing', 'Admin\TranslationController@getMissing');
        $router->post('/translations/update', 'Admin\TranslationController@update');
        $router->post('/translations/bulk-update', 'Admin\TranslationController@bulkUpdate');
        $router->get('/translations/export', 'Admin\TranslationController@export');
        $router->get('/translations/statistics', 'Admin\TranslationController@getStatistics');
        $router->post('/translations/fill-missing', 'Admin\TranslationController@fillMissing');
        $router->post('/translations/validate-key', 'Admin\TranslationController@validateKey');
        $router->get('/translations/detect-spanish', 'Admin\TranslationController@detectSpanishErrors');
        $router->post('/translations/clear-all-spanish', 'Admin\TranslationController@clearAllSpanish');
        
        // API routes for admin-only resources
        $router->post('/api/teachers', 'Admin\TeacherController@handleRequest');
        $router->post('/api/subjects', 'Admin\SubjectController@handleRequest');
        $router->post('/api/groups', 'Admin\GroupController@handleRequest');
        $router->post('/api/schedules', function() {
            require __DIR__ . '/../controllers/HorarioHandler.php';
        });
        $router->post('/api/users', 'Admin\UserController@handleRequest');
        $router->post('/api/coordinators', 'Admin\CoordinatorController@handleRequest');
        
        // Publish Request API Routes
        $router->post('/api/publish-request/create', function() {
            require __DIR__ . '/../controllers/PublishRequestHandler.php';
        });
        $router->get('/api/publish-request/status', function() {
            require __DIR__ . '/../controllers/PublishRequestHandler.php';
        });
        $router->post('/api/publish-request/approve', function() {
            require __DIR__ . '/../controllers/PublishRequestHandler.php';
        });
        $router->post('/api/publish-request/reject', function() {
            require __DIR__ . '/../controllers/PublishRequestHandler.php';
        });
    });

    // Coordinator-specific routes - keep prefix for role-specific views
    $router->group(['prefix' => '/coordinator', 'middleware' => ['coordinator']], function($router) {
        
        $router->get('/dashboard', function() {
            require __DIR__ . '/../views/coordinador/dashboard.php';
        });
        
        $router->get('/teachers', function() {
            require __DIR__ . '/../views/coordinador/CoordinadorDocentes.php';
        });
        
        $router->get('/calendar', function() {
            require __DIR__ . '/../views/coordinador/CoordinadorCalendario.php';
        });
    });

    // Teacher-specific routes - keep prefix for personal views
    $router->group(['prefix' => '/teacher', 'middleware' => ['teacher']], function($router) {
        
        $router->get('/dashboard', function() {
            require __DIR__ . '/../views/docente/dashboard.php';
        });
        
        $router->get('/my-schedule', function() {
            require __DIR__ . '/../views/docente/MiHorario.php';
        });
        
        $router->get('/my-availability', function() {
            require __DIR__ . '/../views/docente/MiDisponibilidad.php';
        });
    });

    // Parent-specific routes - keep prefix
    $router->group(['prefix' => '/parent', 'middleware' => ['parent']], function($router) {
        
        $router->get('/dashboard', function() {
            require __DIR__ . '/../views/padre/dashboard.php';
        });
        
        $router->get('/students', function() {
            require __DIR__ . '/../views/padre/AdminEstudiantes.php';
        });
        
        $router->get('/student-schedules', function() {
            require __DIR__ . '/../views/padre/AdminHorariosEstudiante.php';
        });
    });
});

$router->middleware('auth', [AuthMiddleware::class, 'handle']);
$router->middleware('admin', RoleMiddleware::admin());
$router->middleware('director', RoleMiddleware::director());
$router->middleware('coordinator', RoleMiddleware::coordinator());
$router->middleware('teacher', RoleMiddleware::teacher());
$router->middleware('parent', RoleMiddleware::parent());
$router->middleware('adminOrCoordinator', RoleMiddleware::adminOrCoordinator());

$router->dispatch();
