<?php
/**
 * Sample Validation Script
 * Selects 5 random RSIDs from wp_genetic_variants table and compares their records against Database.xlsx
 */

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Database configuration
global $wpdb;
if (!isset($wpdb)) {
    // Include WordPress for database access
    require_once 'wp-load.php';
}

// File path
$xlsx_file = 'wp-content/plugins/mthfr-genetic-reportsold/data/Database.xlsx';

// Possible column mappings (XLSX column name => DB field name)
$column_mappings = [
    'RSID' => 'rsid',
    'Gene' => 'gene',
    'SNP' => 'snp_name',
    'Risk' => 'risk_allele',
    'Info' => 'info',
    'Video' => 'video',
    'Report Name' => 'report_name',
    'Tags' => 'tags'
];

echo "=== Sample Validation Report ===\n\n";

try {
    // Load XLSX file
    if (!file_exists($xlsx_file)) {
        throw new Exception("Database.xlsx file not found at: $xlsx_file");
    }

    echo "Loading XLSX file: $xlsx_file\n";
    $spreadsheet = IOFactory::load($xlsx_file);
    $worksheet = $spreadsheet->getActiveSheet();

    // Get headers and create column index mapping
    $highest_column = $worksheet->getHighestColumn();
    $highest_column_index = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highest_column);

    $headers = [];
    $column_indices = [];

    // Read header row
    for ($col = 1; $col <= $highest_column_index; $col++) {
        $column_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $header_value = trim($worksheet->getCell($column_letter . '1')->getValue());
        $headers[$col] = $header_value;

        // Map XLSX columns to DB fields
        if (isset($column_mappings[$header_value])) {
            $column_indices[$column_mappings[$header_value]] = $col;
        }
    }

    echo "Available XLSX columns: " . implode(', ', $headers) . "\n";
    echo "Mapped columns: " . implode(', ', array_keys($column_indices)) . "\n\n";

    // Select 5 random RSIDs from database
    $table_name = $wpdb->prefix . 'genetic_variants';
    $random_rsids = $wpdb->get_col("SELECT rsid FROM $table_name WHERE rsid IS NOT NULL AND rsid != '' ORDER BY RAND() LIMIT 5");

    if (empty($random_rsids)) {
        throw new Exception("No RSIDs found in database table");
    }

    echo "Selected 5 random RSIDs for validation: " . implode(', ', $random_rsids) . "\n\n";

    // Process each RSID
    $all_match = true;
    $highest_row = $worksheet->getHighestRow();

    foreach ($random_rsids as $rsid) {
        echo "=== Validating RSID: $rsid ===\n";

        // Get full record from database
        $db_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE rsid = %s LIMIT 1",
            $rsid
        ), ARRAY_A);

        if (!$db_record) {
            echo "ERROR: RSID not found in database\n\n";
            $all_match = false;
            continue;
        }

        // Find corresponding row in XLSX
        $xlsx_row = null;
        $xlsx_data = [];

        for ($row = 2; $row <= $highest_row; $row++) {
            $rsid_column = isset($column_indices['rsid']) ? $column_indices['rsid'] : null;
            if (!$rsid_column) {
                echo "ERROR: No RSID column found in XLSX\n\n";
                $all_match = false;
                break 2;
            }

            $column_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($rsid_column);
            $xlsx_rsid = trim($worksheet->getCell($column_letter . $row)->getValue());

            if (strtolower($xlsx_rsid) === strtolower($rsid)) {
                $xlsx_row = $row;
                break;
            }
        }

        if ($xlsx_row === null) {
            echo "ERROR: RSID not found in XLSX file\n\n";
            $all_match = false;
            continue;
        }

        // Extract XLSX data for this row
        foreach ($column_indices as $field => $col_index) {
            $column_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_index);
            $xlsx_data[$field] = trim($worksheet->getCell($column_letter . $xlsx_row)->getValue());
        }

        // Compare key fields
        $key_fields = ['gene', 'risk_allele', 'info'];
        $matches = true;
        $discrepancies = [];

        echo "Database vs XLSX comparison:\n";

        foreach ($key_fields as $field) {
            $db_value = isset($db_record[$field]) ? trim($db_record[$field]) : '';
            $xlsx_value = isset($xlsx_data[$field]) ? trim($xlsx_data[$field]) : '';

            $match = (strtolower($db_value) === strtolower($xlsx_value));
            echo "- $field: DB='$db_value' | XLSX='$xlsx_value' | " . ($match ? "MATCH" : "DIFFER") . "\n";

            if (!$match) {
                $matches = false;
                $discrepancies[] = $field;
            }
        }

        // Also check other mapped fields
        foreach ($column_indices as $field => $col_index) {
            if (!in_array($field, $key_fields)) {
                $db_value = isset($db_record[$field]) ? trim($db_record[$field]) : '';
                $xlsx_value = isset($xlsx_data[$field]) ? trim($xlsx_data[$field]) : '';

                $match = (strtolower($db_value) === strtolower($xlsx_value));
                echo "- $field: DB='$db_value' | XLSX='$xlsx_value' | " . ($match ? "MATCH" : "DIFFER") . "\n";

                if (!$match) {
                    $discrepancies[] = $field;
                }
            }
        }

        if ($matches && empty($discrepancies)) {
            echo "RESULT: All fields match ✓\n\n";
        } else {
            echo "RESULT: Discrepancies found in: " . implode(', ', $discrepancies) . "\n\n";
            $all_match = false;
        }
    }

    echo "=== Validation Summary ===\n";
    if ($all_match) {
        echo "✓ All 5 samples validated successfully - data correctness confirmed!\n";
    } else {
        echo "✗ Discrepancies found in validation samples - data integrity issues detected\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}