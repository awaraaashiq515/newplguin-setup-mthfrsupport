<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * MTHFR Report Generator Class - Fixed Production Version
 * Main class for generating genetic reports with proper JSON structure
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_Report_Generator {

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
    
    private const COVID_KEYWORDS = ['covid', 'sars-cov-2', 'coronavirus'];

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
   /**
     * Classify row by type - IMPROVED VERSION
     */
    private static function classify_row(array $row): string {
    $content = self::norm([
        $row['SNP Name'] ?? $row['SNP_Name'] ?? '',
        $row['Description'] ?? $row['Info'] ?? '',
        $row['Group'] ?? $row['Category'] ?? $row['category'] ?? $row['rs10306114'] ?? '',
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

    /**
     * Filter input data by report type
     */
   private static function filter_enhanced_input_by_report($enhanced_data, string $report_type) {
    $report_type = mb_strtolower(trim($report_type));
    $rows = [];
    
    if (is_array($enhanced_data)) {
        $rows = isset($enhanced_data['raw_variants']) && is_array($enhanced_data['raw_variants'])
            ? $enhanced_data['raw_variants'] : $enhanced_data;
    }
    
    $keep = [];
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        
        $bucket = self::classify_row($row);
        switch ($report_type) {
            case 'variant':
            case 'methylation':
                if ($bucket === 'variant') $keep[] = $row;
                break;
            case 'excipient':
                if ($bucket === 'excipients') $keep[] = $row;
                break;
            case 'covid':
                if ($bucket === 'covid') $keep[] = $row;
                break;
            case 'bundled':
                // ✅ Allow all categories that bundled is supposed to merge
                if (in_array($bucket, ['variant', 'excipients', 'covid', 'methylation'])) {
                    $keep[] = $row;
                }
                break;
        }
    }
    
    if (isset($enhanced_data['raw_variants']) && is_array($enhanced_data)) {
        $enhanced_data['raw_variants'] = $keep;
        return $enhanced_data;
    }
    
    return $keep;
}

    /**
     * Determine report type from product name
     */
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
     * Main report generation method
     */
    public static function generate_report($upload_id, $order_id, $product_name, $has_subscription = false) {



        
        try {
            error_log('MTHFR: Starting report generation');
            error_log("MTHFR: Parameters: upload_id={$upload_id}, order_id={$order_id}, product_name={$product_name}");
            
            // Initialize Excel databases
            if (class_exists('MTHFR_Excel_Database')) {
                MTHFR_Excel_Database::initialize_databases();
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
         
            // Generate PDF
           $pdf_result = self::generate_pdf_with_validation($json_report, $product_name, $report_type, $has_subscription, $original_filename);           
            $pdf_report_path = null;
            if ($pdf_result['success']) {
                $pdf_report_path = self::save_pdf_report_file($upload_id, $order_id, $report_type, $pdf_result['content'], $original_filename);
            } else {
                error_log('MTHFR: PDF generation failed: ' . $pdf_result['error']);
            }
            
            // Save to database
            $db_success = self::save_to_database($upload_id, $order_id, $report_type, $product_name, 
                $json_report_path, $pdf_report_path, $json_report, $has_subscription);
            
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
                if (class_exists('MTHFR_Database')) {
                    MTHFR_Database::save_report(
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
     * Get genetic data from upload with filename extraction
     */
    private static function get_genetic_data_with_filename($upload_id) {
        error_log('MTHFR: Getting genetic data with filename for upload_id: ' . $upload_id);
        
        // Get upload record from database
        $upload_data = null;
        if (class_exists('MTHFR_Database')) {
            $upload_data = MTHFR_Database::get_upload_data($upload_id);
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
        if (class_exists('MTHFR_ZIP_Processor')) {
            $file_extension = pathinfo($upload_data->file_path, PATHINFO_EXTENSION);
            
            if (strtolower($file_extension) === 'zip') {
                $genetic_data = MTHFR_ZIP_Processor::process_zip($upload_data->file_path, $upload_id);
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
        
        // Get database for matching
        if (!class_exists('MTHFR_Excel_Database')) {
            throw new Exception('Database matching system not available');
        }
        
        $database = MTHFR_Excel_Database::get_database();

        if (empty($database)) {
            throw new Exception('Genetic variant database not loaded');
        }
        
        error_log('MTHFR: Database loaded with ' . count($database) . ' unique RSIDs');
        
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
                    $variant_category = $db_entry['rs10306114'] ?? '';
                    
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
                            "Group" => $variant_category,
                            "rs10306114" => $variant_category
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
        error_log("MTHFR: Matching statistics - Total: {$total_checked}, Valid RSIDs: {$valid_rsids}, Matched: {$matched_count}, Final variants: " . count($matched_variants));
        
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

    /**
     * Get category filters for report type
     */
   private static function get_category_filters_for_report_type($report_type) {
    
    // $report_type_lower = strtolower($report_type);
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
            // ✅ Exclude: COMT Activity, Trans-Sulfuration Pathway, Yeast/Alcohol Metabolism
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
                'Immune Response',
                'Inflammatory Response',
                'HLA'
            );
            
        case 'covid':
            return array(
                'Covid',
                'Immune Response',
                'Inflammatory Response'
            );

        case 'eye_health':
            return array(
                'Eye Health'
            );

        case 'bundled':
    return array_merge(
        self::get_category_filters_for_report_type('excipient'),
        self::get_category_filters_for_report_type('methylation'),
        self::get_category_filters_for_report_type('covid'),
        self::get_category_filters_for_report_type('variant')
    );

        case 'variant':
            // ✅ FIX: Define specific categories for Variant Report
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
     * Format user genotype
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
            
            // Handle formats like "A/T", "A;T", "A T", "A|T"
            $parts = preg_split('/[\/;,\s\|]+/', trim($genotype));
            if (count($parts) == 2) {
                $allele1 = strtoupper(trim($parts[0]));
                $allele2 = strtoupper(trim($parts[1]));
                if (preg_match('/^[ATCG]$/', $allele1) && preg_match('/^[ATCG]$/', $allele2)) {
                    return $allele1 . $allele2;
                }
            }
        }
        
        // Method 3: Try other common field names
        $other_fields = array('your_genotype', 'alleles', 'calls', 'variant');
        foreach ($other_fields as $field) {
            if (isset($variant[$field]) && !empty($variant[$field])) {
                $value = strtoupper(trim($variant[$field]));
                if (preg_match('/^[ATCG]{2}$/', $value)) {
                    return $value;
                }
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
     * Generate PDF with validation
     */
private static function generate_pdf_with_validation($json_data, $product_name, $report_type, $has_subscription, $original_filename = null) {
        try {
  
            
            if (!class_exists('MTHFR_PDF_Generator')) {
                return array(
                    'success' => false,
                    'error' => 'PDF Generator class not available'
                );
            }
            
            error_log('MTHFR: Starting PDF generation...');
            
$enhanced_data = array(
    'raw_variants' => is_array($json_data) ? $json_data : [],
    'report_type' => $report_type,
    'file_info' => array(
        'original_filename' => $original_filename
    )
);
 
            $enhanced_data = self::filter_enhanced_input_by_report($enhanced_data, $report_type);

            $pdf_content = MTHFR_PDF_Generator::generate_pdf(
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
            
            error_log('MTHFR: PDF generated successfully, size: ' . strlen($pdf_content) . ' bytes');
            
            return array(
                'success' => true,
                'content' => $pdf_content,
                'content_type' => 'pdf'
            );
            
        } catch (Exception $e) {
            error_log('MTHFR: PDF generation exception: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => 'PDF generation failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Save JSON report file with proper structure - FIXED VERSION
     */
    /**
     * Save JSON report file with upload ID folder structure - UPDATED VERSION
     */
    private static function save_json_report_file($upload_id, $order_id, $report_type, $json_report, $original_filename = null) {
        $upload_dir = wp_upload_dir();
        $reports_dir = $upload_dir['basedir'] . '/user_reports';
        
        // Create upload ID specific folder
        $upload_folder = $reports_dir . '/upload_' . $upload_id;
        
        // Create directories if they don't exist
        if (!file_exists($reports_dir)) {
            wp_mkdir_p($reports_dir);
        }
        
        if (!file_exists($upload_folder)) {
            wp_mkdir_p($upload_folder);
            error_log("MTHFR: Created upload folder: {$upload_folder}");
        }
        
        $timestamp = date('Ymd_His');
        
        // Create filename with original filename if available
        if (!empty($original_filename) && $original_filename !== 'unknown_file') {
            $clean_filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $original_filename);
            $json_filename = "report_json_{$clean_filename}_{$order_id}_{$timestamp}.json";
        } else {
            $json_filename = "report_json_{$order_id}_{$timestamp}.json";
        }
        
        $json_report_path = $upload_folder . '/' . $json_filename;
        
        try {
            // Create properly structured JSON with metadata
            $structured_json = array(
                'report_metadata' => array(
                    'report_type' => $report_type,
                    'upload_id' => $upload_id,
                    'order_id' => $order_id,
                    'generated_at' => current_time('c'),
                    'original_filename' => $original_filename,
                    'total_variants' => count($json_report),
                    'folder_path' => $upload_folder,
                    'version' => '2.6'
                ),
                'variants' => $json_report
            );
            
            $json_content = json_encode($structured_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            if (file_put_contents($json_report_path, $json_content)) {
                $file_size = filesize($json_report_path);
                error_log("MTHFR: JSON report saved to: {$json_report_path} ({$file_size} bytes)");
                return $json_report_path;
            }
        } catch (Exception $e) {
            error_log("MTHFR: Failed to save JSON file - " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Save PDF report file with original filename - FIXED VERSION
     */
   /**
     * Save PDF report file with upload ID folder structure - UPDATED VERSION
     */
    private static function save_pdf_report_file($upload_id, $order_id, $report_type, $pdf_data, $original_filename = null) {
        if (!$pdf_data) {
            error_log("MTHFR: PDF data is empty");
            return null;
        }
        
        $upload_dir = wp_upload_dir();
        $reports_dir = $upload_dir['basedir'] . '/user_reports';
        
        // Create upload ID specific folder
        $upload_folder = $reports_dir . '/upload_' . $upload_id;
     
        
        // Create directories if they don't exist
        if (!file_exists($reports_dir)) {
            wp_mkdir_p($reports_dir);
        }
        
        if (!file_exists($upload_folder)) {
            wp_mkdir_p($upload_folder);
            error_log("MTHFR: Created upload folder: {$upload_folder}");
        }
        
        // Create filename with original filename if available
        $timestamp = date('Ymd_His');
        
        if (!empty($original_filename) && $original_filename !== 'unknown_file') {
            $clean_filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $original_filename);
            $filename = "MTHFRSupport_{$report_type}_{$clean_filename}_{$order_id}_{$timestamp}.pdf";
        } else {
            $filename = "MTHFRSupport_{$report_type}_{$order_id}_{$timestamp}.pdf";
        }
        
        $file_path = $upload_folder . '/' . $filename;
        
        try {
            $bytes_written = file_put_contents($file_path, $pdf_data);
            
            if ($bytes_written === false) {
                error_log("MTHFR: Failed to write PDF file to {$file_path}");
                return null;
            }
            
            // Verify file
            if (!file_exists($file_path) || filesize($file_path) === 0) {
                error_log("MTHFR: PDF file verification failed at {$file_path}");
                return null;
            }
            
            error_log("MTHFR: PDF saved: {$file_path} ({$bytes_written} bytes)");
            return $file_path;
            
        } catch (Exception $e) {
            error_log("MTHFR: Error saving PDF to {$file_path}: " . $e->getMessage());
            return null;
        }
    }
    /**
     * Save report to database
     */
    private static function save_to_database($upload_id, $order_id, $report_type, $product_name, 
                                           $json_report_path, $pdf_report_path, $json_report, $has_subscription) {
        if (!class_exists('MTHFR_Database')) {
            error_log('MTHFR: Database class not available');
            return false;
        }
        
        try {
            $result = MTHFR_Database::save_report(
                $upload_id,
                $order_id,
                $report_type,
                $product_name . ' - ' . $report_type . ' Report',
                $json_report_path,
                $pdf_report_path,
                json_encode($json_report),
                'completed',
                $has_subscription
            );
            
            return $result !== false;
            
        } catch (Exception $e) {
            error_log('MTHFR: Database save failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available report types
     */
    public static function get_available_report_types() {
        if (!class_exists('MTHFR_Excel_Database')) {
            return array('Variant');
        }
        
        $all_categories = array();
        if (method_exists('MTHFR_Excel_Database', 'get_all_categories')) {
            $all_categories = MTHFR_Excel_Database::get_all_categories();
        }
        
        $available_types = array('Variant', 'Bundled'); // Always available
        
        // Check which report types are supported based on available categories
        $category_checks = array(
            'Excipient' => array('Alcohol Sensitivity', 'Chemical Sensitivity', 'Aluminum & Metal Sensitivity'),
            'Methylation' => array('Methylation & Methionine/Homocysteine Pathways', 'COMT Activity'),
            'Detox' => array('Liver Detox', 'Chemical Detoxification', 'Heavy Metal Detox'),
            'Immune' => array('Immune Response', 'Inflammatory Response', 'HLA'),
            'Covid' => array('Covid', 'Immune Response'),
            'Eye Health' => array('Eye Health')
        );
        
        foreach ($category_checks as $report_type => $required_categories) {
            $has_categories = false;
            foreach ($required_categories as $required_cat) {
                if (in_array($required_cat, $all_categories)) {
                    $has_categories = true;
                    break;
                }
            }
            if ($has_categories) {
                $available_types[] = $report_type;
            }
        }
        
        return array_unique($available_types);
    }

    /**
     * Get report preview (variant count by category)
     */
    public static function get_report_preview($genetic_data, $report_type) {
        try {
            $category_filters = self::get_category_filters_for_report_type($report_type);
            
            if (!class_exists('MTHFR_Excel_Database')) {
                return array('error' => 'Database not available');
            }
            
            // Use enhanced matching if available
            if (method_exists('MTHFR_Excel_Database', 'match_user_data_with_categories')) {
                $matched_data = MTHFR_Excel_Database::match_user_data_with_categories($genetic_data, $category_filters);
            } else {
                $matched_data = MTHFR_Excel_Database::match_user_data($genetic_data);
            }
            
            $preview = array();
            $total_variants = 0;
            
            foreach ($matched_data as $category => $variants) {
                $count = count($variants);
                $preview[$category] = $count;
                $total_variants += $count;
            }
            
            return array(
                'report_type' => $report_type,
                'total_variants' => $total_variants,
                'categories' => $preview,
                'category_filters_applied' => $category_filters ? true : false
            );
            
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

   
/**
 * Get report URLs for download - FIXED VERSION
 */
public static function get_report_urls($order_id) {
    if (!class_exists('MTHFR_Database')) {
        return array(
            'success' => false,
            'message' => 'Database class not available'
        );
    }
    
    $report = MTHFR_Database::get_report_by_order($order_id);
    
    if (!$report) {
        return array(
            'success' => false,
            'message' => 'No report found for this order'
        );
    }
    
    $upload_dir = wp_upload_dir();
    
    $json_url = null;
    $pdf_url = null;
    
    // FIXED: Generate JSON download URL - full path convert karo
    if (!empty($report->report_path) && file_exists($report->report_path)) {
        $json_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $report->report_path);
    }
    
    // FIXED: Generate PDF download URL - full path convert karo
    if (!empty($report->pdf_report) && file_exists($report->pdf_report)) {
        $pdf_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $report->pdf_report);
    }
    
    return array(
        'success' => true,
        'json_url' => $json_url,
        'pdf_url' => $pdf_url,
        'report_type' => $report->report_type,
        'status' => $report->status,
        'created_at' => $report->created_at
    );
}

    /**
     * Get report status
     */
    public static function get_report_status($order_id) {
        try {
            if (!class_exists('MTHFR_Database')) {
                return array(
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Database class not available'
                );
            }
            
            $report = MTHFR_Database::get_report_by_order($order_id);
            
            if (!$report) {
                return array(
                    'success' => false,
                    'status' => 'not_found',
                    'message' => 'No report found for this order'
                );
            }
            
            return array(
                'success' => true,
                'status' => $report->status,
                'report_id' => $report->id,
                'order_id' => $report->order_id,
                'pdf_path' => $report->pdf_report ?? null,
                'json_path' => $report->report_path ?? null,
                'created_at' => $report->created_at,
                'message' => 'Report found successfully'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'status' => 'error',
                'message' => 'Error retrieving report: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get report JSON data
     */
    public static function get_report_json_data($order_id) {
        if (!class_exists('MTHFR_Database')) {
            return null;
        }
        
        $report = MTHFR_Database::get_report_by_order($order_id);
        
        if (!$report || empty($report->report_path) || !file_exists($report->report_path)) {
            return null;
        }
        
        try {
            $json_content = file_get_contents($report->report_path);
            $json_data = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("MTHFR: JSON decode error for order {$order_id}: " . json_last_error_msg());
                return null;
            }
            
            return $json_data;
            
        } catch (Exception $e) {
            error_log("MTHFR: Error reading JSON data for order {$order_id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get filename info for debugging
     */
    public static function get_filename_info($upload_id) {
        if (!class_exists('MTHFR_Database')) {
            return array(
                'success' => false,
                'message' => 'Database class not available'
            );
        }
        
        try {
            $upload_data = MTHFR_Database::get_upload_data($upload_id);
            
            if (!$upload_data) {
                return array(
                    'success' => false,
                    'message' => 'Upload record not found'
                );
            }
            
            $original_filename = 'unknown_file';
            $filename_source = 'default';
            
            // Check database field first
            if (isset($upload_data->file_name) && !empty($upload_data->file_name)) {
                $original_filename = $upload_data->file_name;
                $filename_source = 'database_field';
            } elseif (isset($upload_data->file_path) && !empty($upload_data->file_path)) {
                $original_filename = basename($upload_data->file_path);
                $filename_source = 'file_path';
            }
            
            // Clean filename
            $clean_filename = pathinfo($original_filename, PATHINFO_FILENAME);
            $clean_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $clean_filename);
            
            return array(
                'success' => true,
                'original_filename' => $original_filename,
                'clean_filename' => $clean_filename,
                'filename_source' => $filename_source,
                'file_path' => $upload_data->file_path ?? null,
                'upload_data' => $upload_data
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Error getting filename info: ' . $e->getMessage()
            );
        }
    }

    /**
     * Validate JSON report structure
     */
    public static function validate_json_report($json_report) {
        $validation_results = array(
            'is_valid' => true,
            'errors' => array(),
            'warnings' => array(),
            'statistics' => array()
        );
        
        // Check if it's an array
        if (!is_array($json_report)) {
            $validation_results['is_valid'] = false;
            $validation_results['errors'][] = 'JSON report is not an array';
            return $validation_results;
        }
        
        // Check if it's empty
        if (empty($json_report)) {
            $validation_results['is_valid'] = false;
            $validation_results['errors'][] = 'JSON report is empty';
            return $validation_results;
        }
        
        $validation_results['statistics']['total_variants'] = count($json_report);
        
        // Required fields for each variant
        $required_fields = array('SNP ID', 'SNP Name', 'Risk Allele', 'Your Allele', 'Result', 'Group');
        $field_counts = array();
        $categories = array();
        
        foreach ($json_report as $index => $variant) {
            if (!is_array($variant)) {
                $validation_results['errors'][] = "Variant at index {$index} is not an array";
                continue;
            }
            
            // Check required fields
            foreach ($required_fields as $field) {
                if (!isset($variant[$field])) {
                    $validation_results['warnings'][] = "Variant at index {$index} missing field: {$field}";
                } else {
                    $field_counts[$field] = ($field_counts[$field] ?? 0) + 1;
                }
            }
            
            // Collect categories
            if (isset($variant['Group'])) {
                $categories[$variant['Group']] = ($categories[$variant['Group']] ?? 0) + 1;
            }
            
            // Validate specific field formats
            if (isset($variant['SNP ID']) && strpos($variant['SNP ID'], 'rs') !== 0) {
                $validation_results['warnings'][] = "Invalid SNP ID format at index {$index}: " . $variant['SNP ID'];
            }
            
            if (isset($variant['Your Allele']) && $variant['Your Allele'] === 'Unknown') {
                $validation_results['warnings'][] = "Unknown genotype at index {$index} for " . ($variant['SNP ID'] ?? 'unknown SNP');
            }
        }
        
        $validation_results['statistics']['field_completeness'] = $field_counts;
        $validation_results['statistics']['categories'] = $categories;
        $validation_results['statistics']['total_categories'] = count($categories);
        
        // Check if we have too many errors
        if (count($validation_results['errors']) > 0) {
            $validation_results['is_valid'] = false;
        }
        
        return $validation_results;
    }

    /**
     * Get debug info
     */
    public static function get_debug_info() {
        global $wpdb;
        
        // Check if required functions exist
        $functions_available = array(
            'wp_upload_dir' => function_exists('wp_upload_dir'),
            'wp_mkdir_p' => function_exists('wp_mkdir_p'),
            'current_time' => function_exists('current_time'),
            'get_bloginfo' => function_exists('get_bloginfo')
        );
        
        // Check if classes are loaded
        $classes_available = array(
            'MTHFR_Database' => class_exists('MTHFR_Database'),
            'MTHFR_Excel_Database' => class_exists('MTHFR_Excel_Database'),
            'MTHFR_PDF_Generator' => class_exists('MTHFR_PDF_Generator'),
            'MTHFR_ZIP_Processor' => class_exists('MTHFR_ZIP_Processor'),
            'Mpdf\Mpdf' => class_exists('Mpdf\Mpdf')
        );
        
        // Get upload directory info
        $upload_dir = wp_upload_dir();
        $reports_dir = $upload_dir['basedir'] . '/user_reports';
        
        // Database test
        $db_test = array('status' => 'error', 'message' => 'Database class not available');
        if (class_exists('MTHFR_Database')) {
            $db_test = MTHFR_Database::test_connection();
        }
        
        $pdf_test = array('status' => 'error', 'message' => 'PDF Generator class not available');
        if (class_exists('MTHFR_PDF_Generator')) {
            if (method_exists('MTHFR_PDF_Generator', 'test_generation')) {
                $pdf_test = MTHFR_PDF_Generator::test_generation();
            } else {
                $pdf_test = array('status' => 'error', 'message' => 'test_generation method not found');
            }
        }
        
        return array(
            'timestamp' => current_time('c'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => defined('MTHFR_PLUGIN_VERSION') ? MTHFR_PLUGIN_VERSION : 'Unknown',
            'current_user' => wp_get_current_user()->user_login,
            'api_status' => 'working',
            'database_test' => $db_test,
            'pdf_test' => $pdf_test,
            'reports_directory' => $reports_dir,
            'reports_dir_exists' => file_exists($reports_dir),
            'reports_dir_writable' => is_writable(dirname($reports_dir)),
            'upload_directory' => $upload_dir['basedir'],
            'functions_available' => $functions_available,
            'classes_available' => $classes_available,
            'database_table_info' => array(
                'uploads_table' => $wpdb->prefix . 'user_uploads',
                'table_exists' => $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}user_uploads'") ? true : false
            ),
            'server_info' => array(
                'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
                'PHP_MEMORY_LIMIT' => ini_get('memory_limit'),
                'PHP_MAX_EXECUTION_TIME' => ini_get('max_execution_time')
            )
        );
    }
    
    /**
     * Health check
     */
    public static function health_check() {
        try {
            // Test database connection
            $db_status = class_exists('MTHFR_Database') ? MTHFR_Database::test_connection() : array('status' => 'error', 'message' => 'Database class not loaded');
            
            // Test PDF generation
            $pdf_status = class_exists('MTHFR_PDF_Generator') ? MTHFR_PDF_Generator::test_generation() : array('status' => 'error', 'message' => 'PDF Generator class not loaded');
            
            // Test Excel database
            $excel_status = 'error';
            if (class_exists('MTHFR_Excel_Database')) {
                try {
                    MTHFR_Excel_Database::initialize_databases();
                    $database = MTHFR_Excel_Database::get_database();
                    $excel_status = !empty($database) ? 'working' : 'empty';
                } catch (Exception $e) {
                    $excel_status = 'error';
                }
            }
            
            return array(
                'status' => 'healthy',
                'timestamp' => current_time('c'),
                'version' => defined('MTHFR_PLUGIN_VERSION') ? MTHFR_PLUGIN_VERSION : 'Unknown',
                'components' => array(
                    'database' => $db_status['status'],
                    'pdf_generation' => $pdf_status['status'],
                    'excel_database' => $excel_status,
                    'zip_processor' => class_exists('MTHFR_ZIP_Processor') ? 'working' : 'error'
                ),
                'uptime' => 'N/A (stateless)',
                'message' => 'All systems operational'
            );
            
        } catch (Exception $e) {
            return array(
                'status' => 'unhealthy',
                'timestamp' => current_time('c'),
                'error' => $e->getMessage(),
                'message' => 'System experiencing issues'
            );
        }
    }
    
}

?>