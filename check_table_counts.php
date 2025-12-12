<?php
require_once('wp-load.php');

global $wpdb;

$tables = array(
    'wpub_genetic_variants',
    'wpub_variant_categories',
    'wpub_variant_tags',
    'wpub_pathways'
);

echo "Table Record Counts and Metadata:\n\n";

foreach ($tables as $table) {
    // Get count
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "$table: $count records\n";

    // Attempt to get metadata (timestamps)
    $columns = $wpdb->get_col("DESCRIBE $table", 0);
    $timestamp_columns = array_filter($columns, function($col) {
        return strpos($col, 'created_at') !== false || strpos($col, 'updated_at') !== false || strpos($col, 'import_date') !== false;
    });

    if (!empty($timestamp_columns)) {
        foreach ($timestamp_columns as $col) {
            $latest = $wpdb->get_var("SELECT MAX($col) FROM $table");
            echo "  Latest $col: $latest\n";
        }
    } else {
        echo "  No timestamp columns found\n";
    }
    echo "\n";
}
?>