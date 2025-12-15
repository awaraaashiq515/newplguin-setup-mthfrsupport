<?php
/**
 * MTHFR Report Generator Class - Modern Version
 * Main class for generating genetic reports with proper JSON structure
 */

namespace MTHFR\Core\Report;

use MTHFR\Core\Report\Exception;

if (!defined('ABSPATH')) {
    exit;
}

class ReportGenerator {

    private const EXCIPIENT_KEYWORDS = [
        'castor oil', 'sodium deoxycholate', 'mercury', 'potassium chloride', 'beta-propiolactone',
        'polysorbate 20', 'gentamicin sulfate', 'formaldehyde', 'acetone', 'sorbitol', 'lactose',
        'insect cell', 'a-tocopheryl hydrogen succinate', 'amphotericin b', 'plasdone c',
        'magnesium stearate', 'benzethonium chloride', 'ovalbumin', 'polysorbate 80', 'sucrose',
        'sodium chloride', 'dextrose', 'polymyxin b', 'urea', 'gelatin', 'hydrocortisone',
        'fd&c yellow #6 aluminum lake dye', 'calcium chloride', 'sodium borate', 'protamine sulphate',
        'd-fructose', 'phenol red', 'nonylphenol ethoxylate', 'microcrystalline cellulose',
        'magnesium sulfate', 'disodium phosphate', 'phosphate-buffered saline', 'd-mannose',
        'sodium taurodeoxycholate', 'human serum albumin', 'aluminum sulfate', 'l-tyrosine'
    ];

    private const COVID_KEYWORDS = ['covid', 'sars-cov-2', 'coronavirus', 'immune response'];

    private const VARIANT_KEYWORDS = [
        'methylation', 'mthfr', 'comt', 'cbs', 'mtr', 'mtrr', 'snp', 'rs', 'allele',
        'genotype', 'pathway', 'enzyme', 'detox', 'neurotransmitter', 'gene', 'alzheimer',
        'cardio', 'lipid', 'cannabinoid', 'celiac', 'gluten', 'clotting', 'factor',
        'eye', 'health', 'glyoxylate', 'metabolic', 'hla', 'iga', 'ige', 'igg',
        'iron', 'uptake', 'transport', 'liver', 'phase', 'mitochondrial', 'function',
        'molybdenum', 'glutamate', 'gaba', 'serotonin', 'dopamine', 'immune', 'pentose',
        'phosphate', 'thiamin', 'thiamine', 'degradation', 'thyroid', 'trans-sulfuration',
        'yeast', 'alcohol', 'metabolism'
    ];

    /**
     * Main report generation method
     */
    public static function generate_report($upload_id, $order_id, $product_name, $has_subscription = false) {
        try {
            error_log('MTHFR: Starting report generation');
            error_log("MTHFR: Parameters: upload_id={$upload_id}, order_id={$order_id}, product_name={$product_name}");

            // Initialize Excel databases
            if (class_exists('\MTHFR\Core\Database\ExcelDatabase')) {
                \MTHFR\Core\Database\ExcelDatabase::initialize_databases();
            } else {
                throw new Exception('Excel Database system not available');
            }

            // Get genetic data with filename
            $upload_info = self::get_genetic_data_with_filename($upload_id);
            $genetic_data = $upload_info['genetic_data'];
            $original_filename = $upload_info['filename'];

            // Determine report type
            $report_type = self::determine_report_type($product_name);
            error_log("MTHFR: Report type determined: {$report_type}");

            // Generate JSON report
            $json_report = self::create_json_report($genetic_data, $report_type);

            // Save JSON report
            $json_report_path = self::save_json_report_file($upload_id, $order_id, $report_type, $json_report, $original_filename);

            // Generate PDF using optimized generator
            $pdf_result = self::generate_pdf_with_validation($json_report, $product_name, $report_type, $has_subscription, $original_filename);
            $pdf_report_path = null;
            if ($pdf_result['success']) {
                $pdf_report_path = self::save_pdf_report_file($upload_id, $order_id, $report_type, $pdf_result['content'], $original_filename);
            } else {
                error_log('MTHFR: PDF generation failed: ' . $pdf_result['error']);
            }

            // Fetch order status
            $order_status = 'unknown';
            if (function_exists('wc_get_order')) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order_status = $order->get_status();
                }
            }

