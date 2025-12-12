<?php
/**
 * MTHFR PDF Generator Enhanced Class - CATEGORY DATA FIXED
 * Fixed category data filtering and rs10306114 field processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_PDF_Generator {
    
    // OPTIMIZED: Memory management constants with better page utilization
    const MAX_VARIANTS_PER_PAGE = 500;        
    const MAX_MEMORY_THRESHOLD = '256M';
    const MEMORY_WARNING_THRESHOLD = 0.85;
    const MAX_VARIANTS_FOR_MPDF = 500;
    const BATCH_SIZE = 30;                   
    
    // FIXED: Complete category order - ALL categories included
    const CATEGORY_ORDER = array(


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
        'Covid',
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
        'Yeast/Alcohol Metabolism',


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
        'L-Tyrosine'
    );
    
    // FIXED: Only show these categor
    
    const ALLOWED_BOOKMARK_CATEGORIES = array(
        'Alzheimers/Cardio/Lipid',
        'COMT Activity',                                              
        'Cannabinoid Pathway',
        'Celiac Disease/Gluten Intolerance',
        'Clotting Factors',
        'Covid',
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
        'Yeast/Alcohol Metabolism',


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
    
    // FIXED: Complete pathway figure mapping with all categories
    const PATHWAY_FIGURE_MAPPING = array(
        'Liver Detox - Phase I' => 1,
        'Liver Detox - Phase II' => 1,
        'Methylation & Methionine/Homocysteine Pathways' => 2,
        'Neurotransmitter Pathway: Serotonin & Dopamine' => 3,
        'COMT Activity' => 4,                                        
        'Glyoxylate Metabolic Process' => 5,
        'HLA' => 6,
        'Yeast/Alcohol Metabolism' => 7,
        'Trans-Sulfuration Pathway' => 8,
        'Neurotransmitter Pathway: Glutamate & GABA' => 9,          
        'Pentose Phosphate Pathway' => 10,
        'Thiamin/Thiamine Degradation' => 11,
        'Mitochondrial Function' => 12,
    );
    
    const FIGURE_NUMBERS = array(
        'Liver Detox - Phase I' => 1,
        'Liver Detox - Phase II' => 1,
        'Methylation & Methionine/Homocysteine Pathways' => 2,
        'Neurotransmitter Pathway: Serotonin & Dopamine' => 3,
        'COMT Activity' => 4,                                        
        'Glyoxylate Metabolic Process' => 5,
        'HLA' => 6,
        'Yeast/Alcohol Metabolism' => 7,
        'Trans-Sulfuration Pathway' => 8,
        'Neurotransmitter Pathway: Glutamate & GABA' => 9,          
        'Pentose Phosphate Pathway' => 10,
        'Thiamin/Thiamine Degradation' => 11,
        'Mitochondrial Function' => 12,
        'Eye Health' => 13                                           
    );

    const PATHWAY_IMAGE_URLS = array(
        'Liver Detox - Phase I' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/Liver_detox.jpeg',
        'Liver Detox - Phase II' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/Liver_detox.jpeg',
        'Yeast/Alcohol Metabolism' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/yeast.jpeg',
        'Methylation & Methionine/Homocysteine Pathways' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/Methylation.jpeg',
        'Neurotransmitter Pathway: Serotonin & Dopamine' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/Neuro_transmitter.jpeg',
        'Neurotransmitter Pathway: Glutamate & GABA' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/neuro_transmitter_glutamate.jpeg',
        'COMT Activity' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/COMT.jpeg',
        'Mitochondrial Function' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/electron_transport.jpeg',
        'Pentose Phosphate Pathway' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/Pentose_phosphate_pathway.jpeg',
        'Glyoxylate Metabolic Process' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/Glyphosate_degradation.jpeg',
        'Thiamin/Thiamine Degradation' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/Glycolysis.jpeg',
        'HLA' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/biotoxin_pathway.jpeg',
        'Trans-Sulfuration Pathway' => 'https://mthfrsupport.org/wp-content/uploads/pathway-images/Trans_sulfuration.jpeg',
    );
    
    private static $memory_stats = array();
    private static $processing_time = 0;
    
    private const REQUIRED_CATEGORY_COUNTS = [
        'COMT Activity'              => 22,
        'Yeast/Alcohol Metabolism'   => 15,
    ];

    private static function ensure_minimum_category_rows(array $processed_data, string $report_type): array {
    $rt = strtolower($report_type);
    if (!in_array($rt, ['variant','methylation','genetic analysis'], true)) return $processed_data;

    if (!class_exists('MTHFR_Excel_Database')) return $processed_data;
    $db = MTHFR_Excel_Database::get_database();
    if (empty($db) || !is_array($db)) return $processed_data;

    error_log('=== ENSURING MINIMUM CATEGORY ROWS WITH COMT GENE OVERRIDE ===');

    foreach (self::REQUIRED_CATEGORY_COUNTS as $cat => $min) {
        $have = isset($processed_data['categories'][$cat]) ? $processed_data['categories'][$cat] : [];
        $present_rsids = [];
        foreach ($have as $v) {
            $rid = (string)($v['SNP ID'] ?? '');
            if ($rid !== '') $present_rsids[$rid] = true;
        }

        error_log("ENHANCED: Category '{$cat}' has " . count($have) . " variants, needs {$min} minimum");

        if (count($have) >= $min) continue;

        $candidates = [];
        $searched = 0;
        $found_candidates = 0;

        // 🚨 SPECIAL HANDLING FOR COMT ACTIVITY
        if ($cat === 'COMT Activity') {
            error_log("COMT SPECIAL: Searching database for COMT gene variants...");
            
            foreach ($db as $rsid => $entry) {
                $searched++;
                if (!is_array($entry)) continue;
                $rsid = (string)$rsid;
                if ($rsid === '' || isset($present_rsids[$rsid])) continue;

                $gene = $entry['Gene'] ?? '';
                
                // 🚨 FORCE COMT GENE VARIANTS INTO COMT ACTIVITY
                if (!empty($gene) && strtoupper(trim($gene)) === 'COMT') {
                    $found_candidates++;
                    $snp_name = self::create_snp_name($gene, $entry['SNP'] ?? '');
                    $risk = $entry['Risk'] ?? 'Unknown';

                    $candidate = [
                        'SNP ID' => $rsid,
                        'SNP Name' => $snp_name,
                        'Risk Allele' => $risk,
                        'Your Allele' => 'Unknown',
                        'Result' => 'Unknown',
                        'Category' => 'COMT Activity',
                        'rs10306114' => 'COMT Activity', // Force the category
                    ];
                    
                    $candidates[] = $candidate;
                    error_log("COMT GENE OVERRIDE: Found {$rsid} (Gene: {$gene}) - FORCED into COMT Activity");
                }
            }
        } else {
            // Regular processing for other categories
            foreach ($db as $rsid => $entry) {
                $searched++;
                if (!is_array($entry)) continue;
                $rsid = (string)$rsid;
                if ($rsid === '' || isset($present_rsids[$rsid])) continue;

                $entry_for_cat = $entry;
                $entry_for_cat['SNP ID'] = $rsid;
                $entry_for_cat['rsid'] = $rsid;

                $category = $entry['rs10306114'] ?? ($entry['Category'] ?? $entry['Pathway'] ?? null);

                if (!$category || $category === 'nan' || $category === 'NaN') {
                    $gene = $entry['Gene'] ?? '';
                    if (!empty($gene) && class_exists('MTHFR_Excel_Database')) {
                        $category = MTHFR_Excel_Database::determine_pathway_from_gene($gene);
                    }
                    
                    if (!$category || $category === 'Unknown') {
                        $category = self::determine_category_enhanced($entry_for_cat, $report_type);
                    }
                }

                if ($category === $cat) {
                    $found_candidates++;
                    $snp_name = self::create_snp_name($entry['Gene'] ?? 'Unknown', $entry['SNP'] ?? '');
                    $risk = $entry['Risk'] ?? 'Unknown';

                    $candidate = [
                        'SNP ID' => $rsid,
                        'SNP Name' => $snp_name,
                        'Risk Allele' => $risk,
                        'Your Allele' => 'Unknown',
                        'Result' => 'Unknown',
                        'Category' => $cat,
                        'rs10306114' => $cat,
                    ];
                    
                    $candidates[] = $candidate;
                }
            }
        }

        error_log("Database search for '{$cat}': Searched {$searched} entries, found {$found_candidates} candidates");

        // Add candidates to meet minimum requirement
        $added_count = 0;
        foreach ($candidates as $row) {
            if (count($have) >= $min) break;
            $have[] = $row;
            $processed_data['variants'][] = $row;
            $added_count++;
            
            if ($cat === 'COMT Activity') {
                error_log("ADDED: COMT variant {$row['SNP ID']} to meet minimum requirement");
            }
        }

        $processed_data['categories'][$cat] = $have;
        error_log("FINAL: Category '{$cat}' now has " . count($have) . " variants (added {$added_count})");
    }

    $processed_data['report_info']['total_variants'] = count($processed_data['variants']);
    return $processed_data;
}

public static function debug_comt_json_report($json_report) {
    error_log('=== DEBUGGING JSON REPORT FOR COMT ===');
    
    if (empty($json_report)) {
        error_log('JSON report is empty!');
        return;
    }
    
    $total_variants = count($json_report);
    $comt_count = 0;
    $category_counts = array();
    
    foreach ($json_report as $variant) {
        $category = $variant['rs10306114'] ?? 'Unknown';
        
        if (!isset($category_counts[$category])) {
            $category_counts[$category] = 0;
        }
        $category_counts[$category]++;
        
        // Check for COMT
        if (stripos($category, 'comt') !== false || 
            stripos($variant['SNP Name'] ?? '', 'comt') !== false) {
            $comt_count++;
            error_log("JSON COMT #{$comt_count}: " . json_encode([
                'SNP ID' => $variant['SNP ID'] ?? 'Unknown',
                'SNP Name' => $variant['SNP Name'] ?? 'Unknown',
                'Category' => $category,
                'Result' => $variant['Result'] ?? 'Unknown'
            ]));
        }
    }
    
    error_log("JSON REPORT SUMMARY:");
    error_log("Total variants: {$total_variants}");
    error_log("COMT variants found: {$comt_count}");
    error_log("Category breakdown:");
    arsort($category_counts);
    foreach ($category_counts as $cat => $count) {
        error_log("  {$cat}: {$count} variants");
    }
    
    if ($comt_count === 0) {
        error_log("❌ NO COMT VARIANTS IN JSON REPORT - This explains why PDF shows 0");
    } else {
        error_log("✅ COMT variants found in JSON - PDF should show them");
    }
}
    /**
     * OPTIMIZED: Main PDF generation method with compact layout
     */
   public static function generate_pdf($enhanced_data, $product_name, $report_type, $has_subscription = false, $folder_name = '') {
        $start_time = microtime(true);
        self::$memory_stats['start'] = memory_get_usage(true);
        
        error_log('MTHFR Fixed: Starting PDF generation with dynamic bookmarks');
        error_log('MTHFR Fixed: Initial memory usage: ' . self::format_memory(memory_get_usage(true)));
        
        if (empty($folder_name)) {
            $folder_name = self::extract_file_name_from_database($enhanced_data);
        }
        
        self::optimize_memory_settings();
        
        try {
            // Process data with proper filtering for allowed categories only
            $processed_data = self::process_enhanced_data_with_ordering($enhanced_data, $report_type);
            $processed_data = self::ensure_minimum_category_rows($processed_data, $report_type);
            
            if (empty($processed_data['variants'])) {
             
                error_log('MTHFR Fixed: No variants found in processed data');
                return self::generate_fallback_report($product_name, $report_type, 'No genetic variants found', $folder_name);
            }
            
            $variant_count = count($processed_data['variants']);
            error_log("MTHFR Fixed: Processing {$variant_count} variants for {$report_type} report");
            $exclude_categories = ['COMT Activity', 'Trans-Sulfuration Pathway', 'Yeast/Alcohol Metabolism'];

// Remove them from processed_data if report type = methylation
if (strtolower($report_type) === 'methylation' || strtolower($report_type) === 'meth') {
    if (!empty($processed_data['categories'])) {
        foreach ($exclude_categories as $exclude) {
            if (isset($processed_data['categories'][$exclude])) {
                unset($processed_data['categories'][$exclude]);
            }
        }
    }
}
            // Generate dynamic bookmarks data
            $bookmark_data = self::generate_dynamic_bookmark_data($processed_data, $report_type);
            error_log('MTHFR Fixed: Generated ' . count($bookmark_data['bookmarks']) . ' dynamic bookmarks');
            
            $pdf_result = self::try_mpdf_generation_with_dynamic_bookmarks($processed_data, $product_name, $report_type, $has_subscription, $folder_name, $bookmark_data);
            
            if ($pdf_result && self::is_valid_pdf($pdf_result)) {
                error_log('MTHFR Fixed: mPDF generation with dynamic bookmarks successful');
                return $pdf_result;
            }
            
            error_log('MTHFR Fixed: mPDF failed, generating HTML report with dynamic bookmarks');
            return self::generate_enhanced_pdf_content_with_dynamic_bookmarks($processed_data, $product_name, $folder_name, $report_type);

        } catch (Exception $e) {
            error_log('MTHFR Fixed: PDF generation failed - ' . $e->getMessage());
            self::cleanup_memory();
            return self::generate_fallback_report($product_name, $report_type, 'Error: ' . $e->getMessage(), $folder_name);
        } finally {
            self::$processing_time = microtime(true) - $start_time;
            self::$memory_stats['end'] = memory_get_usage(true);
            self::$memory_stats['peak'] = memory_get_peak_usage(true);
            
            error_log('MTHFR Fixed: PDF generation completed in ' . round(self::$processing_time, 2) . 's');
            error_log('MTHFR Fixed: Memory stats: ' . json_encode(self::format_memory_stats()));
        }
    }
