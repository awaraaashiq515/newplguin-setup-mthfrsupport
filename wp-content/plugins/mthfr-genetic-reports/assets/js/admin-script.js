jQuery(document).ready(function($) {
    
    // Show results div
    function showResults(title, content) {
        $('#mthfr-results').show();
        $('#mthfr-results h2').text(title);
        $('#results-content').text(JSON.stringify(content, null, 2));
    }
    
    // Make API request
    function makeApiRequest(endpoint, method = 'GET', data = null) {
        const baseUrl = window.location.origin + '/wp-json/mthfr/v1/';
        const url = baseUrl + endpoint;
        
        const requestOptions = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': $('meta[name="wp-nonce"]').attr('content') || ''
            }
        };
        
        if (data && method === 'POST') {
            requestOptions.body = JSON.stringify(data);
        }
        
        return fetch(url, requestOptions)
            .then(response => response.json())
            .catch(error => {
                console.error('API request failed:', error);
                return { error: 'Request failed: ' + error.message };
            });
    }
    
    // Test Health Check
    $('#test-health').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('Testing...');
        
        makeApiRequest('health')
            .then(response => {
                showResults('Health Check Results', response);
                button.prop('disabled', false).text('Test Health Check');
            });
    });
    
    // Test Database
    $('#test-database').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('Testing...');
        
        makeApiRequest('test-db')
            .then(response => {
                showResults('Database Test Results', response);
                button.prop('disabled', false).text('Test Database');
            });
    });
    
    // Test PDF Generation
    $('#test-pdf').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('Testing...');
        
        makeApiRequest('test-pdf')
            .then(response => {
                showResults('PDF Test Results', response);
                button.prop('disabled', false).text('Test PDF Generation');
            });
    });
    
    // Generate Sample Report
    $('#generate-sample').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('Generating...');
        
        const sampleData = {
            upload_id: 999,
            order_id: 888,
            product_name: 'Sample COVID Report',
            has_subscription: true
        };
        
        makeApiRequest('generate-report', 'POST', sampleData)
            .then(response => {
                showResults('Sample Report Generation Results', response);
                button.prop('disabled', false).text('Generate Sample Report');
                
                // Refresh the page to show new report in the list
                if (response.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            });
    });
    
    // Test individual endpoints
    $('.test-endpoint').on('click', function() {
        const button = $(this);
        const endpoint = button.data('endpoint');
        
        button.prop('disabled', true).text('Testing...');
        
        makeApiRequest(endpoint)
            .then(response => {
                showResults(endpoint.charAt(0).toUpperCase() + endpoint.slice(1) + ' Results', response);
                button.prop('disabled', false).text('Test');
            });
    });
    
    // Generate Report Form
    $('#generate-report-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            upload_id: parseInt(formData.get('upload_id')),
            order_id: parseInt(formData.get('order_id')),
            product_name: formData.get('product_name'),
            has_subscription: formData.get('has_subscription') === '1'
        };
        
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Generating Report...');
        
        makeApiRequest('generate-report', 'POST', data)
            .then(response => {
                showResults('Report Generation Results', response);
                submitButton.prop('disabled', false).text('Generate Report');
                
                // Show success/error message
                if (response.success) {
                    $('<div class="notice notice-success"><p>Report generated successfully!</p></div>')
                        .insertAfter('#generate-report-form')
                        .delay(3000)
                        .fadeOut();
                    
                    // Refresh the page to show new report
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    $('<div class="notice notice-error"><p>Report generation failed: ' + (response.error || 'Unknown error') + '</p></div>')
                        .insertAfter('#generate-report-form')
                        .delay(5000)
                        .fadeOut();
                }
            });
    });
    
    // Generate sample data
    $('#generate-sample-data').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        button.text('Generating...');
        
        // Generate multiple sample reports
        const sampleReports = [
            { upload_id: 1001, order_id: 2001, product_name: 'COVID Report' },
            { upload_id: 1002, order_id: 2002, product_name: 'Methylation Report' },
            { upload_id: 1003, order_id: 2003, product_name: 'Variant Report' }
        ];
        
        let completedCount = 0;
        
        sampleReports.forEach((reportData, index) => {
            setTimeout(() => {
                makeApiRequest('generate-report', 'POST', reportData)
                    .then(response => {
                        completedCount++;
                        if (completedCount === sampleReports.length) {
                            button.text('Generate sample data');
                            location.reload();
                        }
                    });
            }, index * 1000); // Stagger requests
        });
    });
    
    // Copy API endpoint URLs to clipboard
    $('.api-endpoints code').on('click', function() {
        const text = $(this).text();
        
        // Create temporary textarea to copy text
        const textarea = $('<textarea>');
        $('body').append(textarea);
        textarea.val(text).select();
        
        try {
            document.execCommand('copy');
            
            // Show feedback
            const originalBg = $(this).css('background-color');
            $(this).css('background-color', '#d1e7dd');
            setTimeout(() => {
                $(this).css('background-color', originalBg);
            }, 500);
            
        } catch (err) {
            console.error('Failed to copy text: ', err);
        }
        
        textarea.remove();
    });
    
    // Auto-refresh report status (optional)
    function refreshReportStatus() {
        $('.status-pending').closest('tr').each(function() {
            const row = $(this);
            const reportId = row.find('td:first').text();
            
            // You could add an API endpoint to check individual report status
            // and update the UI accordingly
        });
    }
    
    // Refresh status every 30 seconds
    setInterval(refreshReportStatus, 30000);
    
    // Add tooltip functionality
    $('[title]').each(function() {
        const tooltip = $(this).attr('title');
        $(this).removeAttr('title').on('mouseenter', function() {
            const tooltipDiv = $('<div class="mthfr-tooltip">' + tooltip + '</div>');
            $('body').append(tooltipDiv);
            
            const offset = $(this).offset();
            tooltipDiv.css({
                position: 'absolute',
                top: offset.top - tooltipDiv.outerHeight() - 5,
                left: offset.left,
                background: '#333',
                color: '#fff',
                padding: '5px 10px',
                borderRadius: '3px',
                fontSize: '12px',
                zIndex: 1000
            });
        }).on('mouseleave', function() {
            $('.mthfr-tooltip').remove();
        });
    });
    
    // Format JSON in results
    function formatJsonInResults() {
        $('#results-content').each(function() {
            const content = $(this).text();
            try {
                const parsed = JSON.parse(content);
                $(this).html('<pre>' + JSON.stringify(parsed, null, 2) + '</pre>');
            } catch (e) {
                // Not valid JSON, leave as is
            }
        });
    }
    
    // Add search functionality for reports table
    if ($('.wp-list-table').length > 0) {
        const searchHtml = '<div class="tablenav top"><div class="alignleft actions">' +
            '<input type="search" id="report-search" placeholder="Search reports..." style="margin-right: 10px;">' +
            '<button type="button" id="search-reports" class="button">Search</button>' +
            '<button type="button" id="clear-search" class="button">Clear</button>' +
            '</div></div>';
        
        $('.wp-list-table').before(searchHtml);
        
        $('#search-reports').on('click', function() {
            const searchTerm = $('#report-search').val().toLowerCase();
            $('.wp-list-table tbody tr').each(function() {
                const rowText = $(this).text().toLowerCase();
                if (rowText.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        $('#clear-search').on('click', function() {
            $('#report-search').val('');
            $('.wp-list-table tbody tr').show();
        });
        
        // Search on Enter key
        $('#report-search').on('keypress', function(e) {
            if (e.which === 13) {
                $('#search-reports').click();
            }
        });
    }
    
});