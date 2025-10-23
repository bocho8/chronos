/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * AutoSaveManager - Unified auto-save utility
 * Provides debouncing, visual feedback, error handling, and request deduplication
 */
class AutoSaveManager {
    constructor(options = {}) {
        this.config = {
            debounceDelay: options.debounceDelay || 1000,
            maxRetries: options.maxRetries || 3,
            retryDelay: options.retryDelay || 1000,
            indicatorFadeDelay: options.indicatorFadeDelay || 2000,
            ...options
        };
        
        this.debounceTimers = new Map();
        this.activeRequests = new Map();
        this.retryQueues = new Map();
        this.indicators = new Map();
        this.unsavedChanges = new Set();
        
        // Bind methods
        this.save = this.save.bind(this);
        this.debounce = this.debounce.bind(this);
        this.preventDuplicates = this.preventDuplicates.bind(this);
        this.showSaveIndicator = this.showSaveIndicator.bind(this);
        this.retryWithBackoff = this.retryWithBackoff.bind(this);
        
        // Setup beforeunload warning
        this.setupBeforeUnloadWarning();
        
        // Setup periodic cleanup of stale requests
        setInterval(() => {
            this.clearStaleRequests();
        }, 10000); // Every 10 seconds
    }

    /**
     * Main save method with debouncing and deduplication
     */
    async save(key, saveFunction, options = {}) {
        const {
            indicator = null,
            onSuccess = () => {},
            onError = () => {},
            debounceDelay = this.config.debounceDelay,
            preventDuplicate = true,
            retryOnError = true
        } = options;

        // Create debounced version
        const debouncedSave = this.debounce(key, async () => {
            console.log(`AutoSave: Executing debounced save for key: ${key}`);
            await this.executeSave(key, saveFunction, {
                indicator,
                onSuccess,
                onError,
                preventDuplicate,
                retryOnError
            });
        }, debounceDelay);

        // Execute debounced save (don't call immediately)
        console.log(`AutoSave: Scheduling debounced save for key: ${key} with delay: ${debounceDelay}ms`);
        debouncedSave();
    }

    /**
     * Debounce function calls
     */
    debounce(key, callback, delay) {
        return () => {
            // Clear existing timer
            if (this.debounceTimers.has(key)) {
                clearTimeout(this.debounceTimers.get(key));
            }

            // Set new timer
            const timer = setTimeout(() => {
                this.debounceTimers.delete(key);
                callback();
            }, delay);

            this.debounceTimers.set(key, timer);
        };
    }

    /**
     * Execute the actual save with deduplication and error handling
     */
    async executeSave(key, saveFunction, options) {
        const {
            indicator,
            onSuccess,
            onError,
            preventDuplicate,
            retryOnError
        } = options;

        // Check for duplicate requests
        if (preventDuplicate && this.activeRequests.has(key)) {
            console.log(`AutoSave: Skipping duplicate request for key: ${key} (active since: ${new Date(this.activeRequests.get(key)).toISOString()})`);
            return;
        }

        // Mark as active request
        if (preventDuplicate) {
            this.activeRequests.set(key, Date.now());
            console.log(`AutoSave: Starting request for key: ${key}`);
        }

        // Show saving indicator
        if (indicator) {
            this.showSaveIndicator(indicator, 'saving');
        }

        try {
            // Execute save function
            const result = await saveFunction();
            
            // Mark as saved
            this.unsavedChanges.delete(key);
            
            // Show success indicator
            if (indicator) {
                this.showSaveIndicator(indicator, 'saved');
            }

            // Call success callback
            onSuccess(result);
            
        } catch (error) {
            console.error(`AutoSave error for key ${key}:`, error);
            
            // Show error indicator
            if (indicator) {
                this.showSaveIndicator(indicator, 'error', error);
            }

            // Retry on error if enabled
            if (retryOnError) {
                await this.retryWithBackoff(key, saveFunction, options);
            }

            // Call error callback
            onError(error);
            
        } finally {
            // Clear active request
            if (preventDuplicate) {
                this.activeRequests.delete(key);
                console.log(`AutoSave: Completed request for key: ${key}`);
            }
        }
    }

