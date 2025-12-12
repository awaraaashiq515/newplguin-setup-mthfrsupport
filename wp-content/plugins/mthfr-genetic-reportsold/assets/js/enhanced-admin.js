/**
 * MTHFR Enhanced Admin JavaScript
 */

jQuery(document).ready(function ($) {
    'use strict';

    // Initialize admin functionality
    var MTHFR_Admin = {

        init: function () {
            this.bindEvents();
            this.checkEndpointsStatus();
            this.initApiTester();
        },

        bindEvents: function () {
            // Test endpoint buttons
            $(document).on('click', '.test-endpoint', this.testEndpoint);

            // Copy URL buttons
            $(document).on('click', '.copy-url', this.copyUrl);

            // API test form
            $('#api-test-form').on('submit', this.handleApiTest);

            // Clear results button
            $('#clear-results').on('click', this.clearResults);

            // Endpoint selector change
            $('#endpoint-select').on('change', this.handleEndpointChange);

            // Database action buttons
            $('#recreate-tables').on('click', this.recreateTables);
            $('#optimize-tables').on('click', this.optimizeTables);
            $('#check-tables').on('click', this.checkTables);
            $('#reset-plugin-data').on('click', this.resetPluginData);

            // Table management buttons
            $(document).on('click', '.view-table-data', this.viewTableData);
            $(document).on('click', '.truncate-table', this.truncateTable);
            $(document).on('click', '.create-table', this.createTable);

            // Sample data form
            $('#generate-sample-data').on('submit', this.generateSampleData);
            $('#clear-sample-data').on('click', this.clearSampleData);

            // Debug buttons
            $('#download-debug').on('click', this.downloadDebugInfo);
            $('#copy-debug').on('click', this.copyDebugInfo);
        },

        checkEndpointsStatus: function () {
            $('.endpoint-status').each(function () {
                var $status = $(this);
                var endpoint = $status.data('endpoint');

                if (!endpoint) return;

                $.ajax({
                    url: mthfr_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mthfr_get_endpoint_status',
                        endpoint: endpoint,
                        nonce: mthfr_admin.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            $status.removeClass('status-error').addClass('status-ok');
                            $status.html('<span class="dashicons dashicons-yes"></span> ' + response.data.message);
                        } else {
                            $status.removeClass('status-ok').addClass('status-error');
                            $status.html('<span class="dashicons dashicons-no"></span> ' + response.data.message);
                        }
                    },
                    error: function () {
                        $status.removeClass('status-ok').addClass('status-error');
                        $status.html('<span class="dashicons dashicons-no"></span> Error');
                    }
                });
            });
        },

        testEndpoint: function (e) {
            e.preventDefault();

            var $button = $(this);
            var endpoint = $button.data('endpoint');
            var method = $button.data('method');

            $button.prop('disabled', true).text('Testing...');

            $.ajax({
                url: mthfr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mthfr_test_endpoint',
                    endpoint: endpoint,
                    method: method,
                    nonce: mthfr_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        MTHFR_Admin.showToast('Endpoint test completed successfully', 'success');
                        console.log('Endpoint test result:', response.data);
                    } else {
                        MTHFR_Admin.showToast('Endpoint test failed: ' + response.data.message, 'error');
                    }
                },
                error: function () {
                    MTHFR_Admin.showToast('Failed to test endpoint', 'error');
                },
                complete: function () {
                    $button.prop('disabled', false).text('Test');
                }
            });
        },

        copyUrl: function (e) {
            e.preventDefault();

            var url = $(this).data('url');

            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () {
                    MTHFR_Admin.showToast(mthfr_admin.strings.copied, 'success');
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                MTHFR_Admin.showToast(mthfr_admin.strings.copied, 'success');
            }
        },

        initApiTester: function () {
            this.handleEndpointChange();
        },

        handleEndpointChange: function () {
            var selectedEndpoint = $('#endpoint-select').val();
            var isPost = selectedEndpoint === '/generate-report';

            if (isPost) {
                $('#post-params').show();
                // Set default JSON for generate-report
                $('#request-body').val(JSON.stringify({
                    "upload_id": 1,
                    "order_id": 123,
                    "product_name": "Test Product",
                    "has_subscription": false
                }, null, 2));
            } else {
                $('#post-params').hide();
                $('#request-body').val('');
            }
        },

        handleApiTest: function (e) {
            e.preventDefault();

            var endpoint = $('#endpoint-select').val();
            var body = $('#request-body').val();
            var method = endpoint === '/generate-report' ? 'POST' : 'GET';

            $('#api-test-results').html('Testing endpoint...\n\nURL: ' + mthfr_admin.rest_url + endpoint.substring(1) + '\nMethod: ' + method);

            var requestData = {
                action: 'mthfr_test_endpoint',
                endpoint: endpoint,
                method: method,
                nonce: mthfr_admin.nonce
            };

            if (method === 'POST' && body.trim()) {
                try {
                    JSON.parse(body); // Validate JSON
                    requestData.body = body;
                } catch (e) {
                    $('#api-test-results').html('ERROR: Invalid JSON in request body\n\n' + e.message);
                    return;
                }
            }

            $.ajax({
                url: mthfr_admin.ajax_url,
                type: 'POST',
                data: requestData,
                success: function (response) {
                    MTHFR_Admin.displayApiTestResult(response);
                },
                error: function (xhr, status, error) {
                    $('#api-test-results').html('AJAX ERROR:\n' + status + ': ' + error);
                }
            });
        },

        displayApiTestResult: function (response) {
            var output = '';

            if (response.success) {
                output += 'SUCCESS\n';
                output += '====================\n\n';
                output += 'URL: ' + response.data.url + '\n';
                output += 'Method: ' + response.data.method + '\n';
                output += 'Response Code: ' + response.data.response_code + '\n\n';
                output += 'Response Body:\n';
                output += JSON.stringify(response.data.response_body, null, 2);

                if (response.data.raw_response) {
                    output += '\n\nRaw Response:\n';
                    output += response.data.raw_response;
                }
            } else {
                output += 'ERROR\n';
                output += '====================\n\n';
                output += 'Message: ' + response.data.message + '\n';

                if (response.data.url) {
                    output += 'URL: ' + response.data.url + '\n';
                }

                if (response.data.method) {
                    output += 'Method: ' + response.data.method + '\n';
                }
            }

            $('#api-test-results').html(output);
        },

        clearResults: function (e) {
            e.preventDefault();
            $('#api-test-results').html('Select an endpoint and click "Test Endpoint" to see results here.');
        },

        // Database Tools Functions
        recreateTables: function (e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to recreate all tables? This will delete all existing data.')) {
                return;
            }

            MTHFR_Admin.executeDbAction('recreate_tables', 'Recreating database tables...');
        },

        optimizeTables: function (e) {
            e.preventDefault();
            MTHFR_Admin.executeDbAction('optimize_tables', 'Optimizing database tables...');
        },

        checkTables: function (e) {
            e.preventDefault();
            MTHFR_Admin.executeDbAction('check_tables', 'Checking table integrity...');
        },

        resetPluginData: function (e) {
            e.preventDefault();

            if (!confirm(mthfr_admin.strings.confirm_reset)) {
                return;
            }

            MTHFR_Admin.executeDbAction('reset_plugin_data', 'Resetting all plugin data...');
        },

        executeDbAction: function (action, loadingText) {
            var $results = $('#database-action-results');
            $results.removeClass('success error').addClass('loading').show();
            $results.html(loadingText);

            $.ajax({
                url: mthfr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mthfr_db_action',
                    db_action: action,
                    nonce: mthfr_admin.nonce
                },
                success: function (response) {
                    $results.removeClass('loading');

                    if (response.success) {
                        $results.addClass('success').html('✓ ' + response.data.message);
                        MTHFR_Admin.showToast('Database action completed successfully', 'success');

                        // Reload page after certain actions
                        if (action === 'recreate_tables' || action === 'reset_plugin_data') {
                            setTimeout(function () {
                                location.reload();
                            }, 2000);
                        }
                    } else {
                        $results.addClass('error').html('✗ ' + response.data.message);
                        MTHFR_Admin.showToast('Database action failed', 'error');
                    }
                },
                error: function () {
                    $results.removeClass('loading').addClass('error');
                    $results.html('✗ Failed to execute database action');
                    MTHFR_Admin.showToast('Database action failed', 'error');
                }
            });
        },

        viewTableData: function (e) {
            e.preventDefault();

            var table = $(this).data('table');
            var $button = $(this);

            $button.prop('disabled', true).text('Loading...');

            $.ajax({
                url: mthfr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mthfr_view_table_data',
                    table: table,
                    nonce: mthfr_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        MTHFR_Admin.showTableDataModal(table, response.data);
                    } else {
                        MTHFR_Admin.showToast('Failed to load table data: ' + response.data.message, 'error');
                    }
                },
                error: function () {
                    MTHFR_Admin.showToast('Failed to load table data', 'error');
                },
                complete: function () {
                    $button.prop('disabled', false).text('View Data');
                }
            });
        },

        truncateTable: function (e) {
            e.preventDefault();

            var table = $(this).data('table');

            if (!confirm(mthfr_admin.strings.confirm_truncate)) {
                return;
            }

            var $button = $(this);
            $button.prop('disabled', true).text('Truncating...');

            $.ajax({
                url: mthfr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mthfr_truncate_table',
                    table: table,
                    nonce: mthfr_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        MTHFR_Admin.showToast('Table truncated successfully', 'success');
                        location.reload();
                    } else {
                        MTHFR_Admin.showToast('Failed to truncate table: ' + response.data.message, 'error');
                    }
                },
                error: function () {
                    MTHFR_Admin.showToast('Failed to truncate table', 'error');
                },
                complete: function () {
                    $button.prop('disabled', false).text('Truncate');
                }
            });
        },

        createTable: function (e) {
            e.preventDefault();

            var table = $(this).data('table');
            var $button = $(this);

            $button.prop('disabled', true).text('Creating...');

            $.ajax({
                url: mthfr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mthfr_create_table',
                    table: table,
                    nonce: mthfr_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        MTHFR_Admin.showToast('Table created successfully', 'success');
                        location.reload();
                    } else {
                        MTHFR_Admin.showToast('Failed to create table: ' + response.data.message, 'error');
                    }
                },
                error: function () {
                    MTHFR_Admin.showToast('Failed to create table', 'error');
                },
                complete: function () {
                    $button.prop('disabled', false).text('Create Table');
                }
            });
        },

        generateSampleData: function (e) {
            e.preventDefault();

            var uploads = $('#sample-uploads').val();
            var reports = $('#sample-reports').val();

            $.ajax({
                url: mthfr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mthfr_generate_sample_data',
                    uploads: uploads,
                    reports: reports,
                    nonce: mthfr_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        MTHFR_Admin.showToast('Sample data generated successfully', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        MTHFR_Admin.showToast('Failed to generate sample data: ' + response.data.message, 'error');
                    }
                },
                error: function () {
                    MTHFR_Admin.showToast('Failed to generate sample data', 'error');
                }
            });
        },

        clearSampleData: function (e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to clear all sample data?')) {
                return;
            }

            $.ajax({
                url: mthfr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mthfr_clear_sample_data',
                    nonce: mthfr_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        MTHFR_Admin.showToast('Sample data cleared successfully', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        MTHFR_Admin.showToast('Failed to clear sample data: ' + response.data.message, 'error');
                    }
                },
                error: function () {
                    MTHFR_Admin.showToast('Failed to clear sample data', 'error');
                }
            });
        },

        downloadDebugInfo: function (e) {
            e.preventDefault();

            var debugText = $('#debug-output pre').text();
            var blob = new Blob([debugText], { type: 'application/json' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');

            a.href = url;
            a.download = 'mthfr-debug-info-' + new Date().toISOString().split('T')[0] + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            MTHFR_Admin.showToast('Debug info downloaded', 'success');
        },

        copyDebugInfo: function (e) {
            e.preventDefault();

            var debugText = $('#debug-output pre').text();

            if (navigator.clipboard) {
                navigator.clipboard.writeText(debugText).then(function () {
                    MTHFR_Admin.showToast('Debug info copied to clipboard', 'success');
                });
            } else {
                // Fallback
                var textArea = document.createElement('textarea');
                textArea.value = debugText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                MTHFR_Admin.showToast('Debug info copied to clipboard', 'success');
            }
        },

        showTableDataModal: function (tableName, data) {
            var modalHtml = '<div id="mthfr-table-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999; display: flex; align-items: center; justify-content: center;">';
            modalHtml += '<div style="background: white; padding: 20px; border-radius: 8px; max-width: 90%; max-height: 90%; overflow: auto;">';
            modalHtml += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
            modalHtml += '<h2>Table Data: ' + tableName + '</h2>';
            modalHtml += '<button id="close-table-modal" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>';
            modalHtml += '</div>';

            if (data.length > 0) {
                modalHtml += '<table class="wp-list-table widefat fixed striped">';
                modalHtml += '<thead><tr>';

                // Table headers
                var keys = Object.keys(data[0]);
                keys.forEach(function (key) {
                    modalHtml += '<th>' + key + '</th>';
                });

                modalHtml += '</tr></thead><tbody>';

                // Table rows
                data.forEach(function (row) {
                    modalHtml += '<tr>';
                    keys.forEach(function (key) {
                        var value = row[key];
                        if (value === null) value = '<em>NULL</em>';
                        if (typeof value === 'object') value = JSON.stringify(value);
                        modalHtml += '<td>' + value + '</td>';
                    });
                    modalHtml += '</tr>';
                });

                modalHtml += '</tbody></table>';
            } else {
                modalHtml += '<p>No data found in this table.</p>';
            }

            modalHtml += '</div></div>';

            $('body').append(modalHtml);

            $('#close-table-modal, #mthfr-table-modal').on('click', function (e) {
                if (e.target === this) {
                    $('#mthfr-table-modal').remove();
                }
            });
        },

        showToast: function (message, type) {
            type = type || 'info';

            var toast = $('<div class="mthfr-toast ' + type + '">' + message + '</div>');
            $('body').append(toast);

            setTimeout(function () {
                toast.addClass('show');
            }, 100);

            setTimeout(function () {
                toast.removeClass('show');
                setTimeout(function () {
                    toast.remove();
                }, 300);
            }, 3000);
        }
    };

    // Initialize the admin interface
    MTHFR_Admin.init();

    // Global access for debugging
    window.MTHFR_Admin = MTHFR_Admin;
});