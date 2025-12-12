<?php
// Check specific SNP records to verify SNP values are populated

// Include WordPress
require_once('wp-load.php');

// Database credentials from wp-config.php
$dsn = 'mysql:unix_socket=/Users/bot/Library/Application Support/Local/run/X7kPppFz5/mysql/mysqld.sock;dbname=local';
$user = 'root';
$password = 'root';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check specific records
    $specific_rsids = ['rs1801133', 'rs1801131', 'rs2066470', 'rs4648328'];

    echo "Checking specific SNP records:\n\n";

    foreach ($specific_rsids as $rsid) {
        $stmt = $pdo->prepare("SELECT id, rsid, gene, snp, snp_name FROM wpub_genetic_variants WHERE rsid = ?");
        $stmt->execute([$rsid]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            echo "RSID: {$record['rsid']}\n";
            echo "Gene: {$record['gene']}\n";
            echo "SNP: " . ($record['snp'] ?? 'NULL') . "\n";
            echo "SNP Name: " . ($record['snp_name'] ?? 'NULL') . "\n";
            echo "---\n";
        } else {
            echo "RSID $rsid not found\n---\n";
        }
    }

    // Check a few random records
    echo "\nRandom sample of 5 records:\n\n";
    $stmt = $pdo->prepare("SELECT id, rsid, gene, snp, snp_name FROM wpub_genetic_variants ORDER BY RAND() LIMIT 5");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as $record) {
        echo "ID: {$record['id']}, RSID: {$record['rsid']}, Gene: {$record['gene']}, SNP: " . ($record['snp'] ?? 'NULL') . ", SNP Name: " . ($record['snp_name'] ?? 'NULL') . "\n";
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>