/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Schedule Drag & Drop Manager
 * Handles drag-and-drop functionality for schedule management
 */

class ScheduleDragDropManager {
    constructor() {
        this.currentGroupId = null;
        this.assignments = [];
        this.draggedElement = null;
        this.draggedData = null;
        this.isDragging = false;
        this.dropZones = [];
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupSidebarToggle();
        this.setupSearchAndFilters();
    }

    setupEventListeners() {
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', this.toggleSidebar.bind(this));
        }

        // Group selection change - wait for the element to be available
        const checkGroupFilter = () => {
            const groupFilter = document.getElementById('filter_grupo');
            if (groupFilter) {
                groupFilter.addEventListener('change', (e) => {
                    this.currentGroupId = e.target.value;
                    this.loadAssignments();
                });
                
                // Load assignments for initially selected group
                if (groupFilter.value) {
                    this.currentGroupId = groupFilter.value;
                    this.loadAssignments();
                }
            } else {
                // Retry after a short delay if element not found
                setTimeout(checkGroupFilter, 100);
            }
        };
        
        checkGroupFilter();
    }

    setupSidebarToggle() {
        // Don't collapse the sidebar by default - let it be visible
        // const sidebar = document.querySelector('.bg-white.rounded-lg.shadow-sm.border.border-lightborder.mb-6');
        // if (sidebar) {
        //     sidebar.classList.add('sidebar-collapsed');
        // }
    }

    setupSearchAndFilters() {
        // Search functionality
        const searchInput = document.getElementById('sidebarSearch');
        if (searchInput) {
            searchInput.addEventListener('input', this.filterAssignments.bind(this));
        }

        // Filter buttons
        const filterButtons = ['filterAll', 'filterAvailable', 'filterBySubject'];
        filterButtons.forEach(buttonId => {
            const button = document.getElementById(buttonId);
            if (button) {
                button.addEventListener('click', () => this.setActiveFilter(buttonId));
            }
        });
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.bg-white.rounded-lg.shadow-sm.border.border-lightborder.mb-6');
        if (sidebar) {
            sidebar.classList.toggle('sidebar-collapsed');
        }
    }

    async loadAssignments() {
        if (!this.currentGroupId) {
            this.showMessage('Seleccione un grupo para ver las asignaciones disponibles');
            return;
        }


        try {
            const response = await fetch(`/src/controllers/HorarioHandler.php?action=get_available_assignments&grupo_id=${this.currentGroupId}`);
            const data = await response.json();
            
            
            if (data.success) {
                this.assignments = data.data || [];
                this.renderAssignments();
            } else {
                console.error('Error loading assignments:', data.message);
                this.showMessage('Error cargando asignaciones: ' + data.message);
            }
        } catch (error) {
            console.error('Error loading assignments:', error);
            this.showMessage('Error de conexión al cargar asignaciones');
        }
    }

    renderAssignments() {
        const container = document.getElementById('assignmentsList');
        if (!container) {
            console.error('Assignments container not found');
            return;
        }


        if (this.assignments.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 text-sm py-8">No hay materias asignadas a este grupo<br><small>Configure las materias del grupo primero</small></div>';
            return;
        }

        container.innerHTML = this.assignments.map(assignment => this.createAssignmentCard(assignment)).join('');
        
        // Setup drag events for each card
        this.setupDragEvents();
    }

    createAssignmentCard(assignment) {
        const availabilityClass = this.getAvailabilityClass(assignment);
        const availabilityIcon = this.getAvailabilityIcon(assignment);
        
        return `
            <div class="draggable-assignment" 
                 draggable="true"
                 data-subject-id="${assignment.id_materia}"
                 data-teacher-id="${assignment.id_docente}"
                 data-subject-name="${assignment.materia_nombre}"
                 data-teacher-name="${assignment.docente_nombre} ${assignment.docente_apellido}"
                 data-assignment-id="${assignment.id_materia}_${assignment.id_docente}">
                <div class="assignment-availability ${availabilityClass}"></div>
                <div class="assignment-subject">${assignment.materia_nombre}</div>
                <div class="assignment-teacher">${assignment.docente_nombre} ${assignment.docente_apellido}</div>
                <div class="assignment-hours">${assignment.hours_assigned || 0}/${assignment.hours_available || 0} horas</div>
            </div>
        `;
    }

    getAvailabilityClass(assignment) {
        if (assignment.is_available === true) return 'availability-available';
        if (assignment.is_available === false) return 'availability-unavailable';
        return 'availability-partial';
    }

    getAvailabilityIcon(assignment) {
        if (assignment.is_available === true) return '🟢';
        if (assignment.is_available === false) return '🔴';
        return '🟡';
    }

    setupDragEvents() {
        const draggableElements = document.querySelectorAll('.draggable-assignment');
        const dropZones = document.querySelectorAll('.drop-zone');
        const existingAssignments = document.querySelectorAll('.draggable-existing-assignment');


        // Setup draggable elements from sidebar
        draggableElements.forEach(element => {
            element.addEventListener('dragstart', this.handleDragStart.bind(this));
            element.addEventListener('dragend', this.handleDragEnd.bind(this));
        });

        // Setup existing schedule assignments as draggable
        existingAssignments.forEach(element => {
            element.addEventListener('dragstart', this.handleExistingDragStart.bind(this));
            element.addEventListener('dragend', this.handleDragEnd.bind(this));
            element.draggable = true;
            
            // Prevent buttons from interfering with drag
            const buttons = element.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('mousedown', (e) => {
                    e.stopPropagation();
                });
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            });
        });

        // Setup drop zones
        dropZones.forEach((zone, index) => {
            zone.addEventListener('dragover', this.handleDragOver.bind(this));
            zone.addEventListener('dragenter', this.handleDragEnter.bind(this));
            zone.addEventListener('dragleave', this.handleDragLeave.bind(this));
            
            // Use addEventListener with capture: true to intercept before onclick
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleDrop(e);
            }, true); // capture: true
            
            // Prevent onclick from interfering with drag and drop
            zone.addEventListener('mousedown', (e) => {
                if (this.isDragging) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
            
            zone.addEventListener('click', (e) => {
                if (this.isDragging) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
            
        });
    }

    // Method to refresh drag events after schedule grid updates
    refreshDragEvents() {
        this.setupDragEvents();
    }

    // Debug method to check existing assignments
    debugExistingAssignments() {
        const existingAssignments = document.querySelectorAll('.draggable-existing-assignment');
        existingAssignments.forEach((assignment, index) => {
            // Debug info for existing assignments
        });
    }

    handleDragStart(e) {
        this.draggedElement = e.target;
        this.draggedData = {
            subjectId: e.target.dataset.subjectId,
            teacherId: e.target.dataset.teacherId,
            subjectName: e.target.dataset.subjectName,
            teacherName: e.target.dataset.teacherName,
            assignmentId: e.target.dataset.assignmentId
        };
        
        this.isDragging = true;
        e.target.classList.add('dragging');
        
        // Disable onclick on all drop zones during drag
        document.querySelectorAll('.drop-zone').forEach(zone => {
            zone.setAttribute('data-original-onclick', zone.getAttribute('onclick') || '');
            zone.removeAttribute('onclick');
        });
        
        // Set drag image and data
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', JSON.stringify(this.draggedData));
        
        
        // Store data in a more persistent way
        this.currentDragData = this.draggedData;
    }

    handleExistingDragStart(e) {
        // Prevent dragging if clicking on buttons
        if (e.target.tagName === 'BUTTON') {
            e.preventDefault();
            return;
        }
        
        this.draggedElement = e.target;
        this.draggedData = {
            subjectId: e.target.dataset.subjectId,
            teacherId: e.target.dataset.teacherId,
            subjectName: e.target.dataset.subjectName,
            teacherName: e.target.dataset.teacherName,
            assignmentId: e.target.dataset.assignmentId
        };
        
        
        this.isDragging = true;
        e.target.classList.add('dragging');
        
        // Disable onclick on all drop zones during drag
        document.querySelectorAll('.drop-zone').forEach(zone => {
            zone.setAttribute('data-original-onclick', zone.getAttribute('onclick') || '');
            zone.removeAttribute('onclick');
        });
        
        // Set drag image
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', JSON.stringify(this.draggedData));
        
    }

    handleDragEnd(e) {
        e.target.classList.remove('dragging');
        this.isDragging = false;
        this.draggedElement = null;
        
        // Check if this is an existing assignment being dragged (has assignmentId without underscore)
        if (this.draggedData && this.draggedData.assignmentId && !this.draggedData.assignmentId.includes('_')) {
            // This is an existing assignment being moved within the table
            // Use elementFromPoint to detect where it was dropped
            const dropTarget = document.elementFromPoint(e.clientX, e.clientY);
            const dropZone = dropTarget?.closest('.drop-zone');
            
            if (dropZone) {
                // Ensure currentDragData is set for moveAssignment
                this.currentDragData = this.draggedData;
                this.handleDrop({
                    target: dropZone,
                    preventDefault: () => {},
                    stopPropagation: () => {}
                });
            }
        }
        
        // Restore onclick on all drop zones after drag
        document.querySelectorAll('.drop-zone').forEach(zone => {
            const originalOnclick = zone.getAttribute('data-original-onclick');
            if (originalOnclick) {
                zone.setAttribute('onclick', originalOnclick);
                zone.removeAttribute('data-original-onclick');
            }
        });
        
        // Clear all drag states
        document.querySelectorAll('.drop-zone').forEach(zone => {
            zone.classList.remove('drag-over', 'drag-over-invalid', 'drag-over-move');
        });
        
        // Clear dragged data only after processing
        // this.draggedData = null;
        // this.currentDragData = null;
    }

    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    }

    async handleDragEnter(e) {
        e.preventDefault();
        const dropZone = e.target.closest('.drop-zone');
        if (!dropZone) return;

        const isValid = await this.validateDrop(dropZone);
        if (isValid) {
            if (this.draggedData.assignmentId) {
                dropZone.classList.add('drag-over-move');
            } else {
                dropZone.classList.add('drag-over');
            }
        } else {
            dropZone.classList.add('drag-over-invalid');
        }
    }

    handleDragLeave(e) {
        const dropZone = e.target.closest('.drop-zone');
        if (!dropZone) return;

        // Only remove classes if we're actually leaving the drop zone
        if (!dropZone.contains(e.relatedTarget)) {
            dropZone.classList.remove('drag-over', 'drag-over-invalid', 'drag-over-move');
        }
    }

    async handleDrop(e) {
        e.preventDefault();
        const dropZone = e.target.closest('.drop-zone');
        if (!dropZone) {
            return;
        }

        dropZone.classList.remove('drag-over', 'drag-over-invalid', 'drag-over-move');

        // Try to get dragged data from instance, currentDragData, or dataTransfer
        
        let draggedData = this.draggedData || this.currentDragData;
        if (!draggedData) {
            try {
                const dataString = e.dataTransfer.getData('text/plain');
                if (dataString) {
                    draggedData = JSON.parse(dataString);
                }
            } catch (error) {
                console.error('Error parsing dragged data:', error);
                this.showToast('Error: No se encontraron datos de arrastre', 'error');
                return;
            }
        }

        if (!draggedData) {
            console.error('No dragged data available');
            this.showToast('Error: No se encontraron datos de arrastre', 'error');
            return;
        }

        // Ensure data is available for validation
        this.draggedData = draggedData;

        const isValid = await this.validateDrop(dropZone);
        if (!isValid) {
            this.showToast('No se puede asignar en este horario', 'error');
            return;
        }

        const isOccupied = dropZone.dataset.occupied === 'true';
        // For sidebar assignments, always create new (assignmentId format: "materiaId_docenteId")
        // For existing schedule entries, assignmentId would be a simple number
        const isMovingExisting = draggedData.assignmentId && draggedData.assignmentId !== '' && !draggedData.assignmentId.includes('_');
        
        
        if (isMovingExisting) {
            // Moving existing assignment
            await this.moveAssignment(dropZone);
        } else if (!isOccupied) {
            // Creating new assignment
            await this.createAssignment(dropZone);
        } else {
            this.showToast('Esta celda ya está ocupada. Arrastra a una celda vacía.', 'warning');
        }
        
        // Clean up drag data
        this.cleanupDragData();
    }
    
    cleanupDragData() {
        this.draggedData = null;
        this.currentDragData = null;
        this.draggedElement = null;
        this.isDragging = false;
    }

    async validateDrop(dropZone) {
        if (!this.draggedData || !this.currentGroupId) return false;

        const bloque = dropZone.dataset.bloque;
        const dia = dropZone.dataset.dia;
        const isOccupied = dropZone.dataset.occupied === 'true';

        // Basic validation
        if (!bloque || !dia) return false;

        // If dropping on occupied cell, it's invalid for new assignments
        if (isOccupied && !this.draggedData.assignmentId) {
            return false;
        }

        // Check teacher availability
        try {
            const response = await fetch(`/src/controllers/HorarioHandler.php?action=check_availability&docente_id=${this.draggedData.teacherId}&bloque=${bloque}&dia=${dia}`);
            const data = await response.json();
            
            if (data.success && !data.data.is_available) {
                return false;
            }
        } catch (error) {
            console.error('Error checking availability:', error);
            // Allow drop if availability check fails
        }

        return true;
    }

    async createAssignment(dropZone) {
        const bloque = dropZone.dataset.bloque;
        const dia = dropZone.dataset.dia;

        // Get the dragged data from the current context
        const draggedData = this.draggedData;
        
        if (!draggedData) {
            this.showToast('Error: No se encontraron datos de arrastre', 'error');
            return;
        }


        // Make AJAX call first
        try {
            const response = await fetch('/src/controllers/HorarioHandler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'quick_create',
                    id_grupo: this.currentGroupId,
                    id_materia: draggedData.subjectId,
                    id_docente: draggedData.teacherId,
                    id_bloque: bloque,
                    dia: dia
                })
            });

            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                console.error('Error parsing JSON response:', jsonError);
                this.showToast('Error: Respuesta del servidor no válida', 'error');
                return;
            }
            
            if (data.success) {
                this.showToast(`Asignación creada: ${draggedData.subjectName} - ${draggedData.teacherName}`, 'success');
                this.updateDropZone(dropZone, data.data);
                this.loadAssignments();
                
                if (typeof filterScheduleGrid === 'function') {
                    filterScheduleGrid(this.currentGroupId);
                }
        } else {
            console.error('Assignment creation failed:', data);
            console.error('Response status:', response.status);
            console.error('Response headers:', response.headers);
            
            // Show confirmation modal for conflicts
            if (data.message && data.message.includes('Conflicto detectado')) {
                const cleanMessage = data.message.replace('Conflicto detectado: ', '');
                
                
                if (typeof confirmConflict === 'function') {
                    const confirmed = await confirmConflict(cleanMessage, {
                        title: 'Conflicto de Horario',
                        confirmText: 'Crear de Todas Formas',
                        cancelText: 'Cancelar'
                    });
                    
                    
                    if (confirmed) {
                        await this.forceCreateAssignment(dropZone, draggedData);
                    }
                } else {
                    console.error('confirmConflict function not available, falling back to error toast');
                    this.showToast('Error: ' + (data.message || 'No se pudo crear la asignación'), 'error');
                }
            } else {
                this.showToast('Error: ' + (data.message || 'No se pudo crear la asignación'), 'error');
            }
        }
        } catch (error) {
            console.error('Error creating assignment:', error);
            this.showToast('Error de conexión al crear asignación', 'error');
        }
    }

    async forceCreateAssignment(dropZone, draggedData) {
        const bloque = dropZone.dataset.bloque;
        const dia = dropZone.dataset.dia;
        
        try {
            const response = await fetch('/src/controllers/HorarioHandler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'quick_create',
                    id_grupo: this.currentGroupId,
                    id_materia: draggedData.subjectId,
                    id_docente: draggedData.teacherId,
                    id_bloque: bloque,
                    dia: dia,
                    force_override: true
                })
            });

            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                console.error('Error parsing JSON response:', jsonError);
                this.showToast('Error: Respuesta del servidor no válida', 'error');
                return;
            }
            
            if (data.success) {
                this.showToast(`Asignación creada: ${draggedData.subjectName} - ${draggedData.teacherName}`, 'success');
                this.updateDropZone(dropZone, data.data);
                this.loadAssignments();
                
                if (typeof filterScheduleGrid === 'function') {
                    filterScheduleGrid(this.currentGroupId);
                }
            } else {
                this.showToast('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error forcing assignment:', error);
            this.showToast('Error al crear asignación', 'error');
        }
    }

    async moveAssignment(dropZone) {
        const bloque = dropZone.dataset.bloque;
        const dia = dropZone.dataset.dia;

        try {
            const response = await fetch('/src/controllers/HorarioHandler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'quick_move',
                    id_horario: this.draggedData.assignmentId,
                    new_bloque: bloque,
                    new_dia: dia
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(`Asignación movida: ${this.draggedData.subjectName} - ${this.draggedData.teacherName}`, 'success');
                
                // Refresh the entire schedule grid to show the moved assignment
                if (typeof filterScheduleGrid === 'function') {
                    filterScheduleGrid(this.currentGroupId);
                }
            } else {
                this.showToast('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error moving assignment:', error);
            this.showToast('Error de conexión al mover asignación', 'error');
        }
    }

    updateDropZone(dropZone, assignmentData) {
        dropZone.dataset.occupied = 'true';
        dropZone.innerHTML = `
            <div class="bg-blue-100 text-blue-800 p-1 rounded text-xs draggable-existing-assignment cursor-move" 
                 draggable="true"
                 data-grupo-id="${assignmentData.id_grupo}"
                 data-materia-id="${assignmentData.id_materia}"
                 data-docente-id="${assignmentData.id_docente}"
                 data-horario-id="${assignmentData.id_horario}"
                 data-assignment-id="${assignmentData.id_horario}"
                 data-subject-id="${assignmentData.id_materia}"
                 data-teacher-id="${assignmentData.id_docente}"
                 data-subject-name="${assignmentData.materia_nombre}"
                 data-teacher-name="${assignmentData.docente_nombre} ${assignmentData.docente_apellido}">
                <div class="font-semibold">${assignmentData.grupo_nombre}</div>
                <div>${assignmentData.materia_nombre}</div>
                <div class="text-xs">${assignmentData.docente_nombre} ${assignmentData.docente_apellido}</div>
                <div class="mt-1">
                    <button onclick="event.stopPropagation(); editHorario(${assignmentData.id_horario})" 
                            class="text-blue-600 hover:text-blue-800 text-xs mr-1">
                        Editar
                    </button>
                    <button onclick="event.stopPropagation(); deleteHorario(${assignmentData.id_horario})" 
                            class="text-red-600 hover:text-red-800 text-xs">
                        Eliminar
                    </button>
                </div>
            </div>
        `;
        
        // Setup drag events for the new assignment
        this.setupExistingAssignmentDragEvents(dropZone.querySelector('.draggable-existing-assignment'));
    }

    setupExistingAssignmentDragEvents(element) {
        if (!element) return;
        
        element.addEventListener('dragstart', this.handleExistingDragStart.bind(this));
        element.addEventListener('dragend', this.handleDragEnd.bind(this));
        element.draggable = true;
        
        // Prevent buttons from interfering with drag
        const buttons = element.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('mousedown', (e) => {
                e.stopPropagation();
            });
            button.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });
    }

    filterAssignments() {
        const searchTerm = document.getElementById('sidebarSearch').value.toLowerCase();
        const assignments = document.querySelectorAll('.draggable-assignment');
        
        assignments.forEach(assignment => {
            const subjectName = assignment.dataset.subjectName.toLowerCase();
            const teacherName = assignment.dataset.teacherName.toLowerCase();
            
            if (subjectName.includes(searchTerm) || teacherName.includes(searchTerm)) {
                assignment.style.display = 'block';
            } else {
                assignment.style.display = 'none';
            }
        });
    }

    setActiveFilter(filterId) {
        // Update button states
        const buttons = ['filterAll', 'filterAvailable', 'filterBySubject'];
        buttons.forEach(id => {
            const button = document.getElementById(id);
            if (button) {
                if (id === filterId) {
                    button.className = 'px-3 py-1 text-xs bg-darkblue text-white rounded hover:bg-blue-800';
                } else {
                    button.className = 'px-3 py-1 text-xs text-gray-600 hover:text-gray-800 border border-gray-300 rounded hover:bg-gray-50';
                }
            }
        });

        // Apply filter logic
        this.applyFilter(filterId);
    }

    applyFilter(filterId) {
        const assignments = document.querySelectorAll('.draggable-assignment');
        
        assignments.forEach(assignment => {
            let shouldShow = true;
            
            switch (filterId) {
                case 'filterAvailable':
                    shouldShow = assignment.querySelector('.availability-available') !== null;
                    break;
                case 'filterBySubject':
                    // Group by subject logic could be implemented here
                    break;
                default:
                    shouldShow = true;
            }
            
            assignment.style.display = shouldShow ? 'block' : 'none';
        });
    }

    showMessage(message) {
        const container = document.getElementById('assignmentsList');
        if (container) {
            container.innerHTML = `<div class="text-center text-gray-500 text-sm py-8">${message}</div>`;
        }
    }

    showToast(message, type = 'info') {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.scheduleDragDropManager = new ScheduleDragDropManager();
    
    // Make debug method available globally
    window.debugExistingAssignments = () => {
        if (window.scheduleDragDropManager) {
            window.scheduleDragDropManager.debugExistingAssignments();
        }
    };
});