    /**
     * Retry failed requests with exponential backoff
     */
    async retryWithBackoff(key, saveFunction, options, attempt = 1) {
        if (attempt > this.config.maxRetries) {
            console.error(`AutoSave: Max retries exceeded for key: ${key}`);
            return;
        }

        const delay = this.config.retryDelay * Math.pow(2, attempt - 1);
        console.log(`AutoSave: Retrying ${key} in ${delay}ms (attempt ${attempt})`);

        await new Promise(resolve => setTimeout(resolve, delay));

        try {
            await this.executeSave(key, saveFunction, { ...options, retryOnError: false });
        } catch (error) {
            await this.retryWithBackoff(key, saveFunction, options, attempt + 1);
        }
    }

    /**
     * Show visual save indicator
     */
    showSaveIndicator(element, state, error = null) {
        if (!element) return;

        const indicatorId = `autosave-indicator-${Date.now()}`;
        
        // Remove existing indicators
        const existingIndicators = element.querySelectorAll('.autosave-indicator');
        existingIndicators.forEach(indicator => indicator.remove());

        // Create indicator element
        const indicator = document.createElement('span');
        indicator.className = 'autosave-indicator';
        indicator.id = indicatorId;
        
        switch (state) {
            case 'saving':
                indicator.innerHTML = `
                    <span class="inline-flex items-center text-blue-600 text-xs">
                        <svg class="animate-spin -ml-1 mr-1 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Guardando...
                    </span>
                `;
                break;
                
            case 'saved':
                indicator.innerHTML = `
                    <span class="inline-flex items-center text-green-600 text-xs">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Guardado
                    </span>
                `;
                
                // Fade out after delay
                setTimeout(() => {
                    if (indicator.parentNode) {
                        indicator.style.transition = 'opacity 0.5s';
                        indicator.style.opacity = '0';
                        setTimeout(() => indicator.remove(), 500);
                    }
                }, this.config.indicatorFadeDelay);
                break;
                
            case 'error':
                indicator.innerHTML = `
                    <span class="inline-flex items-center text-red-600 text-xs">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Error - 
                        <button onclick="window.autoSaveManager.retrySave('${element.dataset.autosaveKey || ''}')" 
                                class="underline hover:no-underline ml-1">
                            Reintentar
                        </button>
                    </span>
                `;
                break;
        }

        // Position indicator
        if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
            // For form inputs, add after the element
            element.parentNode.insertBefore(indicator, element.nextSibling);
        } else {
            // For other elements, append inside
            element.appendChild(indicator);
        }

        // Store reference for retry
        this.indicators.set(indicatorId, { element, state, error });
    }

    /**
     * Retry a specific save
     */
    async retrySave(key) {
        // This would need to be implemented based on how saves are stored
        console.log(`Retrying save for key: ${key}`);
    }

    /**
     * Mark changes as unsaved
     */
    markUnsaved(key) {
        this.unsavedChanges.add(key);
    }

    /**
     * Mark changes as saved
     */
    markSaved(key) {
        this.unsavedChanges.delete(key);
    }

    /**
     * Check if there are unsaved changes
     */
    hasUnsavedChanges() {
        return this.unsavedChanges.size > 0;
    }

    /**
     * Setup beforeunload warning for unsaved changes
     */
    setupBeforeUnloadWarning() {
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
                return e.returnValue;
            }
        });
    }

    /**
     * Clear stale active requests (older than 30 seconds)
     */
    clearStaleRequests() {
        const now = Date.now();
        const staleThreshold = 30000; // 30 seconds
        
        for (const [key, timestamp] of this.activeRequests.entries()) {
            if (now - timestamp > staleThreshold) {
                console.log(`AutoSave: Clearing stale request for key: ${key}`);
                this.activeRequests.delete(key);
            }
        }
    }

    /**
     * Clear all timers and active requests
     */
    cleanup() {
        // Clear all debounce timers
        this.debounceTimers.forEach(timer => clearTimeout(timer));
        this.debounceTimers.clear();
        
        // Clear active requests
        this.activeRequests.clear();
        
        // Clear indicators
        this.indicators.clear();
        
        // Clear unsaved changes
        this.unsavedChanges.clear();
    }

    /**
     * Get current status
     */
    getStatus() {
        return {
            activeRequests: this.activeRequests.size,
            unsavedChanges: this.unsavedChanges.size,
            pendingTimers: this.debounceTimers.size
        };
    }
}

// Create global instance
window.autoSaveManager = new AutoSaveManager();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AutoSaveManager;
}
