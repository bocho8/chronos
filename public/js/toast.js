/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Toast Notification System for Chronos
 * Sistema unificado de notificaciones toast
 */

// Prevent multiple loading - check if already exists
if (typeof window.ToastManager === 'undefined' || !window.toastManager) {

(function() {
    'use strict';

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = new Map();
        this.queue = [];
        this.maxToasts = 5;
        this.queueIndicator = null;
        this.init();
    }

    init() {
        this.createContainer();
        this.addStyles();
    }

    createContainer() {
        this.container = document.getElementById('toastContainer');
        
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toastContainer';
            this.container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(this.container);
        }
        
        // Create confirmation modal container
        this.createConfirmModal();
    }

    createConfirmModal() {
        if (document.getElementById('confirmModal')) return;
        
        
        const modal = document.createElement('div');
        modal.id = 'confirmModal';
        modal.className = 'hidden';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        modal.innerHTML = `
            <div style="
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                max-width: 400px;
                width: 90%;
                margin: 16px;
                max-height: 90vh;
                overflow-y: auto;
            ">
                <div style="padding: 24px;">
                    <div style="display: flex; align-items: flex-start; margin-bottom: 16px;">
                        <div style="flex-shrink: 0; margin-right: 16px;">
                            <svg style="width: 32px; height: 32px; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <h3 id="confirmModalTitle" style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">Conflicto Detectado</h3>
                            <p id="confirmModalMessage" style="margin: 8px 0 0 0; font-size: 14px; color: #6b7280;">Mensaje de conflicto</p>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                        <button type="button" id="confirmModalCancel" style="
                            padding: 8px 16px;
                            border: 1px solid #d1d5db;
                            border-radius: 6px;
                            font-size: 14px;
                            font-weight: 500;
                            color: #374151;
                            background-color: white;
                            cursor: pointer;
                            transition: all 0.2s;
                        " onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='white'">
                            Cancelar
                        </button>
                        <button type="button" id="confirmModalConfirm" style="
                            padding: 8px 16px;
                            background-color: #f59e0b;
                            color: white;
                            border: none;
                            border-radius: 6px;
                            font-size: 14px;
                            font-weight: 500;
                            cursor: pointer;
                            transition: all 0.2s;
                        " onmouseover="this.style.backgroundColor='#d97706'" onmouseout="this.style.backgroundColor='#f59e0b'">
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Setup event listeners
        this.setupModalListeners();
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

            .toast::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 6px;
                background: rgba(0, 0, 0, 0.1);
                border-radius: 0 0 8px 8px;
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
            height: 6px;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0.95) 0%, 
                rgba(255, 255, 255, 0.8) 30%,
                rgba(255, 255, 255, 0.6) 60%, 
                rgba(255, 255, 255, 0.3) 100%);
            border-radius: 0 0 8px 8px;
            transition: width linear;
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.5), 0 0 4px rgba(255, 255, 255, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .toast-progress.animated {
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0.95) 0%, 
                rgba(255, 255, 255, 0.8) 30%,
                rgba(255, 255, 255, 0.6) 60%, 
                rgba(255, 255, 255, 0.3) 100%);
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.5), 0 0 4px rgba(255, 255, 255, 0.3);
        }




        /* Confirmation Modal Styles */
        #confirmModal {
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            background-color: rgba(0, 0, 0, 0.5);
        }

        #confirmModal.show {
            display: flex !important;
            opacity: 1;
            visibility: visible;
        }

        #confirmModal.hidden {
            display: none !important;
            opacity: 0;
            visibility: hidden;
        }

        #confirmModal .modal-content {
            animation: modalSlideIn 0.3s ease-out;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        #confirmModal button {
            transition: all 0.2s ease-in-out;
        }

        #confirmModal button:hover {
            transform: translateY(-1px);
        }

        #confirmModal button:active {
            transform: translateY(0);
        }

        /* Action Buttons */
        .toast-actions {
            display: flex;
            gap: 8px;
            margin-left: 12px;
        }

        .toast-action-btn {
            padding: 4px 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .toast-action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .toast-action-btn.primary {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .toast-action-btn.primary:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.6);
        }

        /* Queue Indicator */
        .toast-queue-indicator {
            position: fixed;
            top: 4px;
            right: 4px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            z-index: 60;
            backdrop-filter: blur(4px);
        }

        /* Pause state */
        .toast.paused .toast-progress {
            animation-play-state: paused;
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
            onClose = null,
            pauseOnHover = true,
            actions = []
        } = options;

        // Check if we're at the limit
        if (this.toasts.size >= this.maxToasts) {
            this.queue.push({ message, type, options });
            this.updateQueueIndicator();
            return null;
        }

        const toastId = this.generateId();
        const toast = this.createToast(toastId, message, type, icon, closable, actions);
        
        this.container.appendChild(toast);
        this.toasts.set(toastId, {
            element: toast,
            duration,
            remainingTime: duration,
            startTime: Date.now(),
            paused: false,
            onClose
        });

        setTimeout(() => {
            toast.classList.add('show', 'slide-in');
        }, 100);

        // Setup pause on hover
        if (pauseOnHover) {
            this.setupPauseOnHover(toastId);
        }

        if (duration > 0) {
            this.startProgressAnimation(toastId, duration);
        }

        return toastId;
    }

    createToast(id, message, type, icon, closable, actions = []) {
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

        // Build action buttons HTML
        let actionsHtml = '';
        if (actions.length > 0) {
            actionsHtml = '<div class="toast-actions">';
            actions.forEach((action, index) => {
                const actionClass = action.style === 'primary' ? 'primary' : '';
                actionsHtml += `<button class="toast-action-btn ${actionClass}" data-action-index="${index}">${action.text}</button>`;
            });
            actionsHtml += '</div>';
        }

        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon">${toastIcon}</div>
                <div class="toast-message">${message}</div>
            </div>
            ${actionsHtml}
            ${closable ? `<button class="toast-close" onclick="toastManager.hide('${id}')">×</button>` : ''}
            <div class="toast-progress animated" style="width: 100%;"></div>
        `;

        // Add event listeners for action buttons
        if (actions.length > 0) {
            actions.forEach((action, index) => {
                const button = toast.querySelector(`[data-action-index="${index}"]`);
                if (button && action.onClick) {
                    button.addEventListener('click', (e) => {
                        e.stopPropagation();
                        action.onClick(id);
                    });
                }
            });
        }

        return toast;
    }

    hide(toastId, onClose = null) {
        const toastData = this.toasts.get(toastId);
        if (!toastData) return;

        const toast = toastData.element;
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

            // Process queue if there are waiting toasts
            this.processQueue();
        }, 300);
    }

    setupPauseOnHover(toastId) {
        const toastData = this.toasts.get(toastId);
        if (!toastData) return;

        const toast = toastData.element;
        
        toast.addEventListener('mouseenter', () => {
            if (toastData.paused) return;
            
            toastData.paused = true;
            toast.classList.add('paused');
            
            // Calculate remaining time
            const elapsed = Date.now() - toastData.startTime;
            toastData.remainingTime = Math.max(0, toastData.duration - elapsed);
            
            // Pause the progress bar animation
            const progressBar = toast.querySelector('.toast-progress');
            if (progressBar) {
                const computedStyle = window.getComputedStyle(progressBar);
                const currentWidth = computedStyle.width;
                progressBar.style.width = currentWidth;
                progressBar.style.transition = 'none';
            }
        });

        toast.addEventListener('mouseleave', () => {
            if (!toastData.paused) return;
            
            toastData.paused = false;
            toast.classList.remove('paused');
            toastData.startTime = Date.now();
            
            // Restart progress animation with remaining time
            if (toastData.remainingTime > 0) {
                const progressBar = toast.querySelector('.toast-progress');
                if (progressBar) {
                    // Calculate current width based on remaining time
                    const currentWidth = (toastData.remainingTime / toastData.duration) * 100;
                    progressBar.style.width = `${currentWidth}%`;
                    progressBar.style.transition = 'none';
                    
                    // Force reflow
                    progressBar.offsetHeight;
                    
                    // Animate to 0%
                    progressBar.style.transition = `width ${toastData.remainingTime}ms linear`;
                    progressBar.style.width = '0%';
                }
            }
        });
    }

    startProgressAnimation(toastId, duration) {
        const toastData = this.toasts.get(toastId);
        if (!toastData) return;

        const toast = toastData.element;
        const progressBar = toast.querySelector('.toast-progress');
        
        if (progressBar) {
            // Start at 100% width
            progressBar.style.width = '100%';
            progressBar.style.transition = 'none';
            
            // Force a reflow to ensure the initial state is applied
            progressBar.offsetHeight;
            
            // Now animate to 0% over the duration
            progressBar.style.transition = `width ${duration}ms linear`;
            progressBar.style.width = '0%';
        }

        // Auto-hide after duration
        setTimeout(() => {
            if (this.toasts.has(toastId)) {
                this.hide(toastId, toastData.onClose);
            }
        }, duration);
    }

    updateQueueIndicator() {
        if (this.queue.length === 0) {
            if (this.queueIndicator) {
                this.queueIndicator.remove();
                this.queueIndicator = null;
            }
            return;
        }

        if (!this.queueIndicator) {
            this.queueIndicator = document.createElement('div');
            this.queueIndicator.className = 'toast-queue-indicator';
            document.body.appendChild(this.queueIndicator);
        }

        this.queueIndicator.textContent = `+${this.queue.length} more`;
    }

    processQueue() {
        if (this.queue.length === 0 || this.toasts.size >= this.maxToasts) {
            return;
        }

        const queuedToast = this.queue.shift();
        this.updateQueueIndicator();
        
        // Show the queued toast
        this.show(queuedToast.message, queuedToast.type, queuedToast.options);
    }

    hideAll() {
        this.toasts.forEach((toastData, id) => {
            this.hide(id);
        });
        this.queue = [];
        this.updateQueueIndicator();
    }

    generateId() {
        return 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

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

    // New convenience methods for common actions
    successWithUndo(message, undoCallback, options = {}) {
        return this.success(message, {
            ...options,
            actions: [{
                text: 'Undo',
                onClick: undoCallback,
                style: 'primary'
            }]
        });
    }

    errorWithRetry(message, retryCallback, options = {}) {
        return this.error(message, {
            ...options,
            actions: [{
                text: 'Retry',
                onClick: retryCallback,
                style: 'primary'
            }]
        });
    }

    setupModalListeners() {
        const modal = document.getElementById('confirmModal');
        const cancelBtn = document.getElementById('confirmModalCancel');
        
        // Close on cancel button
        cancelBtn?.addEventListener('click', () => this.hideConfirmModal());
        
        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal?.classList.contains('show')) {
                this.hideConfirmModal();
            }
        });
        
        // Close on backdrop click
        modal?.addEventListener('click', (e) => {
            if (e.target.id === 'confirmModal') {
                this.hideConfirmModal();
            }
        });
    }

    showConfirmModal(title, message, confirmText = 'Continuar', cancelText = 'Cancelar') {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirmModal');
            const titleEl = document.getElementById('confirmModalTitle');
            const messageEl = document.getElementById('confirmModalMessage');
            const confirmBtn = document.getElementById('confirmModalConfirm');
            const cancelBtn = document.getElementById('confirmModalCancel');
            
            
            if (!modal || !titleEl || !messageEl || !confirmBtn || !cancelBtn) {
                console.error('Modal elements not found');
                resolve(false);
                return;
            }
            
            // Set content
            titleEl.textContent = title;
            messageEl.textContent = typeof message === 'string' ? message : JSON.stringify(message);
            confirmBtn.textContent = confirmText;
            cancelBtn.textContent = cancelText;
            
            
            // Show modal with proper timing
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            modal.style.opacity = '0';
            
            // Force reflow
            modal.offsetHeight;
            
            // Fade in
            modal.style.opacity = '1';
            
            
            // Setup one-time event listeners
            const handleConfirm = () => {
                cleanup();
                resolve(true);
            };
            
            const handleCancel = () => {
                cleanup();
                resolve(false);
            };
            
            const cleanup = () => {
                confirmBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
                this.hideConfirmModal();
            };
            
            confirmBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);
        });
    }

    hideConfirmModal() {
        const modal = document.getElementById('confirmModal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }, 200);
        }
    }

    // Convenience method for conflict warnings
    async confirmConflict(message, options = {}) {
        const title = options.title || 'Conflicto Detectado';
        const confirmText = options.confirmText || 'Continuar de Todas Formas';
        const cancelText = options.cancelText || 'Cancelar';
        
        return await this.showConfirmModal(title, message, confirmText, cancelText);
    }
}

