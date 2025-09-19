<?php
/**
 * Reusable Header Component
 * Generates header with user menu, notifications, and language switcher
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/Translation.php';

class Header {
    private $translation;
    private $languageSwitcher;
    private $pageTitle;
    
    public function __construct($pageTitle = '', $languageSwitcher = null) {
        $this->translation = Translation::getInstance();
        $this->languageSwitcher = $languageSwitcher;
        $this->pageTitle = $pageTitle;
    }
    
    /**
     * Render the complete header
     * 
     * @return string HTML content for the header
     */
    public function render() {
        ob_start();
        ?>
        <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
            <div class="w-8"></div>
            
            <div class="text-white text-xl font-semibold text-center">
                <?php echo $this->getWelcomeMessage(); ?>
            </div>
            
            <div class="flex items-center">
                <?php echo $this->renderLanguageSwitcher(); ?>
                <?php echo $this->renderNotificationButton(); ?>
                <?php echo $this->renderUserMenu(); ?>
            </div>
        </header>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get welcome message with user info
     * 
     * @return string Welcome message
     */
    private function getWelcomeMessage() {
        $userName = htmlspecialchars(AuthHelper::getUserDisplayName());
        $userRole = AuthHelper::getCurrentUserRole();
        $roleText = $this->getRoleText($userRole);
        
        if ($this->pageTitle) {
            return $this->pageTitle . ' - ' . $userName . ' (' . $roleText . ')';
        }
        
        ob_start();
        _e('welcome');
        $welcome = ob_get_clean();
        return $welcome . ', ' . $userName . ' (' . $roleText . ')';
    }
    
    /**
     * Get role text for display
     * 
     * @param string $role User role
     * @return string Translated role text
     */
    private function getRoleText($role) {
        ob_start();
        switch ($role) {
            case 'ADMIN':
                _e('role_admin');
                break;
            case 'DIRECTOR':
                _e('role_director');
                break;
            case 'COORDINADOR':
                _e('role_coordinator');
                break;
            case 'DOCENTE':
                _e('role_teacher');
                break;
            case 'PADRE':
                _e('role_parent');
                break;
            default:
                echo $role;
        }
        return ob_get_clean();
    }
    
    /**
     * Render language switcher
     * 
     * @return string HTML for language switcher
     */
    private function renderLanguageSwitcher() {
        if ($this->languageSwitcher) {
            return $this->languageSwitcher->render('', 'mr-4');
        }
        return '';
    }
    
    /**
     * Render notification button
     * 
     * @return string HTML for notification button
     */
    private function renderNotificationButton() {
        ob_start();
        ?>
        <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </button>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render user menu dropdown
     * 
     * @return string HTML for user menu
     */
    private function renderUserMenu() {
        $userRole = AuthHelper::getCurrentUserRole();
        $roleText = $this->getRoleText($userRole);
        
        ob_start();
        ?>
        <div class="relative group">
            <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
                <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
            </button>
            
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block" id="userMenu">
                <div class="px-4 py-2 text-sm text-gray-700 border-b">
                    <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                    <div class="text-gray-500"><?php echo $roleText; ?></div>
                </div>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                    <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <?php _e('profile'); ?>
                </a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                    <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                    </svg>
                    <?php _e('settings'); ?>
                </a>
                <div class="border-t"></div>
                <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                    <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 713 3v1"></path>
                    </svg>
                    <?php _e('logout'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get JavaScript for header functionality
     * 
     * @return string JavaScript code
     */
    public static function getJavaScript() {
        ob_start();
        ?>
        <script>
        // Header functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Logout functionality
            const logoutButton = document.getElementById('logoutButton');
            if (logoutButton) {
                logoutButton.addEventListener('click', function() {
                    if (confirm('<?php _e('confirm_logout'); ?>')) {
                        window.location.href = '/src/controllers/LogoutController.php';
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
