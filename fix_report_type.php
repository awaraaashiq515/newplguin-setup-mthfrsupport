<?php
// Fix the report_type for the test report

// Include WordPress
require_once('wp-load.php');

global $wpdb;
$table = $wpdb->prefix . 'user_reports';

// Update the report_type from 'completed' to 'Covid' for report ID 1303
$result = $wpdb->update(
    $table,
    array('report_type' => 'Covid'),
    array('id' => 1303),
    array('%s'),
    array('%d')
);

if ($result !== false) {
    echo "Successfully updated report_type to 'Covid' for report ID 1303\n";
} else {
    echo "Failed to update report_type\n";
}
?>