const toastManager = new ToastManager();

function showToast(message, type = 'info', options = {}) {
    return toastManager.show(message, type, options);
}

// New global convenience functions
function showSuccessWithUndo(message, undoCallback, options = {}) {
    return toastManager.successWithUndo(message, undoCallback, options);
}

function showErrorWithRetry(message, retryCallback, options = {}) {
    return toastManager.errorWithRetry(message, retryCallback, options);
}

function hideToast(toastId) {
    if (typeof toastId === 'string') {
        toastManager.hide(toastId);
    } else if (toastId && toastId.parentNode) {
        const id = toastId.getAttribute('data-toast-id');
        if (id) {
            toastManager.hide(id);
        }
    }
}

async function showConfirmModal(title, message, confirmText, cancelText) {
    return await toastManager.showConfirmModal(title, message, confirmText, cancelText);
}

async function confirmConflict(message, options = {}) {
    return await toastManager.confirmConflict(message, options);
}

// Make available globally
window.showConfirmModal = showConfirmModal;
window.confirmConflict = confirmConflict;

// Test function for debugging
window.testModal = async function() {
    console.log('Testing modal...');
    const result = await confirmConflict('Este es un mensaje de prueba', {
        title: 'Prueba de Modal',
        confirmText: 'Aceptar Prueba',
        cancelText: 'Cancelar Prueba'
    });
    console.log('Modal result:', result);
    return result;
};

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { ToastManager, toastManager };
    }

    // Make ToastManager and showToast available globally
    window.ToastManager = ToastManager;
    window.toastManager = toastManager;
    window.showToast = showToast;
    window.hideToast = hideToast;
    window.showSuccessWithUndo = showSuccessWithUndo;
    window.showErrorWithRetry = showErrorWithRetry;

})();

} // End of loading check
