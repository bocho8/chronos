/**
 * Toast Notification System for Chronos
 * Sistema unificado de notificaciones toast
 */

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = new Map();
        this.init();
    }

    init() {
        this.createContainer();
        this.addStyles();
    }

    createContainer() {
        // Check if container already exists
        this.container = document.getElementById('toastContainer');
        
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toastContainer';
            this.container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(this.container);
        }
    }

    addStyles() {
        if (document.getElementById('toast-styles')) return;

        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                min-width: 300px;
                max-width: 500px;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                display: flex;
                align-items: center;
                justify-content: space-between;
                transform: translateX(100%);
                opacity: 0;
                transition: all 0.3s ease;
                margin-bottom: 8px;
                position: relative;
                overflow: hidden;
            }

            .toast.show {
                transform: translateX(0);
                opacity: 1;
            }

            .toast-success {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
            }

            .toast-error {
                background: linear-gradient(135deg, #ef4444, #dc2626);
                color: white;
            }

            .toast-warning {
                background: linear-gradient(135deg, #f59e0b, #d97706);
                color: white;
            }

            .toast-info {
                background: linear-gradient(135deg, #3b82f6, #2563eb);
                color: white;
            }

            .toast-content {
                display: flex;
                align-items: center;
                flex: 1;
            }

            .toast-icon {
                margin-right: 12px;
                font-size: 18px;
                display: flex;
                align-items: center;
            }

            .toast-message {
                flex: 1;
                font-size: 14px;
                font-weight: 500;
            }

            .toast-close {
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                margin-left: 12px;
                font-size: 18px;
                line-height: 1;
                opacity: 0.8;
                transition: opacity 0.2s;
            }

            .toast-close:hover {
                opacity: 1;
                background: rgba(255, 255, 255, 0.1);
            }

            .toast-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 0 0 8px 8px;
                transition: width linear;
            }

            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }

            .toast.slide-in {
                animation: slideIn 0.3s ease;
            }

            .toast.slide-out {
                animation: slideOut 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    }

    show(message, type = 'info', options = {}) {
        const {
            duration = 5000,
            closable = true,
            icon = null,
            onClose = null
        } = options;

        const toastId = this.generateId();
        const toast = this.createToast(toastId, message, type, icon, closable);
        
        this.container.appendChild(toast);
        this.toasts.set(toastId, toast);

        // Trigger animation
        setTimeout(() => {
            toast.classList.add('show', 'slide-in');
        }, 100);

        // Auto hide
        if (duration > 0) {
            const progressBar = toast.querySelector('.toast-progress');
            if (progressBar) {
                progressBar.style.transition = `width ${duration}ms linear`;
                progressBar.style.width = '0%';
            }

            setTimeout(() => {
                this.hide(toastId, onClose);
            }, duration);
        }

        return toastId;
    }

    createToast(id, message, type, icon, closable) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.setAttribute('data-toast-id', id);

        const defaultIcons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        const toastIcon = icon || defaultIcons[type] || defaultIcons.info;

        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon">${toastIcon}</div>
                <div class="toast-message">${message}</div>
            </div>
            ${closable ? `<button class="toast-close" onclick="toastManager.hide('${id}')">×</button>` : ''}
            <div class="toast-progress" style="width: 100%;"></div>
        `;

        return toast;
    }

    hide(toastId, onClose = null) {
        const toast = this.toasts.get(toastId);
        if (!toast) return;

        toast.classList.remove('show');
        toast.classList.add('slide-out');

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            this.toasts.delete(toastId);
            
            if (onClose && typeof onClose === 'function') {
                onClose();
            }
        }, 300);
    }

    hideAll() {
        this.toasts.forEach((toast, id) => {
            this.hide(id);
        });
    }

    generateId() {
        return 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    // Convenience methods
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    error(message, options = {}) {
        return this.show(message, 'error', options);
    }

    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }

    info(message, options = {}) {
        return this.show(message, 'info', options);
    }
}

// Global instance
const toastManager = new ToastManager();

// Global functions for backward compatibility
function showToast(message, type = 'info', options = {}) {
    return toastManager.show(message, type, options);
}

function hideToast(toastId) {
    if (typeof toastId === 'string') {
        toastManager.hide(toastId);
    } else if (toastId && toastId.parentNode) {
        // Handle old way of passing toast element
        const id = toastId.getAttribute('data-toast-id');
        if (id) {
            toastManager.hide(id);
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ToastManager, toastManager };
}
