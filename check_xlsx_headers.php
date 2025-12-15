<?php

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'wp-content/plugins/mthfr-genetic-reportsold/data/Database.xlsx';

if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}

try {
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    // Get headers from first row
    $highestCol = $sheet->getHighestColumn();
    echo "Headers in XLSX file:\n";

    for ($col = 'A'; $col <= $highestCol; ++$col) {
        $header = trim($sheet->getCell($col . '1')->getValue());
        if ($header) {
            echo "Column $col: '$header'\n";
        }
    }

    echo "\nFirst few data rows:\n";
    for ($row = 2; $row <= 5; $row++) {
        echo "Row $row: ";
        for ($col = 'A'; $col <= 'E'; ++$col) {
            $value = trim($sheet->getCell($col . $row)->getValue());
            echo "$col='$value' ";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}