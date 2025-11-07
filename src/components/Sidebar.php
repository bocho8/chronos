<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/RouteHelper.php';

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
        <aside class="w-64 lg:w-64 md:w-56 sm:w-0 bg-sidebar border-r border-border sidebar-container">
            <div class="sidebar-content">
                {$this->renderHeader()}
                {$this->renderNavigation()}
            </div>
        </aside>
        HTML;
    }
    
    private function renderHeader() {
        return <<<HTML
        <div class="px-3 md:px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
            <img src="/assets/images/LogoScuola.png" alt="{$this->translation->get('scuola_italiana')}" class="h-8 md:h-9 w-auto">
            <span class="text-white font-semibold text-sm md:text-lg hidden md:block">{$this->translation->get('scuola_italiana')}</span>
        </div>
        HTML;
    }
    
    private function renderNavigation() {
        return <<<HTML
        <ul class="py-3 md:py-5 list-none">
            {$this->renderDashboardSection()}
            {$this->renderRoleBasedSections()}
        </ul>
        HTML;
    }
    
    private function renderDashboardSection() {
        $dashboardUrl = \RouteHelper::getDashboardUrl($this->userRole);
        $isActive = \RouteHelper::isActive(\RouteHelper::getCurrentPath(), $dashboardUrl);
        $activeClass = $isActive ? 'active' : '';
        $textColor = $isActive ? 'text-gray-800' : 'text-gray-600';
        
        return <<<HTML
        <li>
            <a href="{$dashboardUrl}" class="sidebar-link {$activeClass} flex items-center py-2 md:py-3 px-3 md:px-5 {$textColor} no-underline transition-all hover:bg-sidebarHover">
                <span class="text-sm mr-2 md:mr-3">🏠</span>
                <span class="text-xs md:text-sm">{$this->translation->get('dashboard')}</span>
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
        <li class="mt-3 md:mt-4">
            <div class="px-3 md:px-5 py-1 md:py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {$sectionTitle}
            </div>
        </li>
        {$menuItems}
        HTML;
    }
    
    private function renderMenuItem($item) {
        $isActive = \RouteHelper::isActive(\RouteHelper::getCurrentPath(), $item['url']);
        $activeClass = $isActive ? 'active' : '';
        $textColor = $isActive ? 'text-gray-800' : 'text-gray-600';
        $itemText = $this->translation->get($item['text']);
        
        return <<<HTML
        <li>
            <a href="{$item['url']}" class="sidebar-link {$activeClass} flex items-center py-2 md:py-3 px-3 md:px-5 {$textColor} no-underline transition-all hover:bg-sidebarHover">
                <span class="text-sm mr-2 md:mr-3">{$item['icon']}</span>
                <span class="text-xs md:text-sm">{$itemText}</span>
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
                $this->getCoordinatorSections()
            ),
            'DIRECTOR' => array_merge(
                [],
                $this->getAdministrationSectionsForDirector(),
                $this->getAcademicSections(),
                $this->getDirectorSections(),
                $this->getCoordinatorSections()
            ),
            'COORDINADOR' => array_merge(
                [],
                $this->getAdministrationSectionsForCoordinator(),
                $this->getAcademicSectionsForCoordinator(),
                $this->getCoordinatorSections()
            ),
            'DOCENTE' => array_merge([], $this->getTeacherSections()),
            'PADRE' => array_merge([], $this->getParentSections()),
            default => []
        };
    }
    
    private function getAdministrationSections() {
        $urls = \RouteHelper::getAdminUrls();
        return [
            [
                'title' => 'administration',
                'items' => [
                    [
                        'url' => $urls['users'],
                        'text' => 'users',
                        'icon' => '👥'
                    ],
                    [
                        'url' => $urls['teachers'],
                        'text' => 'teachers',
                        'icon' => '👨‍🏫'
                    ],
                    [
                        'url' => '/translations',
                        'text' => 'translations_management',
                        'icon' => '🌐'
                    ]
                ]
            ]
        ];
    }
    
    private function getAdministrationSectionsForDirector() {
        $urls = \RouteHelper::getAdminUrls();
        return [
            [
                'title' => 'administration',
                'items' => [
                    [
                        'url' => $urls['users'],
                        'text' => 'users',
                        'icon' => '👥'
                    ],
                    [
                        'url' => $urls['teachers'],
                        'text' => 'teachers',
                        'icon' => '👨‍🏫'
                    ]
                ]
            ]
        ];
    }
    
    private function getAdministrationSectionsForCoordinator() {
        $urls = \RouteHelper::getAdminUrls();
        return [
            [
                'title' => 'administration',
                'items' => [
                    [
                        'url' => $urls['teachers'],
                        'text' => 'teachers',
                        'icon' => '👨‍🏫'
                    ]
                ]
            ]
        ];
    }
    
    private function getAcademicSections() {
        $urls = \RouteHelper::getAdminUrls();
        return [
            [
                'title' => 'academic_management',
                'items' => [
                    [
                        'url' => $urls['subjects'],
                        'text' => 'subjects',
                        'icon' => '📚'
                    ],
                    [
                        'url' => $urls['schedule_management'],
                        'text' => 'schedule_management',
                        'icon' => '⚙️'
                    ],
                    [
                        'url' => $urls['groups'],
                        'text' => 'groups',
                        'icon' => '👥'
                    ],
                    [
                        'url' => '/parent-assignments',
                        'text' => 'parent_group_assignment',
                        'icon' => '👨‍👩‍👧‍👦'
                    ],
                    [
                        'url' => '/group-subjects',
                        'text' => 'group_subject_assignment',
                        'icon' => '📖'
                    ],
                    [
                        'url' => '/bloques',
                        'text' => 'time_blocks_management',
                        'icon' => '⏰'
                    ]
                ]
            ]
        ];
    }
    
    private function getAcademicSectionsForCoordinator() {
        $urls = \RouteHelper::getAdminUrls();
        return [
            [
                'title' => 'academic_management',
                'items' => [
                    [
                        'url' => $urls['subjects'],
                        'text' => 'subjects',
                        'icon' => '📚'
                    ],
                    [
                        'url' => $urls['groups'],
                        'text' => 'groups',
                        'icon' => '👥'
                    ],
                    [
                        'url' => '/parent-assignments',
                        'text' => 'parent_group_assignment',
                        'icon' => '👨‍👩‍👧‍👦'
                    ],
                    [
                        'url' => '/group-subjects',
                        'text' => 'group_subject_assignment',
                        'icon' => '📖'
                    ],
                    [
                        'url' => '/bloques',
                        'text' => 'time_blocks_management',
                        'icon' => '⏰'
                    ]
                ]
            ]
        ];
    }
    
    private function getDirectorSections() {
        $urls = \RouteHelper::getAdminUrls();
        return [
            [
                'title' => 'director_functions',
                'items' => [
                    [
                        'url' => $urls['publish_schedules'],
                        'text' => 'publish_schedules',
                        'icon' => '📢'
                    ]
                ]
            ]
        ];
    }
    
    private function getCoordinatorSections() {
        $urls = \RouteHelper::getCoordinatorUrls();
        return [
            [
                'title' => 'coordinator_functions',
                'items' => [
                    [
                        'url' => $urls['teacher_availability'],
                        'text' => 'teacher_availability',
                        'icon' => '⏰'
                    ],
                    [
                        'url' => '/observaciones-predefinidas',
                        'text' => 'observaciones_predefinidas',
                        'icon' => '📝'
                    ],
                    [
                        'url' => $urls['reports'],
                        'text' => 'reports',
                        'icon' => '📊'
                    ]
                ]
            ]
        ];
    }
    
    private function getTeacherSections() {
        $urls = \RouteHelper::getTeacherUrls();
        return [
            [
                'title' => 'teacher_functions',
                'items' => [
                    [
                        'url' => $urls['my_schedule'],
                        'text' => 'my_schedule',
                        'icon' => '📅'
                    ],
                    [
                        'url' => $urls['my_availability'],
                        'text' => 'my_availability',
                        'icon' => '⏰'
                    ]
                ]
            ]
        ];
    }
    
    private function getParentSections() {
        $urls = \RouteHelper::getParentUrls();
        return [
            [
                'title' => 'parent_functions',
                'items' => [
                    [
                        'url' => $urls['students'],
                        'text' => 'students',
                        'icon' => '👨‍🎓'
                    ],
                    [
                        'url' => $urls['student_schedules'],
                        'text' => 'student_schedules',
                        'icon' => '📅'
                    ]
                ]
            ]
        ];
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
        .sidebar-container {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 40;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
        }
        .sidebar-content {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
        }
        .sidebar-link span {
            margin-right: 12px;
        }
        .sidebar-container ul {
            overflow-y: auto;
            flex: 1;
            max-height: calc(100vh - 60px);
        }
        
        /* Responsive behavior */
        @media (max-width: 640px) {
            .sidebar-container {
                width: 0 !important;
                overflow: hidden;
            }
            .sidebar-container.sidebar-open {
                width: 256px !important;
                z-index: 50 !important;
                box-shadow: 4px 0 6px -1px rgba(0, 0, 0, 0.1);
            }
        }
        
        @media (min-width: 641px) and (max-width: 1023px) {
            .sidebar-container {
                width: 224px !important; /* w-56 */
            }
        }
        
        @media (min-width: 1024px) {
            .sidebar-container {
                width: 256px !important; /* w-64 */
            }
        }
        </style>
        ';
    }
}