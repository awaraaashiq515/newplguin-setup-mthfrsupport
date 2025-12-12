<?php
// Check count of genetic variants

// Include WordPress
require_once('wp-load.php');

// Database credentials from wp-config.php
$dsn = 'mysql:unix_socket=/Users/bot/Library/Application Support/Local/run/X7kPppFz5/mysql/mysqld.sock;dbname=local';
$user = 'root';
$password = 'root';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Count rows in wpub_genetic_variants
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wpub_genetic_variants");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Total genetic variants: " . $result['count'] . "\n";

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>