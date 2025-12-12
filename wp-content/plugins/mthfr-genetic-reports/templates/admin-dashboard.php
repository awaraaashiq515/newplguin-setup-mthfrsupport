<?php
/**
 * Admin Dashboard Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$stats = MTHFR_Database::get_report_stats();
?>

<div class="wrap">
    <h1>MTHFR Genetic Reports Dashboard</h1>
    
    <div class="mthfr-dashboard">
        <!-- Stats Cards -->
        <div class="mthfr-stats-grid">
            <div class="mthfr-stat-card">
                <h3>Total Uploads</h3>
                <div class="stat-number"><?php echo esc_html($uploads_count); ?></div>
            </div>
            
            <div class="mthfr-stat-card">
                <h3>Total Reports</h3>
                <div class="stat-number"><?php echo esc_html($reports_count); ?></div>
            </div>
            
            <div class="mthfr-stat-card">
                <h3>Completed Reports</h3>
                <div class="stat-number"><?php echo esc_html($stats['by_status']['completed'] ?? 0); ?></div>
            </div>
            
            <div class="mthfr-stat-card">
                <h3>Failed Reports</h3>
                <div class="stat-number"><?php echo esc_html($stats['by_status']['failed'] ?? 0); ?></div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="mthfr-quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <button type="button" class="button button-primary" id="test-health">Test Health Check</button>
                <button type="button" class="button button-secondary" id="test-database">Test Database</button>
                <button type="button" class="button button-secondary" id="test-pdf">Test PDF Generation</button>
                <button type="button" class="button button-secondary" id="generate-sample">Generate Sample Report</button>
            </div>
        </div>
        
        <!-- API Endpoints -->
        <div class="mthfr-api-info">
            <h2>API Endpoints</h2>
            <div class="api-endpoints">
                <div class="endpoint">
                    <strong>Health Check:</strong>
                    <code><?php echo esc_url(rest_url('mthfr/v1/health')); ?></code>
                    <button type="button" class="button button-small test-endpoint" data-endpoint="health">Test</button>
                </div>
                
                <div class="endpoint">
                    <strong>Debug Info:</strong>
                    <code><?php echo esc_url(rest_url('mthfr/v1/debug')); ?></code>
                    <button type="button" class="button button-small test-endpoint" data-endpoint="debug">Test</button>
                </div>
                
                <div class="endpoint">
                    <strong>Generate Report:</strong>
                    <code><?php echo esc_url(rest_url('mthfr/v1/generate-report')); ?></code>
                    <span class="method-tag">POST</span>
                </div>
                
                <div class="endpoint">
                    <strong>Test Database:</strong>
                    <code><?php echo esc_url(rest_url('mthfr/v1/test-db')); ?></code>
                    <button type="button" class="button button-small test-endpoint" data-endpoint="test-db">Test</button>
                </div>
                
                <div class="endpoint">
                    <strong>Test PDF:</strong>
                    <code><?php echo esc_url(rest_url('mthfr/v1/test-pdf')); ?></code>
                    <button type="button" class="button button-small test-endpoint" data-endpoint="test-pdf">Test</button>
                </div>
            </div>
        </div>
        
        <!-- Generate Report Form -->
        <div class="mthfr-generate-form">
            <h2>Generate Test Report</h2>
            <form id="generate-report-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">Upload ID</th>
                        <td><input type="number" name="upload_id" value="443" required /></td>
                    </tr>
                    <tr>
                        <th scope="row">Order ID</th>
                        <td><input type="number" name="order_id" value="120" required /></td>
                    </tr>
                    <tr>
                        <th scope="row">Product Name</th>
                        <td>
                            <select name="product_name">
                                <option value="COVID Report">COVID Report</option>
                                <option value="Methylation Report">Methylation Report</option>
                                <option value="Variant Report">Variant Report</option>
                                <option value="Excipient Report">Excipient Report</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Has Subscription</th>
                        <td><input type="checkbox" name="has_subscription" value="1" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Generate Report</button>
                </p>
            </form>
        </div>
        
        <!-- Recent Reports -->
        <div class="mthfr-recent-reports">
            <h2>Recent Reports</h2>
            <?php if (!empty($recent_reports)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Upload ID</th>
                            <th>Order ID</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_reports as $report): ?>
                            <tr>
                                <td><?php echo esc_html($report->id); ?></td>
                                <td><?php echo esc_html($report->upload_id); ?></td>
                                <td><?php echo esc_html($report->order_id); ?></td>
                                <td><?php echo esc_html($report->report_type); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($report->status); ?>">
                                        <?php echo esc_html(ucfirst($report->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('M j, Y H:i', strtotime($report->created_at))); ?></td>
                                <td>
                                    <?php if ($report->report_path && file_exists($report->report_path)): ?>
                                        <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/user_reports/' . basename($report->report_path)); ?>" 
                                           class="button button-small" target="_blank">View Report</a>
                                    <?php else: ?>
                                        <span class="description">No file</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No reports found. <a href="#" id="generate-sample-data">Generate sample data</a></p>
            <?php endif; ?>
        </div>
        
        <!-- Results Display -->
        <div id="mthfr-results" style="display: none;">
            <h2>Test Results</h2>
            <div id="results-content"></div>
        </div>
    </div>
</div>

<style>
.mthfr-dashboard {
    max-width: 1200px;
}

.mthfr-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.mthfr-stat-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    text-align: center;
}

.mthfr-stat-card h3 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #0073aa;
}

.mthfr-quick-actions,
.mthfr-api-info,
.mthfr-generate-form,
.mthfr-recent-reports {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.mthfr-quick-actions h2,
.mthfr-api-info h2,
.mthfr-generate-form h2,
.mthfr-recent-reports h2 {
    margin-top: 0;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.api-endpoints .endpoint {
    margin-bottom: 15px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.api-endpoints code {
    flex: 1;
    background: #fff;
    padding: 5px 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.method-tag {
    background: #0073aa;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.status-completed { color: #008a00; }
.status-failed { color: #d63638; }
.status-pending { color: #dba617; }

#mthfr-results {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
}

#results-content {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    white-space: pre-wrap;
    font-family: monospace;
    max-height: 400px;
    overflow-y: auto;
}
</style>