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
    $highestRow = $sheet->getHighestRow();

    $categories = [];

    for ($row = 2; $row <= $highestRow; $row++) {
        $category = trim($sheet->getCell('B' . $row)->getValue());
        if ($category && !isset($categories[$category])) {
            $categories[$category] = true;
        }
    }

    $covid_related = ['Covid', 'HLA', 'Other Immune Factors', 'Immune Response', 'Inflammatory Response'];

    echo "All categories found in XLSX file:\n\n";
    foreach (array_keys($categories) as $category) {
        echo "- $category\n";
    }

    echo "\nTotal categories: " . count($categories) . "\n\n";

    echo "=== COVID-RELATED CATEGORIES ANALYSIS ===\n\n";

    foreach ($covid_related as $cat) {
        if (isset($categories[$cat])) {
            echo "✓ Category '$cat' EXISTS in XLSX\n";

            // Count variants in this category
            $count = 0;
            $genes = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $category = trim($sheet->getCell('B' . $row)->getValue());
                if ($category === $cat) {
                    $count++;
                    $gene = trim($sheet->getCell('D' . $row)->getValue());
                    if ($gene) $genes[$gene] = true;
                }
            }

            echo "  - $count variants\n";
            echo "  - Genes: " . implode(', ', array_keys($genes)) . "\n";
        } else {
            echo "✗ Category '$cat' MISSING from XLSX\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}