<?php
/**
 * Reusable JavaScript Component for Admin Views
 * Provides common JavaScript functionality
 */

class AdminJS {
    
    /**
     * Get complete JavaScript for admin views
     * 
     * @param array $options Configuration options
     * @return string JavaScript code
     */
    public static function getScript($options = []) {
        $includeToast = $options['toast'] ?? true;
        $includeModal = $options['modal'] ?? true;
        $includeLogout = $options['logout'] ?? true;
        $includeUserMenu = $options['userMenu'] ?? true;
        
        ob_start();
        ?>
        <script>
        <?php if ($includeToast): ?>
        // Toast notification functions
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = getToastIcon(type);
            toast.innerHTML = `
                <div class="flex items-center">
                    ${icon}
                    <span>${message}</span>
                </div>
                <button onclick="hideToast(this)" class="ml-4 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            
            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => hideToast(toast), 5000);
        }

        function getToastIcon(type) {
            const icons = {
                success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
                info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };
            return icons[type] || icons.info;
        }

        function hideToast(toast) {
            if (toast && toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }
        <?php endif; ?>

        <?php if ($includeModal): ?>
        // Modal utility functions
        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
        }
        <?php endif; ?>

        // DOM Ready functions
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($includeLogout): ?>
            // Logout functionality
            const logoutButton = document.getElementById('logoutButton');
            if (logoutButton) {
                logoutButton.addEventListener('click', function() {
                    if (confirm('<?php _e('confirm_logout'); ?>')) {
                        window.location.href = '/src/controllers/LogoutController.php';
                    }
                });
            }
            <?php endif; ?>

            <?php if ($includeUserMenu): ?>
            // User menu toggle
            const userMenuButton = document.getElementById('userMenuButton');
            const userMenu = document.getElementById('userMenu');
            
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
            <?php endif; ?>
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
