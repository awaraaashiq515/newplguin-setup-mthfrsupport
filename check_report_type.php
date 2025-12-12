<?php
// Check the report_type for the specific report

// Include WordPress
require_once('wp-load.php');

global $wpdb;
$table = $wpdb->prefix . 'user_reports';
$report = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", 1303));

echo "Report details:\n";
print_r($report);

echo "\nReport type: " . $report->report_type . "\n";
echo "Status: " . $report->status . "\n";
?>