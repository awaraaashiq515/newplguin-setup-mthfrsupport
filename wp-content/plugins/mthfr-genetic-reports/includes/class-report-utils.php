<?php
/**
 * MTHFR Report Utils Class
 * Utility functions for report generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_Report_Utils {

    /**
     * Determine report type from product name
     */
    public static function determine_report_type($product_name) {
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
     * Get category filters for report type
     */
    public static function get_category_filters_for_report_type($report_type) {
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
                return null;
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
     * Format user genotype
     */
    public static function format_user_genotype($variant) {
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
    public static function create_snp_name($gene, $snp_info) {
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
    public static function calculate_result($genotype, $risk_allele) {
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
}