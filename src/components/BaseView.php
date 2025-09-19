<?php
/**
 * Base View Component
 * Handles all common initialization and setup for admin views
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../components/LanguageSwitcher.php';
require_once __DIR__ . '/Sidebar.php';
require_once __DIR__ . '/Header.php';
require_once __DIR__ . '/Toast.php';
require_once __DIR__ . '/Modal.php';
require_once __DIR__ . '/AdminJS.php';

class BaseView {
    protected $translation;
    protected $languageSwitcher;
    protected $sidebar;
    protected $header;
    protected $currentPage;
    protected $pageTitle;
    
    public function __construct($currentPage, $pageTitle = '', $requiredRole = null) {
        $this->currentPage = $currentPage;
        $this->pageTitle = $pageTitle;
        
        // Initialize secure session
        initSecureSession();
        
        // Initialize translation system
        $this->translation = Translation::getInstance();
        $this->languageSwitcher = new LanguageSwitcher();
        
        // Handle language change
        $this->languageSwitcher->handleLanguageChange();
        
        // Require authentication and role (if specified)
        if ($requiredRole) {
            AuthHelper::requireRole($requiredRole);
        } else {
            // Just require login, allow any authenticated role
            AuthHelper::requireLogin();
        }
        
        // Check session timeout
        if (!AuthHelper::checkSessionTimeout()) {
            header("Location: /src/views/login.php?message=session_expired");
            exit();
        }
        
        // Initialize components
        $this->sidebar = new Sidebar($currentPage);
        $this->header = new Header($pageTitle, $this->languageSwitcher);
    }
    
    /**
     * Render complete page
     * 
     * @param string $title Page title for <title> tag
     * @param string $mainContent Main content HTML
     * @param array $options Layout options
     * @return string Complete HTML page
     */
    public function renderPage($title, $mainContent, $options = []) {
        $additionalCSS = $options['css'] ?? [];
        $jsOptions = $options['javascript'] ?? [];
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="<?php echo $this->translation->getCurrentLanguage(); ?>"<?php echo $this->translation->isRTL() ? ' dir="rtl"' : ''; ?>>
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title><?php echo $title; ?></title>
            <link rel="stylesheet" href="/css/styles.css">
            <?php echo Sidebar::getStyles(); ?>
            <?php echo Toast::getStyles(); ?>
            <?php echo Modal::getStyles(); ?>
            <?php foreach ($additionalCSS as $css): ?>
                <?php echo $css; ?>
            <?php endforeach; ?>
        </head>
        <body class="bg-bg font-sans text-gray-800 leading-relaxed">
            <div class="flex min-h-screen">
                <?php echo $this->sidebar->render(); ?>
                
                <main class="flex-1 flex flex-col">
                    <?php echo $this->header->render(); ?>
                    <?php echo $mainContent; ?>
                </main>
            </div>
            
            <?php echo Toast::getContainer(); ?>
            <?php echo AdminJS::getScript($jsOptions); ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render main content section
     * 
     * @param string $title Section title
     * @param string $description Section description
     * @param string $content Section content
     * @return string HTML for main content section
     */
    public function renderMainSection($title, $description, $content) {
        ob_start();
        ?>
        <section class="flex-1 px-6 py-8">
            <div class="max-w-6xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php echo $title; ?></h2>
                    <?php if ($description): ?>
                        <p class="text-muted mb-6 text-base"><?php echo $description; ?></p>
                    <?php endif; ?>
                </div>
                <?php echo $content; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Create a simple admin view with minimal code
     * 
     * @param string $pageFile Current page filename
     * @param string $pageTitle Page title
     * @param string $sectionTitle Main section title
     * @param string $sectionDescription Section description
     * @param string $content Main content
     * @param array $options Additional options
     * @return string Complete HTML page
     */
    public static function createSimpleView($pageFile, $pageTitle, $sectionTitle, $sectionDescription, $content, $options = []) {
        $view = new BaseView($pageFile, $pageTitle);
        
        $mainContent = $view->renderMainSection($sectionTitle, $sectionDescription, $content);
        
        return $view->renderPage($pageTitle, $mainContent, $options);
    }
}
?>
