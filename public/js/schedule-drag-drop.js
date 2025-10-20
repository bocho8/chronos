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
        this.currentDropZone = null; // Track currently highlighted drop zone
        this.currentFilter = 'filterAll'; // Track current active filter
        this.currentTeacherAvailability = null; // Store fetched availability
        
        // Auto-scroll properties
        this.scrollInterval = null;
        this.scrollZoneHeight = 50; // pixels
        this.maxScrollSpeed = 10; // pixels per frame
        this.assignmentsContainer = null;
        
        // Error handling and operation state
        this.lastToastMessage = null;
        this.lastToastTime = 0;
        this.toastDebounceMs = 500;
        this.operationInProgress = {
            type: null, // 'create', 'move', 'delete'
            startTime: 0
        };
        
        this.init();
        
        // Make preference functions available globally for debugging
        window.schedulePreferences = {
            clear: () => this.clearPreferences(),
            get: () => this.getPreferences(),
            save: (key, value) => this.savePreferences(key, value)
        };
        
        // Make group ID setter available globally
        window.setScheduleGroupId = (groupId) => {
            this.currentGroupId = groupId;
            this.savePreferences('scheduleSelectedGroup', groupId);
            this.loadAssignments();
        };
    }

    init() {
        this.setupEventListeners();
        this.setupSidebarToggle();
        this.setupSearchAndFilters();
        
        // Wait for DOM to be fully ready before loading preferences
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.loadPreferences();
                this.loadInitialAssignments();
            });
        } else {
            this.loadPreferences();
            this.loadInitialAssignments();
        }
    }

    // Preferences management
    loadPreferences() {
        // Load saved group
        const savedGroup = localStorage.getItem('scheduleSelectedGroup');
        console.log('Loading preferences, savedGroup:', savedGroup);
        if (savedGroup) {
            const groupFilter = document.getElementById('filter_grupo');
            console.log('Group filter element:', groupFilter);
            if (groupFilter && groupFilter.querySelector(`option[value="${savedGroup}"]`)) {
                groupFilter.value = savedGroup;
                this.currentGroupId = savedGroup;
                console.log('Group preference restored:', savedGroup);
            } else {
                console.log('Group filter not found or option not available');
            }
        }
        
        // Load sidebar state
        const sidebarCollapsed = localStorage.getItem('scheduleSidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            this.collapseSidebar();
        }
        
        // Load assignment filter
        const savedFilter = localStorage.getItem('scheduleAssignmentFilter');
        if (savedFilter) {
            this.currentFilter = savedFilter;
            this.setActiveFilter(savedFilter);
        }
        
        // Load search term
        const savedSearchTerm = localStorage.getItem('scheduleSearchTerm');
        if (savedSearchTerm) {
            const searchInput = document.getElementById('sidebarSearch');
            if (searchInput) {
                searchInput.value = savedSearchTerm;
            }
        }
    }

    savePreferences(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {
            console.error('Error saving preference:', e);
        }
    }

    collapseSidebar() {
        const sidebarContent = document.getElementById('sidebarContent');
        if (sidebarContent) {
            sidebarContent.style.display = 'none';
        }
    }

    clearPreferences() {
        const keys = [
            'scheduleSelectedGroup',
            'scheduleSidebarCollapsed',
            'scheduleViewMode',
            'scheduleAssignmentFilter',
            'scheduleSearchTerm'
        ];
        keys.forEach(key => localStorage.removeItem(key));
    }

    // Debug function to view all preferences
    getPreferences() {
        return {
            selectedGroup: localStorage.getItem('scheduleSelectedGroup'),
            sidebarCollapsed: localStorage.getItem('scheduleSidebarCollapsed'),
            viewMode: localStorage.getItem('scheduleViewMode'),
            assignmentFilter: localStorage.getItem('scheduleAssignmentFilter'),
            searchTerm: localStorage.getItem('scheduleSearchTerm')
        };
    }

    loadInitialAssignments() {
        // Load assignments for the current group (after preferences are loaded)
        console.log('Loading initial assignments, currentGroupId:', this.currentGroupId);
        if (this.currentGroupId) {
            console.log('Loading assignments for group:', this.currentGroupId);
            this.loadAssignments();
        } else {
            console.log('No group selected, skipping assignment loading');
        }
    }

    setupEventListeners() {
        // Group selection change - wait for the element to be available
        const checkGroupFilter = () => {
            const groupFilter = document.getElementById('filter_grupo');
            if (groupFilter) {
                groupFilter.addEventListener('change', (e) => {
                    this.currentGroupId = e.target.value;
                    this.savePreferences('scheduleSelectedGroup', e.target.value);
                    this.loadAssignments();
                });
                
                // Note: Initial assignments are loaded in loadInitialAssignments() 
                // after preferences are restored
            } else {
                // Retry after a short delay if element not found
                setTimeout(checkGroupFilter, 100);
            }
        };
        
        checkGroupFilter();
        
        // Global dragend safety net - catch all cases where highlights might get stuck
        document.addEventListener('dragend', () => {
            this.clearAllHighlights();
        });
    }

    setupSidebarToggle() {
        // Set up the sidebar toggle button
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', this.toggleSidebar.bind(this));
        }
    }

    setupSearchAndFilters() {
        // Search functionality
        const searchInput = document.getElementById('sidebarSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.savePreferences('scheduleSearchTerm', e.target.value);
                this.filterAssignments();
            });
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
        // Find the sidebar content element directly
        const sidebarContent = document.getElementById('sidebarContent');
        if (sidebarContent) {
            // Toggle visibility directly
            const isCollapsed = sidebarContent.style.display === 'none';
            sidebarContent.style.display = isCollapsed ? 'block' : 'none';
            this.savePreferences('scheduleSidebarCollapsed', !isCollapsed);
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

        // Check if assignments are already grouped (new format) or need grouping (old format)
        const isGrouped = this.assignments.length > 0 && this.assignments[0].hasOwnProperty('teachers');
        
        if (isGrouped) {
            // New grouped format - render directly
            container.innerHTML = this.assignments.map(assignment => this.createGroupedAssignmentCard(assignment)).join('');
        } else {
            // Old format - group by subject first
            const groupedAssignments = this.groupAssignmentsBySubject(this.assignments);
            container.innerHTML = groupedAssignments.map(assignment => this.createGroupedAssignmentCard(assignment)).join('');
        }
        
        // Setup drag events for each card
        this.setupDragEvents();
        
        // Apply current filter after loading assignments
        this.applyCurrentFilter();
    }

    groupAssignmentsBySubject(assignments) {
        const grouped = {};
        
        assignments.forEach(assignment => {
            const subjectId = assignment.id_materia;
            
            if (!grouped[subjectId]) {
                grouped[subjectId] = {
                    id_materia: subjectId,
                    materia_nombre: assignment.materia_nombre,
                    teachers: [],
                    total_teachers: 0,
                    available_teachers: 0,
                    total_hours_available: 0,
                    availability_percentage: 0,
                    is_auto_selectable: false
                };
            }
            
            grouped[subjectId].teachers.push({
                id_docente: assignment.id_docente,
                nombre: assignment.docente_nombre,
                apellido: assignment.docente_apellido,
                hours_assigned: assignment.hours_assigned,
                hours_available: assignment.hours_available,
                is_available: assignment.is_available,
                hours_total: assignment.hours_total
            });
            
            grouped[subjectId].total_teachers++;
            if (assignment.is_available) {
                grouped[subjectId].available_teachers++;
                grouped[subjectId].total_hours_available += assignment.hours_available;
            }
        });
        
        // Calculate availability percentage and auto-selectable status
        Object.values(grouped).forEach(group => {
            group.availability_percentage = group.total_teachers > 0 
                ? Math.round((group.available_teachers / group.total_teachers) * 100) 
                : 0;
            group.is_auto_selectable = group.available_teachers > 0;
        });
        
        return Object.values(grouped);
    }

    createGroupedAssignmentCard(assignment) {
        const availabilityClass = this.getGroupedAvailabilityClass(assignment);
        
        // Sort teachers: available first, then by score
        const sortedTeachers = assignment.teachers
            .sort((a, b) => {
                if (a.is_available !== b.is_available) return b.is_available - a.is_available;
                return (b.score || 0) - (a.score || 0);
            });
        
        // Show max 4 teacher badges
        const visibleTeachers = sortedTeachers.slice(0, 4);
        const remainingCount = sortedTeachers.length - visibleTeachers.length;
        
        return `
            <div class="draggable-assignment grouped-assignment" 
                 draggable="true"
                 data-subject-id="${assignment.id_materia}"
                 data-subject-name="${assignment.materia_nombre}"
                 data-is-grouped="true"
                 data-auto-selectable="${assignment.is_auto_selectable}">
                
                <div class="assignment-header">
                    <div class="assignment-subject">${assignment.materia_nombre}</div>
                </div>
                
                <div class="availability-bar">
                    <div class="availability-fill ${availabilityClass}" 
                         style="width: ${assignment.availability_percentage}%"></div>
                </div>
                
                <div class="teacher-badges">
                    ${visibleTeachers.map(teacher => {
                        const scoreClass = this.getTeacherScoreClass(teacher.score || 0);
                        return `
                            <div class="teacher-badge ${teacher.is_available ? 'available' : 'unavailable'} ${scoreClass}"
                                 draggable="${teacher.is_available ? 'true' : 'false'}"
                                 data-teacher-id="${teacher.id_docente}"
                                 data-teacher-name="${teacher.nombre} ${teacher.apellido}"
                                 data-subject-id="${assignment.id_materia}"
                                 data-subject-name="${assignment.materia_nombre}"
                                 data-score="${teacher.score || 0}"
                                 title="${teacher.nombre} ${teacher.apellido}&#10;Score: ${teacher.score || 0}&#10;Hours: ${teacher.hours_available}/${teacher.hours_total}h">
                                👤 ${teacher.apellido}
                            </div>
                        `;
                    }).join('')}
                    ${remainingCount > 0 ? `
                        <span class="teacher-badge-more" title="${remainingCount} docentes más">
                            +${remainingCount}
                        </span>
                    ` : ''}
                </div>
                
                <div class="assignment-stats">
                    <div class="teacher-count">${assignment.available_teachers}/${assignment.total_teachers} docentes</div>
                    <div class="hours-available">${assignment.total_hours_available} hrs</div>
                </div>
            </div>
        `;
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

    getGroupedAvailabilityClass(assignment) {
        const percentage = assignment.availability_percentage;
        if (percentage >= 80) return 'availability-high';
        if (percentage >= 50) return 'availability-medium';
        if (percentage >= 20) return 'availability-low';
        return 'availability-none';
    }

    getTeacherScoreClass(score) {
        if (score >= 80) return 'score-excellent';
        if (score >= 60) return 'score-good';
        if (score >= 40) return 'score-fair';
        return 'score-low';
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

        // Setup teacher badge drag events
        const teacherBadges = document.querySelectorAll('.teacher-badge[draggable="true"]');
        teacherBadges.forEach(badge => {
            badge.addEventListener('dragstart', (e) => {
                e.stopPropagation(); // Prevent parent card drag
                this.handleBadgeDragStart(e);
            });
            badge.addEventListener('dragend', this.handleDragEnd.bind(this));
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
                // Don't prevent click propagation - let our event delegation handle it
                // button.addEventListener('click', (e) => {
                //     e.stopPropagation();
                // });
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

    handleBadgeDragStart(e) {
        this.draggedElement = e.target;
        this.draggedData = {
            subjectId: e.target.dataset.subjectId,
            teacherId: e.target.dataset.teacherId,
            subjectName: e.target.dataset.subjectName,
            teacherName: e.target.dataset.teacherName,
            isGrouped: false,
            isBadgeDrag: true,
            score: e.target.dataset.score
        };
        
        this.isDragging = true;
        e.target.classList.add('dragging');
        
        // Load availability for this specific teacher
        this.loadAndApplyAvailabilityHighlights(this.draggedData.teacherId).catch(error => {
            console.error('Error loading availability highlights:', error);
        });
        
        this.startScrollSupport();
        
        // Disable onclick on all drop zones during drag
        document.querySelectorAll('.drop-zone').forEach(zone => {
            zone.setAttribute('data-original-onclick', zone.getAttribute('onclick') || '');
            zone.removeAttribute('onclick');
        });
        
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', JSON.stringify(this.draggedData));
        
        this.currentDragData = this.draggedData;
    }

    handleDragStart(e) {
        this.draggedElement = e.target;
        
        // Check if this is a grouped assignment
        const isGrouped = e.target.dataset.isGrouped === 'true';
        
        if (isGrouped) {
            this.draggedData = {
                subjectId: e.target.dataset.subjectId,
                subjectName: e.target.dataset.subjectName,
                isGrouped: true,
                autoSelectable: e.target.dataset.autoSelectable === 'true'
            };
        } else {
            this.draggedData = {
                subjectId: e.target.dataset.subjectId,
                teacherId: e.target.dataset.teacherId,
                subjectName: e.target.dataset.subjectName,
                teacherName: e.target.dataset.teacherName,
                assignmentId: e.target.dataset.assignmentId,
                isGrouped: false
            };
        }
        
        this.isDragging = true;
        e.target.classList.add('dragging');
        
        // For grouped assignments, we'll handle availability highlighting after auto-selection
        if (!isGrouped && this.draggedData.teacherId) {
            // Fetch and apply availability highlighting (async, don't await to avoid blocking drag)
            this.loadAndApplyAvailabilityHighlights(this.draggedData.teacherId).catch(error => {
                console.error('Error loading availability highlights:', error);
            });
        }
        
        // Start scroll support for auto-scroll and wheel scrolling
        this.startScrollSupport();
        
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
        
        // Fetch and apply availability highlighting (async, don't await to avoid blocking drag)
        this.loadAndApplyAvailabilityHighlights(this.draggedData.teacherId).catch(error => {
            console.error('Error loading availability highlights:', error);
        });
        
        // Start scroll support for auto-scroll and wheel scrolling
        this.startScrollSupport();
        
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
        
        // Clear availability highlights
        this.clearAvailabilityHighlights();
        this.currentTeacherAvailability = null;
        
        // Stop scroll support
        this.stopScrollSupport();
        
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
        this.clearAllHighlights();
    }

    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    }

    async handleDragEnter(e) {
        e.preventDefault();
        
        // Safety check: ensure e.target is a DOM element
        if (!e.target || typeof e.target.closest !== 'function') {
            return;
        }
        
        const dropZone = e.target.closest('.drop-zone');
        if (!dropZone) return;

        const isValid = await this.validateDrop(dropZone);
        if (isValid) {
            // Check if this is an existing assignment being moved (has assignmentId without underscore)
            if (this.draggedData && this.draggedData.assignmentId && !this.draggedData.assignmentId.includes('_')) {
                this.setDropZoneHighlight(dropZone, 'drag-over-move');
            } else {
                this.setDropZoneHighlight(dropZone, 'drag-over');
            }
        } else {
            this.setDropZoneHighlight(dropZone, 'drag-over-invalid');
        }
    }

    handleDragLeave(e) {
        // Safety check: ensure e.target is a DOM element
        if (!e.target || typeof e.target.closest !== 'function') {
            return;
        }
        
        const dropZone = e.target.closest('.drop-zone');
        if (!dropZone) return;

        // Check if we're truly leaving the drop zone
        // relatedTarget can be null when dragging fast or during scroll
        const isLeavingZone = !e.relatedTarget || !dropZone.contains(e.relatedTarget);
        
        if (isLeavingZone) {
            // Only clear if this is the currently highlighted zone
            if (this.currentDropZone === dropZone) {
                dropZone.classList.remove('drag-over', 'drag-over-invalid', 'drag-over-move');
                this.currentDropZone = null;
            }
        }
    }

    async handleDrop(e) {
        e.preventDefault();
        
        // Clear all highlights immediately when drop occurs
        this.clearAllHighlights();
        
        const dropZone = e.target.closest('.drop-zone');
        if (!dropZone) {
            return;
        }

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
            if (isOccupied) {
                // Swapping assignments - both cells are occupied
                await this.swapAssignments(dropZone);
            } else {
                // Moving to empty cell
                await this.moveAssignment(dropZone);
            }
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

        // Skip availability check - let the backend handle all validation
        return true;
    }

    async createAssignment(dropZone) {
        // Prevent duplicate operations
        if (!this.startOperation('create')) {
            return;
        }

        const bloque = dropZone.dataset.bloque;
        const dia = dropZone.dataset.dia;

        // Get the dragged data from the current context
        const draggedData = this.draggedData;
        
        if (!draggedData) {
            this.showToast('Error: No se encontraron datos de arrastre', 'error');
            this.endOperation();
            return;
        }

        // Handle grouped assignments with auto-selection
        if (draggedData.isGrouped) {
            await this.createGroupedAssignment(dropZone, draggedData, bloque, dia);
            return;
        }

        this.showToast('Creando asignación...', 'info');

        // Make AJAX call first
        const requestData = {
            action: 'quick_create',
            id_grupo: this.currentGroupId,
            id_materia: draggedData.subjectId,
            id_docente: draggedData.teacherId,
            id_bloque: bloque,
            dia: dia
        };
        
        
        try {
            const response = await fetch('/src/controllers/HorarioHandler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
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
                        // End the current operation before starting the force create
                        this.endOperation();
                        await this.forceCreateAssignment(dropZone, draggedData);
                    } else {
                        // User cancelled, end the operation
                        this.endOperation();
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
            this.endOperation();
        }
    }

    async forceCreateAssignment(dropZone, draggedData) {
        // Prevent duplicate operations
        if (!this.startOperation('create')) {
            return;
        }

        const bloque = dropZone.dataset.bloque;
        const dia = dropZone.dataset.dia;
        
        this.showToast('Creando asignación...', 'info');
        
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
                
                // Only call these once after successful creation
                this.loadAssignments();
                if (typeof filterScheduleGrid === 'function') {
                    filterScheduleGrid(this.currentGroupId);
                }
                
            } else {
                this.showToast('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error forcing assignment:', error);
            this.showToast('Error de conexión al crear asignación', 'error');
        } finally {
            this.endOperation();
        }
    }

    async createGroupedAssignment(dropZone, draggedData, bloque, dia) {
        try {
            // First, auto-select the best teacher
            this.showToast('Seleccionando mejor docente...', 'info');
            
            const autoSelectResponse = await fetch('/src/controllers/HorarioHandler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'auto_select_teacher',
                    id_materia: draggedData.subjectId,
                    id_grupo: this.currentGroupId,
                    id_bloque: bloque,
                    dia: dia
                })
            });

            const autoSelectData = await autoSelectResponse.json();
            
            if (!autoSelectData.success) {
                this.showToast('Error: ' + autoSelectData.message, 'error');
                this.endOperation();
                return;
            }

            const selectedTeacher = autoSelectData.data.selected_teacher;
            this.showToast(`Docente seleccionado: ${selectedTeacher.nombre} ${selectedTeacher.apellido}`, 'success');

            // Now create the assignment with the selected teacher
            this.showToast('Creando asignación...', 'info');
            
            const requestData = {
                action: 'quick_create',
                id_grupo: this.currentGroupId,
                id_materia: draggedData.subjectId,
                id_docente: selectedTeacher.id_docente,
                id_bloque: bloque,
                dia: dia
            };
            
            const response = await fetch('/src/controllers/HorarioHandler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(`Asignación creada: ${draggedData.subjectName} - ${selectedTeacher.nombre} ${selectedTeacher.apellido}`, 'success');
                this.updateDropZone(dropZone, data.data);
                
                // Refresh assignments and schedule grid
                this.loadAssignments();
                if (typeof filterScheduleGrid === 'function') {
                    filterScheduleGrid(this.currentGroupId);
                }
            } else {
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
                            // End the current operation before starting the force create
                            this.endOperation();
                            await this.forceCreateGroupedAssignment(dropZone, draggedData, selectedTeacher, bloque, dia);
                        } else {
                            this.endOperation();
                        }
                    } else {
                        this.showToast('Error: ' + (data.message || 'No se pudo crear la asignación'), 'error');
                        this.endOperation();
                    }
                } else {
                    this.showToast('Error: ' + (data.message || 'No se pudo crear la asignación'), 'error');
                    this.endOperation();
                }
            }
        } catch (error) {
            console.error('Error creating grouped assignment:', error);
            this.showToast('Error de conexión al crear asignación', 'error');
            this.endOperation();
        }
    }

    async forceCreateGroupedAssignment(dropZone, draggedData, selectedTeacher, bloque, dia) {
        // Prevent duplicate operations
        if (!this.startOperation('create')) {
            return;
        }

        this.showToast('Creando asignación...', 'info');
        
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
                    id_docente: selectedTeacher.id_docente,
                    id_bloque: bloque,
                    dia: dia,
                    force_override: true
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(`Asignación creada: ${draggedData.subjectName} - ${selectedTeacher.nombre} ${selectedTeacher.apellido}`, 'success');
                this.updateDropZone(dropZone, data.data);
                
                // Refresh assignments and schedule grid
                this.loadAssignments();
                if (typeof filterScheduleGrid === 'function') {
                    filterScheduleGrid(this.currentGroupId);
                }
            } else {
                this.showToast('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error forcing grouped assignment:', error);
            this.showToast('Error de conexión al crear asignación', 'error');
        } finally {
            this.endOperation();
        }
    }

    async moveAssignment(dropZone) {
        const bloque = dropZone.dataset.bloque;
        const dia = dropZone.dataset.dia;
        
        // Use currentDragData as fallback if draggedData is null
        const draggedData = this.draggedData || this.currentDragData;
        
        
        if (!draggedData) {
            this.showToast('Error: No se encontraron datos de asignación', 'error');
            return;
        }

        try {
            const response = await fetch('/src/controllers/HorarioHandler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'quick_move',
                    id_horario: draggedData.assignmentId,
                    new_bloque: bloque,
                    new_dia: dia
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(`Asignación movida: ${draggedData.subjectName} - ${draggedData.teacherName}`, 'success');
                
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

    async swapAssignments(dropZone) {
        const bloque = dropZone.dataset.bloque;
        const dia = dropZone.dataset.dia;
        
        // Use currentDragData as fallback if draggedData is null
        const draggedData = this.draggedData || this.currentDragData;
        
        if (!draggedData) {
            this.showToast('Error: No se encontraron datos de asignación origen', 'error');
            return;
        }

        // Extract destination assignment data from dropZone
        const destinationElement = dropZone.querySelector('.draggable-existing-assignment');
        if (!destinationElement) {
            this.showToast('Error: No se encontró la asignación destino', 'error');
            return;
        }

        const destinationData = {
            assignmentId: destinationElement.dataset.assignmentId,
            teacherId: destinationElement.dataset.teacherId,
            subjectId: destinationElement.dataset.subjectId,
            subjectName: destinationElement.dataset.subjectName,
            teacherName: destinationElement.dataset.teacherName
        };

        this.showToast('Intercambiando asignaciones...', 'info');

        // Try swap without force override first
        // The backend will handle validation and return appropriate errors
        await this.performSwap(draggedData, destinationData, dropZone, false);
    }

    async performSwap(sourceData, destData, dropZone, forceOverride) {
        // Prevent duplicate operations
        if (!this.startOperation('swap')) {
            return;
        }

        try {
            const response = await fetch('/src/controllers/HorarioHandler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'swap_assignments',
                    id_horario_1: sourceData.assignmentId,
                    id_horario_2: destData.assignmentId,
                    force_override: forceOverride
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(`Asignaciones intercambiadas: ${sourceData.subjectName} ↔ ${destData.subjectName}`, 'success');
                
                // Refresh the entire schedule grid to show the swapped assignments
                if (typeof filterScheduleGrid === 'function') {
                    filterScheduleGrid(this.currentGroupId);
                }
            } else {
                // Check if this is a conflict error and we haven't tried force override yet
                if (!forceOverride && data.message && data.message.includes('Conflicto de disponibilidad')) {
                    const conflictMessage = `Los docentes no están disponibles en los nuevos horarios. ¿Intercambiar de Todas Formas?`;
                    
                    if (typeof confirmConflict === 'function') {
                        const confirmed = await confirmConflict(conflictMessage, {
                            title: 'Conflicto de Disponibilidad',
                            confirmText: 'Intercambiar de Todas Formas',
                            cancelText: 'Cancelar'
                        });
                        
                        if (confirmed) {
                            // Try again with force override
                            this.endOperation(); // End current operation
                            await this.performSwap(sourceData, destData, dropZone, true);
                            return; // Don't end operation here, let the recursive call handle it
                        } else {
                            this.endOperation();
                            return;
                        }
                    } else {
                        this.showToast('Error: ' + data.message, 'error');
                    }
                } else {
                    this.showToast('Error: ' + data.message, 'error');
                }
            }
        } catch (error) {
            console.error('Error performing swap:', error);
            this.showToast('Error de conexión al intercambiar asignaciones', 'error');
        } finally {
            this.endOperation();
        }
    }


    async checkTeacherAvailability(teacherId, bloque, dia) {
        try {
            const response = await fetch(`/src/controllers/HorarioHandler.php?action=check_availability&docente_id=${teacherId}&bloque=${bloque}&dia=${dia}`);
            const data = await response.json();
            return data.success && data.data && data.data.is_available;
        } catch (error) {
            console.error('Error checking teacher availability:', error);
            return false; // Assume not available if check fails
        }
    }

    async loadAndApplyAvailabilityHighlights(teacherId) {
        if (!teacherId) return;
        
        try {
            const response = await fetch(`/src/controllers/HorarioHandler.php?action=get_teacher_availability_grid&docente_id=${teacherId}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                this.currentTeacherAvailability = data.data.availability_grid;
                this.applyAvailabilityHighlights();
            }
        } catch (error) {
            console.error('Error loading teacher availability:', error);
        }
    }

    applyAvailabilityHighlights() {
        const dropZones = document.querySelectorAll('.drop-zone');
        
        dropZones.forEach(zone => {
            const bloque = zone.dataset.bloque;
            const dia = zone.dataset.dia;
            const isOccupied = zone.dataset.occupied === 'true';
            
            // Check availability from fetched grid
            const isAvailable = this.isTeacherAvailableForSlot(dia, bloque);
            
            // Skip occupied cells - don't highlight them
            if (!isOccupied) {
                if (isAvailable) {
                    zone.classList.add('availability-highlight-valid');
                } else {
                    zone.classList.add('availability-highlight-invalid');
                }
            }
        });
    }

    isTeacherAvailableForSlot(dia, bloque) {
        if (!this.currentTeacherAvailability) return true; // default to available
        
        if (!this.currentTeacherAvailability[dia]) return true;
        
        const availability = this.currentTeacherAvailability[dia][bloque];
        return availability !== false; // true or undefined means available
    }

    clearAvailabilityHighlights() {
        const dropZones = document.querySelectorAll('.drop-zone');
        dropZones.forEach(zone => {
            zone.classList.remove('availability-highlight-valid', 'availability-highlight-invalid');
        });
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
            // Don't prevent click propagation - let our event delegation handle it
            // button.addEventListener('click', (e) => {
            //     e.stopPropagation();
            // });
        });
    }

    filterAssignments() {
        const searchInput = document.getElementById('sidebarSearch');
        if (!searchInput) return;
        
        const searchTerm = searchInput.value.toLowerCase().trim();
        const assignments = document.querySelectorAll('.draggable-assignment');
        
        assignments.forEach(assignment => {
            // First apply the current filter
            let shouldShow = true;
            
            switch (this.currentFilter) {
                case 'filterAvailable':
                    shouldShow = assignment.querySelector('.availability-available') !== null;
                    break;
                case 'filterBySubject':
                    shouldShow = true;
                    break;
                case 'filterAll':
                default:
                    shouldShow = true;
            }
            
            // Then apply search filter
            if (shouldShow && searchTerm !== '') {
                const subjectName = (assignment.dataset.subjectName || '').toLowerCase();
                const teacherName = (assignment.dataset.teacherName || '').toLowerCase();
                shouldShow = subjectName.includes(searchTerm) || teacherName.includes(searchTerm);
            }
            
            assignment.style.display = shouldShow ? 'block' : 'none';
        });
    }

    setActiveFilter(filterId) {
        this.currentFilter = filterId;
        this.savePreferences('scheduleAssignmentFilter', filterId);
        
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
        this.filterAssignments();
    }

    applyCurrentFilter() {
        // Apply the current filter and search
        this.filterAssignments();
    }

    showMessage(message) {
        const container = document.getElementById('assignmentsList');
        if (container) {
            container.innerHTML = `<div class="text-center text-gray-500 text-sm py-8">${message}</div>`;
        }
    }

    showToast(message, type = 'info') {
        const now = Date.now();
        
        // Prevent duplicate toasts within debounce period
        if (this.lastToastMessage === message && 
            (now - this.lastToastTime) < this.toastDebounceMs) {
            return;
        }
        
        this.lastToastMessage = message;
        this.lastToastTime = now;
        
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            console.error('Toast system not available:', message);
        }
    }

    // Operation state management
    startOperation(type) {
        if (this.operationInProgress.type) {
            console.warn('Operation already in progress:', this.operationInProgress.type);
            return false;
        }
        this.operationInProgress = { type, startTime: Date.now() };
        return true;
    }

    endOperation() {
        this.operationInProgress = { type: null, startTime: 0 };
    }

    isOperationInProgress() {
        return this.operationInProgress.type !== null;
    }

    // Highlight management methods
    clearAllHighlights() {
        document.querySelectorAll('.drop-zone').forEach(zone => {
            zone.classList.remove('drag-over', 'drag-over-invalid', 'drag-over-move', 'availability-highlight-valid', 'availability-highlight-invalid');
        });
        this.currentDropZone = null;
    }

    setDropZoneHighlight(dropZone, highlightClass) {
        // Clear previous drag-over highlight but preserve availability highlights
        if (this.currentDropZone && this.currentDropZone !== dropZone) {
            this.currentDropZone.classList.remove('drag-over', 'drag-over-invalid', 'drag-over-move');
            // Don't remove availability highlights - they should persist
        }
        
        // Set new highlight
        dropZone.classList.add(highlightClass);
        this.currentDropZone = dropZone;
    }

    // Auto-scroll methods
    setupScrollWhileDragging() {
        // Add wheel event listener for manual scrolling while dragging on the main page
        document.addEventListener('wheel', this.handleWheelWhileDragging.bind(this), { passive: true });
        
        // Add dragover event to detect cursor position for auto-scroll on the main page
        document.addEventListener('dragover', this.handleDragOverForScroll.bind(this));
    }

    handleWheelWhileDragging(e) {
        if (!this.isDragging) return;
        
        // Allow normal wheel scrolling during drag on the main page
        window.scrollBy(0, e.deltaY);
    }

    handleDragOverForScroll(e) {
        if (!this.isDragging) return;
        
        const viewportHeight = window.innerHeight;
        const cursorY = e.clientY;
        
        // Check if cursor is in scroll zones (top/bottom of viewport)
        if (cursorY <= this.scrollZoneHeight) {
            // Near top of viewport - scroll up
            this.startAutoScroll('up', cursorY);
        } else if (cursorY >= viewportHeight - this.scrollZoneHeight) {
            // Near bottom of viewport - scroll down
            this.startAutoScroll('down', viewportHeight - cursorY);
        } else {
            // In middle - stop auto-scroll
            this.stopAutoScroll();
        }
    }

    startAutoScroll(direction, distanceFromEdge) {
        // Stop any existing auto-scroll
        this.stopAutoScroll();
        
        const scrollSpeed = this.calculateScrollSpeed(distanceFromEdge);
        
        this.scrollInterval = requestAnimationFrame(() => {
            this.performAutoScroll(direction, scrollSpeed);
        });
    }

    performAutoScroll(direction, speed) {
        if (!this.isDragging) {
            this.stopAutoScroll();
            return;
        }
        
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        
        let newScroll = currentScroll;
        
        if (direction === 'up') {
            newScroll = Math.max(0, currentScroll - speed);
        } else if (direction === 'down') {
            newScroll = Math.min(maxScroll, currentScroll + speed);
        }
        
        window.scrollTo(0, newScroll);
        
        // Continue scrolling if we haven't reached the limit
        if ((direction === 'up' && newScroll > 0) || (direction === 'down' && newScroll < maxScroll)) {
            this.scrollInterval = requestAnimationFrame(() => {
                this.performAutoScroll(direction, speed);
            });
        }
    }

    stopAutoScroll() {
        if (this.scrollInterval) {
            cancelAnimationFrame(this.scrollInterval);
            this.scrollInterval = null;
        }
    }

    calculateScrollSpeed(distanceFromEdge) {
        // Closer to edge = faster scroll (inverse relationship)
        const normalizedDistance = Math.max(0, Math.min(1, distanceFromEdge / this.scrollZoneHeight));
        const speedMultiplier = 1 - normalizedDistance; // 1 at edge, 0 at zone boundary
        return Math.max(1, this.maxScrollSpeed * speedMultiplier);
    }

    startScrollSupport() {
        this.setupScrollWhileDragging();
    }

    stopScrollSupport() {
        this.stopAutoScroll();
        document.removeEventListener('wheel', this.handleWheelWhileDragging.bind(this));
        document.removeEventListener('dragover', this.handleDragOverForScroll.bind(this));
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
