
#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php'; // Assuming PHPSpreadsheet is installed via Composer

use PhpOffice\PhpSpreadsheet\IOFactory;

$dataDir = 'wp-content/plugins/mthfr-genetic-reportsold/data/';
$files = ['Database.xlsx'];

$totals = [
    'total_data_rows' => 0,
    'rows_with_rsid' => 0,
    'unique_rsids' => [],
    'unique_categories' => [],
    'unique_tags' => [],
    'unique_pathways' => [],
    'variant_category_links' => 0,
    'variant_tag_links' => 0,
    'total_rsid' => 0,
    'unique_rsid' => [],
    'total_gene' => 0,
    'unique_gene' => [],
    'total_snp' => 0,
    'unique_snp' => [],
    'total_alleles' => 0,
    'unique_alleles' => [],
    'total_risk' => 0,
    'unique_risk' => [],
    'rs_start_count' => 0,
    'non_rs_start_count' => 0,
    'empty_gene_count' => 0,
];

foreach ($files as $file) {
    $filePath = $dataDir . $file;
    if (!file_exists($filePath)) {
        error_log("File not found: $filePath");
        continue;
    }

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Find column indices by headers (case-insensitive)
        $headers = [];
        for ($col = 'A'; $col <= $highestColumn; ++$col) {
            $header = strtolower(trim($sheet->getCell($col . '1')->getValue()));
            $headers[$header] = $col;
        }

        // Infer columns if not found
        $rsid_col = $headers['rsid'] ?? 'C';
        $gene_col = $headers['gene'] ?? 'D';
        $snp_col = $headers['snp'] ?? 'E';
        $alleles_col = $headers['alleles'] ?? 'F';
        $risk_col = $headers['risk'] ?? 'G';
        $categories_col = 'B'; // excipients as categories
        $tags_col = $headers['tags'] ?? 'K';
        // no pathways

        for ($row = 2; $row <= $highestRow; $row++) {
            $totals['total_data_rows']++;
            $rsid = trim($sheet->getCell($rsid_col . $row)->getValue());
            $gene = trim($sheet->getCell($gene_col . $row)->getValue());
            $snp = trim($sheet->getCell($snp_col . $row)->getValue());
            $alleles = trim($sheet->getCell($alleles_col . $row)->getValue());
            $risk = trim($sheet->getCell($risk_col . $row)->getValue());
            $categories = trim($sheet->getCell($categories_col . $row)->getValue());
            $tags = trim($sheet->getCell($tags_col . $row)->getValue());

            if ($gene === '') {
                $totals['empty_gene_count']++;
            }

            if ($rsid) {
                $totals['total_rsid']++;
                $totals['unique_rsid'][$rsid] = true;
                if (strpos($rsid, 'rs') === 0) {
                    $totals['rs_start_count']++;
                } else {
                    $totals['non_rs_start_count']++;
                }
            }

            if ($gene) {
                $totals['total_gene']++;
                $totals['unique_gene'][$gene] = true;
            }

            if ($snp) {
                $totals['total_snp']++;
                $totals['unique_snp'][$snp] = true;
            }

            if ($alleles) {
                $totals['total_alleles']++;
                $totals['unique_alleles'][$alleles] = true;
            }

            if ($risk) {
                $totals['total_risk']++;
                $totals['unique_risk'][$risk] = true;
            }

            if (!$rsid) {
                error_log("Skipping row $row in $file: missing RSID\n");
                continue;
            }

            $totals['rows_with_rsid']++;
            $totals['unique_rsids'][$rsid] = true;

            if ($categories) {
                $totals['unique_categories'][$categories] = true;
                $totals['variant_category_links']++;
            }

            if ($tags) {
                $tag_list = array_map('trim', explode(',', $tags));
                foreach ($tag_list as $tag) {
                    if ($tag) $totals['unique_tags'][$tag] = true;
                }
                $totals['variant_tag_links'] += count(array_filter($tag_list));
            }
        }
    } catch (Exception $e) {
        error_log("Error processing $path: " . $e->getMessage() . "\n");
    }
}

$markdown = "# XLSX Data Analysis\n\n";
$markdown .= "| Data Type | Count | Description |\n";
$markdown .= "|-----------|-------|-------------|\n";
$markdown .= "| Genetic Variants | " . count($totals['unique_rsids']) . " | Unique RSID entries |\n";
$markdown .= "| Total Data Rows | " . $totals['total_data_rows'] . " | Total rows in the XLSX file (excluding header) |\n";
$markdown .= "| Rows with RSID | " . $totals['rows_with_rsid'] . " | Rows containing RSID data |\n";
$markdown .= "| Categories | " . count($totals['unique_categories']) . " | Unique category names |\n";
$markdown .= "| Tags | " . count($totals['unique_tags']) . " | Unique tag names |\n";
$markdown .= "| Pathways | " . count($totals['unique_pathways']) . " | Pathway definitions |\n";
$markdown .= "| Variant-Category Links | " . $totals['variant_category_links'] . " | RSID to category associations |\n";
$markdown .= "| Variant-Tag Links | " . $totals['variant_tag_links'] . " | RSID to tag associations |\n";
$markdown .= "| RSID Total Records | " . $totals['total_rsid'] . " | Total records with RSID |\n";
$markdown .= "| RSID Unique Entries | " . count($totals['unique_rsid']) . " | Unique RSID entries |\n";
$markdown .= "| RSID Starting with 'rs' | " . $totals['rs_start_count'] . " | RSID entries starting with 'rs' |\n";
$markdown .= "| RSID Not Starting with 'rs' | " . $totals['non_rs_start_count'] . " | RSID entries not starting with 'rs' |\n";
$markdown .= "| Gene Total Records | " . $totals['total_gene'] . " | Total records with Gene |\n";
$markdown .= "| Gene Unique Entries | " . count($totals['unique_gene']) . " | Unique Gene entries |\n";
$markdown .= "| Rows with Empty Gene | " . $totals['empty_gene_count'] . " | Rows where Gene field is empty |\n";
$markdown .= "| SNP Total Records | " . $totals['total_snp'] . " | Total records with SNP |\n";
$markdown .= "| SNP Unique Entries | " . count($totals['unique_snp']) . " | Unique SNP entries |\n";
$markdown .= "| Alleles Total Records | " . $totals['total_alleles'] . " | Total records with Alleles |\n";
$markdown .= "| Alleles Unique Entries | " . count($totals['unique_alleles']) . " | Unique Alleles entries |\n";
$markdown .= "| Risk Total Records | " . $totals['total_risk'] . " | Total records with Risk |\n";
$markdown .= "| Risk Unique Entries | " . count($totals['unique_risk']) . " | Unique Risk entries |\n";

file_put_contents('xlsx_data_analysis.md', $markdown);

echo "Analysis complete. Markdown saved to xlsx_data_analysis.md\n";