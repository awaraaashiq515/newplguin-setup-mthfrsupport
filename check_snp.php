<?php
// Check SNP names in wp_genetic_variants table

// Include WordPress
require_once('wp-load.php');

// Database credentials from wp-config.php
$dsn = 'mysql:unix_socket=/Users/bot/Library/Application Support/Local/run/X7kPppFz5/mysql/mysqld.sock;dbname=local';
$user = 'root';
$password = 'root';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // List all tables
    echo "All tables in database:\n";
    $stmt = $pdo->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "$table\n";
    }
    echo "\n";

    // Check if wp_genetic_variants exists
    $table_name = 'wp_genetic_variants';
    if (in_array($table_name, $tables)) {
        echo "Table $table_name exists.\n";
        // Get table structure
        echo "Table structure for $table_name:\n";
        $stmt = $pdo->prepare("DESCRIBE $table_name");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "{$col['Field']} - {$col['Type']}\n";
        }
        echo "\n";
    } else {
        echo "Table $table_name does not exist. Checking with prefix wpub_\n";
        $table_name = 'wpub_genetic_variants';
        if (in_array($table_name, $tables)) {
            echo "Table $table_name exists.\n";
            // Get table structure
            echo "Table structure for $table_name:\n";
            $stmt = $pdo->prepare("DESCRIBE $table_name");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo "{$col['Field']} - {$col['Type']}\n";
            }
            echo "\n";
        } else {
            echo "Table $table_name also does not exist.\n";
            return;
        }
    }

    // Total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM $table_name");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total records: $total\n";

    // Count NULL snp
    $stmt = $pdo->prepare("SELECT COUNT(*) as null_count FROM $table_name WHERE snp IS NULL");
    $stmt->execute();
    $null_count = $stmt->fetch(PDO::FETCH_ASSOC)['null_count'];
    echo "Records with NULL snp: $null_count\n";

    // Count NOT NULL snp
    $not_null_count = $total - $null_count;
    echo "Records with NOT NULL snp: $not_null_count\n";

    // Recent records (last 10)
    echo "\nRecent 10 records (ordered by id DESC):\n";
    $stmt = $pdo->prepare("SELECT id, snp, rsid, gene FROM $table_name ORDER BY id DESC LIMIT 10");
    $stmt->execute();
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recent as $row) {
        echo "ID: {$row['id']}, SNP: " . ($row['snp'] ?? 'NULL') . ", RSID: {$row['rsid']}, Gene: {$row['gene']}\n";
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>