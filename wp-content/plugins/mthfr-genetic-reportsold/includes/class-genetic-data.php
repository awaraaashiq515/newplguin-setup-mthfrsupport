<?php
/**
 * MTHFR Genetic Data Class
 * Handles genetic data constants and sample data generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_Genetic_Data {
    
    // Gene references mapping
    public static $gene_references = array(
        'Primary' => array('ACE2', 'IFNAR1', 'IL12A-AS1', 'IL12B', 'CIITA', 'ABCG2', 'IFNG', 'B9D2',
                          'TGFB1', 'B9D2/TGFB1', 'IFNGR1', 'MRC1', 'BAHD1', 'TNF', 'FAS', 'IL6', 
                          'NLRP3', 'FURIN', 'TMPRSS2', 'NOS3', 'PYCARD', 'CASP1', 'IL1B', 'HMGB1'),
        'Secondary' => array('FUT2', 'SOD3', 'HFE', 'IFIH1', 'NOD2', 'G6PD', 'PTGS2', 'COX2', 'PTGS2/COX2')
    );
    
    public static function create_sample_data($count = null) {
        $sample_data = array(
            array('rsid' => 'rs1801133', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'MTHFR', 'position' => 'chr1:11856378'),
            array('rsid' => 'rs1801131', 'genotype' => 'AC', 'risk_allele' => 'C', 'gene' => 'MTHFR', 'position' => 'chr1:11854476'),
            array('rsid' => 'rs662', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'PON1', 'position' => 'chr7:95308134'),
            array('rsid' => 'rs1805087', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'MTR', 'position' => 'chr1:236885200'),
            array('rsid' => 'rs1801394', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'MTRR', 'position' => 'chr5:7870860'),
            array('rsid' => 'rs234715', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'FOLH1', 'position' => 'chr11:49164404'),
            array('rsid' => 'rs1051298', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'RFC1', 'position' => 'chr21:46961093'),
            array('rsid' => 'rs4680', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'COMT', 'position' => 'chr22:19963748'),
            array('rsid' => 'rs6323', 'genotype' => 'GT', 'risk_allele' => 'T', 'gene' => 'MAOA', 'position' => 'chrX:43654907'),
            array('rsid' => 'rs1799978', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'DHFR', 'position' => 'chr5:79950116'),
            array('rsid' => 'rs2298771', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'SHMT1', 'position' => 'chr17:18323351'),
            array('rsid' => 'rs72552713', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'BHMT', 'position' => 'chr5:78408395'),
            array('rsid' => 'rs28399499', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'CYP2D6', 'position' => 'chr22:42523805'),
            array('rsid' => 'rs267598', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'CBS', 'position' => 'chr21:44508052'),
            array('rsid' => 'rs1695', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'GSTP1', 'position' => 'chr11:67585218'),
            array('rsid' => 'rs4244285', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'CYP2C19', 'position' => 'chr10:94781859'),
            array('rsid' => 'rs1065852', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'CYP2D6', 'position' => 'chr22:42523943'),
            array('rsid' => 'rs776746', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'CYP3A5', 'position' => 'chr7:99672916'),
            array('rsid' => 'rs1800497', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'ANKK1', 'position' => 'chr11:113270828'),
            array('rsid' => 'rs25531', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'SLC6A4', 'position' => 'chr17:30194319'),
            // COVID-related variants
            array('rsid' => 'rs2285666', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'ACE2', 'position' => 'chrX:15561033'),
            array('rsid' => 'rs4291', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'ACE2', 'position' => 'chrX:15582223'),
            array('rsid' => 'rs12329760', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'TMPRSS2', 'position' => 'chr21:42836478'),
            array('rsid' => 'rs1799724', 'genotype' => 'AG', 'risk_allele' => 'G', 'gene' => 'TNF', 'position' => 'chr6:31543031'),
            array('rsid' => 'rs1800896', 'genotype' => 'CT', 'risk_allele' => 'T', 'gene' => 'IL10', 'position' => 'chr1:206946897')
        );
        
        if ($count && is_numeric($count)) {
            return array_slice($sample_data, 0, $count);
        }
        
        return $sample_data;
    }
    
    public static function create_health_recommendations($genetic_data, $report_type) {
        $recommendations = array();
        
        $report_type_lower = strtolower($report_type);
        
        if ($report_type_lower === 'covid') {
            $recommendations = array(
                array(
                    'category' => 'Immune Support',
                    'recommendations' => array(
                        'Consider vitamin D supplementation (2000-4000 IU daily)',
                        'Maintain adequate zinc levels (15-30mg daily)',
                        'Support with vitamin C (1000-2000mg daily)',
                        'Consider quercetin supplementation (500mg daily)'
                    )
                ),
                array(
                    'category' => 'Lifestyle Modifications',
                    'recommendations' => array(
                        'Maintain regular exercise routine',
                        'Ensure adequate sleep (7-9 hours nightly)',
                        'Manage stress through meditation or yoga',
                        'Avoid smoking and limit alcohol consumption'
                    )
                )
            );
        } elseif ($report_type_lower === 'methylation') {
            $recommendations = array(
                array(
                    'category' => 'Methylation Support',
                    'recommendations' => array(
                        'Consider methylated B vitamins (B6, B12, folate)',
                        'Support with SAMe (200-400mg daily)',
                        'Include choline-rich foods in diet',
                        'Consider betaine supplementation'
                    )
                ),
                array(
                    'category' => 'Dietary Recommendations',
                    'recommendations' => array(
                        'Consume leafy green vegetables daily',
                        'Include legumes and beans in diet',
                        'Limit alcohol consumption',
                        'Consider organic foods when possible'
                    )
                )
            );
        } else {
            $recommendations = array(
                array(
                    'category' => 'General Health',
                    'recommendations' => array(
                        'Maintain a balanced, nutrient-dense diet',
                        'Regular physical activity (150 minutes/week)',
                        'Adequate hydration (8-10 glasses water daily)',
                        'Regular health screenings and check-ups'
                    )
                )
            );
        }
        
        return $recommendations;
    }
    
  public static function filter_data_by_type($genetic_data, $report_type) {
   
    $report_type_lower = strtolower($report_type);
    
    // Ensure genetic_data is an array
    if (!is_array($genetic_data)) {
        // Try to decode if it's JSON
        if (is_string($genetic_data)) {
            $decoded_data = json_decode($genetic_data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                $genetic_data = $decoded_data;
            } else {
                // Try to unserialize if it's a serialized PHP array
                $unserialized_data = @unserialize($genetic_data);
                if ($unserialized_data !== false && is_array($unserialized_data)) {
                    $genetic_data = $unserialized_data;
                } else {
                    error_log('MTHFR Plugin: Invalid genetic_data format - expected array, got: ' . gettype($genetic_data));
                    return array(); // Return empty array on invalid data
                }
            }
        } else {
            error_log('MTHFR Plugin: Invalid genetic_data type - expected array, got: ' . gettype($genetic_data));
            return array(); // Return empty array on invalid data
        }
    }
    
    if ($report_type_lower === 'covid') {
        $covid_genes = array('ACE2', 'FURIN', 'TMPRSS2', 'IL6', 'NLRP3', 'IFNAR1', 'NOS3', 'TNF', 'IL10');
        return array_filter($genetic_data, function($variant) use ($covid_genes) {
            // Additional safety check
            if (!is_array($variant) || !isset($variant['gene'])) {
                error_log('MTHFR Plugin: Invalid variant data structure: ' . print_r($variant, true));
                return false;
            }
            return in_array($variant['gene'], $covid_genes);
        });
    } elseif ($report_type_lower === 'methylation') {
        $methylation_genes = array('MTHFR', 'MTR', 'MTRR', 'DHFR', 'SHMT1', 'BHMT', 'CBS');
        return array_filter($genetic_data, function($variant) use ($methylation_genes) {
            // Additional safety check
            if (!is_array($variant) || !isset($variant['gene'])) {
                error_log('MTHFR Plugin: Invalid variant data structure: ' . print_r($variant, true));
                return false;
            }
            return in_array($variant['gene'], $methylation_genes);
        });
    }
    
    return $genetic_data; // Return all data for other types
}
    
    public static function get_gene_info($gene_name) {
        // Primary genes information (simplified from your constants)
        $primary_genes = array(
            'ACE2' => array(
                'header' => 'Angiotensin-converting enzyme 2 (ACE2)',
                'description' => 'ACE2 gene encodes the angiotensin-converting enzyme-2. ACE2 acts as a cell surface receptor for Human coronavirus.',
                'studies' => array('http://www.nephjc.com/news/covidace2')
            ),
            'FURIN' => array(
                'header' => 'Furin (FURIN)',
                'description' => 'Furin is an enzyme encoded by the FURIN gene. Furin is utilized by pathogens including SARS-CoV-2.',
                'studies' => array('https://www.sciencedirect.com/science/article/pii/S0166354220300528')
            ),
            'MTHFR' => array(
                'header' => 'Methylenetetrahydrofolate reductase (MTHFR)',
                'description' => 'MTHFR is crucial for folate metabolism and methylation processes.',
                'studies' => array()
            ),
            'TMPRSS2' => array(
                'header' => 'Transmembrane protease serine 2 (TMPRSS2)',
                'description' => 'TMPRSS2 is required for SARS-CoV-2 spike protein activation.',
                'studies' => array()
            ),
            'TNF' => array(
                'header' => 'Tumor necrosis factor (TNF)',
                'description' => 'TNF is a key inflammatory cytokine involved in immune responses.',
                'studies' => array()
            )
        );
        
        // Secondary genes information
        $secondary_genes = array(
            'FUT2' => array(
                'header' => 'FUT2',
                'description' => 'This gene gives us the ability to produce an enzyme called α1,2-fucosyltransferase.',
            ),
            'SOD3' => array(
                'header' => 'SOD3',
                'description' => 'Extracellular superoxide dismutase helps to protect the lungs from oxidative stress.',
            ),
            'HFE' => array(
                'header' => 'HFE',
                'description' => 'The HFE gene provides instructions for producing a protein that regulates iron absorption.',
            )
        );
        
        if (isset($primary_genes[$gene_name])) {
            return $primary_genes[$gene_name];
        } elseif (isset($secondary_genes[$gene_name])) {
            return $secondary_genes[$gene_name];
        }
        
        return array(
            'header' => $gene_name,
            'description' => 'Gene information not available.',
            'studies' => array()
        );
    }
    
  public static function analyze_mthfr_variants($genetic_data) {
    $mthfr_variants = array();
    
    // Ensure genetic_data is an array
    if (!is_array($genetic_data)) {
        if (is_string($genetic_data)) {
            $decoded_data = json_decode($genetic_data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                $genetic_data = $decoded_data;
            } else {
                $unserialized_data = @unserialize($genetic_data);
                if ($unserialized_data !== false && is_array($unserialized_data)) {
                    $genetic_data = $unserialized_data;
                } else {
                    error_log('MTHFR Plugin: Invalid genetic_data format in analyze_mthfr_variants');
                    return array();
                }
            }
        } else {
            error_log('MTHFR Plugin: Invalid genetic_data type in analyze_mthfr_variants');
            return array();
        }
    }
    
    foreach ($genetic_data as $variant) {
        // Add safety check for variant structure
        if (is_array($variant) && isset($variant['gene']) && $variant['gene'] === 'MTHFR') {
            $mthfr_variants[] = $variant;
        } elseif (!is_array($variant)) {
            error_log('MTHFR Plugin: Invalid variant structure in analyze_mthfr_variants: ' . print_r($variant, true));
        }
    }
    
    return $mthfr_variants;
}
    
    public static function calculate_risk_score($variants) {
        $risk_score = 0;
        $risk_factors = array();
        
        foreach ($variants as $variant) {
            $gene = $variant['gene'] ?? '';
            $genotype = $variant['genotype'] ?? '';
            $risk_allele = $variant['risk_allele'] ?? '';
            
            // Count risk alleles
            $risk_allele_count = substr_count($genotype, $risk_allele);
            
            switch ($gene) {
                case 'MTHFR':
                    if ($risk_allele_count >= 2) {
                        $risk_score += 3;
                        $risk_factors[] = "Homozygous {$gene} variant - High impact";
                    } elseif ($risk_allele_count === 1) {
                        $risk_score += 1;
                        $risk_factors[] = "Heterozygous {$gene} variant - Moderate impact";
                    }
                    break;
                    
                case 'COMT':
                case 'MTR':
                case 'MTRR':
                    if ($risk_allele_count >= 2) {
                        $risk_score += 2;
                        $risk_factors[] = "Homozygous {$gene} variant - Moderate impact";
                    } elseif ($risk_allele_count === 1) {
                        $risk_score += 0.5;
                        $risk_factors[] = "Heterozygous {$gene} variant - Low impact";
                    }
                    break;
                    
                default:
                    if ($risk_allele_count >= 2) {
                        $risk_score += 1;
                        $risk_factors[] = "Homozygous {$gene} variant";
                    } elseif ($risk_allele_count === 1) {
                        $risk_score += 0.25;
                        $risk_factors[] = "Heterozygous {$gene} variant";
                    }
                    break;
            }
        }
        
        // Determine overall risk level
        $risk_level = 'low';
        if ($risk_score >= 5) {
            $risk_level = 'high';
        } elseif ($risk_score >= 2) {
            $risk_level = 'moderate';
        }
        
        return array(
            'risk_score' => $risk_score,
            'risk_level' => $risk_level,
            'risk_factors' => $risk_factors,
            'total_variants' => count($variants)
        );
    }
    
    public static function generate_recommendations($risk_analysis) {
        $recommendations = array();
        
        switch ($risk_analysis['risk_level']) {
            case 'high':
                $recommendations[] = 'Consider comprehensive genetic counseling';
                $recommendations[] = 'Work with healthcare provider for personalized treatment plan';
                $recommendations[] = 'Regular monitoring of relevant biomarkers';
                $recommendations[] = 'Consider specialized supplementation protocols';
                break;
                
            case 'moderate':
                $recommendations[] = 'Discuss findings with healthcare provider';
                $recommendations[] = 'Consider targeted nutritional support';
                $recommendations[] = 'Monitor relevant health markers annually';
                $recommendations[] = 'Implement lifestyle modifications as appropriate';
                break;
                
            case 'low':
                $recommendations[] = 'Maintain healthy lifestyle practices';
                $recommendations[] = 'Consider general wellness supplementation';
                $recommendations[] = 'Regular health check-ups as recommended';
                break;
        }
        
        // Add general recommendations
        $recommendations[] = 'Maintain a balanced, nutrient-rich diet';
        $recommendations[] = 'Engage in regular physical activity';
        $recommendations[] = 'Manage stress through appropriate techniques';
        $recommendations[] = 'Avoid smoking and limit alcohol consumption';
        $recommendations[] = 'Consult healthcare providers before making significant changes';
        
        return $recommendations;
    }
    
}