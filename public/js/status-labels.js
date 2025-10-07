/**
 * Status Labels Module - Reusable Data-Driven Version
 * 
 * This module provides clickable status labels that can be selected from a dropdown.
 * It's designed to be reusable across different views by reading data attributes
 * from the HTML elements instead of doing database-related work.
 * 
 * Usage:
 * 1. Add data attributes to your HTML elements with the available labels
 * 2. Initialize the StatusLabels class with the appropriate selectors
 * 3. The script will automatically detect available labels and create the dropdown
 */

class StatusLabels {
    constructor(options = {}) {
        this.container = options.container || document;
        this.itemSelector = options.itemSelector || '.item-row';
        this.metaSelector = options.metaSelector || '.meta .text-muted';
        this.dropdownContainer = null;
        this.selectedLabels = new Set();
        this.storageKey = options.storageKey || 'statusLabels_selected';
        
        this.init();
    }

    init() {
        this.createDropdown();
        this.bindEvents();
        this.populateDropdown();
        this.loadDefaultSelection();
        this.loadSavedSelection();
        this.updateItemLabels();
    }
    
    createDropdown() {
        const header = this.container.querySelector('.flex.gap-2, .flex.justify-between, .flex.items-center');
        if (!header) return;
        
        this.dropdownContainer = document.createElement('div');
        this.dropdownContainer.className = 'status-labels-dropdown-container';
        this.dropdownContainer.innerHTML = this.getDropdownHTML();
        
        const addButton = header.querySelector('button');
        if (addButton) {
            addButton.parentNode.insertBefore(this.dropdownContainer, addButton);
        } else {
            header.appendChild(this.dropdownContainer);
        }
    }
    
    getDropdownHTML() {
        return `
            <div class="status-labels-dropdown">
                <button class="status-labels-toggle" type="button">
                    <span class="status-labels-icon">🏷️</span>
                    <span class="status-labels-text">Etiquetas</span>
                    <span class="status-labels-arrow">▼</span>
                </button>
                <div class="status-labels-content hidden">
                    <div class="status-labels-list" id="allLabelsList">
                        <!-- All labels will be added here -->
                    </div>
                    <div class="status-labels-actions">
                        <button class="clear-labels-btn" type="button">Limpiar Selección</button>
                    </div>
                </div>
            </div>
        `;
    }
    
