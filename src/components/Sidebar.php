<?php
/**
 * Reusable Sidebar Component
 * Generates sidebar navigation based on user roles
 */

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
    
    /**
     * Render the complete sidebar
     * 
     * @return string HTML content for the sidebar
     */
    public function render() {
        ob_start();
        ?>
        <aside class="w-64 bg-sidebar border-r border-border">
            <?php echo $this->renderHeader(); ?>
            <?php echo $this->renderNavigation(); ?>
        </aside>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render sidebar header with logo
     * 
     * @return string HTML content for the header
     */
    private function renderHeader() {
        ob_start();
        ?>
        <div class="px-5 flex items-center h-[60px] bg-darkblue gap-2.5">
            <img src="/assets/images/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
            <span class="text-white font-semibold text-lg"><?php _e('scuola_italiana'); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render navigation menu based on user role
     * 
     * @return string HTML content for the navigation
     */
    private function renderNavigation() {
        ob_start();
        ?>
        <ul class="py-5 list-none">
            <?php echo $this->renderDashboardSection(); ?>
            <?php echo $this->renderRoleBasedSections(); ?>
        </ul>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render dashboard section (always visible)
     * 
     * @return string HTML content for dashboard section
     */
    private function renderDashboardSection() {
        $dashboardUrl = $this->getDashboardUrl();
        $isActive = $this->isActive('dashboard.php');
        
        ob_start();
        ?>
        <li>
            <a href="<?php echo $dashboardUrl; ?>" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?> flex items-center py-3 px-5 <?php echo $isActive ? 'text-gray-800' : 'text-gray-600'; ?> no-underline transition-all hover:bg-sidebarHover">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6a2 2 0 01-2 2H10a2 2 0 01-2-2V5z"></path>
                </svg>
                <?php _e('dashboard'); ?>
            </a>
        </li>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render role-based sections
     * 
     * @return string HTML content for role-based sections
     */
    private function renderRoleBasedSections() {
        $sections = $this->getRoleBasedSections();
        
        ob_start();
        foreach ($sections as $section) {
            echo $this->renderSection($section);
        }
        return ob_get_clean();
    }
    
    /**
     * Render a single section with its items
     * 
     * @param array $section Section configuration
     * @return string HTML content for the section
     */
    private function renderSection($section) {
        ob_start();
        ?>
        <li class="mt-4">
            <div class="px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <?php _e($section['title']); ?>
            </div>
        </li>
        <?php
        foreach ($section['items'] as $item) {
            echo $this->renderMenuItem($item);
        }
        return ob_get_clean();
    }
    
    /**
     * Render a single menu item
     * 
     * @param array $item Menu item configuration
     * @return string HTML content for the menu item
     */
    private function renderMenuItem($item) {
        $isActive = $this->isActive($item['file']);
        
        ob_start();
        ?>
        <li>
            <a href="<?php echo $item['url']; ?>" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?> flex items-center py-3 px-5 <?php echo $isActive ? 'text-gray-800' : 'text-gray-600'; ?> no-underline transition-all hover:bg-sidebarHover">
                <?php echo $item['icon']; ?>
                <?php _e($item['text']); ?>
            </a>
        </li>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get sections based on user role
     * 
     * @return array Array of sections with their items
     */
    private function getRoleBasedSections() {
        $sections = [];
        
        switch ($this->userRole) {
            case 'ADMIN':
                $sections = array_merge(
                    $sections,
                    $this->getAdministrationSections(),
                    $this->getAcademicSections(),
                    $this->getDirectorSections(),
                    $this->getCoordinatorSections(),
                    $this->getTeacherSections(),
                    $this->getParentSections()
                );
                break;
                
            case 'DIRECTOR':
                $sections = array_merge(
                    $sections,
                    $this->getAdministrationSections(),
                    $this->getAcademicSections(),
                    $this->getDirectorSections()
                );
                break;
                
            case 'COORDINADOR':
                $sections = array_merge(
                    $sections,
                    $this->getCoordinatorSections(),
                    $this->getTeacherSections()
                );
                break;
                
            case 'DOCENTE':
                $sections = array_merge(
                    $sections,
                    $this->getTeacherSections()
                );
                break;
                
            case 'PADRE':
                $sections = array_merge(
                    $sections,
                    $this->getParentSections()
                );
                break;
        }
        
        return $sections;
    }
    
    /**
     * Get administration sections
     * 
     * @return array Administration sections
     */
    private function getAdministrationSections() {
        return [
            [
                'title' => 'administration',
                'items' => [
                    [
                        'url' => 'admin-usuarios.php',
                        'file' => 'admin-usuarios.php',
                        'text' => 'users',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>'
                    ],
                    [
                        'url' => 'admin-docentes.php',
                        'file' => 'admin-docentes.php',
                        'text' => 'teachers',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>'
                    ],
                    [
                        'url' => 'admin-coordinadores.php',
                        'file' => 'admin-coordinadores.php',
                        'text' => 'coordinators',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get academic sections
     * 
     * @return array Academic sections
     */
    private function getAcademicSections() {
        return [
            [
                'title' => 'academic_management',
                'items' => [
                    [
                        'url' => 'admin-materias.php',
                        'file' => 'admin-materias.php',
                        'text' => 'subjects',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>'
                    ],
                    [
                        'url' => 'admin-horarios.php',
                        'file' => 'admin-horarios.php',
                        'text' => 'schedules',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                    ],
                    [
                        'url' => 'admin-grupos.php',
                        'file' => 'admin-grupos.php',
                        'text' => 'groups',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get director sections
     * 
     * @return array Director sections
     */
    private function getDirectorSections() {
        return [
            [
                'title' => 'director_functions',
                'items' => [
                    [
                        'url' => 'admin-publicar-horarios.php',
                        'file' => 'admin-publicar-horarios.php',
                        'text' => 'publish_schedules',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>'
                    ],
                    [
                        'url' => 'admin-agregar-docentes.php',
                        'file' => 'admin-agregar-docentes.php',
                        'text' => 'add_new_teachers',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>'
                    ],
                    [
                        'url' => 'admin-agregar-materias.php',
                        'file' => 'admin-agregar-materias.php',
                        'text' => 'add_new_subjects',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get coordinator sections
     * 
     * @return array Coordinator sections
     */
    private function getCoordinatorSections() {
        return [
            [
                'title' => 'coordinator_functions',
                'items' => [
                    [
                        'url' => 'admin-disponibilidad.php',
                        'file' => 'admin-disponibilidad.php',
                        'text' => 'teacher_availability',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>'
                    ],
                    [
                        'url' => 'admin-asignaciones.php',
                        'file' => 'admin-asignaciones.php',
                        'text' => 'subject_assignments',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 712-2h2a2 2 0 712 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>'
                    ],
                    [
                        'url' => 'admin-reportes.php',
                        'file' => 'admin-reportes.php',
                        'text' => 'reports',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 712-2h2a2 2 0 712 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 712-2h2a2 2 0 712 2v14a2 2 0 71-2 2h-2a2 2 0 71-2-2z"></path></svg>'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get teacher sections
     * 
     * @return array Teacher sections
     */
    private function getTeacherSections() {
        return [
            [
                'title' => 'teacher_functions',
                'items' => [
                    [
                        'url' => 'admin-mi-horario.php',
                        'file' => 'admin-mi-horario.php',
                        'text' => 'my_schedule',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 712-2h4a2 2 0 712 2v4m-6 0V3a2 2 0 712-2h4a2 2 0 712 2v4M7 7h10l4 10v4a1 1 0 71-1 1H4a1 1 0 71-1-1v-4L7 7z"></path></svg>'
                    ],
                    [
                        'url' => 'admin-mi-disponibilidad.php',
                        'file' => 'admin-mi-disponibilidad.php',
                        'text' => 'my_availability',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get parent sections
     * 
     * @return array Parent sections
     */
    private function getParentSections() {
        return [
            [
                'title' => 'parent_functions',
                'items' => [
                    [
                        'url' => 'admin-estudiantes.php',
                        'file' => 'admin-estudiantes.php',
                        'text' => 'students',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>'
                    ],
                    [
                        'url' => 'admin-horarios-estudiante.php',
                        'file' => 'admin-horarios-estudiante.php',
                        'text' => 'student_schedules',
                        'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get dashboard URL based on user role
     * 
     * @return string Dashboard URL
     */
    private function getDashboardUrl() {
        switch ($this->userRole) {
            case 'ADMIN':
            case 'DIRECTOR':
                return 'dashboard.php';
            case 'COORDINADOR':
                return '../coordinador/dashboard.php';
            case 'DOCENTE':
                return '../docente/dashboard.php';
            case 'PADRE':
                return '../padre/dashboard.php';
            default:
                return 'dashboard.php';
        }
    }
    
    /**
     * Check if current page matches the given file
     * 
     * @param string $file File to check
     * @return bool True if current page matches
     */
    private function isActive($file) {
        return basename($_SERVER['PHP_SELF']) === $file || $this->currentPage === $file;
    }
    
    /**
     * Add CSS styles for the sidebar
     * 
     * @return string CSS styles
     */
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
