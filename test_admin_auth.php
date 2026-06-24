<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

try {
    echo "<h3>Testing Connection and Schema</h3>\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admins'");
    $tableExists = $stmt->fetch();
    if ($tableExists) {
        echo "admins table exists.<br>\n";
    } else {
        echo "admins table DOES NOT exist.<br>\n";
    }

    echo "<h3>Checking Admins in Table</h3>\n";
    $stmtAdmins = $pdo->query("SELECT id, username, password_hash, created_at FROM admins");
    $admins = $stmtAdmins->fetchAll();
    if (empty($admins)) {
        echo "No admins registered in the database.<br>\n";
    } else {
        foreach ($admins as $admin) {
            echo "ID: {$admin['id']} | Username: {$admin['username']} | Hash: " . substr($admin['password_hash'], 0, 15) . "... | Created: {$admin['created_at']}<br>\n";
        }
    }

    echo "<h3>Testing Password Hashing and Verify</h3>\n";
    $testPassword = "admin";
    $hash = password_hash($testPassword, PASSWORD_DEFAULT);
    $verify = password_verify($testPassword, $hash);
    echo "Test password: 'admin'<br>\n";
    echo "Hash: $hash<br>\n";
    echo "Verify test: " . ($verify ? "SUCCESS" : "FAILED") . "<br>\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>\n";
}
?>