    bindEvents() {
        if (!this.dropdownContainer) return;

        const toggle = this.dropdownContainer.querySelector('.status-labels-toggle');
        const content = this.dropdownContainer.querySelector('.status-labels-content');
        
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            content.classList.toggle('hidden');
            const arrow = toggle.querySelector('.status-labels-arrow');
            arrow.textContent = content.classList.contains('hidden') ? '▼' : '▲';
        });

        document.addEventListener('click', (e) => {
            if (!this.dropdownContainer.contains(e.target)) {
                content.classList.add('hidden');
                const arrow = toggle.querySelector('.status-labels-arrow');
                arrow.textContent = '▼';
            }
        });

        const clearBtn = this.dropdownContainer.querySelector('.clear-labels-btn');
        clearBtn.addEventListener('click', () => {
            this.clearSelection();
        });
    }
    
    populateDropdown() {
        const allLabelsList = this.dropdownContainer.querySelector('#allLabelsList');
        
        if (!allLabelsList) return;

        const items = this.container.querySelectorAll(this.itemSelector);
        const availableCategories = new Set();
        
        items.forEach(item => {
            const labelMapping = item.dataset.labelMapping;
            if (labelMapping) {
                try {
                    const mapping = JSON.parse(labelMapping);
                    Object.keys(mapping).forEach(category => {
                        availableCategories.add(category);
                    });
                } catch (e) {
                    console.error('Error parsing label mapping for dropdown:', e);
                }
            }
        });

        Array.from(availableCategories).sort().forEach(category => {
            const labelElement = this.createDropdownLabel(category, 'label');
            allLabelsList.appendChild(labelElement);
        });
    }
    
    createDropdownLabel(labelText, type, checkedByDefault = false) {
        const labelElement = document.createElement('div');
        labelElement.className = 'status-labels-dropdown-item';
        labelElement.innerHTML = `
            <label class="status-labels-checkbox">
                <input type="checkbox" data-label="${labelText}" data-type="${type}" ${checkedByDefault ? 'checked' : ''}>
                <span class="status-labels-text">${labelText}</span>
            </label>
        `;

        const checkbox = labelElement.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', (e) => {
            this.handleLabelSelection(labelText, type, e.target.checked);
        });

        if (checkedByDefault) {
            this.selectedLabels.add(labelText);
        }
        
        return labelElement;
    }
    
    handleLabelSelection(labelText, type, isSelected) {
        if (isSelected) {
            this.selectedLabels.add(labelText);
        } else {
            this.selectedLabels.delete(labelText);
        }
        
        this.updateItemLabels();
        this.saveSelection();
    }
    
    updateItemLabels() {
        const items = this.container.querySelectorAll(this.itemSelector);
        
        items.forEach(item => {
            const metaElement = item.querySelector(this.metaSelector);
            if (!metaElement) return;
            
            if (this.selectedLabels.size > 0) {

                const selectedLabels = this.getSelectedLabelsForItem(item);
                if (selectedLabels) {
                    const clickableLabels = this.makeLabelsClickable(selectedLabels);
                    metaElement.innerHTML = clickableLabels;
                    this.highlightSelectedLabels();
                } else {

                    metaElement.textContent = '';
                }
            } else {

                metaElement.textContent = '';
            }
        });
    }
    
    getSelectedLabelsForItem(item) {

        const labelMapping = item.dataset.labelMapping;
        if (!labelMapping) return '';
        
        try {
            const mapping = JSON.parse(labelMapping);
            const selectedLabels = [];

            this.selectedLabels.forEach(selectedLabel => {
                if (mapping[selectedLabel]) {
                    selectedLabels.push(mapping[selectedLabel]);
                }
            });
            
            return selectedLabels.length > 0 ? selectedLabels.join(' • ') : '';
        } catch (e) {
            console.error('Error parsing label mapping:', e);
            return '';
        }
    }
    
    checkItemForSelectedLabels(itemLabels) {
        const selectedLabels = [];

        this.selectedLabels.forEach(selectedLabel => {

            const matchingLabels = itemLabels.filter(itemLabel => 
                this.labelsMatch(selectedLabel, itemLabel)
            );
            selectedLabels.push(...matchingLabels);
        });
        
        return selectedLabels;
    }
    
    labelsMatch(selectedLabel, itemLabel) {

        return itemLabel.includes(selectedLabel) || selectedLabel.includes(itemLabel);
    }
    
    saveSelection() {
        try {
            const selectionArray = Array.from(this.selectedLabels);
            localStorage.setItem(this.storageKey, JSON.stringify(selectionArray));
        } catch (e) {
            console.warn('Could not save selection to localStorage:', e);
        }
    }
    
    loadDefaultSelection() {

        const defaultSelection = this.container.dataset.defaultLabels;
        if (defaultSelection) {
            try {
                const defaultArray = JSON.parse(defaultSelection);
                defaultArray.forEach(label => {
                    this.selectedLabels.add(label);
                });

                this.updateDropdownCheckboxes();
            } catch (e) {
                console.warn('Could not parse default labels:', e);
            }
        }
    }
    
    loadSavedSelection() {
        try {
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                const selectionArray = JSON.parse(saved);
                this.selectedLabels = new Set(selectionArray);
                this.updateDropdownCheckboxes();
            }
        } catch (e) {
            console.warn('Could not load saved selection from localStorage:', e);
        }
    }
    
    updateDropdownCheckboxes() {
        if (!this.dropdownContainer) return;
        
        const checkboxes = this.dropdownContainer.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            const labelText = checkbox.nextElementSibling.textContent.trim();
            checkbox.checked = this.selectedLabels.has(labelText);
        });
    }
    
    groupLabels(labels) {
        const grouped = [];
        const hasHours = labels.some(label => label.includes('horas semanales'));
        const hasPautas = labels.some(label => label.includes('Pauta'));
        const hasPrograms = labels.some(label => label.includes('Programa'));
        const hasStatus = labels.some(label => label.includes('Estado:'));
        
        if (hasHours) grouped.push('Horas semanales');
        if (hasPautas) grouped.push('Pautas');
        if (hasPrograms) grouped.push('Programas');
        if (hasStatus) grouped.push('Estados');
        
        return grouped;
    }
    
    makeLabelsClickable(labelsText) {

        return labelsText.replace(/(Estado: [^•]+|Programa [^•]+|\d+ horas semanales|Pauta [^•]+|roles: [^•]+)/g, (match) => {
            let labelType, labelValue, cleanValue;
            
            if (match.includes('Estado:')) {
                labelType = 'status';
                labelValue = match.replace(/^Estado: /, '');
            } else if (match.includes('Programa')) {
                labelType = 'program';
                labelValue = match.replace(/^Programa /, '');
            } else if (match.includes('horas semanales')) {
                labelType = 'info';
                labelValue = match;
            } else if (match.includes('Pauta')) {
                labelType = 'info';
                labelValue = match.replace(/^Pauta /, '');
            } else if (match.includes('roles:')) {
                labelType = 'info';
                labelValue = match.replace(/^roles: /, '');
            }
            
            cleanValue = labelValue.toLowerCase().replace(/\s+/g, '-');
            return `<span class="status-label clickable" data-filter="${labelType}" data-value="${cleanValue}" data-original="${match}">${match}</span>`;
        });
    }
    
    highlightSelectedLabels() {

        const allLabels = this.container.querySelectorAll('.status-label.clickable');
        allLabels.forEach(label => {
            label.classList.remove('highlighted');
        });

        this.selectedLabels.forEach(selectedLabel => {
            allLabels.forEach(label => {
                if (label.dataset.original === selectedLabel) {
                    label.classList.add('highlighted');
                }
            });
        });
    }
    
    clearSelection() {
        this.selectedLabels.clear();

        const checkboxes = this.dropdownContainer.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });

        this.updateItemLabels();
        this.saveSelection();
    }
}