            // Save to database
            $db_success = self::save_to_database($upload_id, $order_id, $report_type, $product_name,
                $json_report_path, $pdf_report_path, $json_report, $order_status);

            error_log('MTHFR: Report generation completed successfully');

            return array(
                'success' => true,
                'message' => $report_type . ' report generated successfully',
                'json_report_path' => $json_report_path,
                'pdf_report_path' => $pdf_report_path,
                'report_type' => $report_type,
                'variants_processed' => count($json_report),
                'database_saved' => $db_success,
                'pdf_generated' => $pdf_result['success'],
                'upload_id' => $upload_id,
                'order_id' => $order_id,
                'original_filename' => $original_filename,
                'timestamp' => current_time('c')
            );

        } catch (Exception $e) {
            error_log('MTHFR: Report generation failed - ' . $e->getMessage());

            // Save error to database
            try {
                if (class_exists('\MTHFR_Database')) {
                    \MTHFR_Database::save_report(
                        $upload_id, $order_id, 'Failed', 'Failed Report',
                        null, null, json_encode(array('error' => $e->getMessage())),
                        'failed', $has_subscription, $e->getMessage()
                    );
                }
            } catch (Exception $db_e) {
                error_log('MTHFR: Failed to save error status: ' . $db_e->getMessage());
            }

            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'upload_id' => $upload_id,
                'order_id' => $order_id,
                'timestamp' => current_time('c')
            );
        }
    }

    /**
     * Generate PDF with validation using optimized generator
     */
    private static function generate_pdf_with_validation($json_data, $product_name, $report_type, $has_subscription, $original_filename = null) {
        try {
            // Use the optimized PDF generator from the src directory
            if (!class_exists('\MTHFR_PDF_Generator')) {
                return array(
                    'success' => false,
                    'error' => 'Optimized PDF Generator class not available'
                );
            }

            error_log('MTHFR: Starting optimized PDF generation...');

            $enhanced_data = array(
                'raw_variants' => is_array($json_data) ? $json_data : [],
                'report_type' => $report_type,
                'file_info' => array(
                    'original_filename' => $original_filename
                )
            );

            $enhanced_data = self::filter_enhanced_input_by_report($enhanced_data, $report_type);

            $pdf_content = \MTHFR_PDF_Generator::generate_pdf(
                $enhanced_data,
                $product_name,
                $report_type,
                $has_subscription
            );

            if (!$pdf_content) {
                return array(
                    'success' => false,
                    'error' => 'PDF generation returned empty content'
                );
            }

            error_log('MTHFR: Optimized PDF generated successfully, size: ' . strlen($pdf_content) . ' bytes');

            return array(
                'success' => true,
                'content' => $pdf_content,
                'content_type' => 'pdf'
            );

        } catch (Exception $e) {
            error_log('MTHFR: Optimized PDF generation exception: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => 'Optimized PDF generation failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get genetic data from upload with filename extraction
     */
    private static function get_genetic_data_with_filename($upload_id) {
        error_log('MTHFR: Getting genetic data with filename for upload_id: ' . $upload_id);

        // Get upload record from database
        $upload_data = null;
        if (class_exists('\MTHFR_Database')) {
            $upload_data = \MTHFR_Database::get_upload_data($upload_id);
        }

        if (!$upload_data) {
            error_log('MTHFR: No upload record found for upload_id: ' . $upload_id);
            throw new Exception('Upload record not found');
        }

        // Extract filename from database record
        $original_filename = 'unknown_file';
        if (isset($upload_data->file_name) && !empty($upload_data->file_name)) {
            $original_filename = $upload_data->file_name;
        } elseif (isset($upload_data->file_path) && !empty($upload_data->file_path)) {
            $original_filename = basename($upload_data->file_path);
        }

        // Clean filename
        $clean_filename = pathinfo($original_filename, PATHINFO_FILENAME);
        $clean_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $clean_filename);

        if (!isset($upload_data->file_path) || !file_exists($upload_data->file_path)) {
            error_log('MTHFR: Upload file not found: ' . ($upload_data->file_path ?? 'null'));
            throw new Exception('Uploaded genetic file not found');
        }

        error_log('MTHFR: Processing uploaded file: ' . $upload_data->file_path);

        $genetic_data = array();

        // Process file based on extension
        if (class_exists('\MTHFR_ZIP_Processor')) {
            $file_extension = pathinfo($upload_data->file_path, PATHINFO_EXTENSION);

            if (strtolower($file_extension) === 'zip') {
                $genetic_data = \MTHFR_ZIP_Processor::process_zip($upload_data->file_path, $upload_id);
            } else {
                $genetic_data = self::process_text_file($upload_data->file_path);
            }
        }

        if (empty($genetic_data)) {
            throw new Exception('No genetic variants could be extracted from the uploaded file');
        }

        error_log('MTHFR: Successfully loaded ' . count($genetic_data) . ' genetic variants from: ' . $original_filename);

        return array(
            'genetic_data' => $genetic_data,
            'filename' => $clean_filename,
            'original_filename' => $original_filename
        );
    }

    private static function determine_report_type($product_name) {
        $product_name_lower = strtolower($product_name);

        if (strpos($product_name_lower, 'covid') !== false) {
            return 'Covid';
        } elseif (strpos($product_name_lower, 'methylation') !== false || strpos($product_name_lower, 'meth') !== false) {
            return 'Methylation';
        } elseif (strpos($product_name_lower, 'excipient') !== false) {
            return 'Excipient';
        } elseif (strpos($product_name_lower, 'detox') !== false) {
            return 'Detox';
        } elseif (strpos($product_name_lower, 'immune') !== false) {
            return 'Immune';
        } elseif (strpos($product_name_lower, 'variant') !== false) {
            return 'Variant';
        } elseif (strpos($product_name_lower, 'bundled') !== false) {
            return 'Bundled';
        } else {
            return 'Variant';
        }
    }

    /**
     * Normalize string for comparison
     */
    private static function norm($value) {
        $value = is_array($value) ? implode(' ', $value) : (string)$value;
        $value = mb_strtolower($value, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Check if haystack contains any needles
     */
    private static function contains_any(string $haystack, array $needles): bool {
        foreach ($needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if row looks like a genetic variant
     */
    private static function looks_like_variant(array $row): bool {
        $rsid = (string)($row['rsid'] ?? $row['SNP ID'] ?? '');
        $genotype = (string)($row['genotype'] ?? $row['Your Allele'] ?? '');
        $gene = (string)($row['gene'] ?? $row['Gene'] ?? '');
        $snp = (string)($row['SNP'] ?? $row['SNP Name'] ?? '');

        if ($rsid && preg_match('/^rs\d+$/i', $rsid)) return true;
        if ($genotype && preg_match('/^[ACGT]{1,2}\/?[ACGT]{0,2}$/i', $genotype)) return true;
        if ($gene && $snp) return true;

        return false;
    }

    /**
     * Classify row by type
     */
    private static function classify_row(array $row): string {
        $content = self::norm([
            $row['SNP Name'] ?? $row['SNP_Name'] ?? '',
            $row['Description'] ?? $row['Info'] ?? '',
            $row['Group'] ?? $row['Category'] ?? $row['category'] ?? '',
            $row['Ingredient'] ?? $row['Name'] ?? '',
            $row['Panel'] ?? '',
        ]);

        // Priority order
        if (self::contains_any($content, self::COVID_KEYWORDS)) return 'covid';
        if (self::contains_any($content, self::EXCIPIENT_KEYWORDS)) return 'excipients';
        if (self::contains_any($content, self::VARIANT_KEYWORDS)) return 'variant';

        // Fallback check: looks like variant (rsID pattern etc.)
        if (self::looks_like_variant($row)) return 'variant';

        return 'other';
    }

    private static function filter_enhanced_input_by_report($enhanced_data, string $report_type) {
        $report_type = mb_strtolower(trim($report_type));
        $rows = [];

        if (is_array($enhanced_data)) {
            $rows = isset($enhanced_data['raw_variants']) && is_array($enhanced_data['raw_variants'])
                ? $enhanced_data['raw_variants'] : $enhanced_data;
        }

        // Get category filters for this report type
        $category_filters = self::get_category_filters_for_report_type($report_type);

        $keep = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;

            // Determine category for filtering
            $variant_category = $row['Group'] ?? $row['Category'] ?? $row['rs10306114'] ?? '';

            if (empty($variant_category)) {
                $variant_category = self::determine_pathway_from_gene($row['Gene'] ?? '');
            }

            // Apply category filtering
            if ($category_filters && !in_array($variant_category, $category_filters)) {
                continue;
            }

            $keep[] = $row;
        }

        if (isset($enhanced_data['raw_variants']) && is_array($enhanced_data)) {
            $enhanced_data['raw_variants'] = $keep;
            return $enhanced_data;
        }

        return $keep;
    }

    /**
     * Determine pathway from gene name
     */
    public static function determine_pathway_from_gene($gene) {
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

    /**
     * Get category filters for report type
     */
    private static function get_category_filters_for_report_type($report_type) {

        $report_type_lower = strtolower(trim($report_type));

        switch ($report_type_lower) {
            case 'excipient':
                return array(
                    'Castor Oil',
                    'Sodium Deoxycholate',
                    'Mercury',
                    'Potassium Chloride',
                    'Beta-Propiolactone',
                    'Polysorbate 20',
                    'Gentamicin Sulfate',
                    'Formaldehyde',
                    'Acetone',
                    'Sorbitol',
                    'Lactose',
                    'Insect Cell',
                    'A-Tocopheryl Hydrogen Succinate',
                    'Amphotericin B',
                    'Plasdone C',
                    'Magnesium Stearate',
                    'Benzethonium Chloride',
                    'Ovalbumin',
                    'Polysorbate 80',
                    'Sucrose',
                    'Sodium Chloride',
                    'Dextrose',
                    'Polymyxin B',
                    'Urea',
                    'Gelatin',
                    'Hydrocortisone',
                    'FD&C Yellow #6 Aluminum Lake Dye',
                    'Calcium Chloride',
                    'Sodium Borate',
                    'Protamine Sulphate',
                    'D-Fructose',
                    'Phenol Red',
                    'Nonylphenol Ethoxylate',
                    'Microcrystalline Cellulose',
                    'Magnesium Sulfate',
                    'Disodium Phosphate',
                    'Phosphate-Buffered Saline',
                    'D-Mannose',
                    'Sodium Taurodeoxycholate',
                    'Human Serum Albumin',
                    'Aluminum Sulfate',
                    'L-Tyrosine',
                );

            case 'methylation':
            case 'meth':
                return array(
                    'Methylation & Methionine/Homocysteine Pathways'
                );

            case 'detox':
                return array(
                    'Liver Detox',
                    'Chemical Detoxification',
                    'Heavy Metal Detox',
                    'DNA Repair'
                );

            case 'immune':
                return array(
                    'HLA',
                    'Other Immune Factors'
                );

              case 'covid':
            return array(
                'Covid'
                
            );

            case 'eye_health':
                return array(
                    'Eye Health'
                );

            case 'bundled':
                // Bundled reports combine Variant, Methylation, Excipient, and COVID categories
                $variant_categories = array(
                    'Alzheimers/Cardio/Lipid',
                    'COMT Activity',
                    'Cannabinoid Pathway',
                    'Celiac Disease/Gluten Intolerance',
                    'Clotting Factors',
                    'Eye Health',
                    'Glyoxylate Metabolic Process',
                    'HLA',
                    'IgA',
                    'IgE',
                    'IgG',
                    'Iron Uptake & Transport',
                    'Liver Detox - Phase I',
                    'Liver Detox - Phase II',
                    'Methylation & Methionine/Homocysteine Pathways',
                    'Mitochondrial Function',
                    'Molybdenum',
                    'Neurotransmitter Pathway: Glutamate & GABA',
                    'Neurotransmitter Pathway: Serotonin & Dopamine',
                    'Other Immune Factors',
                    'Pentose Phosphate Pathway',
                    'Thiamin/Thiamine Degradation',
                    'Thyroid',
                    'Trans-Sulfuration Pathway',
                    'Yeast/Alcohol Metabolism'
                );

                $methylation_categories = array(
                    'Methylation & Methionine/Homocysteine Pathways'
                );

                $excipient_categories = array(
                    'Castor Oil',
                    'Sodium Deoxycholate',
                    'Mercury',
                    'Potassium Chloride',
                    'Beta-Propiolactone',
                    'Polysorbate 20',
                    'Gentamicin Sulfate',
                    'Formaldehyde',
                    'Acetone',
                    'Sorbitol',
                    'Lactose',
                    'Insect Cell',
                    'A-Tocopheryl Hydrogen Succinate',
                    'Amphotericin B',
                    'Plasdone C',
                    'Magnesium Stearate',
                    'Benzethonium Chloride',
                    'Ovalbumin',
                    'Polysorbate 80',
                    'Sucrose',
                    'Sodium Chloride',
                    'Dextrose',
                    'Polymyxin B',
                    'Urea',
                    'Gelatin',
                    'Hydrocortisone',
                    'FD&C Yellow #6 Aluminum Lake Dye',
                    'Calcium Chloride',
                    'Sodium Borate',
                    'Protamine Sulphate',
                    'D-Fructose',
                    'Phenol Red',
                    'Nonylphenol Ethoxylate',
                    'Microcrystalline Cellulose',
                    'Magnesium Sulfate',
                    'Disodium Phosphate',
                    'Phosphate-Buffered Saline',
                    'D-Mannose',
                    'Sodium Taurodeoxycholate',
                    'Human Serum Albumin',
                    'Aluminum Sulfate',
                    'L-Tyrosine',
                );

                $covid_categories = array(
                    'Covid'
                );

                $all_categories = array_merge($variant_categories, $methylation_categories, $excipient_categories, $covid_categories);
                return array_unique($all_categories);

            case 'variant':
                return array(
                    'Alzheimers/Cardio/Lipid',
                    'COMT Activity',
                    'Cannabinoid Pathway',
                    'Celiac Disease/Gluten Intolerance',
                    'Clotting Factors',
                    'Eye Health',
                    'Glyoxylate Metabolic Process',
                    'HLA',
                    'IgA',
                    'IgE',
                    'IgG',
                    'Iron Uptake & Transport',
                    'Liver Detox - Phase I',
                    'Liver Detox - Phase II',
                    'Methylation & Methionine/Homocysteine Pathways',
                    'Mitochondrial Function',
                    'Molybdenum',
                    'Neurotransmitter Pathway: Glutamate & GABA',
                    'Neurotransmitter Pathway: Serotonin & Dopamine',
                    'Other Immune Factors',
                    'Pentose Phosphate Pathway',
                    'Thiamin/Thiamine Degradation',
                    'Thyroid',
                    'Trans-Sulfuration Pathway',
                    'Yeast/Alcohol Metabolism'
                );

            default:
                return null; // No filtering for general variant report
        }
    }

    /**
     * Format user genotype with improved edge case handling
     */
    private static function format_user_genotype($variant) {
        // Try multiple ways to get genotype
        $allele1 = $variant['allele1'] ?? '';
        $allele2 = $variant['allele2'] ?? '';
        $genotype = $variant['genotype'] ?? '';

        // Method 1: Combine allele1 + allele2
        if (!empty($allele1) && !empty($allele2)) {
            $result = strtoupper(trim($allele1) . trim($allele2));
            if (preg_match('/^[ATCG]{2}$/', $result)) {
                return $result;
            }
        }

        // Method 2: Use existing genotype field
        if (!empty($genotype)) {
            $cleaned = strtoupper(trim($genotype));

            // Handle common genotype formats
            if (preg_match('/^[ATCG]{2}$/', $cleaned)) {
                return $cleaned;
            }

            // Handle formats like "A/T", "A;T", "A T", "A|T", "A--T", etc.
            $parts = preg_split('/[\/;,\s\|\-\-\-]+/', trim($genotype));
            if (count($parts) == 2) {
                $allele1 = strtoupper(trim($parts[0]));
                $allele2 = strtoupper(trim($parts[1]));
                // Validate alleles are single nucleotides
                if (preg_match('/^[ATCG]$/', $allele1) && preg_match('/^[ATCG]$/', $allele2)) {
                    return $allele1 . $allele2;
                }
            }

            // Handle formats like "AA", "AT", "TT" (already handled above)
            // Handle formats like "A A" (space separated)
            if (preg_match('/^[ATCG]\s+[ATCG]$/', $cleaned)) {
                $cleaned = preg_replace('/\s+/', '', $cleaned);
                if (preg_match('/^[ATCG]{2}$/', $cleaned)) {
                    return $cleaned;
                }
            }
        }

        // Method 3: Try other common field names
        $other_fields = array('your_genotype', 'alleles', 'calls', 'variant', 'Your Allele', 'genotype_data');
        foreach ($other_fields as $field) {
            if (isset($variant[$field]) && !empty($variant[$field])) {
                $value = strtoupper(trim($variant[$field]));
                if (preg_match('/^[ATCG]{2}$/', $value)) {
                    return $value;
                }

                // Also try splitting this field
                $parts = preg_split('/[\/;,\s\|\-\-\-]+/', trim($variant[$field]));
                if (count($parts) == 2) {
                    $allele1 = strtoupper(trim($parts[0]));
                    $allele2 = strtoupper(trim($parts[1]));
                    if (preg_match('/^[ATCG]$/', $allele1) && preg_match('/^[ATCG]$/', $allele2)) {
                        return $allele1 . $allele2;
                    }
                }
            }
        }

        // Method 4: Check for single allele fields that might be duplicated
        if (!empty($allele1) && empty($allele2)) {
            $allele1 = strtoupper(trim($allele1));
            if (preg_match('/^[ATCG]$/', $allele1)) {
                // Assume homozygous
                return $allele1 . $allele1;
            }
        }

        return 'Unknown';
    }

    /**
     * Create SNP name
     */
    private static function create_snp_name($gene, $snp_info) {
        $gene = trim($gene ?? '');
        $snp_info = trim($snp_info ?? '');

        // Validate gene name
        if (empty($gene) || in_array(strtolower($gene), array('nan', 'null'))) {
            $gene = 'Unknown Gene';
        }

        // Validate SNP info
        if (!empty($snp_info) && !in_array(strtolower($snp_info), array('nan', 'null'))) {
            return $gene . ' ' . $snp_info;
        }

        return $gene . ' variant';
    }

    /**
     * Calculate result based on genotype and risk allele
     */
    private static function calculate_result($genotype, $risk_allele) {
        if (empty($genotype) || empty($risk_allele) || $genotype === 'Unknown') {
            return 'Unknown';
        }

        $genotype = strtoupper(trim($genotype));
        $risk_allele = strtoupper(trim($risk_allele));

        // Validate inputs
        if (!preg_match('/^[ATCG]{2}$/', $genotype) || !preg_match('/^[ATCG]$/', $risk_allele)) {
            return 'Unknown';
        }

        $alleles = str_split($genotype);
        $risk_count = 0;

        foreach ($alleles as $allele) {
            if ($allele === $risk_allele) {
                $risk_count++;
            }
        }

        switch ($risk_count) {
            case 0: return '-/-';
            case 1: return '+/-';
            case 2: return '+/+';
            default: return 'Unknown';
        }
    }

    /**
     * Process text file genetic data
     */
    private static function process_text_file($file_path) {
        $genetic_data = array();

        try {
            $content = file_get_contents($file_path);
            if (!$content) {
                return array();
            }

            $lines = explode("\n", $content);

            foreach ($lines as $line) {
                $line = trim($line);

                // Skip comments and headers
                if (empty($line) || $line[0] === '#' || strpos($line, 'rsid') === 0) {
                    continue;
                }

                // Parse tab-delimited: rsid, chromosome, position, allele1, allele2
                $parts = explode("\t", $line);

                if (count($parts) >= 5) {
                    $rsid = trim($parts[0]);
                    $allele1 = trim($parts[3]);
                    $allele2 = trim($parts[4]);

                    if (strpos($rsid, 'rs') === 0 && $allele1 && $allele2) {
                        $genetic_data[] = array(
                            'rsid' => $rsid,
                            'chromosome' => trim($parts[1]),
                            'position' => trim($parts[2]),
                            'allele1' => $allele1,
                            'allele2' => $allele2,
                            'genotype' => $allele1 . $allele2
                        );
                    }
                }
            }

            error_log('MTHFR: Parsed ' . count($genetic_data) . ' variants from text file');

        } catch (Exception $e) {
            error_log('MTHFR: Error processing text file: ' . $e->getMessage());
        }

        return $genetic_data;
    }

    /**
     * Create JSON report with proper structure
     */
    private static function create_json_report($genetic_data, $report_type) {

        error_log('MTHFR: Starting JSON report creation');

        // Validate genetic data input
        if (empty($genetic_data)) {
            throw new Exception('No genetic data available for processing');
        }

        error_log('MTHFR: Received ' . count($genetic_data) . ' genetic variants for report type: ' . $report_type);

        // Get category filters for this report type
        $category_filters = self::get_category_filters_for_report_type($report_type);
        if ($category_filters) {
            error_log('MTHFR: Applying category filters: ' . implode(', ', $category_filters));
        }

        // Get database for matching using lazy loading
        if (!class_exists('\MTHFR\Core\Database\Database')) {
            throw new Exception('Database matching system not available');
        }

        // Extract RSIDs from genetic data for lazy loading
        $user_rsids = array();
        foreach ($genetic_data as $variant) {
            $rsid = $variant['rsid'] ?? '';
            if (!empty($rsid) && strpos($rsid, 'rs') === 0) {
                $user_rsids[] = $rsid;
            }
        }

        if (empty($user_rsids)) {
            throw new Exception('No valid RSIDs found in genetic data');
        }

        error_log('MTHFR: Extracted ' . count($user_rsids) . ' unique RSIDs from user data');

        // Use lazy loading to get only relevant variants
        $database = \MTHFR\Core\Database\Database::get_variants_by_rsids($user_rsids);

        if (empty($database)) {
            throw new Exception('No genetic variants matched our database');
        }

        error_log('MTHFR: Lazy loaded database with ' . count($database) . ' matched RSIDs');

        // Match variants with database
        $matched_variants = array();
        $total_checked = 0;
        $valid_rsids = 0;
        $matched_count = 0;
        $filtered_count = 0;
        $processing_errors = array();

        foreach ($genetic_data as $variant) {
            $total_checked++;

            // Validate variant structure
            if (!is_array($variant)) {
                $processing_errors[] = "Non-array variant at index {$total_checked}";
                continue;
            }

            $rsid = $variant['rsid'] ?? '';

            if (empty($rsid) || strpos($rsid, 'rs') !== 0) {
                continue;
            }

            $valid_rsids++;

            // Look for match in database
            if (isset($database[$rsid])) {
                $matched_count++;
                $db_entries = $database[$rsid];

                // Handle both old single format and new multi format
                if (!isset($db_entries[0]) || !is_array($db_entries[0])) {
                    $db_entries = array($db_entries);
                }

                // Process each category for this RSID
                foreach ($db_entries as $db_entry) {
                    if (!is_array($db_entry)) {
                        continue;
                    }

                    // Determine category
                    $variant_category = $db_entry['Group'] ?? $db_entry['Category'] ?? $db_entry['rs10306114'] ?? '';

                    if (empty($variant_category)) {
                        $variant_category = self::determine_pathway_from_gene($db_entry['Gene'] ?? '');
                    }

                    // Apply category filtering
                    if ($category_filters && !in_array($variant_category, $category_filters)) {
                        $filtered_count++;
                        continue;
                    }

                    // Format genotype
                    try {
                        $user_genotype = self::format_user_genotype($variant);

                        if ($user_genotype === 'Unknown' || empty($user_genotype)) {
                            continue;
                        }

                        $risk_allele = $db_entry['Risk'] ?? 'Unknown';
                        $gene = $db_entry['Gene'] ?? 'Unknown Gene';
                        $snp = $db_entry['SNP'] ?? '';

                        $json_variant = array(
                            "SNP ID" => $rsid,
                            "SNP Name" => self::create_snp_name($gene, $snp),
                            "Risk Allele" => $risk_allele,
                            "Your Allele" => $user_genotype,
                            "Result" => self::calculate_result($user_genotype, $risk_allele),
                            "Report Name" => $db_entry['Report Name'] ?? ('MTHFRSupport ' . ucfirst($report_type) . ' Report v2.5'),
                            "Info" => $db_entry['Info'] ?? null,
                            "Video" => $db_entry['Video'] ?? null,
                            "Tags" => $db_entry['Tags'] ?? null,
                            "Group" => $variant_category
                        );

                        $matched_variants[] = $json_variant;

                    } catch (Exception $e) {
                        $processing_errors[] = "Error processing {$rsid}: " . $e->getMessage();
                        continue;
                    }
                }
            }
        }


        // Report statistics
        error_log("MTHFR: Matching statistics - Total: {$total_checked}, Valid RSIDs: {$valid_rsids}, Matched: {$matched_count}, Filtered: {$filtered_count}, Final variants: " . count($matched_variants));

        if (!empty($processing_errors)) {
            error_log("MTHFR: Processing errors: " . count($processing_errors));
        }

        // Validate results
        if (empty($matched_variants)) {
            if ($valid_rsids === 0) {
                throw new Exception('No valid RSIDs found in genetic data. Please check file format.');
            } elseif ($matched_count === 0) {
                throw new Exception('No genetic variants matched our database.');
            } elseif ($filtered_count > 0) {
                throw new Exception("All {$filtered_count} matched variants were filtered out by category restrictions for {$report_type} report.");
            } else {
                throw new Exception('No genetic variants could be processed successfully.');
            }
        }

        error_log('MTHFR: Successfully created JSON report with ' . count($matched_variants) . ' variant entries');

        return $matched_variants;
    }

    private static function save_json_report_file($upload_id, $order_id, $report_type, $json_report, $original_filename = null) {
        $clean_filename = sanitize_file_name(pathinfo($original_filename, PATHINFO_FILENAME));
        $timestamp = time();
        $filename = "report_json_{$clean_filename}_{$order_id}_{$timestamp}.json";

        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/user_reports/upload_' . $upload_id . '/';
        wp_mkdir_p($target_dir);

        $file_path = $target_dir . $filename;
        file_put_contents($file_path, json_encode($json_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
        return $file_path;
    }

    private static function save_pdf_report_file($upload_id, $order_id, $report_type, $pdf_data, $original_filename = null) {
        $clean_filename = sanitize_file_name(pathinfo($original_filename, PATHINFO_FILENAME));
        $timestamp = time();
        $filename = "MTHFRSupport_{$report_type}_{$clean_filename}_{$order_id}_{$timestamp}.pdf";

        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/user_reports/upload_' . $upload_id . '/';
        wp_mkdir_p($target_dir);

        $file_path = $target_dir . $filename;
        file_put_contents($file_path, $pdf_data);

        return $file_path;
    }

    private static function save_to_database($upload_id, $order_id, $report_type, $product_name, $json_report_path, $pdf_report_path, $json_report, $status) {
        if (class_exists('\MTHFR_Database')) {
            return \MTHFR_Database::save_report(
                $upload_id,
                $order_id,
                $report_type,
                $product_name,
                $json_report_path,
                $pdf_report_path,
                json_encode($json_report),
                $status
            );
        }
        return false;
    }
}