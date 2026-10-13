<?php
require_once __DIR__ . '/config/database.php';

$name = 'Administrator';
$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$role = 'Admin';

$sql = "INSERT INTO users (name, username, password, role)
        VALUES (:name, :username, :password, :role)";

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        'name' => $name,
        'username' => $username,
        'password' => $password,
        'role' => $role
    ]);

    echo "Akun Admin berhasil dibuat.";
} catch (PDOException $e) {
    echo "Gagal membuat akun. Username mungkin sudah digunakan.";
}