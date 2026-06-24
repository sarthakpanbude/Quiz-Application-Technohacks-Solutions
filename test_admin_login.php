<?php
require_once 'db.php';

function testLogin($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $dbUser = $stmt->fetch();

    if ($dbUser && password_verify($password, $dbUser['password_hash'])) {
        return "SUCCESS (db)";
    } else {
        if ($username === 'admin' && $password === 'admin') {
            return "SUCCESS (fallback)";
        } else {
            return "FAILED";
        }
    }
}

echo "admin/admin: " . testLogin('admin', 'admin') . "\n";
echo "admin/wrong: " . testLogin('admin', 'wrong') . "\n";
echo "recruiter@company.com/wrong: " . testLogin('recruiter@company.com', 'wrong') . "\n";
?>