private static function try_mpdf_generation_with_dynamic_bookmarks($processed_data, $product_name, $report_type, $has_subscription, $folder_name, $bookmark_data) {
    try {
        if (!class_exists('Mpdf\Mpdf')) {
            error_log('MTHFR Fixed: mPDF class not available');
            return null;
        }
        
        $temp_dir = self::create_temp_directory();
        if (!$temp_dir) {
            error_log('MTHFR Fixed: Could not create temporary directory');
            return null;
        }
        
        $config = array(
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,     
            'margin_right' => 8,    
            'margin_top' => 15,     
            'margin_bottom' => 50,  
            'default_font' => 'arial',
            'tempDir' => $temp_dir,
            'simpleTables' => true,
            'packTableData' => true,
            'shrink_tables_to_fit' => 1,
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
            'allow_output_buffering' => true,
            'debug' => false
        );
        
        error_log('MTHFR Fixed: Creating mPDF instance with dynamic bookmarks...');
        $mpdf = new \Mpdf\Mpdf($config);
        
        // Enhanced metadata with bookmark information
        $mpdf->SetTitle($product_name . ' - ' . $report_type . ' Genetic Report (Dynamic Bookmarks)');
        $mpdf->SetAuthor('MTHFR Support');
        $mpdf->SetCreator('MTHFR Plugin v2.5 Enhanced Bookmarks');
        $mpdf->SetSubject('Genetic Analysis Report - ' . $folder_name);
        $mpdf->SetKeywords('genetics, MTHFR, variants, ' . implode(', ', array_keys($processed_data['categories'])));
        
        // Set custom properties for bookmark metadata
        if (method_exists($mpdf, 'SetCustomProperties')) {
            $mpdf->SetCustomProperties(array(
                'Total_Variants'      => $bookmark_data['stats']['total_variants'],
                'Categories_Count'    => $bookmark_data['stats']['categories_with_data'],
                'High_Risk_Variants'  => $bookmark_data['stats']['high_risk_variants'],
                'Available_Figures'   => $bookmark_data['stats']['available_figures'],
            ));
        }
        
        $mpdf->SetHTMLFooter('
            <div style="font-size: 7pt; line-height: 1.1; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; margin-top: 8px;">
                This report is intended to translate your results into an easier to understand form. It is not intended to diagnose or treat. For diagnosis or treatment, please present this to your doctor (or find a doctor on MTHFRSupportTM website under Find a Practitioner. Additionally, genetic mutations are flags that something **could** be wrong and not a guarantee that you are having all or any of the associated issues. Other factors like environment, ethnic background, diet, age, personal history, etc all have a factor in whether a mutation starts to present itself or not and when. Copyright all rights reserved MTHFR SupportTM                
            </div>
        ');
        
        $html_content = self::generate_enhanced_pdf_content_with_dynamic_bookmarks($processed_data, $product_name, $folder_name, $report_type, $bookmark_data);
        
        if (empty($html_content)) {
            error_log('MTHFR Fixed: Generated HTML content is empty');
            return null;
        }
        
        error_log('MTHFR Fixed: Writing HTML to mPDF with dynamic bookmarks (size: ' . strlen($html_content) . ' bytes)');
        error_log('MTHFR Fixed: Bookmarks generated: ' . count($bookmark_data['bookmarks']));
        
        if (ob_get_level()) {
            ob_clean();
        }
        
        $mpdf->WriteHTML($html_content);
        $pdf_content = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
        
        unset($mpdf);
        unset($html_content);
        self::cleanup_temp_directory($temp_dir);
        self::cleanup_memory();
        
        if (!$pdf_content || !self::is_valid_pdf($pdf_content)) {
            error_log('MTHFR Fixed: Generated content is not valid PDF');
            return null;
        }
        
        error_log('MTHFR Fixed: mPDF with dynamic bookmarks generated successfully, size: ' . strlen($pdf_content) . ' bytes');
        return $pdf_content;
        
    } catch (Exception $e) {
        error_log('MTHFR Fixed: mPDF generation with dynamic bookmarks exception: ' . $e->getMessage());
        
        if (isset($mpdf)) {
            unset($mpdf);
        }
        if (isset($temp_dir)) {
            self::cleanup_temp_directory($temp_dir);
        }
        
        return null;
    }
}

    private static function generate_enhanced_pdf_content_with_dynamic_bookmarks($processed_data, $product_name, $folder_name, $report_type, $bookmark_data = null) {
        $html_parts = array();
        
        // Generate bookmark data if not provided
        if (!$bookmark_data) {
            $bookmark_data = self::generate_dynamic_bookmark_data($processed_data, $report_type);
        }
        
        $stats = $bookmark_data['stats'];
        
        // Enhanced CSS with bookmark and navigation styling
        $html_parts[] = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($product_name . ' - ' . $report_type . ' Report') . '</title>
    <meta name="total-variants" content="' . $stats['total_variants'] . '">
    <meta name="total-categories" content="' . $stats['categories_with_data'] . '">
    <meta name="available-figures" content="' . $stats['available_figures'] . '">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 9pt; 
            line-height: 1.2; 
            margin: 0; 
            padding: 15px; 
            padding-bottom: 50px;
        }
        .bookmark-anchor {
            position: relative;
            top: -20px;
            visibility: hidden;
        }
        .table-of-contents {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .toc-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 3px 0;
            border-bottom: 1px dotted #ccc;
        }
        .toc-category {
            margin-left: 20px;
        }
        .toc-figure {
            margin-left: 20px;
            font-style: italic;
        }
        .navigation-bar {
            background: #e9ecef;
            padding: 8px;
            margin: 10px 0;
            border-radius: 3px;
            text-align: center;
            font-size: 8pt;
        }
        .nav-link {
            color: #1B80B6;
            text-decoration: none;
            padding: 3px 8px;
            margin: 0 5px;
            border: 1px solid #1B80B6;
            border-radius: 3px;
            display: inline-block;
        }
        .nav-link:hover {
            background-color: #1B80B6;
            color: white;
        }
        .stats-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .stats-item {
            background: white;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .stats-number {
            font-weight: bold;
            font-size: 14pt;
            color: #1B80B6;
            display: block;
        }
        .stats-label {
            font-size: 8pt;
            color: #6c757d;
            margin-top: 2px;
        }
        .risk-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: 5px;
        }
        .risk-high { background-color: #dc3545; }
        .risk-moderate { background-color: #ffc107; }
        .risk-low { background-color: #28a745; }
        .category-summary {
            background: #f1f3f4;
            padding: 8px;
            margin: 5px 0;
            border-radius: 3px;
            font-size: 8pt;
        }
        /* Existing styles from original code */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1B80B6;
            margin-bottom: 12px;
            font-family: Arial, sans-serif;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            padding: 8px;
        }
        .logo-cell {
            width: 40%;
        }
        .logo-cell img {
            height: 70px;
            width: 35%;
        }
        .title-cell {
            text-align: right;
        }
        .main-title {
            font-weight: bold;
            font-size: 16px;
            color: #1B80B6;
            margin-bottom: 3px;
        }
        .sub-title {
            font-size: 12px;
            color: #444;
            margin-bottom: 3px;
        }
        .file-name {
            font-size: 10px;
            color: #888;
            font-weight: normal;
        }
        .variant-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 12px; 
            font-size: 8pt; 
        }
        .variant-table th { 
            background: #1B80B6; 
            color: white; 
            padding: 5px 3px; 
            text-align: center; 
            font-weight: bold; 
            border: 1px solid #ddd; 
            font-size: 8pt;
        }
        .variant-table td { 
            padding: 3px 2px; 
            text-align: center; 
            border: 1px solid #ddd; 
            vertical-align: middle; 
            font-size: 8pt;
            line-height: 1.1;
        }
        .even-row { background: #f8f8f8; }
        .odd-row { background: white; }
        .category-header-with-figure { 
            background: linear-gradient(135deg, #1B80B6 0%, #155a8a 100%);
            color: white; 
            font-size: 10pt; 
            font-weight: bold; 
            padding: 12px; 
            text-align: center; 
            border-radius: 5px 5px 0 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .figure-reference {
            font-style: italic;
            color: #e0e0e0;
            font-size: 9pt;
        }
        .result-high { background: #ffebee; color: #c62828; font-weight: bold; }
        .result-moderate { background: #fff8e1; color: #f57c00; font-weight: bold; }
        .result-low { background: #e8f5e8; color: #2e7d32; font-weight: bold; }
        .result-unknown { background: #f5f5f5; color: #666; }
        .disclaimer { 
            margin-top: 20px; 
            padding: 12px; 
            background: #fff3cd; 
            border: 1px solid #ffeaa7; 
            font-size: 8pt; 
            line-height: 1.3; 
        }
        .page-break { page-break-after: always; }
        .pathway-image {
            text-align: center;
            margin: 10px 0;
            page-break-inside: avoid;
            max-height: 500px;
            overflow: hidden;
        }
        .pathway-image img {
            max-width: 90%;
            max-height: 400px;
            height: auto;
            border: 1px solid #ddd;
            object-fit: contain;
        }
        .figure-caption {
            font-size: 8pt;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
    </style>
</head>
<body>';

        // Header function
        $generate_header = function() use ($product_name, $folder_name) {
            return '
<table class="header-table">
    <tr>
        <td class="logo-cell">
            <img src="https://www.mthfrsupport.org/wp-content/uploads/loogo.png" alt="Logo" style="height: 70px;">
        </td>
        <td style="vertical-align: middle; text-align: right;">
            <div style="font-weight: bold; font-size: 16px;">MTHFRSupport ' . esc_html($product_name) . '</div>
            <div style="font-size: 10px; color: #888; font-weight: normal;">' . esc_html($folder_name) . '</div>
        </td>
    </tr>
</table>';
        };

        // Add header for first page
        $html_parts[] = $generate_header();
        
        // Overview section with dynamic bookmark
        $html_parts[] = '<bookmark content="Report Overview" level="0" />';
        $html_parts[] = '<a name="overview" class="bookmark-anchor"></a>';
        
        // Enhanced summary statistics with visual indicators
        $html_parts[] = '
        <div class="stats-summary">
            <h3 style="margin-top: 0; color: #1B80B6; text-align: center;">📊 Report Summary</h3>
            <div class="stats-grid">
                <div class="stats-item">
                    <span class="stats-number">' . $stats['total_variants'] . '</span>
                    <div class="stats-label">Total Variants</div>
                </div>
                <div class="stats-item">
                    <span class="stats-number">' . $stats['categories_with_data'] . '</span>
                    <div class="stats-label">Categories</div>
                </div>
                <div class="stats-item">
                    <span class="stats-number">' . $stats['available_figures'] . '</span>
                    <div class="stats-label">Pathway Figures</div>
                </div>
                <div class="stats-item">
                    <span class="stats-number" style="color: #dc3545;">' . $stats['high_risk_variants'] . '</span>
                    <div class="stats-label">High Risk (+/+)</div>
                </div>
                <div class="stats-item">
                    <span class="stats-number" style="color: #ffc107;">' . $stats['moderate_risk_variants'] . '</span>
                    <div class="stats-label">Moderate Risk (+/-)</div>
                </div>
                <div class="stats-item">
                    <span class="stats-number" style="color: #28a745;">' . $stats['low_risk_variants'] . '</span>
                    <div class="stats-label">Low Risk (-/-)</div>
                </div>
            </div>
        </div>';
        
        // Dynamic Table of Contents
        $html_parts[] = self::generate_dynamic_table_of_contents($bookmark_data);
        
        // Navigation bar
        $html_parts[] = self::generate_navigation_bar($bookmark_data);
        
        // Categories section with enhanced bookmarks
        $categories = $processed_data['categories'];
        $category_count = 0;
        $total_categories = count($categories);
        $figures_to_include = array();
        
        if (!empty($categories)) {
            $html_parts[] = '<bookmark content="Genetic Variants (' . $stats['categories_with_data'] . ' categories)" level="0" />';
            $html_parts[] = '<a name="categories_section" class="bookmark-anchor"></a>';
        }
        
        // Process categories with enhanced dynamic bookmarks
        foreach ($categories as $category_name => $variants) {
            $category_count++;
            $variant_count = count($variants);
            $risk_summary = self::get_category_risk_summary($variants);
            
            // Add header for new page (except first)
            if ($category_count > 1) {
                $html_parts[] = '<div class="page-break"></div>';
                $html_parts[] = $generate_header();
            }
            
            // Enhanced dynamic bookmark with risk indicators
            $bookmark_title = $category_name . " ({$variant_count})";
            if ($risk_summary['high'] > 0) {
                $bookmark_title .= " ⚠️";
            }
            
            $bookmark_id = 'cat_' . sanitize_title($category_name);
            $html_parts[] = '<bookmark content="' . esc_attr($bookmark_title) . '" level="1" />';
            $html_parts[] = '<a name="' . $bookmark_id . '" class="bookmark-anchor"></a>';
            
            // Check pathway figure availability
            $figure_number = self::FIGURE_NUMBERS[$category_name] ?? null;
            $has_pathway_image = isset(self::PATHWAY_IMAGE_URLS[$category_name]);
            $figure_text = ($figure_number && $has_pathway_image) ? " (See Figure {$figure_number})" : "";
            
            if ($figure_number && $has_pathway_image) {
                $figures_to_include[$figure_number] = $category_name;
            }
            
            // Enhanced category header with risk summary
            $html_parts[] = '
            <div class="category-header-with-figure">
                <h3 style="color: white; margin: 0;">' . esc_html($category_name) . '</h3>
                <div style="font-size: 9pt; margin-top: 5px;">
                    ' . $variant_count . ' variants found
                    ' . ($risk_summary['high'] > 0 ? '<span class="risk-indicator risk-high" title="High Risk"></span>' . $risk_summary['high'] : '') . '
                    ' . ($risk_summary['moderate'] > 0 ? '<span class="risk-indicator risk-moderate" title="Moderate Risk"></span>' . $risk_summary['moderate'] : '') . '
                    ' . ($risk_summary['low'] > 0 ? '<span class="risk-indicator risk-low" title="Low Risk"></span>' . $risk_summary['low'] : '') . '
                </div>
                ' . ($figure_text ? '<div class="figure-reference">' . esc_html($figure_text) . '</div>' : '') . '
            </div>';
            
            // Category risk summary
            if ($risk_summary['high'] > 0 || $risk_summary['moderate'] > 0) {
                $html_parts[] = '
                <div class="category-summary">
                    <strong>Risk Summary:</strong> 
                    ' . ($risk_summary['high'] > 0 ? $risk_summary['high'] . ' high risk variants' : '') . 
                    ($risk_summary['high'] > 0 && $risk_summary['moderate'] > 0 ? ', ' : '') .
                    ($risk_summary['moderate'] > 0 ? $risk_summary['moderate'] . ' moderate risk variants' : '') . '
                    - Consider discussing with healthcare provider
                </div>';
            }
            
            // Navigation for this category
            $html_parts[] = self::generate_navigation_bar($bookmark_data, $category_name);
            
            // Process variants in batches (existing logic but enhanced)
            $limited_variants = array_slice($variants, 0, self::MAX_VARIANTS_PER_PAGE);
            $total_variants = count($limited_variants);
            $variants_per_page = self::BATCH_SIZE;
            
            for ($i = 0; $i < $total_variants; $i += $variants_per_page) {
                // Add header for new page (except first batch)
                if ($i > 0) {
                    $html_parts[] = '<div class="page-break"></div>';
                    $html_parts[] = $generate_header();
                    
                    // Add category header again for continuation
                    $html_parts[] = '
                    <div class="category-header-with-figure">
                        <h3 style="color: white; margin: 0;">' . esc_html($category_name) . ' (continued)</h3>
                        ' . ($figure_text ? '<div class="figure-reference">' . esc_html($figure_text) . '</div>' : '') . '
                    </div>';
                }
                
                // Start table for this batch
                $html_parts[] = '
                <table class="variant-table">
                    <thead>
                        <tr>
                            <th style="width: 16%;">SNP ID</th>
                            <th style="width: 26%;">SNP Name</th>
                            <th style="width: 16%;">Risk Allele</th>
                            <th style="width: 16%;">Your Allele</th>
                            <th style="width: 26%;">Result</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                // Add variants for this batch
                $batch_variants = array_slice($limited_variants, $i, $variants_per_page);
                foreach ($batch_variants as $index => $variant) {
                    $row_class = (($i + $index) % 2 == 0) ? 'even-row' : 'odd-row';
                    $result_class = self::get_result_css_class($variant['Result'] ?? 'Unknown');
                    
                    $html_parts[] = '
                        <tr class="' . $row_class . '">
                            <td>' . esc_html($variant['SNP ID'] ?? 'Unknown') . '</td>
                            <td>' . esc_html($variant['SNP Name'] ?? 'Unknown') . '</td>
                            <td>' . esc_html($variant['Risk Allele'] ?? 'Unknown') . '</td>
                            <td>' . esc_html($variant['Your Allele'] ?? 'Unknown') . '</td>
                            <td class="' . $result_class . '">' . esc_html($variant['Result'] ?? 'Unknown') . '</td>
                        </tr>';
                }
                
                $html_parts[] = '</tbody></table>';
            }
            
            // Add page break after category except for last one
            if ($category_count < $total_categories) {
                $html_parts[] = '<div class="page-break"></div>';
            }
        }
        
        // Enhanced Figures section with dynamic bookmarks
        if (!empty($figures_to_include)) {
            $html_parts[] = '<div class="page-break"></div>';
            $html_parts[] = $generate_header();
            
            $figure_count = count($figures_to_include);
            $html_parts[] = '<bookmark content="Pathway Figures (' . $figure_count . ')" level="0" />';
            $html_parts[] = '<a name="figures_section" class="bookmark-anchor"></a>';
            
            // Navigation for figures section
            $html_parts[] = self::generate_navigation_bar($bookmark_data, 'figures');
            
            // Sort and process figures with enhanced bookmarks
            ksort($figures_to_include);
            $processed_figures = array();
            $current_figure = 0;
            
            foreach ($figures_to_include as $figure_num => $pathway_name) {
                if (in_array($figure_num, $processed_figures)) {
                    continue;
                }
                $processed_figures[] = $figure_num;
                $current_figure++;

                if (isset(self::PATHWAY_IMAGE_URLS[$pathway_name])) {
                    $image_url = self::PATHWAY_IMAGE_URLS[$pathway_name];

                    // Page break for figures after first one
                    if ($current_figure > 1) {
                        $html_parts[] = '<div class="page-break"></div>';
                        $html_parts[] = $generate_header();
                    }

                    // Enhanced dynamic bookmark for figure
                    $html_parts[] = '<bookmark content="Figure ' . $figure_num . ': ' . esc_attr($pathway_name) . '" level="1" />';
                    $html_parts[] = '<a name="figure_' . $figure_num . '" class="bookmark-anchor"></a>';

                    // Enhanced figure with better styling
                    $html_parts[] = '
                    <div class="pathway-image" style="margin: 20px 0; page-break-inside: avoid;">
                        <div style="background: linear-gradient(135deg, #1B80B6 0%, #155a8a 100%); color: white; padding: 10px; text-align: center; border-radius: 5px 5px 0 0;">
                            <h3 style="margin: 0; font-size: 12pt;">Figure ' . $figure_num . ': ' . esc_html($pathway_name) . '</h3>
                        </div>
                        <div style="background: white; border: 1px solid #dee2e6; border-top: none; padding: 15px; border-radius: 0 0 5px 5px;">
                            <img src="' . esc_url($image_url) . '" alt="Figure ' . $figure_num . ': ' . esc_attr($pathway_name) . '" style="max-width: 100%; max-height: 600px; height: auto; margin: 0 auto; display: block; object-fit: contain;">
                            <div class="figure-caption" style="font-size: 8pt; line-height: 1.3; color: #555; margin-top: 10px; text-align: center; font-style: italic;">
                                Pathway diagram illustrating genetic variants and their interactions in the ' . esc_html($pathway_name) . ' category. 
                                Refer to your specific variant results in the corresponding category section.
                            </div>
                        </div>
                    </div>';
                    
                    // Add navigation for figures
                    $html_parts[] = self::generate_navigation_bar($bookmark_data, 'figure_' . $figure_num);
                }
            }
        }
        
        // Enhanced Disclaimer section with bookmark
        $html_parts[] = '<div class="page-break"></div>';
        $html_parts[] = $generate_header();
        $html_parts[] = '<bookmark content="Important Disclaimer" level="0" />';
        $html_parts[] = '<a name="disclaimer_section" class="bookmark-anchor"></a>';
        
        $html_parts[] = '
        <div class="disclaimer" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 2px solid #ffc107; border-radius: 8px; padding: 20px;">
            <h3 style="color: #1B80B6; margin-top: 0; text-align: center;">⚠️ Important Disclaimer</h3>
            <p><strong>Medical Disclaimer:</strong> This report is intended to translate your genetic results into an easier to understand form. It is <strong>not intended to diagnose or treat</strong> any medical condition.</p>
            <p><strong>Professional Consultation:</strong> For diagnosis or treatment, please present this report to your qualified healthcare provider or find a practitioner on the MTHFRSupport™ website under "Find a Practitioner".</p>
            <p><strong>Genetic Interpretation:</strong> Genetic mutations are indicators that something <em>could</em> be affected, but they are <strong>not a guarantee</strong> that you are experiencing all or any of the associated issues.</p>
            <p><strong>Contributing Factors:</strong> Many factors influence whether genetic variants manifest as health issues, including:</p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Environmental factors</li>
                <li>Ethnic background</li>
                <li>Diet and lifestyle</li>
                <li>Age and personal history</li>
                <li>Other genetic interactions</li>
            </ul>
            <p style="margin-bottom: 0; text-align: center;"><strong>© Copyright all rights reserved MTHFR Support™</strong></p>
        </div>';
        
        // Close HTML
        $html_parts[] = '</body></html>';
        
        return implode('', $html_parts);
    }

    private static function generate_dynamic_table_of_contents($bookmark_data) {
        $toc_html = '
        <div class="table-of-contents">
            <h2 style="color: #1B80B6; border-bottom: 2px solid #1B80B6; padding-bottom: 10px; text-align: center;">📚 Table of Contents</h2>
            <div style="font-size: 10pt; line-height: 1.6;">';
        
        foreach ($bookmark_data['bookmarks'] as $bookmark) {
            $indent = $bookmark['level'] === 0 ? '' : 'margin-left: 20px;';
            $font_weight = $bookmark['level'] === 0 ? 'font-weight: bold;' : '';
            $color = $bookmark['level'] === 0 ? 'color: #1B80B6;' : 'color: #495057;';
            
            // Add risk indicators for categories
            $risk_indicator = '';
            if ($bookmark['type'] === 'category' && isset($bookmark['risk_summary'])) {
                $risk = $bookmark['risk_summary'];
                if ($risk['high'] > 0) {
                    $risk_indicator = ' <span style="color: #dc3545;">⚠️</span>';
                } elseif ($risk['moderate'] > 0) {
                    $risk_indicator = ' <span style="color: #ffc107;">⚡</span>';
                }
            }
            
            $toc_html .= '
            <div class="toc-item" style="' . $indent . '">
                <a href="#' . $bookmark['anchor'] . '" style="text-decoration: none; ' . $color . ' ' . $font_weight . '">
                    ' . esc_html($bookmark['title']) . $risk_indicator . '
                </a>
                <span style="color: #6c757d;">' . $bookmark['page'] . '</span>
            </div>';
        }
        
        $toc_html .= '</div></div>';
        return $toc_html;
    }
    
    /**
     * NEW: Generate enhanced navigation bar
     */
    private static function generate_navigation_bar($bookmark_data, $current_section = '') {
        $nav_html = '<div class="navigation-bar">
            <strong style="color: #495057;">Quick Navigation:</strong> ';
        
        $main_sections = array_filter($bookmark_data['bookmarks'], function($bookmark) {
            return $bookmark['level'] === 0;
        });
        
        $nav_links = array();
        foreach ($main_sections as $bookmark) {
            $is_current = ($current_section === $bookmark['anchor'] || 
                          ($bookmark['type'] === 'categories_main' && strpos($current_section, 'cat_') === 0) ||
                          ($bookmark['type'] === 'figures_main' && strpos($current_section, 'figure_') === 0));
            
            $link_style = $is_current ? 'background-color: #1B80B6; color: white;' : '';
            
            $nav_links[] = '<a href="#' . $bookmark['anchor'] . '" class="nav-link" style="' . $link_style . '">' . 
                          esc_html(self::get_short_title($bookmark['title'])) . '</a>';
        }
        
        $nav_html .= implode(' ', $nav_links);
        $nav_html .= '</div>';
        
        return $nav_html;
    }
    
    /**
     * NEW: Get shortened title for navigation
     */
    private static function get_short_title($title) {
        $short_titles = array(
            'Report Overview' => 'Overview',
            'Important Disclaimer' => 'Disclaimer',
        );
        
        if (isset($short_titles[$title])) {
            return $short_titles[$title];
        }
        
        if (strpos($title, 'Genetic Variants') === 0) {
            return 'Categories';
        }
        
        if (strpos($title, 'Pathway Figures') === 0) {
            return 'Figures';
        }
        
        // Truncate long category names
        if (strlen($title) > 25) {
            return substr($title, 0, 22) . '...';
        }
        
        return $title;
    }
    
    


    private static function generate_dynamic_bookmark_data($processed_data, $report_type) {
        $bookmark_data = array(
            'bookmarks' => array(),
            'toc' => array(),
            'navigation' => array(),
            'stats' => array()
        );
        
        // Generate summary statistics
        $stats = array(
            'total_variants' => count($processed_data['variants'] ?? []),
            'total_categories' => count($processed_data['categories'] ?? []),
            'categories_with_data' => 0,
            'high_risk_variants' => 0,
            'moderate_risk_variants' => 0,
            'low_risk_variants' => 0,
            'unknown_variants' => 0,
            'available_figures' => 0
        );
        
        // Count categories with data and analyze risk levels
        foreach ($processed_data['categories'] ?? [] as $category_name => $variants) {
            if (!empty($variants)) {
                $stats['categories_with_data']++;
                
                foreach ($variants as $variant) {
                    $result = $variant['Result'] ?? 'Unknown';
                    switch ($result) {
                        case '+/+':
                            $stats['high_risk_variants']++;
                            break;
                        case '+/-':
                            $stats['moderate_risk_variants']++;
                            break;
                        case '-/-':
                            $stats['low_risk_variants']++;
                            break;
                        default:
                            $stats['unknown_variants']++;
                            break;
                    }
                }
            }
        }
        
        // Count available figures
        $figures = self::get_available_figures_for_categories($processed_data['categories'] ?? []);
        $stats['available_figures'] = count($figures);
        
        // Generate main bookmarks
        $bookmarks = array();
        $page_counter = 1;
        
        // Overview bookmark
        $bookmarks[] = array(
            'title' => 'Report Overview',
            'level' => 0,
            'anchor' => 'overview',
            'page' => $page_counter++,
            'type' => 'overview'
        );
        
        // Categories section
        if (!empty($processed_data['categories'])) {
            $bookmarks[] = array(
                'title' => 'Genetic Variants (' . $stats['categories_with_data'] . ' categories)',
                'level' => 0,
                'anchor' => 'categories_section',
                'page' => $page_counter,
                'type' => 'categories_main'
            );
            
            // Individual category bookmarks
            foreach ($processed_data['categories'] as $category_name => $variants) {
                if (!empty($variants)) {
                    $variant_count = count($variants);
                    $risk_summary = self::get_category_risk_summary($variants);
                    
                    $bookmark_title = $category_name . " ({$variant_count} variants)";
                    if ($risk_summary['high'] > 0) {
                        $bookmark_title .= " ⚠️";
                    }
                    
                    $bookmarks[] = array(
                        'title' => $bookmark_title,
                        'level' => 1,
                        'anchor' => 'cat_' . sanitize_title($category_name),
                        'page' => $page_counter,
                        'type' => 'category',
                        'category_name' => $category_name,
                        'variant_count' => $variant_count,
                        'risk_summary' => $risk_summary,
                        'has_pathway_figure' => isset(self::PATHWAY_IMAGE_URLS[$category_name])
                    );
                    
                    // Estimate pages needed for this category
                    $pages_needed = ceil($variant_count / self::BATCH_SIZE);
                    $page_counter += $pages_needed;
                }
            }
        }
        
        // Figures section
        if (!empty($figures) && in_array(strtolower($report_type), ['variant', 'methylation'])) {
            $bookmarks[] = array(
                'title' => 'Pathway Figures (' . count($figures) . ' figures)',
                'level' => 0,
                'anchor' => 'figures_section',
                'page' => $page_counter,
                'type' => 'figures_main'
            );
            
            // Individual figure bookmarks
            foreach ($figures as $figure_num => $pathway_name) {
                $bookmarks[] = array(
                    'title' => "Figure {$figure_num}: {$pathway_name}",
                    'level' => 1,
                    'anchor' => 'figure_' . $figure_num,
                    'page' => $page_counter++,
                    'type' => 'figure',
                    'figure_number' => $figure_num,
                    'pathway_name' => $pathway_name
                );
            }
        }
        
        // Disclaimer bookmark
        $bookmarks[] = array(
            'title' => 'Important Disclaimer',
            'level' => 0,
            'anchor' => 'disclaimer_section',
            'page' => $page_counter,
            'type' => 'disclaimer'
        );
        
        $bookmark_data['bookmarks'] = $bookmarks;
        $bookmark_data['stats'] = $stats;
        $bookmark_data['figures'] = $figures;
        
        return $bookmark_data;
    }
    


private static function get_available_figures_for_categories($categories) {
        $figures = array();
        
        foreach ($categories as $category_name => $variants) {
            if (!empty($variants) && isset(self::PATHWAY_IMAGE_URLS[$category_name])) {
                $figure_num = self::FIGURE_NUMBERS[$category_name] ?? null;
                if ($figure_num) {
                    $figures[$figure_num] = $category_name;
                }
            }
        }
        
        ksort($figures); // Sort by figure number
        return $figures;
    }
    private static function get_category_risk_summary($variants) {
        $summary = array('high' => 0, 'moderate' => 0, 'low' => 0, 'unknown' => 0);
        
        foreach ($variants as $variant) {
            $result = $variant['Result'] ?? 'Unknown';
            switch ($result) {
                case '+/+': $summary['high']++; break;
                case '+/-': $summary['moderate']++; break;
                case '-/-': $summary['low']++; break;
                default: $summary['unknown']++; break;
            }
        }
        
        return $summary;
    }
    
    /**
     * Extract file name from database wpub_user_uploads table
     */
  // IN PDF GENERATOR CLASS, FIND extract_file_name_from_database():
private static function extract_file_name_from_database($enhanced_data) {
    
    // 🔥 FIRST PRIORITY: Check if enhanced_data has file_info
    if (isset($enhanced_data['file_info']['original_filename']) && !empty($enhanced_data['file_info']['original_filename'])) {
        $filename = $enhanced_data['file_info']['original_filename'];
        error_log("MTHFR: Using filename from file_info: " . $filename);
        return preg_match('/\.zip$/i', $filename) ? $filename : $filename . '.zip';
    }
    
    // Second priority: Other enhanced data fields
    if (isset($enhanced_data['filename']) && !empty($enhanced_data['filename'])) {
        $filename = $enhanced_data['filename'];
        error_log("MTHFR: Using filename from enhanced_data: " . $filename);
        return preg_match('/\.zip$/i', $filename) ? $filename : $filename . '.zip';
    }
    
    // Third priority: Form data
    if (isset($_FILES['genetic_file']['name'])) {
        $filename = $_FILES['genetic_file']['name'];
        error_log("MTHFR: Using filename from FILES: " . $filename);
        return preg_match('/\.zip$/i', $filename) ? $filename : $filename . '.zip';
    }
    
    if (isset($_POST['zip_filename'])) {
        $filename = $_POST['zip_filename'];
        error_log("MTHFR: Using filename from POST: " . $filename);
        return preg_match('/\.zip$/i', $filename) ? $filename : $filename . '.zip';
    }
    
    // 🔥 LAST PRIORITY: Database lookup (avoid this for old files)
    global $wpdb;
    $user_id = get_current_user_id();
    
    if ($user_id) {
        $table_name = $wpdb->prefix . 'user_uploads';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        
        if ($table_exists) {
            $latest_upload = $wpdb->get_row($wpdb->prepare("
                SELECT file_name 
                FROM {$table_name} 
                WHERE user_id = %d 
                ORDER BY created_at DESC 
                LIMIT 1
            ", $user_id));
            
            if ($latest_upload && !empty($latest_upload->file_name)) {
                $filename = $latest_upload->file_name;
                error_log("MTHFR: Using filename from database (LAST RESORT): " . $filename);
                return preg_match('/\.zip$/i', $filename) ? $filename : $filename . '.zip';
            }
        }
    }
    
    error_log("MTHFR: Using default filename");
    return 'genetic-data-' . date('Y-m-d') . '.zip';
}
    /**
     * FIXED: Process data and only show ALLOWED categories
     */
   private static function process_enhanced_data_with_ordering($enhanced_data, $report_type) {
    
    error_log('MTHFR Fixed: Processing data from rs10306114 field with category filtering...');
    
    $processed_data = array(
        'variants' => array(),
        'categories' => array(),
        'report_info' => array(
            'type' => $report_type,
            'total_variants' => 0
        )
    );
    
    // Handle different data formats
    $raw_variants = array();
    
    if (isset($enhanced_data['raw_variants']) && is_array($enhanced_data['raw_variants'])) {
        $raw_variants = $enhanced_data['raw_variants'];
    } elseif (is_array($enhanced_data) && !empty($enhanced_data)) {
        if (isset($enhanced_data[0]) && is_array($enhanced_data[0])) {
            $raw_variants = $enhanced_data;
        }
    }
    
    error_log('MTHFR Fixed: Found ' . count($raw_variants) . ' raw variants');
    
    // ENHANCED: Process variants and only include ALLOWED categories
    $temp_categories = array();
    $comt_debug_count = 0;
    
    foreach ($raw_variants as $variant) {
        $processed_variant = self::map_variant_to_python_format($variant, $report_type);
        if ($processed_variant) {
            $category = $processed_variant['rs10306114'] ?? 'Other';
            
            // ENHANCED: Debug COMT specifically
            if (stripos($category, 'comt') !== false || 
                stripos($processed_variant['SNP Name'] ?? '', 'comt') !== false ||
                stripos($variant['gene'] ?? $variant['Gene'] ?? '', 'comt') !== false) {
                $comt_debug_count++;
                error_log("COMT DEBUG #{$comt_debug_count}: SNP {$processed_variant['SNP ID']} -> Category: {$category}");
            }
            
            // ✅ ONLY include variants from ALLOWED categories
            if (in_array($category, self::ALLOWED_BOOKMARK_CATEGORIES)) {
                $processed_data['variants'][] = $processed_variant;
                
                if (!isset($temp_categories[$category])) {
                    $temp_categories[$category] = array();
                }
                $temp_categories[$category][] = $processed_variant;
                
                if (stripos($category, 'comt') !== false) {
                    error_log("FIXED: COMT variant {$processed_variant['SNP ID']} INCLUDED in category: {$category}");
                }
            } else {
                if (stripos($category, 'comt') !== false) {
                    error_log("FIXED: COMT variant {$processed_variant['SNP ID']} EXCLUDED from category: {$category} (not in allowed list)");
                }
            }
        }
    }
    
    error_log("COMT SUMMARY: Found {$comt_debug_count} COMT-related variants total");
    
    // Order categories according to CATEGORY_ORDER (only allowed ones)
    $processed_data['categories'] = self::order_categories_filtered($temp_categories);
    $processed_data['report_info']['total_variants'] = count($processed_data['variants']);
    
    error_log('MTHFR Fixed: Processed ' . count($processed_data['variants']) . ' variants into ' . count($processed_data['categories']) . ' allowed categories');
    
    // ENHANCED: Debug final category counts
    foreach ($processed_data['categories'] as $cat_name => $variants) {
        error_log("FINAL: Category '{$cat_name}' has " . count($variants) . ' variants');
        if (stripos($cat_name, 'comt') !== false) {
            error_log("COMT FINAL: {$cat_name} has " . count($variants) . ' variants - SUCCESS!');
        }
    }
    
    return $processed_data;
}
    
    /**
     * FIXED: Order categories and only include ALLOWED ones
     */
    private static function order_categories_filtered($temp_categories) {
        $ordered_categories = array();
        
        // Only add ALLOWED categories in predefined order
        foreach (self::CATEGORY_ORDER as $category_name) {
            if (isset($temp_categories[$category_name]) && in_array($category_name, self::ALLOWED_BOOKMARK_CATEGORIES)) {
                $ordered_categories[$category_name] = $temp_categories[$category_name];
                unset($temp_categories[$category_name]);
            }
        }
        
        // Add any remaining ALLOWED categories at the end
        foreach ($temp_categories as $category_name => $variants) {
            if (in_array($category_name, self::ALLOWED_BOOKMARK_CATEGORIES)) {
                $ordered_categories[$category_name] = $variants;
            }
        }
        
        return $ordered_categories;
    }
    
    /**
     * ✅ FIXED: Enhanced category determination with rs10306114 priority
     */
    private static function determine_category_enhanced($variant, $report_type) {
    $snp_id = $variant['rsid'] ?? $variant['SNP ID'] ?? $variant['id'] ?? '';
    $gene = $variant['gene'] ?? $variant['Gene'] ?? '';
    $snp_name = $variant['SNP Name'] ?? $variant['SNP_Name'] ?? '';
    
    // 🚨 CRITICAL COMT FIX: Force COMT gene variants into COMT Activity category
    if (!empty($gene) && strtoupper(trim($gene)) === 'COMT') {
        error_log("COMT OVERRIDE: Forcing {$snp_id} with gene {$gene} into COMT Activity category");
        return 'COMT Activity';
    }
    
    // 🚨 CRITICAL COMT FIX: Check for COMT in SNP name
    if (!empty($snp_name) && stripos($snp_name, 'comt') !== false) {
        error_log("COMT OVERRIDE: Forcing {$snp_id} with SNP name '{$snp_name}' into COMT Activity category");
        return 'COMT Activity';
    }
    
    // 🚨 CRITICAL COMT FIX: Check specific COMT RSIDs
    $known_comt_rsids = [
        'rs4680', 'rs6269', 'rs4633', 'rs4646312', 'rs4646316', 'rs165656', 
        'rs165774', 'rs165599', 'rs174696', 'rs174699', 'rs769224', 'rs740601',
        'rs8192488', 'rs2239393', 'rs933271', 'rs1544325', 'rs5993883', 
        'rs739368', 'rs9332377'
    ];
    
    if (in_array($snp_id, $known_comt_rsids)) {
        error_log("COMT OVERRIDE: Forcing known COMT RSID {$snp_id} into COMT Activity category");
        return 'COMT Activity';
    }
    
    // ✅ PRIORITY 1: Check rs10306114 field (main category field)
    if (isset($variant['rs10306114']) && !empty($variant['rs10306114'])) {
        $category = trim($variant['rs10306114']);
        if ($category !== 'nan' && $category !== 'NaN' && $category !== '') {
            error_log("FIXED: Found rs10306114 category for {$snp_id} -> {$category}");
            return $category;
        }
    }
    
    // ✅ PRIORITY 2: Check category field as backup
    if (isset($variant['category']) && !empty($variant['category'])) {
        $category = trim($variant['category']);
        if ($category !== 'nan' && $category !== 'NaN' && $category !== '') {
            error_log("FIXED: Found category field for {$snp_id} -> {$category}");
            return $category;
        }
    }
    
    // ✅ PRIORITY 3: Check Category field (capitalized) as backup
    if (isset($variant['Category']) && !empty($variant['Category'])) {
        $category = trim($variant['Category']);
        if ($category !== 'nan' && $category !== 'NaN' && $category !== '') {
            error_log("FIXED: Found Category field for {$snp_id} -> {$category}");
            return $category;
        }
    }
    
    // ✅ PRIORITY 4: Check Pathway field as backup
    if (isset($variant['Pathway']) && !empty($variant['Pathway'])) {
        $category = trim($variant['Pathway']);
        if ($category !== 'nan' && $category !== 'NaN' && $category !== '') {
            error_log("FIXED: Found Pathway field for {$snp_id} -> {$category}");
            return $category;
        }
    }
    
    // ✅ ENHANCED PRIORITY 5: Gene-based mapping with COMT emphasis
    if (!empty($gene)) {
        $gene_upper = strtoupper(trim($gene));
        
        // Other standard gene mappings
        if (in_array($gene_upper, ['MTHFR', 'MTR', 'MTRR', 'AHCY', 'CBS'])) {
            return 'Methylation & Methionine/Homocysteine Pathways';
        }
        
        if (in_array($gene_upper, ['CYP1A1', 'CYP1A2', 'CYP1B1', 'CYP2A6', 'CYP2C9', 'CYP2C19', 'CYP2D6', 'CYP2E1', 'CYP3A4'])) {
            return 'Liver Detox - Phase I';
        }
        
        if (in_array($gene_upper, ['GSTM1', 'GSTT1', 'GSTP1', 'UGT1A1', 'SULT1A1', 'NAT1', 'NAT2'])) {
            return 'Liver Detox - Phase II';
        }
    }
    
    // ✅ PRIORITY 6: Use database gene-to-pathway mapping if available
    if (class_exists('MTHFR_Excel_Database') && !empty($gene)) {
        $db_category = MTHFR_Excel_Database::determine_pathway_from_gene($gene);
        if (!empty($db_category) && $db_category !== 'Unknown') {
            error_log("ENHANCED: Database mapping for {$snp_id} gene {$gene} -> {$db_category}");
            return $db_category;
        }
    }
    
    error_log("FIXED DEBUG: No category mapping found, using 'Other' for SNP: {$snp_id} (gene: {$gene})");
    return 'Other';
}

    
    /**
     * ✅ FIXED: Map variant with enhanced category determination
     */
    private static function map_variant_to_python_format($variant, $report_type) {
        if (!is_array($variant)) {
            return null;
        }
        
        $mapped_variant = array(
            'SNP ID' => $variant['rsid'] ?? $variant['SNP ID'] ?? $variant['id'] ?? 'Unknown',
            'SNP Name' => $variant['SNP Name'] ?? $variant['SNP_Name'] ?? self::create_snp_name(
                $variant['gene'] ?? $variant['Gene'] ?? '',
                $variant['SNP'] ?? ''
            ),
            'Risk Allele' => $variant['risk_allele'] ?? $variant['Risk Allele'] ?? 'Unknown',
            'Your Allele' => $variant['genotype'] ?? $variant['Your Allele'] ?? 'Unknown',
            'Result' => $variant['result'] ?? $variant['Result'] ?? self::calculate_result(
                $variant['genotype'] ?? $variant['Your Allele'] ?? '',
                $variant['risk_allele'] ?? $variant['Risk Allele'] ?? ''
            ),
            'rs10306114' => self::determine_category_enhanced($variant, $report_type)
        );
        
        // Validate essential fields
        if ($mapped_variant['SNP ID'] === 'Unknown' && $mapped_variant['SNP Name'] === 'Unknown') {
            return null;
        }
        
        return $mapped_variant;
    }

    /**
     * Create SNP name from gene and SNP info
     */
    private static function create_snp_name($gene, $snp) {
        if (!empty($gene) && !empty($snp)) {
            return $gene . ' ' . $snp;
        } elseif (!empty($gene)) {
            return $gene;
        } elseif (!empty($snp)) {
            return $snp;
        }
        return 'Unknown';
    }
    
    /**
     * OPTIMIZED: Try mPDF generation with compact layout and filtered bookmarks
     */
    private static function try_mpdf_generation_optimized($processed_data, $product_name, $report_type, $has_subscription, $folder_name) {
        try {
            if (!class_exists('Mpdf\Mpdf')) {
                error_log('MTHFR Fixed: mPDF class not available');
                return null;
            }
            
            $temp_dir = self::create_temp_directory();
            if (!$temp_dir) {
                error_log('MTHFR Fixed: Could not create temporary directory');
                return null;
            }
            
            $config = array(
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 8,     
                'margin_right' => 8,    
                'margin_top' => 15,     
                'margin_bottom' => 50,  
                'default_font' => 'arial',
                'tempDir' => $temp_dir,
                'simpleTables' => true,
                'packTableData' => true,
                'shrink_tables_to_fit' => 1,
                'autoScriptToLang' => false,
                'autoLangToFont' => false,
                'allow_output_buffering' => true,
                'debug' => false
            );
            
            error_log('MTHFR Fixed: Creating mPDF instance...');
            $mpdf = new \Mpdf\Mpdf($config);
            
            $mpdf->SetTitle($product_name . ' - ' . $report_type . ' Genetic Report (Fixed Categories)');
            $mpdf->SetAuthor('MTHFR Support');
            $mpdf->SetCreator('MTHFR Plugin v2.5 Fixed');
            $mpdf->SetSubject('Genetic Analysis Report - ' . $folder_name);
            
            $mpdf->SetHTMLFooter('
                <div style="font-size: 7pt; line-height: 1.1; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; margin-top: 8px;">
This report is intended to translate your results into an easier to understand form. It is not intended to diagnose or treat. For diagnosis or treatment, please present this to your doctor (or find a doctor on MTHFRSupportTM website under Find a Practitioner. Additionally, genetic mutations are flags that something **could** be wrong and not a guarantee that you are having all or any of the associated issues. Other factors like environment, ethnic background, diet, age, personal history, etc all have a factor in whether a mutation starts to present itself or not and when. Copyright all rights reserved MTHFR SupportTM                
                </div>
            ');
            
            $html_content = self::generate_enhanced_pdf_content_optimized($processed_data, $product_name, $folder_name, $report_type);
           



            if (empty($html_content)) {
                error_log('MTHFR Fixed: Generated HTML content is empty');
                return null;
            }
            
            error_log('MTHFR Fixed: Writing HTML to mPDF (size: ' . strlen($html_content) . ' bytes)');
            
            if (ob_get_level()) {
                ob_clean();
            }
            
            $mpdf->WriteHTML($html_content);
            $pdf_content = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
            
            
            unset($mpdf);
            unset($html_content);
            self::cleanup_temp_directory($temp_dir);
            self::cleanup_memory();
            
            if (!$pdf_content || !self::is_valid_pdf($pdf_content)) {
                error_log('MTHFR Fixed: Generated content is not valid PDF');
                return null;
            }
            
            error_log('MTHFR Fixed: mPDF generated successfully, size: ' . strlen($pdf_content) . ' bytes');
            return $pdf_content;
            
        } catch (Exception $e) {
            error_log('MTHFR Fixed: mPDF generation exception: ' . $e->getMessage());
            
            if (isset($mpdf)) {
                unset($mpdf);
            }
            if (isset($temp_dir)) {
                self::cleanup_temp_directory($temp_dir);
            }
            
            return null;
        }
    }
    
    /**
     * FIXED: Enhanced PDF content generation - Only allowed categories
     */
    private static function generate_enhanced_pdf_content_optimized($processed_data, $product_name, $folder_name, $report_type) {

        $html_parts = array();
       
        
        // OPTIMIZED: Enhanced CSS with compact styling
        $html_parts[] = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($product_name . ' - ' . $report_type . ' Report') . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 9pt; 
            line-height: 1.2; 
            margin: 0; 
            padding: 15px; 
            padding-bottom: 50px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1B80B6;
            margin-bottom: 12px;
            font-family: Arial, sans-serif;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            padding: 8px;
        }
        .logo-cell {
            width: 40%;
        }
        .logo-cell img {
            height: 70px;
            width: 35%;
        }
        .title-cell {
            text-align: right;
        }
        .main-title {
            font-weight: bold;
            font-size: 16px;
            color: #1B80B6;
            margin-bottom: 3px;
        }
        .sub-title {
            font-size: 12px;
            color: #444;
            margin-bottom: 3px;
        }
        .file-name {
            font-size: 10px;
            color: #888;
            font-weight: normal;
        }
        .variant-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 12px; 
            font-size: 8pt; 
        }
        .variant-table th { 
            background: #1B80B6; 
            color: white; 
            padding: 5px 3px; 
            text-align: center; 
            font-weight: bold; 
            border: 1px solid #ddd; 
            font-size: 8pt;
        }
        .variant-table td { 
            padding: 3px 2px; 
            text-align: center; 
            border: 1px solid #ddd; 
            vertical-align: middle; 
            font-size: 8pt;
            line-height: 1.1;
        }
        .even-row { background: #f8f8f8; }
        .odd-row { background: white; }
        .category-header { 
            background: #1B80B6; 
            color: white; 
            font-size: 10pt; 
            font-weight: bold; 
            padding: 8px; 
            text-align: center; 
            margin-bottom: 5px;
        }
        .category-header-with-figure { 
            background: #1B80B6; 
            color: white; 
            font-size: 10pt; 
            font-weight: bold; 
            padding: 8px; 
            text-align: center; 
        }
        .figure-reference {
            font-style: italic;
            color: #e0e0e0;
            font-size: 9pt;
        }
        .result-high { background: #ffebee; color: #c62828; font-weight: bold; }
        .result-moderate { background: #fff8e1; color: #f57c00; font-weight: bold; }
        .result-low { background: #e8f5e8; color: #2e7d32; font-weight: bold; }
        .result-unknown { background: #f5f5f5; color: #666; }
        .disclaimer { 
            margin-top: 20px; 
            padding: 12px; 
            background: #fff3cd; 
            border: 1px solid #ffeaa7; 
            font-size: 8pt; 
            line-height: 1.3; 
        }
        .page-break { page-break-after: always; }
        .pathway-image {
            text-align: center;
            margin: 10px 0;
            page-break-inside: avoid;
            max-height: 500px;
            overflow: hidden;
        }
        .pathway-image img {
            max-width: 90%;
            max-height: 400px;
            height: auto;
            border: 1px solid #ddd;
            object-fit: contain;
        }
        .figure-caption {
            font-size: 8pt;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        .section-title {
            color: #1B80B6;
            font-weight: bold;
            margin: 15px 0 8px 0;
        }
        .subsection-title {
            color: #2C5282;
            font-weight: bold;
            margin: 12px 0 6px 0;
        }
        a[name] {
            position: relative;
            top: -20px;
            visibility: hidden;
        }
    </style>
</head>
<body>';

    // Header function
    $generate_header = function() use ($product_name, $folder_name) {
        return '
<table class="header-table">
    <tr>
        <td class="logo-cell">
            <img src="https://www.mthfrsupport.org/wp-content/uploads/loogo.png" alt="Logo" style="height: 70px;">
        </td>
        <td style="vertical-align: middle; text-align: right;">
            <div style="font-weight: bold; font-size: 16px;">MTHFRSupport ' . esc_html($product_name) . '</div>
            <div style="font-size: 10px; color: #888; font-weight: normal;">' . esc_html($folder_name) . '</div>
        </td>
    </tr>
</table>';
    };

    // Add header for first page
    $html_parts[] = $generate_header();
   
    $categories = $processed_data['categories']; // Only allowed categories
    $category_count = 0;
    $total_categories = count($categories);
    $figures_to_include = array();
    
    // Process ONLY allowed categories
    foreach ($categories as $category_name => $variants) {
        $category_count++;
        
        // Add header at the start of each category (except first)
        if ($category_count > 1) {
            $html_parts[] = $generate_header();
        }
        
        // Add bookmark anchor for all categories (since we only have allowed ones now)
        $bookmark_id = 'cat_' . sanitize_title($category_name);
        $html_parts[] = '<bookmark content="' . esc_attr($category_name) . '" level="0" />';
        $html_parts[] = '<a name="' . $bookmark_id . '"></a>';
        
        // Check if this category has a figure
        $figure_number = self::get_figure_number($category_name);
        $has_pathway_image = isset(self::PATHWAY_IMAGE_URLS[$category_name]);
        $figure_text = ($figure_number && $has_pathway_image) ? " (See Figure {$figure_number})" : "";
        
        // Include figures for categories with pathway images
        if ($figure_number && $has_pathway_image) {
            $figures_to_include[$figure_number] = $category_name;
        }
        
        // Category content with header
        $html_parts[] = '
           <div class="category-header-with-figure">
                <h3 class="subsection-title" style="color: white; margin: 0;">' . esc_html($category_name) . '</h3>
                ' . ($figure_text ? '<span class="figure-reference">' . esc_html($figure_text) . '</span>' : '') . '
            </div>';
        
        // Process variants in batches
        $limited_variants = array_slice($variants, 0, self::MAX_VARIANTS_PER_PAGE);
        $total_variants = count($limited_variants);
        $variants_per_page = self::BATCH_SIZE;
        
        for ($i = 0; $i < $total_variants; $i += $variants_per_page) {
            // Add header for new page (except first batch)
            if ($i > 0) {
                $html_parts[] = '<div class="page-break"></div>';
                $html_parts[] = $generate_header();
                
                // Add category header again for continuation
                $html_parts[] = '
                <div class="category-header-with-figure">
                    <h3 class="subsection-title" style="color: white; margin: 0;">' . esc_html($category_name) . ' </h3>
                    ' . ($figure_text ? '<span class="figure-reference">' . esc_html($figure_text) . '</span>' : '') . '
                </div>';
            }
            
            // Start table for this batch
            $html_parts[] = '
            <table class="variant-table">
                <thead>
                    <tr>
                        <th style="width: 16%;">SNP ID</th>
                        <th style="width: 26%;">SNP Name</th>
                        <th style="width: 16%;">Risk Allele</th>
                        <th style="width: 16%;">Your Allele</th>
                        <th style="width: 26%;">Result</th>
                    </tr>
                </thead>
                <tbody>';
            
            // Add variants for this batch
            $batch_variants = array_slice($limited_variants, $i, $variants_per_page);
            foreach ($batch_variants as $index => $variant) {
                $row_class = (($i + $index) % 2 == 0) ? 'even-row' : 'odd-row';
                $result_class = self::get_result_css_class($variant['Result'] ?? 'Unknown');
                
                $html_parts[] = '
                    <tr class="' . $row_class . '">
                        <td>' . esc_html($variant['SNP ID'] ?? 'Unknown') . '</td>
                        <td>' . esc_html($variant['SNP Name'] ?? 'Unknown') . '</td>
                        <td>' . esc_html($variant['Risk Allele'] ?? 'Unknown') . '</td>
                        <td>' . esc_html($variant['Your Allele'] ?? 'Unknown') . '</td>
                        <td class="' . $result_class . '">' . esc_html($variant['Result'] ?? 'Unknown') . '</td>
                    </tr>';
            }
            
            $html_parts[] = '</tbody></table>';
        }
        
        // Add page break after every category EXCEPT the last one
        if ($category_count < $total_categories) {
            $html_parts[] = '<div class="page-break"></div>';
        }
    }
    
    // Add figures section - ONLY for allowed categories with pathway images
    if (in_array(strtolower($report_type), ['variant','methylation'], true) && !empty($figures_to_include)) {
        $html_parts[] = '<div class="page-break"></div>';
        $html_parts[] = $generate_header();
        
        // Add bookmark anchor for Figures section
        $html_parts[] = '<bookmark content="Figures" level="0" />';
        $html_parts[] = '<a name="figures_section"></a>';
        
        // Sort figures by number and remove duplicates
        ksort($figures_to_include);
        $processed_figures = array();
        $figure_count = 0;
        
        foreach ($figures_to_include as $figure_num => $pathway_name) {
            // Skip if we already processed this figure number
            if (in_array($figure_num, $processed_figures)) {
                continue;
            }
            $processed_figures[] = $figure_num;

            // Only show figures that actually have pathway images
            if (isset(self::PATHWAY_IMAGE_URLS[$pathway_name])) {
                $image_url = self::PATHWAY_IMAGE_URLS[$pathway_name];
                $figure_count++;

                // Page break for figures after first one
                if ($figure_count > 1) {
                    $html_parts[] = '<div class="page-break"></div>';
                    $html_parts[] = $generate_header();
                }

                // Add bookmark anchor for each figure
                $html_parts[] = '<bookmark content="Figure ' . $figure_num . '" level="1" />';
                $html_parts[] = '<a name="figure_' . $figure_num . '"></a>';

                $html_parts[] = '
                <div class="pathway-image" style="margin: 20px 0; page-break-inside: avoid;">
                    <img src="' . esc_url($image_url) . '" alt="Figure ' . $figure_num . ': ' . esc_attr($pathway_name) . '" style="max-width: 100%; max-height: 600px; height: auto; border: 1px solid #ddd; margin: 0 auto; display: block; object-fit: contain;">
                    <div class="figure-caption" style="font-size: 7px; line-height: 1.3; color: #555; margin-top: 8px; text-align: justify;">
                        
                    </div>
                </div>';
            }
        }
    }
    
    // Add final page break and disclaimer
    $html_parts[] = '<div class="page-break"></div>';
    $html_parts[] = $generate_header();
    
    $html_parts[] = '
    <div class="disclaimer">
        <h3 style="color: #1B80B6; margin-top: 0;">Important Disclaimer</h3>
        <p>This report is intended to translate your results into an easier to understand form. It is not intended to diagnose or treat. For diagnosis or treatment, please present this to your doctor (or find a doctor on MTHFRSupport™ website under \'Find a Practitioner\').</p>
        <p>Additionally, genetic mutations are flags that something <strong>could</strong> be wrong and not a guarantee that you are having all or any of the associated issues. Other factors like environment, ethnic background, diet, age, personal history, etc all have a factor in whether a mutation starts to present itself or not and when.</p>
        <p style="margin-bottom: 0;"><strong>Copyright all rights reserved MTHFR Support™.</strong></p>
    </div>';
    
    // Close HTML
    $html_parts[] = '</body></html>';
    
    return implode('', $html_parts);
}

    
    /**
     * Generate fallback text report with proper file name
     */
    private static function generate_fallback_report($product_name, $report_type, $error_message = '', $folder_name = '') {
        $content = array();
        $content[] = "=== " . $product_name . " - " . $report_type . " Report ===";
        $content[] = "";
        
        if (!empty($folder_name)) {
            $content[] = "File Name: " . $folder_name;
            $content[] = "";
        }
        
        $content[] = "Generated: " . date('Y-m-d H:i:s');
        $content[] = "Plugin Version: 2.5 Fixed";
        $content[] = "";
        
        if ($error_message) {
            $content[] = "Note: " . $error_message;
            $content[] = "";
        }
        
        $content[] = "This is a simplified text report.";
        $content[] = "For a full detailed report, please contact support.";
        $content[] = "";
        $content[] = "IMPORTANT DISCLAIMER:";
        $content[] = "This genetic analysis is for informational purposes only.";
        $content[] = "Always consult with qualified healthcare providers.";
        $content[] = "";
        
        return implode("\n", $content);
    }
        
    /**
     * Create temporary directory for mPDF
     */
    private static function create_temp_directory() {
        $upload_dir = wp_upload_dir();
        $temp_base = $upload_dir['basedir'] . '/mthfr_temp';
        
        if (!file_exists($temp_base)) {
            if (!wp_mkdir_p($temp_base)) {
                error_log('MTHFR Fixed: Could not create temp base directory');
                return false;
            }
        }
        
        $temp_dir = $temp_base . '/mpdf_' . uniqid();
        
        if (!wp_mkdir_p($temp_dir)) {
            error_log('MTHFR Fixed: Could not create temp directory: ' . $temp_dir);
            return false;
        }
        
        return $temp_dir;
    }
    
    /**
     * Cleanup temporary directory
     */
    private static function cleanup_temp_directory($temp_dir) {
        if (!$temp_dir || !file_exists($temp_dir)) {
            return;
        }

        try {
            // Recursively delete all files and subdirectories
            $items = scandir($temp_dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $item_path = $temp_dir . DIRECTORY_SEPARATOR . $item;

                if (is_dir($item_path)) {
                    self::cleanup_temp_directory($item_path); // Recursive call
                } else {
                    unlink($item_path);
                }
            }

            // Now remove the empty directory
            rmdir($temp_dir);

        } catch (Exception $e) {
            error_log('MTHFR Fixed: Error cleaning temp directory "' . $temp_dir . '": ' . $e->getMessage());
        }
    }
    
    /**
     * Validate PDF content
     */
    private static function is_valid_pdf($content) {
        if (!$content || !is_string($content)) {
            return false;
        }
        
        if (strlen($content) < 100) {
            return false;
        }
        
        // Check PDF header
        if (strpos($content, '%PDF') !== 0) {
            error_log('MTHFR Fixed: Content does not start with PDF header');
            return false;
        }
        
        // Check for common PDF structure
        if (strpos($content, '%%EOF') === false) {
            error_log('MTHFR Fixed: Content does not contain PDF EOF marker');
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculate result based on genotype and risk allele
     */
    private static function calculate_result($genotype, $risk_allele) {
        if (!$genotype || !$risk_allele) {
            return 'Unknown';
        }
        
        $genotype = strtoupper(trim($genotype));
        $risk_allele = strtoupper(trim($risk_allele));
        
        if (strlen($genotype) < 2) {
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
     * Get result CSS class
     */
    private static function get_result_css_class($result) {
        switch ($result) {
            case '+/+': return 'result-high';
            case '+/-': return 'result-moderate';
            case '-/-': return 'result-low';
            default: return 'result-unknown';
        }
    }
    
    /**
     * Get figure number for a pathway category
     */
    private static function get_figure_number($category_name) {
        return self::FIGURE_NUMBERS[$category_name] ?? null;
    }

    /**
     * Count available figures for categories
     */
    private static function count_available_figures($categories) {
        $figure_count = 0;
        foreach (array_keys($categories) as $category_name) {
            if (isset(self::FIGURE_NUMBERS[$category_name])) {
                $figure_count++;
            }
        }
        return $figure_count;
    }
    
    /**
     * Memory optimization functions
     */
    private static function optimize_memory_settings() {
        // Increase memory limit if possible
        $current_limit = ini_get('memory_limit');
        if ($current_limit && $current_limit !== '-1') {
            $current_mb = self::parse_memory_limit($current_limit);
            if ($current_mb < 512) {
                @ini_set('memory_limit', '512M');
                error_log('MTHFR Fixed: Increased memory limit to 512M');
            }
        }
        
        // Set execution time
        @ini_set('max_execution_time', 300);
        
        // Enable garbage collection
        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }
    
    private static function cleanup_memory() {
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
            if ($collected > 0) {
                error_log("MTHFR Fixed: Garbage collected {$collected} cycles");
            }
        }
        
        // Clear WordPress object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    private static function parse_memory_limit($limit) {
        $limit = trim($limit);
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'g': return $value * 1024;
            case 'm': return $value;
            case 'k': return $value / 1024;
            default: return $value / (1024 * 1024);
        }
    }
    
    private static function format_memory($bytes) {
        $mb = $bytes / 1024 / 1024;
        return round($mb, 2) . 'MB';
    }
    
    private static function format_memory_stats() {
        return array(
            'start' => self::format_memory(self::$memory_stats['start']),
            'end' => self::format_memory(self::$memory_stats['end']),
            'peak' => self::format_memory(self::$memory_stats['peak']),
            'used' => self::format_memory(self::$memory_stats['end'] - self::$memory_stats['start']),
            'processing_time' => round(self::$processing_time, 2) . 's'
        );
    }
    
    /**
     * Legacy compatibility method
     */
    private static function detect_output_type($data) {
        if (strpos($data, '%PDF') === 0) {
            return 'PDF';
        } elseif (strpos($data, '<!DOCTYPE html') !== false || strpos($data, '<html') !== false) {
            return 'HTML';
        } else {
            return 'Text';
        }
    }
}

class MTHFR_PDF_Bookmark_Config {
    
    /**
     * Configure mPDF instance with enhanced bookmark settings
     */
    public static function configure_mpdf_bookmarks($mpdf, $bookmark_data) {
        // Set bookmark options
        $mpdf->h2bookmarks = array('H1' => 0, 'H2' => 1, 'H3' => 2);
        $mpdf->bookmarkStyles = array(
            0 => array('color' => array(27, 128, 182), 'style' => 'B'),
            1 => array('color' => array(73, 80, 87), 'style' => ''),
            2 => array('color' => array(108, 117, 125), 'style' => 'I')
        );
        
        return $mpdf;
    }
    
    /**
     * Generate bookmark outline for PDF viewers
     */
    public static function generate_pdf_outline($bookmark_data) {
        $outline = array();
        
        foreach ($bookmark_data['bookmarks'] as $bookmark) {
            $outline[] = array(
                'title' => $bookmark['title'],
                'level' => $bookmark['level'],
                'page' => $bookmark['page'],
                'anchor' => $bookmark['anchor']
            );
        }
        
        return $outline;
    }
}


?>