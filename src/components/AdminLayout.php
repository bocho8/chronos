<?php
/**
 * Complete Admin Layout Component
 * Combines Sidebar, Header, Toast, and common functionality
 */

require_once __DIR__ . '/Sidebar.php';
require_once __DIR__ . '/Header.php';
require_once __DIR__ . '/Toast.php';
require_once __DIR__ . '/Modal.php';
require_once __DIR__ . '/AdminJS.php';

class AdminLayout {
    private $sidebar;
    private $header;
    private $currentPage;
    private $pageTitle;
    private $languageSwitcher;
    
    public function __construct($currentPage, $pageTitle = '', $languageSwitcher = null) {
        $this->currentPage = $currentPage;
        $this->pageTitle = $pageTitle;
        $this->languageSwitcher = $languageSwitcher;
        $this->sidebar = new Sidebar($currentPage);
        $this->header = new Header($pageTitle, $languageSwitcher);
    }
    
    /**
     * Render complete HTML head section
     * 
     * @param string $title Page title
     * @param array $additionalCSS Additional CSS files or styles
     * @return string HTML head content
     */
    public function renderHead($title, $additionalCSS = []) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="<?php echo Translation::getInstance()->getCurrentLanguage(); ?>"<?php echo Translation::getInstance()->isRTL() ? ' dir="rtl"' : ''; ?>>
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
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render complete layout structure
     * 
     * @param string $mainContent Main content HTML
     * @param array $options Layout options
     * @return string Complete HTML layout
     */
    public function render($mainContent, $options = []) {
        $includeToast = $options['toast'] ?? true;
        $includeJS = $options['javascript'] ?? true;
        
        ob_start();
        ?>
        <body class="bg-bg font-sans text-gray-800 leading-relaxed">
            <div class="flex min-h-screen">
                <?php echo $this->sidebar->render(); ?>
                
                <main class="flex-1 flex flex-col">
                    <?php echo $this->header->render(); ?>
                    <?php echo $mainContent; ?>
                </main>
            </div>
            
            <?php if ($includeToast): ?>
                <?php echo Toast::getContainer(); ?>
            <?php endif; ?>
            
            <?php if ($includeJS): ?>
                <?php echo AdminJS::getScript($options); ?>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render main content section wrapper
     * 
     * @param string $title Section title
     * @param string $description Section description
     * @param string $content Section content
     * @return string HTML for main content section
     */
    public static function renderMainSection($title, $description, $content) {
        ob_start();
        ?>
        <section class="flex-1 px-6 py-8">
            <div class="max-w-6xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php echo $title; ?></h2>
                    <p class="text-muted mb-6 text-base"><?php echo $description; ?></p>
                </div>
                <?php echo $content; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render table header with actions
     * 
     * @param string $title Table title
     * @param array $actions Array of action buttons
     * @return string HTML for table header
     */
    public static function renderTableHeader($title, $actions = []) {
        ob_start();
        ?>
        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
                <h3 class="font-medium text-darktext"><?php echo $title; ?></h3>
                <div class="flex gap-2">
                    <?php foreach ($actions as $action): ?>
                        <?php echo $action; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Create action button HTML
     * 
     * @param string $text Button text
     * @param string $onclick Click handler
     * @param string $type Button type (primary, secondary, danger)
     * @param string $icon SVG icon
     * @return string Button HTML
     */
    public static function createActionButton($text, $onclick = '', $type = 'primary', $icon = '') {
        $classes = [
            'primary' => 'bg-darkblue text-white hover:bg-navy',
            'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
            'danger' => 'bg-red-50 text-red-600 border border-red-300 hover:bg-red-100'
        ];
        
        $class = $classes[$type] ?? $classes['primary'];
        
        return sprintf(
            '<button onclick="%s" class="py-2 px-4 rounded cursor-pointer font-medium transition-all text-sm %s flex items-center">%s%s</button>',
            $onclick,
            $class,
            $icon ? $icon . ' ' : '',
            $text
        );
    }
}
?>
