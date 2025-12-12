<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$base_dir = 'wp-content/plugins/mthfr-genetic-reportsold/data/';
$files = ['Database.xlsx', 'Database_0.xlsx', 'current_Database.xlsx'];

echo "XLSX File Row Counts (excluding headers):\n\n";

foreach ($files as $file) {
    $file_path = $base_dir . $file;
    if (file_exists($file_path)) {
        try {
            $spreadsheet = IOFactory::load($file_path);
            $sheet = $spreadsheet->getActiveSheet();
            $highest_row = $sheet->getHighestRow();
            $row_count = max(0, $highest_row - 1); // Assuming header in row 1
            echo "$file: $row_count rows\n";
        } catch (Exception $e) {
            echo "$file: Error loading file - " . $e->getMessage() . "\n";
        }
    } else {
        echo "$file: File not found\n";
    }
}
?>