<?php

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/Translation.php';

class Sidebar {
    private $translation;
    private $currentPage;
    private $userRole;
    
    public function __construct($currentPage = '') {
        $this->translation = Translation::getInstance();
        $this->currentPage = $currentPage;
        $this->userRole = AuthHelper::getCurrentUserRole();
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
                <span class="text-sm">🏠</span>
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
                {$item['icon']}
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
                        'url' => 'admin-usuarios.php',
                        'file' => 'admin-usuarios.php',
                        'text' => 'users',
                        'icon' => '<span class="text-sm">👥</span>'
                    ],
                    [
                        'url' => 'admin-docentes.php',
                        'file' => 'admin-docentes.php',
                        'text' => 'teachers',
                        'icon' => '<span class="text-sm">👨‍🏫</span>'
                    ],
                    [
                        'url' => 'admin-coordinadores.php',
                        'file' => 'admin-coordinadores.php',
                        'text' => 'coordinators',
                        'icon' => '<span class="text-sm">👨‍💼</span>'
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
                        'url' => 'admin-materias.php',
                        'file' => 'admin-materias.php',
                        'text' => 'subjects',
                        'icon' => '<span class="text-sm">📚</span>'
                    ],
                    [
                        'url' => 'admin-horarios.php',
                        'file' => 'admin-horarios.php',
                        'text' => 'schedules',
                        'icon' => '<span class="text-sm">📅</span>'
                    ],
                    [
                        'url' => 'admin-grupos.php',
                        'file' => 'admin-grupos.php',
                        'text' => 'groups',
                        'icon' => '<span class="text-sm">👥</span>'
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
                        'url' => 'admin-publicar-horarios.php',
                        'file' => 'admin-publicar-horarios.php',
                        'text' => 'publish_schedules',
                        'icon' => '<span class="text-sm">📢</span>'
                    ],
                    [
                        'url' => 'admin-agregar-docentes.php',
                        'file' => 'admin-agregar-docentes.php',
                        'text' => 'add_new_teachers',
                        'icon' => '<span class="text-sm">➕</span>'
                    ],
                    [
                        'url' => 'admin-agregar-materias.php',
                        'file' => 'admin-agregar-materias.php',
                        'text' => 'add_new_subjects',
                        'icon' => '<span class="text-sm">➕</span>'
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
                        'url' => 'admin-disponibilidad.php',
                        'file' => 'admin-disponibilidad.php',
                        'text' => 'teacher_availability',
                        'icon' => '<span class="text-sm">⏰</span>'
                    ],
                    [
                        'url' => 'admin-asignaciones.php',
                        'file' => 'admin-asignaciones.php',
                        'text' => 'subject_assignments',
                        'icon' => '<span class="text-sm">🔗</span>'
                    ],
                    [
                        'url' => 'admin-reportes.php',
                        'file' => 'admin-reportes.php',
                        'text' => 'reports',
                        'icon' => '<span class="text-sm">📊</span>'
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
                        'url' => 'mi-horario.php', 
                        'file' => 'mi-horario.php',
                        'text' => 'my_schedule',
                        'icon' => '<span class="text-sm">📅</span>'
                    ],
                    [
                        'url' => 'mi-disponibilidad.php',
                        'file' => 'mi-disponibilidad.php',
                        'text' => 'my_availability',
                        'icon' => '<span class="text-sm">⏰</span>'
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
                        'url' => 'admin-estudiantes.php',
                        'file' => 'admin-estudiantes.php',
                        'text' => 'students',
                        'icon' => '<span class="text-sm">👨‍🎓</span>'
                    ],
                    [
                        'url' => 'admin-horarios-estudiante.php',
                        'file' => 'admin-horarios-estudiante.php',
                        'text' => 'student_schedules',
                        'icon' => '<span class="text-sm">📅</span>'
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
            'DOCENTE' => $currentDir === 'docente' ? 'dashboard.php' : '/src/views/docente/dashboard.php',
            'PADRE' => $currentDir === 'padre' ? 'dashboard.php' : '/src/views/padre/dashboard.php',
            default => 'dashboard.php'
        };
    }
    
    private function isActive($file) {
        return basename($_SERVER['PHP_SELF']) === $file || $this->currentPage === $file;
    }
    
    public static function getStyles() {
        return '
        <style>
        .sidebar-link {
            position: relative;
            transition: all 0.3s;
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
        </style>
        ';
    }
}
