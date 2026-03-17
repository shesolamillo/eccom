// public/js/admin.js

// Initialize DataTables with custom settings for admin
$(document).ready(function() {
    // Initialize admin tables
    $('.admin-table.data-table').DataTable({
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
    
    // Initialize charts
    initializeCharts();
});

// Chart initialization
function initializeCharts() {
    // Find all chart canvases and initialize them
    const chartCanvases = document.querySelectorAll('canvas');
    chartCanvases.forEach(canvas => {
        if (!canvas.chart) {
            const ctx = canvas.getContext('2d');
            const chartType = canvas.dataset.chartType || 'line';
            const chartData = JSON.parse(canvas.dataset.chartData || '{}');
            
            new Chart(ctx, {
                type: chartType,
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    });
}

// Form validation for admin forms
function validateAdminForm(formId) {
    const form = document.getElementById(formId);
    const isValid = form.checkValidity();
    
    if (!isValid) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    form.classList.add('was-validated');
    return isValid;
}

// Confirmation for critical actions
function confirmAction(action, message = 'Are you sure?') {
    return confirm(message);
}

// Toggle user status
function toggleUserStatus(userId, currentStatus) {
    if (confirm(`Are you sure you want to ${currentStatus ? 'deactivate' : 'activate'} this user?`)) {
        // AJAX call to update user status
        fetch(`/admin/user/${userId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating user status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating user status');
        });
    }
}

// Export data function
function exportData(format) {
    const table = $('.data-table').DataTable();
    
    switch(format) {
        case 'csv':
            table.button('.buttons-csv').trigger();
            break;
        case 'excel':
            table.button('.buttons-excel').trigger();
            break;
        case 'pdf':
            table.button('.buttons-pdf').trigger();
            break;
        case 'print':
            table.button('.buttons-print').trigger();
            break;
    }
}

// Search function for tables
function searchTable(tableId, searchTerm) {
    const table = $(`#${tableId}`).DataTable();
    table.search(searchTerm).draw();
}

// Reset filters
function resetFilters() {
    $('.data-table').DataTable().search('').columns().search('').draw();
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Format currency for display
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

// Initialize tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});