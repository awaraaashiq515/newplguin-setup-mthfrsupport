<?php
// Test the fixed import functionality

// Include WordPress
require_once('wp-load.php');

// Include the importer
require_once('wp-content/plugins/mthfr-genetic-reports/src/Core/Database/DatabaseImporter.php');

try {
    // Create importer instance
    $importer = new MTHFR_Database_Importer();

    // Import from XLSX files
    $result = $importer->import_from_xlsx();

    echo "Import Result:\n";
    echo "Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    echo "Message: " . $result['message'] . "\n";
    echo "Files processed: " . $result['files_processed'] . "\n";
    echo "Total rows: " . $result['total_rows'] . "\n";
    echo "Variants inserted: " . $result['inserted_variants'] . "\n";
    echo "Categories inserted: " . $result['inserted_categories'] . "\n";
    echo "Rows skipped: " . $result['skipped_rows'] . "\n";
    echo "Errors: " . $result['errors'] . "\n";

} catch (Exception $e) {
    echo 'Import failed: ' . $e->getMessage();
}
?>