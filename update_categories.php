<?php

require_once 'wp-load.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

global $wpdb;

$filePath = 'wp-content/plugins/mthfr-genetic-reportsold/data/Database.xlsx';

if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}

echo "Loading XLSX file...\n";

try {
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    echo "Processing $highestRow rows...\n";

    $updated = 0;
    $errors = 0;

    // Skip header row, start from row 2
    for ($row = 2; $row <= $highestRow; $row++) {
        $rsid = trim($sheet->getCell('C' . $row)->getValue());
        $category = trim($sheet->getCell('B' . $row)->getValue());

        if (empty($rsid) || strpos($rsid, 'rs') !== 0) {
            continue; // Skip invalid RSID
        }

        if (empty($category)) {
            continue; // Skip empty category
        }

        // Update the category in database
        $result = $wpdb->update(
            $wpdb->prefix . 'genetic_variants',
            array('categories' => $category),
            array('rsid' => $rsid),
            array('%s'),
            array('%s')
        );

        if ($result !== false) {
            $updated++;
            if ($updated % 100 == 0) {
                echo "Updated $updated variants...\n";
            }
        } else {
            $errors++;
        }
    }

    echo "\nCompleted!\n";
    echo "Updated: $updated variants\n";
    echo "Errors: $errors\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}