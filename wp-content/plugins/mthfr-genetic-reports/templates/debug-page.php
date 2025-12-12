<?php
/**
 * Debug Page Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>MTHFR Debug Information</h1>
    
    <div class="mthfr-debug-page">
        <!-- System Information -->
        <div class="debug-section">
            <h2>System Information</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th>Plugin Version</th>
                        <td><?php echo esc_html($debug_info['plugin_version']); ?></td>
                    </tr>
                    <tr>
                        <th>WordPress Version</th>
                        <td><?php echo esc_html($debug_info['wordpress_version']); ?></td>
                    </tr>
                    <tr>
                        <th>PHP Version</th>
                        <td><?php echo esc_html($debug_info['php_version']); ?></td>
                    </tr>
                    <tr>
                        <th>Current User</th>
                        <td><?php echo esc_html($debug_info['current_user']); ?></td>
                    </tr>
                    <tr>
                        <th>Timestamp</th>
                        <td><?php echo esc_html($debug_info['timestamp']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Database Test Results -->
        <div class="debug-section">
            <h2>Database Test Results</h2>
            <div class="test-result <?php echo $debug_info['database_test']['status'] === 'success' ? 'success' : 'error'; ?>">
                <strong>Status:</strong> <?php echo esc_html($debug_info['database_test']['status']); ?>
            </div>
            
            <?php if ($debug_info['database_test']['status'] === 'success'): ?>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <th>Database Host</th>
                            <td><?php echo esc_html($debug_info['database_test']['host']); ?></td>
                        </tr>
                        <tr>
                            <th>Database Name</th>
                            <td><?php echo esc_html($debug_info['database_test']['database']); ?></td>
                        </tr>
                        <tr>
                            <th>Table Prefix</th>
                            <td><?php echo esc_html($debug_info['database_test']['table_prefix']); ?></td>
                        </tr>
                        <tr>
                            <th>Uploads Count</th>
                            <td><?php echo esc_html($debug_info['database_test']['uploads_count']); ?></td>
                        </tr>
                        <tr>
                            <th>Reports Count</th>
                            <td><?php echo esc_html($debug_info['database_test']['reports_count']); ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="error-message">
                    <strong>Error:</strong> <?php echo esc_html($debug_info['database_test']['message'] ?? 'Unknown database error'); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- PDF Test Results -->
        <div class="debug-section">
            <h2>PDF Generation Test Results</h2>
            <div class="test-result <?php echo $debug_info['pdf_test']['status'] === 'success' ? 'success' : ($debug_info['pdf_test']['status'] === 'partial' ? 'warning' : 'error'); ?>">
                <strong>Status:</strong> <?php echo esc_html($debug_info['pdf_test']['status']); ?>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th>Message</th>
                        <td><?php echo esc_html($debug_info['pdf_test']['message']); ?></td>
                    </tr>
                    <tr>
                        <th>mPDF Available</th>
                        <td><?php echo esc_html($debug_info['pdf_test']['mpdf_available'] ?? 'Unknown'); ?></td>
                    </tr>
                    <?php if (isset($debug_info['pdf_test']['pdf_size'])): ?>
                    <tr>
                        <th>PDF Size (bytes)</th>
                        <td><?php echo esc_html($debug_info['pdf_test']['pdf_size']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($debug_info['pdf_test']['variants_tested'])): ?>
                    <tr>
                        <th>Variants Tested</th>
                        <td><?php echo esc_html($debug_info['pdf_test']['variants_tested']); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Directory Information -->
        <div class="debug-section">
            <h2>Directory Information</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th>Reports Directory</th>
                        <td><?php echo esc_html($debug_info['reports_directory']); ?></td>
                    </tr>
                    <tr>
                        <th>Directory Exists</th>
                        <td><?php echo $debug_info['reports_dir_exists'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Directory Writable</th>
                        <td><?php echo $debug_info['reports_dir_writable'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Upload Directory</th>
                        <td><?php echo esc_html($debug_info['upload_directory']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Available Functions -->
        <div class="debug-section">
            <h2>Available Functions</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php foreach ($debug_info['functions_available'] as $function => $available): ?>
                    <tr>
                        <th><?php echo esc_html($function); ?></th>
                        <td>
                            <span class="<?php echo $available ? 'available' : 'not-available'; ?>">
                                <?php echo $available ? 'Available' : 'Not Available'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Available Classes -->
        <div class="debug-section">
            <h2>Available Classes</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php foreach ($debug_info['classes_available'] as $class => $available): ?>
                    <tr>
                        <th><?php echo esc_html($class); ?></th>
                        <td>
                            <span class="<?php echo $available ? 'available' : 'not-available'; ?>">
                                <?php echo $available ? 'Available' : 'Not Available'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Server Information -->
        <div class="debug-section">
            <h2>Server Information</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php foreach ($debug_info['server_info'] as $key => $value): ?>
                    <tr>
                        <th><?php echo esc_html($key); ?></th>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Gene References -->
        <div class="debug-section">
            <h2>Gene References</h2>
            <div class="gene-references">
                <?php foreach ($debug_info['gene_references'] as $category => $genes): ?>
                    <div class="gene-category">
                        <h3><?php echo esc_html($category); ?> Genes</h3>
                        <div class="gene-list">
                            <?php foreach ($genes as $gene): ?>
                                <span class="gene-tag"><?php echo esc_html($gene); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Sample Data Information -->
        <div class="debug-section">
            <h2>Sample Data</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th>Sample Genetic Data Count</th>
                        <td><?php echo esc_html($debug_info['sample_genetic_data_count']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Raw Debug Data -->
        <div class="debug-section">
            <h2>Raw Debug Data</h2>
            <textarea class="debug-raw-data" readonly><?php echo esc_textarea(json_encode($debug_info, JSON_PRETTY_PRINT)); ?></textarea>
        </div>
    </div>
</div>

<style>
.mthfr-debug-page {
    max-width: 1000px;
}

.debug-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.debug-section h2 {
    margin-top: 0;
    color: #23282d;
    border-bottom: 1px solid #ccd0d4;
    padding-bottom: 10px;
}

.test-result {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
    font-weight: bold;
}

.test-result.success {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.test-result.warning {
    background-color: #fff3cd;
    color: #664d03;
    border: 1px solid #ffecb5;
}

.test-result.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c2c7;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #f5c2c7;
}

.available {
    color: #008a00;
    font-weight: bold;
}

.not-available {
    color: #d63638;
    font-weight: bold;
}

.gene-references {
    margin-top: 15px;
}

.gene-category {
    margin-bottom: 20px;
}

.gene-category h3 {
    margin-bottom: 10px;
    color: #0073aa;
}

.gene-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.gene-tag {
    background: #f0f6fc;
    color: #0969da;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    border: 1px solid #d0d7de;
}

.debug-raw-data {
    width: 100%;
    height: 300px;
    font-family: monospace;
    font-size: 12px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    resize: vertical;
}
</style>