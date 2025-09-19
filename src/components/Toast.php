<?php
/**
 * Reusable Toast Notification Component
 * Provides consistent toast notifications across views
 */

class Toast {
    
    /**
     * Get CSS styles for toast notifications
     * 
     * @return string CSS styles
     */
    public static function getStyles() {
        return '
        <style>
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            padding: 16px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            max-width: 400px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toast.show {
            transform: translateX(0);
        }
        .toast-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .toast-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .toast-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .toast-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        #toastContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }
        </style>
        ';
    }
    
    /**
     * Get HTML container for toasts
     * 
     * @return string HTML container
     */
    public static function getContainer() {
        return '<div id="toastContainer"></div>';
    }
    
    /**
     * Get JavaScript for toast functionality
     * 
     * @return string JavaScript code
     */
    public static function getJavaScript() {
        return '
        <script>
        // Toast notification functions
        function showToast(message, type = "info") {
            const container = document.getElementById("toastContainer");
            const toast = document.createElement("div");
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
            
            // Trigger animation
            setTimeout(() => toast.classList.add("show"), 100);
            
            // Auto hide after 5 seconds
            setTimeout(() => hideToast(toast), 5000);
        }

        function getToastIcon(type) {
            const icons = {
                success: \'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>\',
                error: \'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>\',
                warning: \'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>\',
                info: \'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>\'
            };
            return icons[type] || icons.info;
        }

        function hideToast(toast) {
            if (toast && toast.parentNode) {
                toast.classList.remove("show");
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }
        </script>
        ';
    }
}
?>
