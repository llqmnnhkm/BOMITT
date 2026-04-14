// admin/admin_includes/js/admin_drag_drop_handler.js
// Drag and Drop functionality - FIXED VERSION

let draggedElement = null;

console.log('Drag-Drop Handler Loaded');

// Initialize drag-and-drop on page load
document.addEventListener('DOMContentLoaded', function() {
    enableDragDrop();
});

// Enable drag-and-drop for all table rows
function enableDragDrop() {
    const tables = document.querySelectorAll('.equipment-table tbody');
    
    tables.forEach(tbody => {
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach(row => {
            row.setAttribute('draggable', 'true');
            row.classList.add('draggable-row');
            
            // Add drag handle icon
            const firstCell = row.querySelector('td:first-child');
            if (firstCell && !firstCell.querySelector('.drag-handle')) {
                const dragHandle = document.createElement('span');
                dragHandle.className = 'drag-handle';
                dragHandle.innerHTML = '⋮⋮';
                dragHandle.title = 'Drag to reorder';
                firstCell.insertBefore(dragHandle, firstCell.firstChild);
            }
            
            row.addEventListener('dragstart', handleDragStart);
            row.addEventListener('dragover', handleDragOver);
            row.addEventListener('drop', handleDrop);
            row.addEventListener('dragend', handleDragEnd);
            row.addEventListener('dragenter', handleDragEnter);
            row.addEventListener('dragleave', handleDragLeave);
        });
    });
}

function handleDragStart(e) {
    draggedElement = this;
    this.classList.add('dragging');
    
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
    
    setTimeout(() => {
        this.style.opacity = '0.4';
    }, 0);
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDragEnter(e) {
    if (this !== draggedElement) {
        this.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    
    if (draggedElement !== this) {
        const tbody = this.closest('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const draggedIndex = rows.indexOf(draggedElement);
        const targetIndex = rows.indexOf(this);
        
        if (draggedIndex < targetIndex) {
            this.parentNode.insertBefore(draggedElement, this.nextSibling);
        } else {
            this.parentNode.insertBefore(draggedElement, this);
        }
        
        saveNewOrder(tbody);
    }
    
    this.classList.remove('drag-over');
    return false;
}

function handleDragEnd(e) {
    this.style.opacity = '1';
    this.classList.remove('dragging');
    
    document.querySelectorAll('.drag-over').forEach(el => {
        el.classList.remove('drag-over');
    });
    
    draggedElement = null;
}

// Get row ID - FIXED to use same method as arrow buttons
function getRowId(row) {
    const deleteBtn = row.querySelector('.btn-delete');
    if (deleteBtn) {
        const onclick = deleteBtn.getAttribute('onclick');
        const match = onclick.match(/\((\d+)\)/);
        if (match) {
            return match[1];
        }
    }
    return null;
}

// Get table type
function getTableType(tbody) {
    if (tbody.id === 'equipment-tbody') return 'equipment';
    if (tbody.closest('#tab-cables')) return 'cable';
    if (tbody.closest('#tab-config')) return 'config';
    return 'unknown';
}

// Save order - FIXED to use same backend as arrow buttons
async function saveNewOrder(tbody) {
    showSaveIndicator('Saving...', 'info');
    
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const type = getTableType(tbody);
    
    const orderData = [];
    rows.forEach((row, index) => {
        const id = getRowId(row);
        if (id) {
            orderData.push({
                id: parseInt(id),
                display_order: index + 1
            });
        }
    });
    
    console.log('💾 Saving:', { type, count: orderData.length });
    
    try {
        const response = await fetch('admin_update_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: type, order: orderData })
        });
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Response:', text);
            throw new Error('Invalid server response');
        }
        
        if (result.success) {
            showSaveIndicator('✅ Order saved!', 'success');
            setTimeout(hideSaveIndicator, 2000);
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Error:', error);
        showSaveIndicator('❌ Error: ' + error.message, 'error');
        setTimeout(hideSaveIndicator, 3000);
    }
}

// Show save indicator
function showSaveIndicator(message, type = 'info') {
    let indicator = document.getElementById('drag-save-indicator');
    
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'drag-save-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            font-family: Montserrat, sans-serif;
        `;
        document.body.appendChild(indicator);
    }
    
    const colors = {
        info: { bg: '#0070ef', color: 'white' },
        success: { bg: '#28a745', color: 'white' },
        error: { bg: '#dc3545', color: 'white' }
    };
    
    const style = colors[type] || colors.info;
    indicator.style.background = style.bg;
    indicator.style.color = style.color;
    indicator.textContent = message;
    indicator.style.display = 'block';
}

function hideSaveIndicator() {
    const indicator = document.getElementById('drag-save-indicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

// CSS Styles
const style = document.createElement('style');
style.textContent = `
    .draggable-row {
        cursor: move;
        transition: all 0.3s ease;
    }
    
    .draggable-row:hover {
        background: #f8f9fa !important;
    }
    
    .draggable-row.dragging {
        opacity: 0.4;
        background: #e3f2fd !important;
    }
    
    .draggable-row.drag-over {
        border-top: 3px solid #0070ef;
        background: #e3f2fd !important;
    }
    
    .drag-handle {
        display: inline-block;
        color: #999;
        font-size: 1.2rem;
        margin-right: 8px;
        cursor: move;
        user-select: none;
        line-height: 1;
        vertical-align: middle;
    }
    
    .drag-handle:hover {
        color: #0070ef;
    }
    
    .draggable-row:hover .drag-handle {
        color: #0070ef;
    }
`;
document.head.appendChild(style);

console.log('✅ Drag-Drop Handler Ready');