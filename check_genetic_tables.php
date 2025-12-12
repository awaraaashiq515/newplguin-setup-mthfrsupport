<?php
require_once('wp-load.php');

global $wpdb;

// Get all tables with 'genetic_variants' in the name
$tables = $wpdb->get_col("SHOW TABLES LIKE '%genetic_variants%'");

echo "Tables related to genetic variants:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

// Also show all tables for context
echo "\nAll tables in database:\n";
$all_tables = $wpdb->get_col("SHOW TABLES");
foreach ($all_tables as $table) {
    echo "- $table\n";
}
?>