<?php
/**
 * Enhanced Debug Page Template
 * File: templates/debug-page-enhanced.php
 */

// Get comprehensive debug info
$debug_info = MTHFR_Debug_System::get_comprehensive_debug_info();
$endpoint_test_results = get_transient('mthfr_endpoint_test_results');
?>

<div class="wrap mthfr-debug-page">
    <h1>MTHFR Plugin Debug Information</h1>
    
    <!-- Action Buttons -->
    <div class="mthfr-debug-actions">
        <a href="<?php echo add_query_arg('mthfr_action', 'force_register_routes'); ?>" class="button button-secondary">
            Force Register Routes
        </a>
        <a href="<?php echo add_query_arg('mthfr_action', 'test_all_endpoints'); ?>" class="button button-secondary">
            Test All Endpoints
        </a>
        <button id="refresh-debug" class="button button-primary">Refresh Debug Info</button>
        <button id="download-report" class="button button-secondary">Download Diagnostic Report</button>
    </div>

    <!-- Status Overview -->
    <div class="mthfr-status-overview">
        <h2>System Status Overview</h2>
        <div class="status-cards">
            <div class="status-card <?php echo $debug_info['plugin_status']['classes_loaded']['MTHFR_API_Endpoints'] ? 'status-good' : 'status-error'; ?>">
                <h3>Plugin Core</h3>
                <p class="status-value">
                    <?php echo count(array_filter($debug_info['plugin_status']['classes_loaded'])); ?>/<?php echo count($debug_info['plugin_status']['classes_loaded']); ?>
                </p>
                <p class="status-label">Classes Loaded</p>
            </div>
            
            <div class="status-card <?php echo $debug_info['api_status']['namespace_registered'] ? 'status-good' : 'status-error'; ?>">
                <h3>API Status</h3>
                <p class="status-value"><?php echo count($debug_info['api_status']['registered_routes']); ?></p>
                <p class="status-label">Routes Registered</p>
            </div>
            
            <div class="status-card <?php echo $debug_info['database_status']['connection'] ? 'status-good' : 'status-error'; ?>">
                <h3>Database</h3>
                <p class="status-value">
                    <?php 
                    $tables_exist = array_filter($debug_info['database_status']['tables'], function($t) { return $t['exists']; });
                    echo count($tables_exist) . '/' . count($debug_info['database_status']['tables']);
                    ?>
                </p>
                <p class="status-label">Tables Ready</p>
            </div>
            
            <div class="status-card <?php echo count(array_filter($debug_info['file_system']['files'], function($f) { return $f['exists']; })) === count($debug_info['file_system']['files']) ? 'status-good' : 'status-warning'; ?>">
                <h3>File System</h3>
                <p class="status-value">
                    <?php 
                    $files_exist = array_filter($debug_info['file_system']['files'], function($f) { return $f['exists']; });
                    echo count($files_exist) . '/' . count($debug_info['file_system']['files']);
                    ?>
                </p>
                <p class="status-label">Files Present</p>
            </div>
        </div>
    </div>

    <!-- Test Results -->
    <div class="mthfr-test-results">
        <h2>Automated Test Results</h2>
        <div class="test-results-grid">
            <?php foreach ($debug_info['test_results'] as $test_key => $test): ?>
            <div class="test-result-card status-<?php echo $test['status']; ?>">
                <h3><?php echo esc_html($test['name']); ?></h3>
                <div class="test-score">
                    <span class="score"><?php echo $test['score']; ?>/<?php echo $test['max_score']; ?></span>
                    <span class="status-badge status-<?php echo $test['status']; ?>"><?php echo strtoupper($test['status']); ?></span>
                </div>
                <div class="test-details">
                    <?php foreach ($test['details'] as $key => $value): ?>
                    <div class="detail-item">
                        <span class="detail-key"><?php echo esc_html($key); ?>:</span>
                        <span class="detail-value <?php echo $value === 'success' || $value === 'loaded' || $value === 'exists' || $value === 'registered' ? 'value-good' : ($value === 'error' || $value === 'missing' ? 'value-error' : 'value-warning'); ?>">
                            <?php echo esc_html($value); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- API Endpoints Testing -->
    <div class="mthfr-api-testing">
        <h2>API Endpoints Testing</h2>
        <div class="endpoints-grid">
            <?php foreach ($debug_info['available_endpoints'] as $route => $endpoint): ?>
            <div class="endpoint-card">
                <div class="endpoint-header">
                    <h3><?php echo esc_html($route); ?></h3>
                    <span class="method-badge method-<?php echo strtolower($endpoint['method']); ?>">
                        <?php echo $endpoint['method']; ?>
                    </span>
                </div>
                
                <p class="endpoint-description"><?php echo esc_html($endpoint['description']); ?></p>
                
                <div class="endpoint-status">
                    <?php if ($endpoint['status']['available']): ?>
                        <span class="status-indicator status-good">✓ Available</span>
                        <span class="status-code">Status: <?php echo $endpoint['status']['status_code']; ?></span>
                    <?php else: ?>
                        <span class="status-indicator status-error">✗ Unavailable</span>
                        <?php if (isset($endpoint['status']['error'])): ?>
                            <span class="error-message"><?php echo esc_html($endpoint['status']['error']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="endpoint-actions">
                    <button class="button button-small test-endpoint" 
                            data-url="<?php echo esc_attr($endpoint['test_url']); ?>" 
                            data-method="<?php echo esc_attr($endpoint['method']); ?>">
                        Test Endpoint
                    </button>
                    <a href="<?php echo esc_url($endpoint['test_url']); ?>" target="_blank" class="button button-small">
                        Open in Browser
                    </a>
                </div>
                
                <div class="test-result" style="display: none;"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Detailed Information Tabs -->
    <div class="mthfr-detailed-info">
        <h2>Detailed Information</h2>
        
        <div class="nav-tab-wrapper">
            <a href="#plugin-info" class="nav-tab nav-tab-active">Plugin Info</a>
            <a href="#database-info" class="nav-tab">Database</a>
            <a href="#filesystem-info" class="nav-tab">File System</a>
            <a href="#environment-info" class="nav-tab">Environment</a>
            <a href="#recent-errors" class="nav-tab">Recent Errors</a>
            <a href="#raw-debug" class="nav-tab">Raw Debug Data</a>
        </div>

        <!-- Plugin Info Tab -->
        <div id="plugin-info" class="tab-content active">
            <h3>Plugin Information</h3>
            <table class="widefat">
                <tr><td><strong>Version:</strong></td><td><?php echo esc_html($debug_info['plugin_status']['version']); ?></td></tr>
                <tr><td><strong>Path:</strong></td><td><?php echo esc_html($debug_info['plugin_status']['path']); ?></td></tr>
                <tr><td><strong>URL:</strong></td><td><?php echo esc_html($debug_info['plugin_status']['url']); ?></td></tr>
            </table>
            
            <h4>Loaded Classes</h4>
            <table class="widefat">
                <?php foreach ($debug_info['plugin_status']['classes_loaded'] as $class => $loaded): ?>
                <tr>
                    <td><?php echo esc_html($class); ?></td>
                    <td><span class="status-indicator <?php echo $loaded ? 'status-good' : 'status-error'; ?>">
                        <?php echo $loaded ? '✓ Loaded' : '✗ Missing'; ?>
                    </span></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Database Tab -->
        <div id="database-info" class="tab-content">
            <h3>Database Information</h3>
            <table class="widefat">
                <tr><td><strong>Connection:</strong></td><td>
                    <span class="status-indicator <?php echo $debug_info['database_status']['connection'] ? 'status-good' : 'status-error'; ?>">
                        <?php echo $debug_info['database_status']['connection'] ? '✓ Connected' : '✗ Failed'; ?>
                    </span>
                </td></tr>
            </table>
            
            <h4>Tables Status</h4>
            <table class="widefat">
                <thead>
                    <tr><th>Table</th><th>Status</th><th>Records</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($debug_info['database_status']['tables'] as $key => $table): ?>
                    <tr>
                        <td><?php echo esc_html($table['name']); ?></td>
                        <td><span class="status-indicator <?php echo $table['exists'] ? 'status-good' : 'status-error'; ?>">
                            <?php echo $table['exists'] ? '✓ Exists' : '✗ Missing'; ?>
                        </span></td>
                        <td><?php echo $table['exists'] ? number_format($table['count']) : 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- File System Tab -->
        <div id="filesystem-info" class="tab-content">
            <h3>File System Information</h3>
            <table class="widefat">
                <tr><td><strong>Plugin Directory Writable:</strong></td><td>
                    <span class="status-indicator <?php echo $debug_info['file_system']['plugin_directory_writable'] ? 'status-good' : 'status-warning'; ?>">
                        <?php echo $debug_info['file_system']['plugin_directory_writable'] ? '✓ Writable' : '⚠ Read-only'; ?>
                    </span>
                </td></tr>
            </table>
            
            <h4>Required Files</h4>
            <table class="widefat">
                <thead>
                    <tr><th>File</th><th>Status</th><th>Size</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($debug_info['file_system']['files'] as $key => $file): ?>
                    <tr>
                        <td><?php echo esc_html($file['path']); ?></td>
                        <td>
                            <span class="status-indicator <?php echo $file['exists'] ? 'status-good' : 'status-error'; ?>">
                                <?php echo $file['exists'] ? '✓ Exists' : '✗ Missing'; ?>
                            </span>
                            <?php if ($file['exists'] && !$file['readable']): ?>
                                <span class="status-indicator status-warning">⚠ Not readable</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $file['exists'] ? size_format($file['size']) : 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Environment Tab -->
        <div id="environment-info" class="tab-content">
            <h3>WordPress Environment</h3>
            <table class="widefat">
                <tr><td><strong>WordPress Version:</strong></td><td><?php echo esc_html($debug_info['wordpress_environment']['wp_version']); ?></td></tr>
                <tr><td><strong>PHP Version:</strong></td><td><?php echo esc_html($debug_info['wordpress_environment']['php_version']); ?></td></tr>
                <tr><td><strong>MySQL Version:</strong></td><td><?php echo esc_html($debug_info['wordpress_environment']['mysql_version']); ?></td></tr>
                <tr><td><strong>Server Software:</strong></td><td><?php echo esc_html($debug_info['wordpress_environment']['server_software']); ?></td></tr>
                <tr><td><strong>Memory Limit:</strong></td><td><?php echo esc_html($debug_info['wordpress_environment']['memory_limit']); ?></td></tr>
                <tr><td><strong>Max Execution Time:</strong></td><td><?php echo esc_html($debug_info['wordpress_environment']['max_execution_time']); ?>s</td></tr>
                <tr><td><strong>Upload Max Filesize:</strong></td><td><?php echo esc_html($debug_info['wordpress_environment']['upload_max_filesize']); ?></td></tr>
                <tr><td><strong>REST API Enabled:</strong></td><td>
                    <span class="status-indicator <?php echo $debug_info['wordpress_environment']['rest_api_enabled'] ? 'status-good' : 'status-error'; ?>">
                        <?php echo $debug_info['wordpress_environment']['rest_api_enabled'] ? '✓ Enabled' : '✗ Disabled'; ?>
                    </span>
                </td></tr>
                <tr><td><strong>Debug Mode:</strong></td><td>
                    <span class="status-indicator <?php echo $debug_info['wordpress_environment']['debug_mode'] ? 'status-warning' : 'status-good'; ?>">
                        <?php echo $debug_info['wordpress_environment']['debug_mode'] ? '⚠ Enabled' : '✓ Disabled'; ?>
                    </span>
                </td></tr>
                <tr><td><strong>Debug Logging:</strong></td><td>
                    <span class="status-indicator <?php echo $debug_info['wordpress_environment']['debug_log'] ? 'status-good' : 'status-warning'; ?>">
                        <?php echo $debug_info['wordpress_environment']['debug_log'] ? '✓ Enabled' : '⚠ Disabled'; ?>
                    </span>
                </td></tr>
            </table>
        </div>

        <!-- Recent Errors Tab -->
        <div id="recent-errors" class="tab-content">
            <h3>Recent MTHFR-Related Errors</h3>
            <?php if (!empty($debug_info['recent_errors'])): ?>
                <div class="error-log">
                    <?php foreach ($debug_info['recent_errors'] as $error): ?>
                        <div class="error-entry">
                            <code><?php echo esc_html($error); ?></code>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No recent MTHFR-related errors found in the debug log.</p>
            <?php endif; ?>
        </div>

        <!-- Raw Debug Data Tab -->
        <div id="raw-debug" class="tab-content">
            <h3>Raw Debug Data (JSON)</h3>
            <textarea readonly class="large-text" rows="20"><?php echo esc_textarea(json_encode($debug_info, JSON_PRETTY_PRINT)); ?></textarea>
            <p>
                <button id="copy-debug-data" class="button">Copy to Clipboard</button>
                <button id="download-debug-json" class="button">Download as JSON</button>
            </p>
        </div>
    </div>
</div>

<style>
.mthfr-debug-page {
    max-width: 1200px;
}

.mthfr-debug-actions {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.mthfr-debug-actions .button {
    margin-right: 10px;
}

.mthfr-status-overview {
    margin: 20px 0;
}

.status-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.status-card {
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid;
}

.status-card.status-good {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.status-card.status-warning {
    background: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.status-card.status-error {
    background: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.status-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.status-value {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
}

.status-label {
    margin: 5px 0 0 0;
    font-size: 12px;
}

.test-results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.test-result-card {
    padding: 15px;
    border-radius: 8px;
    border: 2px solid;
}

.test-result-card h3 {
    margin: 0 0 10px 0;
}

.test-score {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.score {
    font-size: 18px;
    font-weight: bold;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
}

.status-badge.status-success {
    background: #28a745;
    color: white;
}

.status-badge.status-warning {
    background: #ffc107;
    color: #212529;
}

.status-badge.status-error {
    background: #dc3545;
    color: white;
}

.test-details {
    font-size: 13px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.detail-key {
    font-weight: bold;
}

.value-good {
    color: #28a745;
}

.value-warning {
    color: #ffc107;
}

.value-error {
    color: #dc3545;
}

.endpoints-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.endpoint-card {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
}

.endpoint-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.endpoint-header h3 {
    margin: 0;
    font-size: 14px;
    font-family: monospace;
}

.method-badge {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    color: white;
}

.method-get {
    background: #007cba;
}

.method-post {
    background: #28a745;
}

.method-put {
    background: #ffc107;
    color: #212529;
}

.method-delete {
    background: #dc3545;
}

.endpoint-description {
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
}

.endpoint-status {
    margin-bottom: 15px;
}

.status-indicator {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    margin-right: 10px;
}

.status-indicator.status-good {
    background: #28a745;
    color: white;
}

.status-indicator.status-error {
    background: #dc3545;
    color: white;
}

.status-code {
    font-size: 12px;
    color: #666;
}

.error-message {
    display: block;
    font-size: 11px;
    color: #dc3545;
    margin-top: 5px;
}

.endpoint-actions {
    margin-bottom: 10px;
}

.endpoint-actions .button {
    margin-right: 5px;
    margin-bottom: 5px;
}

.test-result {
    margin-top: 10px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
    font-size: 12px;
}

.nav-tab-wrapper {
    margin: 20px 0 0 0;
}

.tab-content {
    display: none;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.tab-content.active {
    display: block;
}

.tab-content h3 {
    margin-top: 0;
}

.tab-content h4 {
    margin: 20px 0 10px 0;
    color: #555;
}

.error-log {
    max-height: 400px;
    overflow-y: auto;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.error-entry {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    font-family: monospace;
    font-size: 12px;
}

.error-entry:last-child {
    border-bottom: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var targetId = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(targetId).addClass('active');
    });
    
    // Test individual endpoints
    $('.test-endpoint').on('click', function() {
        var $button = $(this);
        var $resultDiv = $button.closest('.endpoint-card').find('.test-result');
        var url = $button.data('url');
        var method = $button.data('method');
        
        $button.prop('disabled', true).text('Testing...');
        $resultDiv.show().html('<div class="spinner is-active"></div>');
        
        $.post(ajaxurl, {
            action: 'mthfr_test_endpoint',
            endpoint_url: url,
            method: method,
            nonce: mthfr_ajax.nonce
        }, function(response) {
            if (response.success) {
                var result = response.data;
                var html = '<strong>Test Result:</strong><br>';
                html += 'Available: ' + (result.available ? 'Yes' : 'No') + '<br>';
                if (result.status_code) {
                    html += 'Status Code: ' + result.status_code + '<br>';
                }
                if (result.response_size) {
                    html += 'Response Size: ' + result.response_size + ' bytes<br>';
                }
                if (result.error) {
                    html += 'Error: ' + result.error + '<br>';
                }
                html += 'Tested: ' + result.tested_at;
                $resultDiv.html(html);
            } else {
                $resultDiv.html('<span style="color: red;">Test failed</span>');
            }
            
            $button.prop('disabled', false).text('Test Endpoint');
        });
    });
    
    // Refresh debug info
    $('#refresh-debug').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Refreshing...');
        
        $.post(ajaxurl, {
            action: 'mthfr_refresh_debug',
            nonce: mthfr_ajax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to refresh debug info');
                $button.prop('disabled', false).text('Refresh Debug Info');
            }
        });
    });
    
    // Copy debug data to clipboard
    $('#copy-debug-data').on('click', function() {
        var debugTextarea = $('#raw-debug textarea')[0];
        debugTextarea.select();
        document.execCommand('copy');
        
        var $button = $(this);
        var originalText = $button.text();
        $button.text('Copied!');
        setTimeout(function() {
            $button.text(originalText);
        }, 2000);
    });
    
    // Download debug data as JSON
    $('#download-debug-json').on('click', function() {
        var debugData = $('#raw-debug textarea').val();
        var blob = new Blob([debugData], {type: 'application/json'});
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'mthfr-debug-' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
    
    // Download diagnostic report
    $('#download-report').on('click', function() {
        $.post(ajaxurl, {
            action: 'mthfr_generate_diagnostic_report',
            nonce: mthfr_ajax.nonce
        }, function(response) {
            if (response.success) {
                var blob = new Blob([response.data], {type: 'text/plain'});
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'mthfr-diagnostic-report-' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } else {
                alert('Failed to generate diagnostic report');
            }
        });
    });
});
</script>