/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 */

/**
 * Filter Manager Component
 * Implements RF080: Multiple simultaneous filters with result count display
 */
class FilterManager {
    constructor(options = {}) {
        this.container = options.container || null;
        this.filters = options.filters || {};
        this.onFilterChange = options.onFilterChange || null;
        this.resultCountContainer = options.resultCountContainer || null;
        this.totalCount = options.totalCount || 0;
        this.filteredCount = options.filteredCount || 0;
    }

    /**
     * Initialize filter manager
     */
    init() {
        if (this.container) {
            this.attachFilterListeners();
            this.updateResultCount();
        }
    }

    /**
     * Attach event listeners to all filter elements
     */
    attachFilterListeners() {
        if (!this.container) return;

        // Find all filter inputs within container
        const filterInputs = this.container.querySelectorAll(
            'input[data-filter], select[data-filter], textarea[data-filter]'
        );

        filterInputs.forEach(input => {
            const filterName = input.getAttribute('data-filter');
            const filterType = input.type || input.tagName.toLowerCase();

            // Store initial value
            if (!this.filters[filterName]) {
                this.filters[filterName] = this.getValue(input, filterType);
            }

            // Attach change listener
            if (filterType === 'text' || filterType === 'textarea' || filterType === 'search') {
                let timeout;
                input.addEventListener('input', (e) => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        this.applyFilter(filterName, this.getValue(e.target, filterType));
                    }, 300); // Debounce for text inputs
                });
            } else {
                input.addEventListener('change', (e) => {
                    this.applyFilter(filterName, this.getValue(e.target, filterType));
                });
            }
        });
    }

    /**
     * Get value from input based on type
     */
    getValue(input, type) {
        if (type === 'checkbox') {
            return input.checked;
        } else if (type === 'select-multiple') {
            return Array.from(input.selectedOptions).map(opt => opt.value);
        } else {
            return input.value.trim();
        }
    }

    /**
     * Apply a single filter
     */
    applyFilter(name, value) {
        this.filters[name] = value;
        this.updateResultCount();
        
        if (this.onFilterChange) {
            this.onFilterChange(this.filters);
        }
    }

    /**
     * Get all active filters
     */
    getFilters() {
        return { ...this.filters };
    }

    /**
     * Reset all filters
     */
    resetFilters() {
        if (!this.container) return;

        const filterInputs = this.container.querySelectorAll(
            'input[data-filter], select[data-filter], textarea[data-filter]'
        );

        filterInputs.forEach(input => {
            const filterName = input.getAttribute('data-filter');
            const filterType = input.type || input.tagName.toLowerCase();

            if (filterType === 'checkbox') {
                input.checked = false;
            } else if (filterType === 'select-multiple') {
                Array.from(input.options).forEach(opt => opt.selected = false);
            } else {
                input.value = '';
            }

            this.filters[filterName] = this.getValue(input, filterType);
        });

        this.updateResultCount();
        
        if (this.onFilterChange) {
            this.onFilterChange(this.filters);
        }
    }

    /**
     * Update filtered result count display
     */
    updateResultCount(filteredCount = null, totalCount = null) {
        if (filteredCount !== null) {
            this.filteredCount = filteredCount;
        }
        if (totalCount !== null) {
            this.totalCount = totalCount;
        }

        if (this.resultCountContainer) {
            if (this.totalCount > 0 && this.filteredCount !== this.totalCount) {
                this.resultCountContainer.innerHTML = `
                    <div class="filter-result-count text-sm text-gray-600 bg-blue-50 px-3 py-2 rounded border border-blue-200 flex items-center justify-between">
                        <span>Showing <strong>${this.filteredCount}</strong> of <strong>${this.totalCount}</strong> results</span>
                        <button 
                            class="ml-2 text-blue-600 hover:text-blue-800 underline text-xs" 
                            onclick="if(window.filterManager) window.filterManager.resetFilters()">
                            Clear filters
                        </button>
                    </div>
                `;
            } else if (this.totalCount > 0) {
                this.resultCountContainer.innerHTML = `
                    <div class="filter-result-count text-sm text-gray-600 px-3 py-2">
                        Showing all <strong>${this.totalCount}</strong> results
                    </div>
                `;
            } else {
                this.resultCountContainer.innerHTML = '';
            }
        }
    }

    /**
     * Check if any filters are active
     */
    hasActiveFilters() {
        return Object.values(this.filters).some(value => {
            if (Array.isArray(value)) {
                return value.length > 0;
            }
            if (typeof value === 'boolean') {
                return value === true;
            }
            return value !== '' && value !== null && value !== undefined;
        });
    }
}

// Make available globally
window.FilterManager = FilterManager;

