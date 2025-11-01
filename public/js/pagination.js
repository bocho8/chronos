/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 */

/**
 * Reusable Pagination Component
 * Implements RF082: Pagination with 10, 25, 50 records per page
 */
class PaginationManager {
    constructor(options = {}) {
        this.container = options.container || null;
        this.currentPage = options.currentPage || 1;
        this.perPage = options.perPage || 10;
        this.totalRecords = options.totalRecords || 0;
        this.onPageChange = options.onPageChange || null;
        this.onPerPageChange = options.onPerPageChange || null;
        this.perPageOptions = options.perPageOptions || [10, 25, 50];
        this.showPerPageSelector = options.showPerPageSelector !== false;
        this.showInfo = options.showInfo !== false;
    }

    /**
     * Render pagination controls
     */
    render(container = null) {
        if (container) {
            this.container = container;
        }

        if (!this.container) {
            console.error('PaginationManager: Container not specified');
            return;
        }

        const totalPages = Math.ceil(this.totalRecords / this.perPage);

        // Don't show pagination if there's only one page or no records
        if (totalPages <= 1 && !this.showPerPageSelector) {
            this.container.innerHTML = '';
            return;
        }

        let html = '<div class="pagination-wrapper flex flex-col sm:flex-row items-center justify-between gap-4 p-4 border-t border-gray-200 bg-gray-50">';

        // Left side: Info and per-page selector
        html += '<div class="flex flex-col sm:flex-row items-center gap-3">';
        
        if (this.showInfo && this.totalRecords > 0) {
            const start = (this.currentPage - 1) * this.perPage + 1;
            const end = Math.min(this.currentPage * this.perPage, this.totalRecords);
            html += `<div class="text-sm text-gray-600">
                ${start}-${end} of ${this.totalRecords}
            </div>`;
        }

        if (this.showPerPageSelector && this.totalRecords > 0) {
            html += '<div class="flex items-center gap-2">';
            html += '<label for="perPageSelect" class="text-sm text-gray-600">Show:</label>';
            html += '<select id="perPageSelect" class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">';
            
            this.perPageOptions.forEach(option => {
                html += `<option value="${option}" ${this.perPage === option ? 'selected' : ''}>${option}</option>`;
            });
            
            html += '</select>';
            html += '</div>';
        }
        html += '</div>';

        // Right side: Page navigation
        if (totalPages > 1) {
            html += '<div class="flex items-center gap-1">';
            
            // First page button
            html += `<button 
                class="pagination-btn px-3 py-1 text-sm border border-gray-300 rounded ${this.currentPage === 1 ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-50 bg-white'}" 
                ${this.currentPage === 1 ? 'disabled' : ''}
                data-page="1"
                aria-label="First page">
                ««
            </button>`;

            // Previous page button
            html += `<button 
                class="pagination-btn px-3 py-1 text-sm border border-gray-300 rounded ${this.currentPage === 1 ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-50 bg-white'}" 
                ${this.currentPage === 1 ? 'disabled' : ''}
                data-page="${this.currentPage - 1}"
                aria-label="Previous page">
                ‹
            </button>`;

            // Page numbers
            const maxVisible = 5;
            let startPage = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);

            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            if (startPage > 1) {
                html += `<button class="pagination-btn px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 bg-white" data-page="1">1</button>`;
                if (startPage > 2) {
                    html += '<span class="px-2 text-gray-400">...</span>';
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `<button 
                    class="pagination-btn px-3 py-1 text-sm border rounded ${
                        i === this.currentPage 
                            ? 'bg-blue-500 text-white border-blue-500' 
                            : 'border-gray-300 hover:bg-gray-50 bg-white'
                    }" 
                    data-page="${i}">
                    ${i}
                </button>`;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += '<span class="px-2 text-gray-400">...</span>';
                }
                html += `<button class="pagination-btn px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 bg-white" data-page="${totalPages}">${totalPages}</button>`;
            }

            // Next page button
            html += `<button 
                class="pagination-btn px-3 py-1 text-sm border border-gray-300 rounded ${this.currentPage === totalPages ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-50 bg-white'}" 
                ${this.currentPage === totalPages ? 'disabled' : ''}
                data-page="${this.currentPage + 1}"
                aria-label="Next page">
                ›
            </button>`;

            // Last page button
            html += `<button 
                class="pagination-btn px-3 py-1 text-sm border border-gray-300 rounded ${this.currentPage === totalPages ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-50 bg-white'}" 
                ${this.currentPage === totalPages ? 'disabled' : ''}
                data-page="${totalPages}"
                aria-label="Last page">
                »»
            </button>`;

            html += '</div>';
        }

        html += '</div>';

        this.container.innerHTML = html;
        this.attachEventListeners();
    }

    /**
     * Attach event listeners to pagination controls
     */
    attachEventListeners() {
        // Page buttons
        const pageButtons = this.container.querySelectorAll('.pagination-btn:not([disabled])');
        pageButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(btn.getAttribute('data-page'));
                if (page && page !== this.currentPage && page >= 1) {
                    this.setPage(page);
                }
            });
        });

        // Per-page selector
        if (this.showPerPageSelector) {
            const perPageSelect = this.container.querySelector('#perPageSelect');
            if (perPageSelect) {
                perPageSelect.addEventListener('change', (e) => {
                    const perPage = parseInt(e.target.value);
                    this.setPerPage(perPage);
                });
            }
        }
    }

    /**
     * Set current page
     */
    setPage(page) {
        const totalPages = Math.ceil(this.totalRecords / this.perPage);
        if (page >= 1 && page <= totalPages) {
            this.currentPage = page;
            this.render();
            if (this.onPageChange) {
                this.onPageChange(page);
            }
        }
    }

    /**
     * Set records per page
     */
    setPerPage(perPage) {
        if (this.perPageOptions.includes(perPage)) {
            this.perPage = perPage;
            this.currentPage = 1; // Reset to first page
            this.render();
            if (this.onPerPageChange) {
                this.onPerPageChange(perPage);
            }
        }
    }

    /**
     * Update total records count
     */
    updateTotalRecords(total) {
        this.totalRecords = total;
        const totalPages = Math.ceil(this.totalRecords / this.perPage);
        if (this.currentPage > totalPages && totalPages > 0) {
            this.currentPage = totalPages;
        }
        this.render();
    }

    /**
     * Get current pagination state
     */
    getState() {
        return {
            currentPage: this.currentPage,
            perPage: this.perPage,
            totalRecords: this.totalRecords,
            totalPages: Math.ceil(this.totalRecords / this.perPage),
            offset: (this.currentPage - 1) * this.perPage,
            limit: this.perPage
        };
    }
}

// Make available globally
window.PaginationManager = PaginationManager;

