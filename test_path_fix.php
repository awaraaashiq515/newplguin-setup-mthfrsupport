<?php
/**
 * Test script to verify path calculations for MTHFR plugin data directory
 */

echo "Testing path calculations for MTHFR plugin\n\n";

// Simulate paths as if from DatabaseImporter.php
$database_importer_file = __DIR__ . '/wp-content/plugins/mthfr-genetic-reports/src/Core/Database/DatabaseImporter.php';

echo "DatabaseImporter.php path: $database_importer_file\n";
echo "File exists: " . (file_exists($database_importer_file) ? 'YES' : 'NO') . "\n\n";

// Old plugin directory path using dirname(__FILE__, 4) (old method)
$old_plugin_dir_old = dirname($database_importer_file, 4);

echo "Old plugin directory path (using dirname(__FILE__, 4)): $old_plugin_dir_old\n";
echo "Directory exists: " . (file_exists($old_plugin_dir_old) ? 'YES' : 'NO') . "\n\n";

// Corrected path calculation (new method)
$current_plugin_dir = dirname($database_importer_file, 4);
$corrected_old_plugin_dir = dirname($current_plugin_dir) . '/mthfr-genetic-reportsold/data';

echo "Corrected old plugin directory path: $corrected_old_plugin_dir\n";
echo "Directory exists: " . (file_exists($corrected_old_plugin_dir) ? 'YES' : 'NO') . "\n\n";

// Check XLSX files in the corrected directory
$files = array('Database.xlsx', 'Database_0.xlsx', 'current_Database.xlsx');

echo "Checking XLSX files in corrected directory:\n";
foreach ($files as $file) {
    $file_path = $corrected_old_plugin_dir . '/' . $file;
    echo "File $file exists: " . (file_exists($file_path) ? 'YES' : 'NO') . "\n";
}

echo "\n";