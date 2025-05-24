/**
 * assets/js/scripts.js
 * Main JavaScript file for Cafe Management System
 */

// Enable Bootstrap tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-close alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

/**
 * Format currency (PKR)
 * @param {number} value - The number to format
 * @returns {string} Formatted string in PKR
 */
function formatCurrency(value) {
    return 'Rs. ' + parseFloat(value).toFixed(2);
}

/**
 * Create a confirmation dialog
 * @param {string} message - The confirmation message
 * @param {function} callback - The callback function to execute if confirmed
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Handle AJAX form submissions
 * @param {string} formId - The ID of the form
 * @param {string} url - The URL to submit to
 * @param {string} method - The HTTP method (GET, POST, etc.)
 * @param {function} successCallback - The callback function on success
 * @param {function} errorCallback - The callback function on error
 */
function submitFormAjax(formId, url, method, successCallback, errorCallback) {
    document.getElementById(formId).addEventListener('submit', function(e) {
        e.preventDefault();
        
        var form = this;
        var formData = new FormData(form);
        
        // Show loading state
        var submitButton = form.querySelector('button[type="submit"]');
        var originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...';
        submitButton.disabled = true;
        
        fetch(url, {
            method: method,
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Reset form state
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
            
            // Call success callback
            if (typeof successCallback === 'function') {
                successCallback(data);
            }
        })
        .catch(error => {
            // Reset form state
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
            
            // Call error callback
            if (typeof errorCallback === 'function') {
                errorCallback(error);
            } else {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    });
}

/**
 * Create a chart using Chart.js
 * @param {string} elementId - The ID of the canvas element
 * @param {string} type - The chart type (bar, line, pie, etc.)
 * @param {object} data - The chart data
 * @param {object} options - The chart options
 * @returns {Chart} The created chart instance
 */
function createChart(elementId, type, data, options) {
    var ctx = document.getElementById(elementId).getContext('2d');
    return new Chart(ctx, {
        type: type,
        data: data,
        options: options
    });
}

/**
 * Dynamic search filter for tables
 * @param {string} inputId - The ID of the input element
 * @param {string} tableId - The ID of the table element
 */
function setupTableSearch(inputId, tableId) {
    document.getElementById(inputId).addEventListener('keyup', function() {
        var searchTerm = this.value.toLowerCase();
        var table = document.getElementById(tableId);
        var rows = table.getElementsByTagName('tr');
        
        // Skip the header row (start at index 1)
        for (var i = 1; i < rows.length; i++) {
            var row = rows[i];
            var cells = row.getElementsByTagName('td');
            var foundMatch = false;
            
            for (var j = 0; j < cells.length; j++) {
                var cell = cells[j];
                if (cell) {
                    var content = cell.textContent || cell.innerText;
                    if (content.toLowerCase().indexOf(searchTerm) > -1) {
                        foundMatch = true;
                        break;
                    }
                }
            }
            
            row.style.display = foundMatch ? '' : 'none';
        }
    });
}