<?php
/**
 * Reusable Modal Component
 * Provides consistent modal structure for forms
 */

class Modal {
    
    /**
     * Get CSS styles for modals
     * 
     * @return string CSS styles
     */
    public static function getStyles() {
        return '
        <style>
        .modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 10000 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: rgba(0, 0, 0, 0.2) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
        }
        
        .modal.hidden {
            display: none !important;
        }
        
        .modal .modal-content {
            position: relative !important;
            z-index: 10001 !important;
            background: white !important;
            border-radius: 8px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }
        
        .modal button[type="submit"], 
        .modal button[type="button"] {
            z-index: 10002 !important;
            position: relative !important;
        }
        </style>
        ';
    }
    
    /**
     * Render modal structure
     * 
     * @param string $id Modal ID
     * @param string $title Modal title
     * @param string $content Modal content (form HTML)
     * @param string $width Modal width class
     * @return string HTML for modal
     */
    public static function render($id, $title, $content, $width = 'max-w-md') {
        ob_start();
        ?>
        <div id="<?php echo $id; ?>" class="modal hidden">
            <div class="modal-content p-8 w-full <?php echo $width; ?> mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 id="<?php echo $id; ?>Title" class="text-lg font-semibold text-gray-900"><?php echo $title; ?></h3>
                    <button onclick="close<?php echo ucfirst($id); ?>()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <?php echo $content; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get JavaScript for modal functionality
     * 
     * @param string $modalId Modal ID
     * @return string JavaScript code
     */
    public static function getJavaScript($modalId) {
        return "
        <script>
        // Modal functionality for {$modalId}
        function close{$modalId}() {
            document.getElementById('{$modalId}').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('{$modalId}').addEventListener('click', function(e) {
            if (e.target === this) {
                close{$modalId}();
            }
        });
        </script>
        ";
    }
}
?>
