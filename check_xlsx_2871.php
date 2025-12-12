<?php
// Check XLSX file for row 2871 and any '2871' in RSID or SNP

require_once 'vendor/autoload.php'; // Assuming composer autoload

use PhpOffice\PhpSpreadsheet\IOFactory;

$xlsxFile = 'wp-content/plugins/mthfr-genetic-reportsold/data/Database.xlsx';

if (!file_exists($xlsxFile)) {
    echo "XLSX file not found.\n";
    exit;
}

try {
    $spreadsheet = IOFactory::load($xlsxFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();

    echo "Total rows: $highestRow\n";

    // Check row 2871
    if (2871 <= $highestRow) {
        $rowData = [];
        foreach ($worksheet->getRowIterator(2871, 2871) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
        }
        echo "Row 2871 data:\n";
        print_r($rowData);
    } else {
        echo "Row 2871 does not exist.\n";
    }

    // Search for '2871' in RSID and SNP columns
    $found = [];
    for ($row = 2; $row <= $highestRow; $row++) { // Assuming header in row 1
        $rsid = $worksheet->getCell('B' . $row)->getValue(); // RSID is column B
        $snp = $worksheet->getCell('E' . $row)->getValue(); // SNP is column E
        if ($rsid == '2871' || $snp == '2871' || $rsid == 2871 || $snp == 2871) {
            $found[] = [
                'row' => $row,
                'rsid' => $rsid,
                'snp' => $snp,
                'gene' => $worksheet->getCell('C' . $row)->getValue(),
                'alleles' => $worksheet->getCell('F' . $row)->getValue()
            ];
        }
    }

    if (!empty($found)) {
        echo "\nFound entries with RSID or SNP '2871':\n";
        foreach ($found as $entry) {
            echo "Row {$entry['row']}: RSID={$entry['rsid']}, SNP={$entry['snp']}, Gene={$entry['gene']}, Alleles={$entry['alleles']}\n";
        }
    } else {
        echo "\nNo entries found with RSID or SNP '2871'.\n";
    }

    // Check for duplicates
    $entries = [];
    $duplicates = [];
    for ($row = 2; $row <= $highestRow; $row++) {
        $rsid = $worksheet->getCell('B' . $row)->getValue();
        $snp = $worksheet->getCell('E' . $row)->getValue();
        $key = $rsid . '|' . $snp;
        if (isset($entries[$key])) {
            $duplicates[$key][] = $row;
        } else {
            $entries[$key] = $row;
        }
    }

    if (!empty($duplicates)) {
        echo "\nDuplicate entries found:\n";
        foreach ($duplicates as $key => $rows) {
            list($rsid, $snp) = explode('|', $key);
            echo "RSID=$rsid, SNP=$snp in rows: " . implode(', ', $rows) . "\n";
        }
    } else {
        echo "\nNo duplicates found.\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>