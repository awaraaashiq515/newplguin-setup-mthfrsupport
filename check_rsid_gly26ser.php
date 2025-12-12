<?php
// Check for RSID 'rs1800458' and SNP 'Gly26Ser' in wpub_genetic_variants table

// Include WordPress
require_once('wp-load.php');

// Database credentials from wp-config.php
$dsn = 'mysql:unix_socket=/Users/bot/Library/Application Support/Local/run/X7kPppFz5/mysql/mysqld.sock;dbname=local';
$user = 'root';
$password = 'root';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking for RSID 'rs1800458' and SNP 'Gly26Ser' in wpub_genetic_variants table:\n\n";

    // Query for the specific RSID
    $query = "SELECT id, rsid, gene, snp, snp_name, risk_allele, info FROM wpub_genetic_variants WHERE rsid = 'rs1800458'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($records) > 0) {
        echo "Found " . count($records) . " record(s) for RSID 'rs1800458':\n";
        foreach ($records as $record) {
            echo "  ID: {$record['id']}, RSID: " . ($record['rsid'] ?? 'NULL') . ", Gene: " . ($record['gene'] ?? 'NULL') . ", SNP: " . ($record['snp'] ?? 'NULL') . ", SNP Name: " . ($record['snp_name'] ?? 'NULL') . ", Risk Allele: " . ($record['risk_allele'] ?? 'NULL') . ", Info: " . ($record['info'] ?? 'NULL') . "\n";

            // Check if SNP matches 'Gly26Ser'
            if (strtolower(trim($record['snp'] ?? '')) === 'gly26ser' || strtolower(trim($record['snp_name'] ?? '')) === 'gly26ser') {
                echo "  ✓ SNP matches 'Gly26Ser'\n";
            } else {
                echo "  ✗ SNP does not match 'Gly26Ser' (found: '" . ($record['snp'] ?? 'NULL') . "' / '" . ($record['snp_name'] ?? 'NULL') . "')\n";
            }
        }
    } else {
        echo "No records found for RSID 'rs1800458'.\n";
    }

    echo "\n---\n";

    // Check total number of records
    $count_query = "SELECT COUNT(*) as total FROM wpub_genetic_variants";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute();
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);

    $total_records = $count_result['total'];
    echo "Total records in wpub_genetic_variants table: {$total_records}\n";

    if ($total_records >= 2870) {
        echo "✓ At least 2870 records imported (total: {$total_records})\n";
    } else {
        echo "✗ Less than 2870 records imported (total: {$total_records})\n";
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>