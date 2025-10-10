<?php

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/Translation.php';

class Sidebar {
    private $translation;
    private $currentPage;
    private $userRole;
    
    public function __construct($currentPage = '') {
        $this->translation = \Translation::getInstance();
        $this->currentPage = $currentPage;
        $this->userRole = \AuthHelper::getCurrentUserRole();
    }
    
    public function render() {
        return <<<HTML
        <aside class="w-64 bg-sidebar border-r border-border">
            {$this->renderHeader()}
            {$this->renderNavigation()}
        </aside>
        HTML;
    }
    
    private function renderHeader() {
        return <<<HTML
        <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
            <img src="/assets/images/LogoScuola.png" alt="{$this->translation->get('scuola_italiana')}" class="h-9 w-auto">
            <span class="text-white font-semibold text-lg">{$this->translation->get('scuola_italiana')}</span>
        </div>
        HTML;
    }
    
    private function renderNavigation() {
        return <<<HTML
        <ul class="py-5 list-none">
            {$this->renderDashboardSection()}
            {$this->renderRoleBasedSections()}
        </ul>
        HTML;
    }
    
    private function renderDashboardSection() {
        $dashboardUrl = $this->getDashboardUrl();
        $isActive = $this->isActive('dashboard.php');
        $activeClass = $isActive ? 'active' : '';
        $textColor = $isActive ? 'text-gray-800' : 'text-gray-600';
        
        return <<<HTML
        <li>
            <a href="{$dashboardUrl}" class="sidebar-link {$activeClass} flex items-center py-3 px-5 {$textColor} no-underline transition-all hover:bg-sidebarHover">
                <span class="text-sm mr-3">🏠</span>
                {$this->translation->get('dashboard')}
            </a>
        </li>
        HTML;
    }
    
    private function renderRoleBasedSections() {
        $sections = $this->getRoleBasedSections();
        
        $html = '';
        foreach ($sections as $section) {
            $html .= $this->renderSection($section);
        }
        
        return $html;
    }
    
    private function renderSection($section) {
        $sectionTitle = $this->translation->get($section['title']);
        $menuItems = '';
        
        foreach ($section['items'] as $item) {
            $menuItems .= $this->renderMenuItem($item);
        }
        
        return <<<HTML
        <li class="mt-4">
            <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {$sectionTitle}
            </div>
        </li>
        {$menuItems}
        HTML;
    }
    
    private function renderMenuItem($item) {
        $isActive = $this->isActive($item['file']);
        $activeClass = $isActive ? 'active' : '';
        $textColor = $isActive ? 'text-gray-800' : 'text-gray-600';
        $itemText = $this->translation->get($item['text']);
        
        return <<<HTML
        <li>
            <a href="{$item['url']}" class="sidebar-link {$activeClass} flex items-center py-3 px-5 {$textColor} no-underline transition-all hover:bg-sidebarHover">
                <span class="text-sm mr-3">{$item['icon']}</span>
                {$itemText}
            </a>
        </li>
        HTML;
    }
    
    private function getRoleBasedSections() {
        return match ($this->userRole) {
            'ADMIN' => array_merge(
                [],
                $this->getAdministrationSections(),
                $this->getAcademicSections(),
                $this->getDirectorSections(),
                $this->getCoordinatorSections(),
                $this->getTeacherSections(),
                $this->getParentSections()
            ),
            'DIRECTOR' => array_merge(
                [],
                $this->getAdministrationSections(),
                $this->getAcademicSections(),
                $this->getDirectorSections()
            ),
            'COORDINADOR' => array_merge(
                [],
                $this->getCoordinatorSections(),
                $this->getTeacherSections()
            ),
            'DOCENTE' => array_merge([], $this->getTeacherSections()),
            'PADRE' => array_merge([], $this->getParentSections()),
            default => []
        };
    }
    
    private function getAdministrationSections() {
        return [
            [
                'title' => 'administration',
                'items' => [
                    [
                        'url' => '/src/views/admin/admin-usuarios.php',
                        'file' => 'admin-usuarios.php',
                        'text' => 'users',
                        'icon' => '👥'
                    ],
                    [
                        'url' => '/src/views/admin/admin-docentes.php',
                        'file' => 'admin-docentes.php',
                        'text' => 'teachers',
                        'icon' => '👨‍🏫'
                    ],
                    [
                        'url' => '/src/views/admin/admin-coordinadores.php',
                        'file' => 'admin-coordinadores.php',
                        'text' => 'coordinators',
                        'icon' => '👨‍💼'
                    ]
                ]
            ]
        ];
    }
    
