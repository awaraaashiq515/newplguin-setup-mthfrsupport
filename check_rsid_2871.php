<?php
// Check for RSID '2871' and SNP '2871' in wp_genetic_variants table

// Include WordPress
require_once('wp-load.php');

// Database credentials from wp-config.php
$dsn = 'mysql:unix_socket=/Users/bot/Library/Application Support/Local/run/X7kPppFz5/mysql/mysqld.sock;dbname=local';
$user = 'root';
$password = 'root';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking for RSID '2871' and SNP '2871' in wpub_genetic_variants table:\n\n";

    // Check for exact string matches
    $queries = [
        "SELECT id, rsid, gene, snp, snp_name FROM wpub_genetic_variants WHERE rsid = '2871'",
        "SELECT id, rsid, gene, snp, snp_name FROM wpub_genetic_variants WHERE snp = '2871'",
        "SELECT id, rsid, gene, snp, snp_name FROM wpub_genetic_variants WHERE rsid = 2871",
        "SELECT id, rsid, gene, snp, snp_name FROM wpub_genetic_variants WHERE snp = 2871"
    ];

    $labels = [
        "RSID = '2871' (string)",
        "SNP = '2871' (string)",
        "RSID = 2871 (numeric)",
        "SNP = 2871 (numeric)"
    ];

    foreach ($queries as $index => $query) {
        echo "Query: {$labels[$index]}\n";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($records) > 0) {
            echo "Found " . count($records) . " record(s):\n";
            foreach ($records as $record) {
                echo "  ID: {$record['id']}, RSID: " . ($record['rsid'] ?? 'NULL') . ", Gene: " . ($record['gene'] ?? 'NULL') . ", SNP: " . ($record['snp'] ?? 'NULL') . ", SNP Name: " . ($record['snp_name'] ?? 'NULL') . "\n";
            }
        } else {
            echo "No records found.\n";
        }
        echo "---\n";
    }

    // Check for any records with numeric RSID or SNP equal to 2871
    echo "\nChecking for records where RSID or SNP is numeric and equals 2871:\n";
    $stmt = $pdo->prepare("SELECT id, rsid, gene, snp, snp_name FROM wpub_genetic_variants WHERE CAST(rsid AS UNSIGNED) = 2871 OR CAST(snp AS UNSIGNED) = 2871");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($records) > 0) {
        echo "Found " . count($records) . " record(s) with numeric RSID or SNP = 2871:\n";
        foreach ($records as $record) {
            echo "  ID: {$record['id']}, RSID: " . ($record['rsid'] ?? 'NULL') . ", Gene: " . ($record['gene'] ?? 'NULL') . ", SNP: " . ($record['snp'] ?? 'NULL') . ", SNP Name: " . ($record['snp_name'] ?? 'NULL') . "\n";
        }
    } else {
        echo "No records found with numeric RSID or SNP = 2871.\n";
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>