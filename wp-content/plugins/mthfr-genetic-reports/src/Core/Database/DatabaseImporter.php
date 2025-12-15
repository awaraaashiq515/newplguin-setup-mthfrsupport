<?php
/**
 * MTHFR Database Importer
 * Migrates XLSX data to database tables
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include PHPSpreadsheet if available
if (file_exists(MTHFR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once MTHFR_PLUGIN_PATH . 'vendor/autoload.php';
}

class MTHFR_Database_Importer {

    /**
     * Import data from XLSX files to database
     */
    public function import_from_xlsx($file_paths = null) {
        error_log('MTHFR: Starting database import from XLSX files');

        if ($file_paths === null) {
            // Use default paths from old plugin directory
            // Calculate path to old plugin data directory
            $current_plugin_dir = dirname(__FILE__, 4); // wp-content/plugins/mthfr-genetic-reports
            $old_plugin_dir = dirname($current_plugin_dir) . '/mthfr-genetic-reportsold/data';
            $file_paths = array();

            if (file_exists($old_plugin_dir)) {
                $files = array(
                    $old_plugin_dir . '/Database.xlsx',
                    $old_plugin_dir . '/Database_0.xlsx',
                    $old_plugin_dir . '/current_Database.xlsx'
                );
                $file_paths = array_filter($files, 'file_exists');
            }
        }

        if (empty($file_paths)) {
            return array(
                'success' => false,
                'message' => 'No XLSX files found to import',
                'files_processed' => 0,
                'total_rows' => 0,
                'inserted_variants' => 0,
                'inserted_categories' => 0,
                'skipped_rows' => 0,
                'errors' => 1
            );
        }

        $total_files = count($file_paths);
        $total_rows = 0;
        $inserted_variants = 0;
        $inserted_categories = 0;
        $skipped_rows = 0;
        $errors = 0;
        $processed_files = 0;

        foreach ($file_paths as $file_path) {
            if (!file_exists($file_path)) {
                error_log("MTHFR: File not found: {$file_path}");
                $errors++;
                continue;
            }

            error_log("MTHFR: Processing file: {$file_path}");

            try {
                $result = $this->process_xlsx_file($file_path);

                if ($result['success']) {
                    $processed_files++;
                    $total_rows += $result['rows_processed'];
                    $inserted_variants += $result['variants_inserted'];
                    $inserted_categories += $result['categories_inserted'];
                    $skipped_rows += $result['rows_skipped'];
                } else {
                    $errors++;
                }
            } catch (Exception $e) {
                error_log("MTHFR: Error processing file {$file_path}: " . $e->getMessage());
                $errors++;
            }
        }

        $message = "Import completed: {$processed_files}/{$total_files} files processed, {$inserted_variants} variants inserted";

        return array(
            'success' => ($processed_files > 0),
            'message' => $message,
            'files_processed' => $processed_files,
            'total_rows' => $total_rows,
            'inserted_variants' => $inserted_variants,
            'inserted_categories' => $inserted_categories,
            'skipped_rows' => $skipped_rows,
            'errors' => $errors
        );
    }

    /**
     * Process a single XLSX file with streaming to reduce memory usage
     */
    private function process_xlsx_file($file_path) {
        if (!class_exists('PhpOffice\PhpSpreadsheet\Reader\Xlsx')) {
            throw new Exception('PHPSpreadsheet Xlsx reader not available');
        }

        if (!is_readable($file_path)) {
            throw new Exception("File is not readable: {$file_path}");
        }

        // Use streaming reader for memory efficiency
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(["Sheet1"]); // Load only the first sheet

        $spreadsheet = $reader->load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();

        // Get headers from first row
        $headers = array();
        $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());

        for ($col = 1; $col <= $highestCol; $col++) {
            $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getCalculatedValue();
            if (!empty($cellValue)) {
                $headers[$col] = trim($cellValue);
            }
        }

        if (empty($headers)) {
            throw new Exception('No headers found in XLSX file');
        }

        // Find RSID column
        $rsid_column = null;
        foreach ($headers as $col_num => $header) {
            if (in_array(strtolower(trim($header)), array('rsid', 'rs_id', 'snp_id', 'snp id'))) {
                $rsid_column = $col_num;
                break;
            }
        }

        if ($rsid_column === null) {
            throw new Exception('No RSID column found in headers');
        }

        // Process data rows in batches for memory efficiency
        $batch_size = 1000;
        $batch_data = array();
        $total_rows_processed = 0;
        $total_variants_inserted = 0;
        $total_categories_inserted = 0;
        $total_rows_skipped = 0;

        // Use row iterator for streaming
        $rowIterator = $worksheet->getRowIterator();
        $rowIterator->seek(2); // Skip header row

        foreach ($rowIterator as $row) {
            $rowIndex = $row->getRowIndex();

            // Extract row data for all rows
            $row_data = array();
            foreach ($headers as $col_num => $header) {
                $value = $worksheet->getCellByColumnAndRow($col_num, $rowIndex)->getCalculatedValue();
                $row_data[strtolower(trim($header))] = $value;
            }

            $batch_data[] = $row_data;
            $total_rows_processed++;

            // Process batch when it reaches the batch size
            if (count($batch_data) >= $batch_size) {
                $batch_result = $this->process_variant_batch($batch_data);

                $total_variants_inserted += $batch_result['variants_inserted'];
                $total_categories_inserted += $batch_result['categories_inserted'];

                // Clear batch data to free memory
                $batch_data = array();

                // Force garbage collection if available
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }

                error_log("MTHFR: Processed batch of {$batch_size} rows. Total processed: {$total_rows_processed}");
            }

            // Safety limit to prevent runaway processing
            if ($total_rows_processed >= 50000) {
                error_log("MTHFR: Reached safety processing limit (50,000 rows) for file");
                break;
            }
        }

        // Process remaining batch data
        if (!empty($batch_data)) {
            $batch_result = $this->process_variant_batch($batch_data);
            $total_variants_inserted += $batch_result['variants_inserted'];
            $total_categories_inserted += $batch_result['categories_inserted'];
        }

        // Clean up spreadsheet object to free memory
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return array(
            'success' => true,
            'rows_processed' => $total_rows_processed,
            'variants_inserted' => $total_variants_inserted,
            'categories_inserted' => $total_categories_inserted,
            'rows_skipped' => $total_rows_skipped
        );
    }

    /**
     * Process a batch of variant rows for improved performance
     */
    private function process_variant_batch($batch_data) {
        if (empty($batch_data)) {
            error_log('MTHFR: process_variant_batch called with empty batch_data');
            return array('variants_inserted' => 0, 'categories_inserted' => 0);
        }

        error_log('MTHFR: Processing batch of ' . count($batch_data) . ' rows');

        $variants_inserted = 0;
        $categories_inserted = 0;

        // Prepare batch data for database inserts
        $variant_batch = array();
        $category_batch = array();
        $tag_batch = array();
        $pathways_to_insert = array();

        foreach ($batch_data as $row_data) {
            // Extract data using multiple possible field names
            $rsid = $this->get_field_value($row_data, array('RSID', 'rsid', 'SNP ID', 'rs_id', 'SNP_ID', 'snp_id'));
            $gene = $this->get_field_value($row_data, array('Gene', 'gene', 'GENE', 'Gene Name', 'gene_name'));
            $snp = $this->get_field_value($row_data, array('SNP', 'snp', 'Variant', 'variant', 'SNP Name', 'snp_name'));
            $risk = $this->get_field_value($row_data, array('Risk', 'risk', 'Risk Allele', 'risk_allele', 'Risk_Allele'));
            $pathway = $this->get_field_value($row_data, array('Pathway', 'pathway', 'Category', 'category'));
            $report_name = $this->get_field_value($row_data, array('Report Name', 'report_name', 'Report_Name', 'report'));
            $info = $this->get_field_value($row_data, array('Info', 'info', 'Description', 'description', 'Details'));
            $video = $this->get_field_value($row_data, array('Video', 'video', 'Video Link', 'video_link'));
            $tags = $this->get_field_value($row_data, array('Tags', 'tags', 'Keywords', 'keywords'));

            // Validate required fields
            if (empty($rsid) || strpos($rsid, 'rs') !== 0) {
                $skipped_rows++;
                continue; // Skip invalid RSID
            }

            if (empty($gene)) {
                $skipped_rows++;
                continue; // Skip missing gene
            }

            if (empty($risk)) {
                $skipped_rows++;
                continue; // Skip missing risk allele
            }

            // Determine pathway if not provided
            if (empty($pathway)) {
                $pathway = $this->determine_pathway_from_gene($gene);
            }

            // Prepare variant data
            $variant_data = array(
                'rsid' => $rsid,
                'gene' => $gene,
                'snp_name' => $snp ?: null,
                'risk_allele' => $risk ?: null,
                'info' => $info ?: null,
                'video' => $video ?: null,
                'report_name' => $report_name ?: null,
                'tags' => $tags ?: null,
                'categories' => $pathway ?: null
            );

            $variant_batch[] = $variant_data;

            // Track pathways for global insertion (if needed elsewhere)
            if (!empty($pathway)) {
                $pathways_to_insert[] = $pathway;
            }

            // Prepare tag data
            if (!empty($tags)) {
                $tag_list = array_map('trim', explode(',', $tags));
                $tag_batch[$rsid] = array_filter($tag_list);
            }
        }

        error_log('MTHFR: Prepared ' . count($variant_batch) . ' variants for batch insert');

        // Batch insert variants
        if (!empty($variant_batch)) {
            try {
                $variant_ids = MTHFR_Database::batch_insert_variants($variant_batch);
                $variants_inserted = count($variant_ids);
                error_log('MTHFR: Batch inserted ' . $variants_inserted . ' variants');

                // Process tags for inserted variants (categories are now stored in variants table)
                if (!empty($variant_ids)) {
                    // Create mapping of RSID to variant ID
                    $rsid_to_id_map = array();
                    foreach ($variant_batch as $index => $variant) {
                        if (isset($variant_ids[$index])) {
                            $rsid_to_id_map[$variant['rsid']] = $variant_ids[$index];
                        }
                    }

                    // Batch insert tags
                    if (!empty($tag_batch)) {
                        $tag_data = array();
                        foreach ($tag_batch as $rsid => $tags) {
                            if (isset($rsid_to_id_map[$rsid])) {
                                foreach ($tags as $tag) {
                                    if (!empty($tag)) {
                                        $tag_data[] = array(
                                            'variant_id' => $rsid_to_id_map[$rsid],
                                            'tag_name' => $tag
                                        );
                                    }
                                }
                            }
                        }

                        if (!empty($tag_data)) {
                            try {
                                MTHFR_Database::batch_insert_variant_tags($tag_data);
                                error_log('MTHFR: Batch inserted ' . count($tag_data) . ' tags');
                            } catch (Exception $e) {
                                error_log('MTHFR: Error inserting tags: ' . $e->getMessage());
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('MTHFR: Error inserting variants: ' . $e->getMessage());
            }
        }

        // Insert pathways (these are global, not per-variant)
        $unique_pathways = array_unique($pathways_to_insert);
        foreach ($unique_pathways as $pathway) {
            MTHFR_Database::insert_pathway($pathway);
        }

        error_log('MTHFR: Batch processing completed - variants: ' . $variants_inserted . ' (categories stored in variants table)');

        return array(
            'variants_inserted' => $variants_inserted,
            'categories_inserted' => $variants_inserted // Categories are now stored in variants table
        );
    }

    /**
     * Process a single variant row (fallback method for compatibility)
     */
    private function process_variant_row($row_data) {
        // Extract data using multiple possible field names
        $rsid = $this->get_field_value($row_data, array('RSID', 'rsid', 'SNP ID', 'rs_id', 'SNP_ID', 'snp_id'));
        $gene = $this->get_field_value($row_data, array('Gene', 'gene', 'GENE', 'Gene Name', 'gene_name'));
        $snp = $this->get_field_value($row_data, array('SNP', 'snp', 'Variant', 'variant', 'SNP Name', 'snp_name'));
        $risk = $this->get_field_value($row_data, array('Risk', 'risk', 'Risk Allele', 'risk_allele', 'Risk_Allele'));
        $pathway = $this->get_field_value($row_data, array('Pathway', 'pathway', 'Category', 'category'));
        $report_name = $this->get_field_value($row_data, array('Report Name', 'report_name', 'Report_Name', 'report'));
        $info = $this->get_field_value($row_data, array('Info', 'info', 'Description', 'description', 'Details'));
        $video = $this->get_field_value($row_data, array('Video', 'video', 'Video Link', 'video_link'));
        $tags = $this->get_field_value($row_data, array('Tags', 'tags', 'Keywords', 'keywords'));

        // Validate required fields
        if (empty($rsid) || strpos($rsid, 'rs') !== 0) {
            return array('success' => false, 'categories_count' => 0);
        }

        if (empty($gene)) {
            return array('success' => false, 'categories_count' => 0);
        }

        if (empty($risk)) {
            return array('success' => false, 'categories_count' => 0);
        }

        // Determine pathway if not provided
        if (empty($pathway)) {
            $pathway = $this->determine_pathway_from_gene($gene);
        }

        // Prepare variant data
        $variant_data = array(
            'rsid' => $rsid,
            'gene' => $gene,
            'snp_name' => $snp ?: null,
            'risk_allele' => $risk ?: null,
            'info' => $info ?: null,
            'video' => $video ?: null,
            'report_name' => $report_name ?: null,
            'tags' => $tags ?: null,
            'categories' => $pathway ?: null
        );

        // Insert variant
        $variant_id = MTHFR_Database::insert_variant($variant_data);

        if (!$variant_id) {
            error_log("MTHFR: Failed to insert variant {$rsid} - {$gene}");
            return array('success' => false, 'categories_count' => 0);
        }

        // Categories are now stored in the variants table
        $categories_count = !empty($pathway) ? 1 : 0;

        // Insert tags
        if (!empty($tags)) {
            $tag_list = array_map('trim', explode(',', $tags));
            foreach ($tag_list as $tag) {
                if (!empty($tag)) {
                    MTHFR_Database::insert_variant_tag($variant_id, $tag);
                }
            }
        }

        // Insert pathway if it doesn't exist
        if (!empty($pathway)) {
            MTHFR_Database::insert_pathway($pathway);
        }

        return array(
            'success' => true,
            'categories_count' => $categories_count
        );
    }

    /**
     * Get field value with fallback options
     */
    private function get_field_value($row_data, $possible_names) {
        foreach ($possible_names as $name) {
            if (isset($row_data[strtolower(trim($name))])) {
                $value = $row_data[strtolower(trim($name))];
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        }
        return '';
    }

    /**
     * Determine pathway from gene name
     */
    private function determine_pathway_from_gene($gene) {
        $gene_pathway_map = array(
            'MTHFR' => 'Methylation & Methionine/Homocysteine Pathways',
            'MTR' => 'Methylation & Methionine/Homocysteine Pathways',
            'MTRR' => 'Methylation & Methionine/Homocysteine Pathways',
            'AHCY' => 'Methylation & Methionine/Homocysteine Pathways',
            'BHMT' => 'Methylation & Methionine/Homocysteine Pathways',
            'COMT' => 'COMT Activity',
            'ALDH2' => 'COMT Activity',
            'ALDH3A2' => 'COMT Activity',
            'ANK3' => 'COMT Activity',
            'CACNA1C' => 'COMT Activity',
            'CYP1A1' => 'COMT Activity',
            'CYP1B1' => 'COMT Activity',
            'DBH' => 'COMT Activity',
            'DRD1' => 'COMT Activity',
            'DRD2' => 'COMT Activity',
            'DRD3' => 'COMT Activity',
            'DRD4' => 'COMT Activity',
            'MAOB' => 'COMT Activity',
            'PAH' => 'COMT Activity',
            'PNMT' => 'COMT Activity',
            'TH' => 'COMT Activity',
            'CBS' => 'Trans-Sulfuration Pathway',
            'GSTP1' => 'Liver Detox',
            'CYP2D6' => 'Liver Detox',
            'CYP2C19' => 'Liver Detox',
            'ACE2' => 'HLA',
            'TNF' => 'HLA',
            'BCMO1' => 'Eye Health',
            'GAD1' => 'Neurotransmitter Pathway: Glutamate & GABA',
            'GAD2' => 'Neurotransmitter Pathway: Glutamate & GABA',
            'GAD' => 'Neurotransmitter Pathway: Glutamate & GABA'
        );

        if (empty($gene)) return 'Primary SNPs';

        $gene_upper = strtoupper(trim($gene));

        // Exact match
        if (isset($gene_pathway_map[$gene_upper])) {
            return $gene_pathway_map[$gene_upper];
        }

        // Split composite names
        foreach (preg_split('/[\/,\s\-]+/', $gene_upper) as $part) {
            if ($part === '') continue;
            if (isset($gene_pathway_map[$part])) {
                return $gene_pathway_map[$part];
            }

            // Prefix match
            foreach ($gene_pathway_map as $needle => $pathway) {
                if (stripos($part, $needle) === 0) {
                    return $pathway;
                }
            }
        }

        // Substring fallback
        foreach ($gene_pathway_map as $needle => $pathway) {
            if (stripos($gene_upper, $needle) !== false) {
                return $pathway;
            }
        }

        return 'Primary SNPs';
    }
}