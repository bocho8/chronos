<?php

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

$router = new Router();

// Public routes
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

// Protected routes
$router->group(['middleware' => ['auth']], function($router) {
    
    // Admin routes
    $router->group(['prefix' => '/admin', 'middleware' => ['admin']], function($router) {
        
        // Dashboard
        $router->get('/dashboard', function() {
            require __DIR__ . '/../views/admin/dashboard.php';
        });
        
        // Teachers management
        $router->get('/teachers', 'Admin\TeacherController@index');
        $router->get('/teachers/create', 'Admin\TeacherController@create');
        $router->post('/teachers', 'Admin\TeacherController@store');
        $router->get('/teachers/{id}', 'Admin\TeacherController@show');
        $router->get('/teachers/{id}/edit', 'Admin\TeacherController@edit');
        $router->put('/teachers/{id}', 'Admin\TeacherController@update');
        $router->delete('/teachers/{id}', 'Admin\TeacherController@destroy');
        $router->get('/teachers/search', 'Admin\TeacherController@search');
        
        // Assignments management
        $router->get('/assignments', 'Admin\AssignmentController@index');
        $router->get('/assignments/create', 'Admin\AssignmentController@create');
        $router->post('/assignments', 'Admin\AssignmentController@store');
        $router->get('/assignments/{id}', 'Admin\AssignmentController@show');
        $router->get('/assignments/{id}/edit', 'Admin\AssignmentController@edit');
        $router->put('/assignments/{id}', 'Admin\AssignmentController@update');
        $router->delete('/assignments/{id}', 'Admin\AssignmentController@destroy');
        
        // Subjects management
        $router->get('/subjects', 'Admin\SubjectController@index');
        $router->get('/subjects/create', 'Admin\SubjectController@create');
        $router->post('/subjects', 'Admin\SubjectController@store');
        $router->get('/subjects/{id}', 'Admin\SubjectController@show');
        $router->get('/subjects/{id}/edit', 'Admin\SubjectController@edit');
        $router->put('/subjects/{id}', 'Admin\SubjectController@update');
        $router->delete('/subjects/{id}', 'Admin\SubjectController@destroy');
        
        // Groups management
        $router->get('/groups', 'Admin\GroupController@index');
        $router->get('/groups/create', 'Admin\GroupController@create');
        $router->post('/groups', 'Admin\GroupController@store');
        $router->get('/groups/{id}', 'Admin\GroupController@show');
        $router->get('/groups/{id}/edit', 'Admin\GroupController@edit');
        $router->put('/groups/{id}', 'Admin\GroupController@update');
        $router->delete('/groups/{id}', 'Admin\GroupController@destroy');
        
        // Schedules management
        $router->get('/schedules', function() {
            require __DIR__ . '/../views/admin/admin-gestion-horarios.php';
        });
        $router->get('/schedules/create', function() {
            require __DIR__ . '/../views/admin/admin-gestion-horarios.php';
        });
        $router->post('/schedules', function() {
            require __DIR__ . '/../controllers/horario_handler.php';
        });
        $router->get('/schedules/{id}', function() {
            require __DIR__ . '/../controllers/horario_handler.php';
        });
        $router->get('/schedules/{id}/edit', function() {
            require __DIR__ . '/../controllers/horario_handler.php';
        });
        $router->put('/schedules/{id}', function() {
            require __DIR__ . '/../controllers/horario_handler.php';
        });
        $router->delete('/schedules/{id}', function() {
            require __DIR__ . '/../controllers/horario_handler.php';
        });
        
        // Advanced schedule management
        $router->get('/gestion-horarios', function() {
            require __DIR__ . '/../views/admin/admin-gestion-horarios.php';
        });
        
        // Users management
        $router->get('/users', 'Admin\UserController@index');
        $router->get('/users/create', 'Admin\UserController@create');
        $router->post('/users', 'Admin\UserController@store');
        $router->get('/users/{id}', 'Admin\UserController@show');
        $router->get('/users/{id}/edit', 'Admin\UserController@edit');
        $router->put('/users/{id}', 'Admin\UserController@update');
        $router->delete('/users/{id}', 'Admin\UserController@destroy');
        
        // Coordinators management
        $router->get('/coordinators', 'Admin\CoordinatorController@index');
        $router->get('/coordinators/create', 'Admin\CoordinatorController@create');
        $router->post('/coordinators', 'Admin\CoordinatorController@store');
        $router->get('/coordinators/{id}', 'Admin\CoordinatorController@show');
        $router->get('/coordinators/{id}/edit', 'Admin\CoordinatorController@edit');
        $router->put('/coordinators/{id}', 'Admin\CoordinatorController@update');
        $router->delete('/coordinators/{id}', 'Admin\CoordinatorController@destroy');
        
        // Reports
        $router->get('/reports', function() {
            require __DIR__ . '/../views/admin/admin-reportes.php';
        });
        
        // Availability management
        $router->get('/availability', function() {
            require __DIR__ . '/../views/admin/admin-disponibilidad.php';
        });
        
        // Legacy routes for backward compatibility
        $router->post('/api/teachers', 'Admin\TeacherController@handleRequest');
        $router->post('/api/assignments', 'Admin\AssignmentController@handleRequest');
        $router->post('/api/subjects', 'Admin\SubjectController@handleRequest');
        $router->post('/api/groups', 'Admin\GroupController@handleRequest');
        $router->post('/api/schedules', function() {
            require __DIR__ . '/../controllers/horario_handler.php';
        });
        $router->post('/api/users', 'Admin\UserController@handleRequest');
        $router->post('/api/coordinators', 'Admin\CoordinatorController@handleRequest');
    });
    
    // Coordinator routes
    $router->group(['prefix' => '/coordinator', 'middleware' => ['coordinator']], function($router) {
        
        $router->get('/dashboard', function() {
            require __DIR__ . '/../views/coordinador/dashboard.php';
        });
        
        $router->get('/teachers', function() {
            require __DIR__ . '/../views/coordinador/coordinador-docentes.php';
        });
        
        $router->get('/calendar', function() {
            require __DIR__ . '/../views/coordinador/coordinador-calendario.php';
        });
    });
    
    // Teacher routes
    $router->group(['prefix' => '/teacher', 'middleware' => ['teacher']], function($router) {
        
        $router->get('/dashboard', function() {
            require __DIR__ . '/../views/docente/dashboard.php';
        });
        
        $router->get('/my-schedule', function() {
            require __DIR__ . '/../views/docente/mi-horario.php';
        });
        
        $router->get('/my-availability', function() {
            require __DIR__ . '/../views/docente/mi-disponibilidad.php';
        });
    });
    
    // Parent routes
    $router->group(['prefix' => '/parent', 'middleware' => ['parent']], function($router) {
        
        $router->get('/dashboard', function() {
            require __DIR__ . '/../views/padre/dashboard.php';
        });
        
        $router->get('/students', function() {
            require __DIR__ . '/../views/padre/admin-estudiantes.php';
        });
        
        $router->get('/student-schedules', function() {
            require __DIR__ . '/../views/padre/admin-horarios-estudiante.php';
        });
    });
});

// Register middleware
$router->middleware('auth', [AuthMiddleware::class, 'handle']);
$router->middleware('admin', RoleMiddleware::admin());
$router->middleware('director', RoleMiddleware::director());
$router->middleware('coordinator', RoleMiddleware::coordinator());
$router->middleware('teacher', RoleMiddleware::teacher());
$router->middleware('parent', RoleMiddleware::parent());

// Dispatch the request
$router->dispatch();