const statusLabelsStyles = `
<style>

.status-labels-dropdown-container {
    display: inline-block;
    margin-right: 1rem;
}

.status-labels-dropdown {
    position: relative;
    display: inline-block;
}

.status-labels-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    color: #495057;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-labels-toggle:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
}

.status-labels-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.status-labels-icon {
    font-size: 1rem;
}

.status-labels-arrow {
    font-size: 0.75rem;
    transition: transform 0.2s ease;
}

.status-labels-content {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    min-width: 250px;
    margin-top: 0.25rem;
}

.status-labels-content.hidden {
    display: none;
}

.status-labels-list {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.status-labels-dropdown-item {
    display: flex;
    align-items: center;
}

.status-labels-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
    color: #495057;
    width: 100%;
}

.status-labels-checkbox input[type="checkbox"] {
    margin: 0;
    width: 1rem;
    height: 1rem;
    accent-color: #0d6efd;
}

.status-labels-checkbox:hover {
    color: #212529;
}

.status-labels-actions {
    padding: 1rem;
    border-top: 1px solid #e9ecef;
}

.clear-labels-btn {
    width: 100%;
    padding: 0.5rem;
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.clear-labels-btn:hover {
    background-color: #5a6268;
}

.status-label.clickable {
    cursor: pointer;
    color: #6b7280;
    font-size: 0.875rem;
    display: inline;
    text-decoration: none;
    transition: color 0.2s ease;
}

.status-label.clickable:hover {
    color: #374151;
    text-decoration: underline;
}

.status-label.clickable.highlighted {
    color: #3b82f6;
    font-weight: 500;
}

.status-label.clickable.highlighted:hover {
    color: #1e40af;
    text-decoration: underline;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', statusLabelsStyles);

window.StatusLabels = StatusLabels;