/**
 * Multiple Selection Module for CRUD Operations
 * Provides functionality for selecting multiple items and performing bulk actions
 */

class MultipleSelection {
    constructor(options = {}) {
        this.container = options.container || document;
        this.itemSelector = options.itemSelector || '.item-row';
        this.checkboxSelector = options.checkboxSelector || '.item-checkbox';
        this.selectAllSelector = options.selectAllSelector || '#selectAll';
        this.bulkActionsSelector = options.bulkActionsSelector || '#bulkActions';
        this.entityType = options.entityType || 'unknown';
        this.selectedItems = new Set();
        this.onSelectionChange = options.onSelectionChange || (() => {});
        this.onBulkAction = options.onBulkAction || (() => {});
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateUI();
    }

    bindEvents() {
        const selectAllCheckbox = this.container.querySelector(this.selectAllSelector);
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.toggleAll(e.target.checked);
            });
        }

        this.container.addEventListener('change', (e) => {
            if (e.target.matches(this.checkboxSelector)) {
                const itemId = e.target.dataset.itemId;
                if (e.target.checked) {
                    this.selectedItems.add(itemId);
                } else {
                    this.selectedItems.delete(itemId);
                }
                this.updateUI();
                this.onSelectionChange(this.selectedItems);
            }
        });

        this.container.addEventListener('click', (e) => {
            if (e.target.matches('[data-bulk-action]')) {
                e.preventDefault();
                const action = e.target.dataset.bulkAction;
                this.handleBulkAction(action);
            }
        });
    }

    toggleAll(checked) {
        const checkboxes = this.container.querySelectorAll(this.checkboxSelector);
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            const itemId = checkbox.dataset.itemId;
            if (checked) {
                this.selectedItems.add(itemId);
            } else {
                this.selectedItems.delete(itemId);
            }
        });
        this.updateUI();
        this.onSelectionChange(this.selectedItems);
    }

    updateUI() {
        const selectAllCheckbox = this.container.querySelector(this.selectAllSelector);
        const bulkActionsContainer = this.container.querySelector(this.bulkActionsSelector);
        const checkboxes = this.container.querySelectorAll(this.checkboxSelector);
        
        if (selectAllCheckbox) {
            if (this.selectedItems.size === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (this.selectedItems.size === checkboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
                selectAllCheckbox.checked = false;
            }
        }

        if (bulkActionsContainer) {
            if (this.selectedItems.size > 0) {
                bulkActionsContainer.classList.remove('hidden');
                this.updateBulkActionText();
            } else {
                bulkActionsContainer.classList.add('hidden');
            }
        }

        this.updateItemStates();
    }

    updateItemStates() {
        const items = this.container.querySelectorAll(this.itemSelector);
        items.forEach(item => {
            const checkbox = item.querySelector(this.checkboxSelector);
            if (checkbox && this.selectedItems.has(checkbox.dataset.itemId)) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }

    updateBulkActionText() {
        const count = this.selectedItems.size;
        const textElements = this.container.querySelectorAll('[data-selection-count]');
        textElements.forEach(element => {
            element.textContent = count;
        });
        
        this.updateStatistics();
    }
    
    updateStatistics() {
        const selectedItems = Array.from(this.selectedItems);
        const statisticsContainer = this.container.querySelector('#statisticsContainer');
        
        if (!statisticsContainer || selectedItems.length === 0) {
            return;
        }
        
        const selectedElements = selectedItems.map(id => 
            this.container.querySelector(`[data-item-id="${id}"]`)
        ).filter(el => el);
        
        const stats = this.calculateStatistics(selectedElements);

        this.displayStatistics(stats);
    }
    
    calculateStatistics(selectedElements) {
        const stats = {
            total: selectedElements.length,
            horas: 0,
            asignados: 0,
            noAsignados: 0,
            programas: new Set(),
            niveles: new Set(),
            roles: new Set()
        };
        
        selectedElements.forEach(element => {

            if (this.entityType === 'materias') {
                this.calculateMateriaStats(element, stats);
            } else if (this.entityType === 'grupos') {
                this.calculateGrupoStats(element, stats);
            } else if (this.entityType === 'usuarios') {
                this.calculateUsuarioStats(element, stats);
            }
        });
        
        return stats;
    }
    
    calculateMateriaStats(element, stats) {

        const metaElement = element.querySelector('.meta .text-muted');
        if (metaElement) {
            const text = metaElement.textContent;
            const horasMatch = text.match(/(\d+)\s*horas?\s*semanales?/i);
            if (horasMatch) {
                stats.horas += parseInt(horasMatch[1]);
            }

            if (text.includes('asignada') || text.includes('horario')) {
                stats.asignados++;
            } else {
                stats.noAsignados++;
            }

            if (text.includes('Programa Italiano')) {
                stats.programas.add('Italiano');
            }
        }
    }
    
    calculateGrupoStats(element, stats) {

        const nivel = element.getAttribute('data-nivel');
        if (nivel) {
            stats.niveles.add(nivel);
        }

        const scheduleButton = element.querySelector('button[onclick*="viewGroupSchedule"]');
        if (scheduleButton) {
            stats.asignados++;
        } else {
            stats.noAsignados++;
        }
    }
    
    calculateUsuarioStats(element, stats) {

        const metaElement = element.querySelector('.meta .text-muted');
        if (metaElement) {
            const text = metaElement.textContent;
            const rolesMatch = text.match(/roles?:\s*([^•]+)/i);
            if (rolesMatch) {
                const roles = rolesMatch[1].trim();
                if (roles !== 'Sin roles') {
                    stats.asignados++;
                    roles.split(',').forEach(role => {
                        stats.roles.add(role.trim());
                    });
                } else {
                    stats.noAsignados++;
                }
            }
        }
    }
    
    displayStatistics(stats) {
        const statisticsContainer = this.container.querySelector('#statisticsContainer');
        if (!statisticsContainer) return;
        
        let html = '';
        
        if (this.entityType === 'materias') {
            html = this.getMateriaStatisticsHTML(stats);
        } else if (this.entityType === 'grupos') {
            html = this.getGrupoStatisticsHTML(stats);
        } else if (this.entityType === 'usuarios') {
            html = this.getUsuarioStatisticsHTML(stats);
        }
        
        statisticsContainer.innerHTML = html;
    }
    
    getMateriaStatisticsHTML(stats) {
        return `
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">${this.getTranslation('total_hours')}:</span>
                    <span class="stat-value">${stats.horas}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">${this.getTranslation('assigned')}:</span>
                    <span class="stat-value">${stats.asignados}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">${this.getTranslation('unassigned')}:</span>
                    <span class="stat-value">${stats.noAsignados}</span>
                </div>
                ${stats.programas.size > 0 ? `
                <div class="stat-item">
                    <span class="stat-label">${this.getTranslation('programs')}:</span>
                    <span class="stat-value">${Array.from(stats.programas).join(', ')}</span>
                </div>
                ` : ''}
            </div>
        `;
    }
    
    getGrupoStatisticsHTML(stats) {
        return `
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Con Horario:</span>
                    <span class="stat-value">${stats.asignados}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Sin Horario:</span>
                    <span class="stat-value">${stats.noAsignados}</span>
                </div>
                ${stats.niveles.size > 0 ? `
                <div class="stat-item">
                    <span class="stat-label">Niveles:</span>
                    <span class="stat-value">${Array.from(stats.niveles).join(', ')}</span>
                </div>
                ` : ''}
            </div>
        `;
    }
    
    getUsuarioStatisticsHTML(stats) {
        return `
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Con Roles:</span>
                    <span class="stat-value">${stats.asignados}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Sin Roles:</span>
                    <span class="stat-value">${stats.noAsignados}</span>
                </div>
                ${stats.roles.size > 0 ? `
                <div class="stat-item">
                    <span class="stat-label">Roles:</span>
                    <span class="stat-value">${Array.from(stats.roles).join(', ')}</span>
                </div>
                ` : ''}
            </div>
        `;
    }

    handleBulkAction(action) {
        if (this.selectedItems.size === 0) {
            this.showToast('No hay elementos seleccionados', 'warning');
            return;
        }

        const selectedIds = Array.from(this.selectedItems);
        
        switch (action) {
            case 'delete':
                this.confirmBulkDelete(selectedIds);
                break;
            case 'export':
                this.exportSelected(selectedIds);
                break;
            case 'activate':
                this.showToast('Esta acción no está disponible para este tipo de entidad', 'warning');
                break;
            case 'deactivate':
                this.showToast('Esta acción no está disponible para este tipo de entidad', 'warning');
                break;
            default:
                this.onBulkAction(action, selectedIds);
        }
    }

    confirmBulkDelete(selectedIds) {
        const count = selectedIds.length;
        const message = this.getTranslation('confirm_bulk_delete') || `¿Estás seguro de que deseas eliminar ${count} elemento${count > 1 ? 's' : ''}? Esta acción no se puede deshacer.`;
        
        if (confirm(message)) {
            this.performBulkDelete(selectedIds);
        }
    }

    async performBulkDelete(selectedIds) {
        try {
            const response = await fetch('/src/controllers/bulk_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'bulk_delete',
                    entity_type: this.getEntityType(),
                    ids: selectedIds
                })
            });

            if (!response.ok) {
                const errorText = await response.text();

                try {
                    const errorData = JSON.parse(errorText);
                    if (errorData.message) {

                        const decodedMessage = errorData.message.replace(/\\u([0-9a-fA-F]{4})/g, (match, code) => {
                            return String.fromCharCode(parseInt(code, 16));
                        });
                        
                        const isForeignKeyError = decodedMessage && (
                            decodedMessage.includes('están siendo utilizadas') ||
                            decodedMessage.includes('están siendo utilizados') ||
                            decodedMessage.includes('tienen roles asignados') ||
                            decodedMessage.includes('⚠️') ||
                            decodedMessage.includes('utilizadas en horarios') ||
                            decodedMessage.includes('utilizados en horarios') ||
                            decodedMessage.includes('primero debes eliminar') ||
                            decodedMessage.includes('primero debes remover')
                        );
                        
                        if (isForeignKeyError) {
                            this.showToast(errorData.message, 'warning');
                            return;
                        } else {
                            throw new Error(errorData.message);
                        }
                    }
                } catch (parseError) {

                }
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast(this.getTranslation('bulk_delete_success') || `Se eliminaron ${selectedIds.length} elementos correctamente`, 'success');
                this.clearSelection();

                setTimeout(() => location.reload(), 1000);
            } else {

                const isForeignKeyError = data.message && (
                    data.message.includes('están siendo utilizadas') ||
                    data.message.includes('están siendo utilizados') ||
                    data.message.includes('están siendo utilizadas') ||
                    data.message.includes('tienen roles asignados') ||
                    data.message.includes('⚠️')
                );
                
                if (isForeignKeyError) {
                    this.showToast(data.message, 'warning');
                } else {
                    this.showToast(this.getTranslation('bulk_delete_error') || 'Error al eliminar elementos: ' + data.message, 'error');
                }
            }
        } catch (error) {

            const isForeignKeyError = error.message && (
                error.message.includes('están siendo utilizadas') ||
                error.message.includes('están siendo utilizados') ||
                error.message.includes('tienen roles asignados') ||
                error.message.includes('⚠️') ||
                error.message.includes('utilizadas en horarios') ||
                error.message.includes('utilizados en horarios')
            );
            
            if (isForeignKeyError) {
                this.showToast(error.message, 'warning');
            } else {
                this.showToast(this.getTranslation('bulk_delete_error') || 'Error de conexión: ' + error.message, 'error');
            }
        }
    }

    exportSelected(selectedIds) {

        const csvData = this.generateCSV(selectedIds);
        this.downloadCSV(csvData, `${this.getEntityType()}_export.csv`);
    }

    generateCSV(selectedIds) {

        const headers = this.getCSVHeaders();
        const rows = selectedIds.map(id => this.getCSVRowData(id));
        
        return [headers, ...rows].map(row => 
            row.map(field => `"${field}"`).join(',')
        ).join('\n');
    }

    getCSVHeaders() {

        return ['ID', 'Nombre'];
    }

    getCSVRowData(id) {

        const item = this.container.querySelector(`[data-item-id="${id}"]`);
        return [id, item?.textContent?.trim() || ''];
    }

    downloadCSV(csvContent, filename) {
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    async bulkUpdateStatus(selectedIds, status) {
        try {
            const response = await fetch('/src/controllers/bulk_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'bulk_update_status',
                    entity_type: this.getEntityType(),
                    ids: selectedIds,
                    status: status
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(this.getTranslation('bulk_update_success') || `Se actualizó el estado de ${selectedIds.length} elementos`, 'success');
                this.clearSelection();
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showToast(this.getTranslation('bulk_update_error') || 'Error al actualizar elementos: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showToast(this.getTranslation('bulk_update_error') || 'Error de conexión', 'error');
        }
    }

    getEntityType() {

        return this.entityType || 'unknown';
    }

    clearSelection() {
        this.selectedItems.clear();
        const checkboxes = this.container.querySelectorAll(this.checkboxSelector);
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        this.updateUI();
    }

    getSelectedIds() {
        return Array.from(this.selectedItems);
    }

    getSelectedCount() {
        return this.selectedItems.size;
    }

    getTranslation(key) {

        if (typeof _e === 'function') {
            const translation = _e(key);

            if (translation && translation !== key) {
                return translation;
            }
        }

        const fallbacks = {
            'total_hours': 'Total Hours',
            'assigned': 'Assigned',
            'unassigned': 'Unassigned',
            'programs': 'Programs',
            'confirm_bulk_delete': 'Are you sure you want to delete these items?',
            'bulk_delete_success': 'Items deleted successfully',
            'bulk_delete_error': 'Error deleting items',
            'bulk_update_success': 'Items updated successfully',
            'bulk_update_error': 'Error updating items'
        };
        
        return fallbacks[key] || key;
    }

    showToast(message, type = 'info') {

        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {

            this.createFallbackToast(message, type);
        }
    }

    createFallbackToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
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
            transition: transform 0.3s ease;
            max-width: 400px;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.4;
        `;

        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        toast.style.backgroundColor = colors[type] || colors.info;

        let decodedMessage = message;

        decodedMessage = decodedMessage.replace(/\\u([0-9a-fA-F]{4})/g, (match, code) => {
            return String.fromCharCode(parseInt(code, 16));
        });

        decodedMessage = decodedMessage.replace(/\\u([0-9a-fA-F]{4})\\u([0-9a-fA-F]{4})/g, (match, code1, code2) => {
            return String.fromCharCode(parseInt(code1, 16)) + String.fromCharCode(parseInt(code2, 16));
        });
        
        toast.innerHTML = decodedMessage;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);

        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 5000);
    }
}

const multipleSelectionStyles = `
<style>
.item-row {
    transition: all 0.2s ease;
}

.item-row.selected {
    background-color: #e3f2fd !important;
    border-left: 4px solid #2196f3;
}

.item-row:hover {
    background-color: #f5f5f5;
}

.item-row.selected:hover {
    background-color: #bbdefb !important;
}

.bulk-actions {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-bottom: 1rem;
    transition: max-height 0.3s ease, opacity 0.3s ease, padding 0.3s ease, margin 0.3s ease;
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
    margin-bottom: 0;
    border-width: 0;
}

.bulk-actions:not(.hidden) {
    max-height: 200px;
    opacity: 1;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
    border-width: 1px;
}

.bulk-actions .action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem;
    background-color: #f9fafb;
    border-radius: 0.375rem;
    border: 1px solid #e5e7eb;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
    text-align: center;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    text-align: center;
}

.stat-item:has(.stat-value:contains("0")) {
    opacity: 0.6;
}

.stat-item:has(.stat-value:not(:contains("0"))) {
    background-color: #f0f9ff;
    border-color: #0ea5e9;
}

.stat-item:has(.stat-value:not(:contains("0"))) .stat-value {
    color: #0369a1;
}

.bulk-actions .action-buttons button {
    padding: 0.5rem 1rem;
    border: 1px solid #6c757d;
    border-radius: 0.25rem;
    background-color: white;
    color: #495057;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.bulk-actions .action-buttons button:hover {
    background-color: #e9ecef;
    border-color: #495057;
}

.bulk-actions .action-buttons button[data-bulk-action="delete"] {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

.bulk-actions .action-buttons button[data-bulk-action="delete"]:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

.bulk-actions .action-buttons button[data-bulk-action="export"] {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
}

.bulk-actions .action-buttons button[data-bulk-action="export"]:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.selection-info {
    color: #6c757d;
    font-size: 0.875rem;
    margin-right: 1rem;
}

.checkbox-container {
    display: flex;
    align-items: center;
    margin-right: 0.75rem;
}

.checkbox-container input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    cursor: pointer;
}

.select-all-container {
    display: flex;
    align-items: center;
    margin-right: 1rem;
}

.select-all-container input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    cursor: pointer;
    margin-right: 0.5rem;
}

.select-all-container label {
    cursor: pointer;
    font-size: 0.875rem;
    color: #495057;
}

@media (max-width: 768px) {
    .bulk-actions .action-buttons {
        flex-direction: column;
    }
    
    .bulk-actions .action-buttons button {
        width: 100%;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', multipleSelectionStyles);

window.MultipleSelection = MultipleSelection;
