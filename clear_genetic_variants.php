<?php
// Clear genetic variants table

// Include WordPress
require_once('wp-load.php');

// Database credentials from wp-config.php
$dsn = 'mysql:unix_socket=/Users/bot/Library/Application Support/Local/run/X7kPppFz5/mysql/mysqld.sock;dbname=local';
$user = 'root';
$password = 'root';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Clear related tables first
    $related_tables = [
        'wpub_variant_tag_relationships',
        'wpub_variant_tags',
        'wpub_variant_categories',
        'wpub_pathways',
        'wpub_genetic_variants'
    ];

    foreach ($related_tables as $table) {
        $stmt = $pdo->prepare("TRUNCATE TABLE $table");
        $stmt->execute();
        echo "Cleared $table\n";
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Successfully cleared all genetic variant tables\n";

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>