    private function getAcademicSections() {
        return [
            [
                'title' => 'academic_management',
                'items' => [
                    [
                        'url' => '/src/views/admin/admin-materias.php',
                        'file' => 'admin-materias.php',
                        'text' => 'subjects',
                        'icon' => '📚'
                    ],
                    [
                        'url' => '/src/views/admin/admin-horarios.php',
                        'file' => 'admin-horarios.php',
                        'text' => 'schedules',
                        'icon' => '📅'
                    ],
                    [
                        'url' => '/src/views/admin/admin-gestion-horarios.php',
                        'file' => 'admin-gestion-horarios.php',
                        'text' => 'schedule_management',
                        'icon' => '⚙️'
                    ],
                    [
                        'url' => '/src/views/admin/admin-grupos.php',
                        'file' => 'admin-grupos.php',
                        'text' => 'groups',
                        'icon' => '👥'
                    ]
                ]
            ]
        ];
    }
    
    private function getDirectorSections() {
        return [
            [
                'title' => 'director_functions',
                'items' => [
                    [
                        'url' => '/src/views/admin/admin-publicar-horarios.php',
                        'file' => 'admin-publicar-horarios.php',
                        'text' => 'publish_schedules',
                        'icon' => '📢'
                    ]
                ]
            ]
        ];
    }
    
    private function getCoordinatorSections() {
        return [
            [
                'title' => 'coordinator_functions',
                'items' => [
                    [
                        'url' => '/src/views/admin/admin-disponibilidad.php',
                        'file' => 'admin-disponibilidad.php',
                        'text' => 'teacher_availability',
                        'icon' => '⏰'
                    ],
                    [
                        'url' => '/src/views/admin/admin-asignaciones.php',
                        'file' => 'admin-asignaciones.php',
                        'text' => 'subject_assignments',
                        'icon' => '🔗'
                    ],
                    [
                        'url' => '/src/views/admin/admin-reportes.php',
                        'file' => 'admin-reportes.php',
                        'text' => 'reports',
                        'icon' => '📊'
                    ]
                ]
            ]
        ];
    }
    
    private function getTeacherSections() {
        return [
            [
                'title' => 'teacher_functions',
                'items' => [
                    [
                        'url' => '/teacher/my-schedule',
                        'file' => 'mi-horario.php',
                        'text' => 'my_schedule',
                        'icon' => '📅'
                    ],
                    [
                        'url' => '/teacher/my-availability',
                        'file' => 'mi-disponibilidad.php',
                        'text' => 'my_availability',
                        'icon' => '⏰'
                    ]
                ]
            ]
        ];
    }
    
    private function getParentSections() {
        return [
            [
                'title' => 'parent_functions',
                'items' => [
                    [
                        'url' => '/src/views/admin/admin-estudiantes.php',
                        'file' => 'admin-estudiantes.php',
                        'text' => 'students',
                        'icon' => '👨‍🎓'
                    ],
                    [
                        'url' => '/src/views/admin/admin-horarios-estudiante.php',
                        'file' => 'admin-horarios-estudiante.php',
                        'text' => 'student_schedules',
                        'icon' => '📅'
                    ]
                ]
            ]
        ];
    }
    
    private function getDashboardUrl() {
        $currentDir = basename(dirname($_SERVER['PHP_SELF']));
        
        return match ($this->userRole) {
            'ADMIN', 'DIRECTOR' => $currentDir === 'admin' ? 'dashboard.php' : '/src/views/admin/dashboard.php',
            'COORDINADOR' => $currentDir === 'coordinador' ? 'dashboard.php' : '/src/views/coordinador/dashboard.php',
            'DOCENTE' => $currentDir === 'docente' ? 'dashboard.php' : '/teacher/dashboard',
            'PADRE' => $currentDir === 'padre' ? 'dashboard.php' : '/src/views/padre/dashboard.php',
            default => 'dashboard.php'
        };
    }
    
    private function isActive($file) {
        $currentFile = basename($_SERVER['PHP_SELF']);
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        // Verificar por nombre de archivo
        if ($currentFile === $file || $this->currentPage === $file) {
            return true;
        }
        
        // Verificar por ruta específica para docentes
        if ($this->userRole === 'DOCENTE') {
            $teacherRoutes = [
                'mi-horario.php' => '/teacher/my-schedule',
                'mi-disponibilidad.php' => '/teacher/my-availability',
                'dashboard.php' => '/teacher/dashboard'
            ];
            
            if (isset($teacherRoutes[$file])) {
                return strpos($currentPath, $teacherRoutes[$file]) !== false;
            }
        }
        
        return false;
    }
    
    public function getDashboardUrlPublic() {
        return $this->getDashboardUrl();
    }
    
    public static function getStyles() {
        return '
        <style>
        .sidebar-link {
            position: relative;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .sidebar-link.active {
            background-color: #e4e6eb;
            font-weight: 600;
        }
        .sidebar-link.active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: #1f366d;
        }
        .sidebar-link:hover {
            background-color: #f3f4f6;
        }
        .bg-sidebar {
            background-color: #f8fafc;
        }
        .bg-sidebarHover {
            background-color: #f1f5f9;
        }
        .border-border {
            border-color: #e2e8f0;
        }
        .bg-darkblue {
            background-color: #1f366d;
        }
        aside {
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 40;
        }
        .sidebar-link span {
            margin-right: 12px;
        }
        </style>
        ';
    }
}