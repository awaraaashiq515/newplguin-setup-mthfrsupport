<?php

require_once 'wp-load.php';

echo "=== Restoring Database with All Data Including Duplicates ===\n\n";

try {
    // Clear existing data first
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->prefix}genetic_variants");

    echo "Cleared existing database tables\n";

    // Import from XLSX
    require_once 'wp-content/plugins/mthfr-genetic-reports/src/Core/Database/DatabaseImporter.php';
    $importer = new MTHFR_Database_Importer();
    $result = $importer->import_from_xlsx();

    if ($result['success']) {
        echo "Import successful!\n";
        echo "Files processed: {$result['files_processed']}\n";
        echo "Total rows: {$result['total_rows']}\n";
        echo "Variants inserted: {$result['inserted_variants']}\n";
        echo "Categories inserted: {$result['inserted_categories']}\n";
    } else {
        echo "Import failed: {$result['message']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>