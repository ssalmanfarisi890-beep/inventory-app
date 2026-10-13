<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$totalProducts = (int) $pdo->query(
    "SELECT COUNT(*) FROM products"
)->fetchColumn();

$totalCategories = (int) $pdo->query(
    "SELECT COUNT(*) FROM categories"
)->fetchColumn();

$totalStock = (int) $pdo->query(
    "SELECT COALESCE(SUM(stock), 0) FROM products"
)->fetchColumn();

$lowStock = (int) $pdo->query(
    "SELECT COUNT(*) FROM products WHERE stock <= minimum_stock"
)->fetchColumn();

$isAdmin = ($_SESSION['role'] ?? '') === 'Admin';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory System</title>
</head>
<body>

    <h1>Inventory Management System</h1>

    <p>
        Selamat datang,
        <strong><?= e($_SESSION['name'] ?? 'Pengguna') ?></strong>!
    </p>

    <p>Role: <?= e($_SESSION['role'] ?? 'Staff') ?></p>

    <hr>

    <h2>Dashboard</h2>

    <ul>
        <li>Total Produk: <?= $totalProducts ?></li>
        <li>Total Kategori: <?= $totalCategories ?></li>
        <li>Total Stok: <?= $totalStock ?></li>
        <li>Produk Stok Menipis: <?= $lowStock ?></li>
    </ul>

    <h3>Menu</h3>

    <ul>
        <?php if ($isAdmin): ?>
            <li><a href="categories.php">Kelola Kategori</a></li>
            <li><a href="suppliers.php">Kelola Supplier</a></li>
        <?php endif; ?>

        <li><a href="products.php">Kelola Produk</a></li>
        <li><a href="stock.php">Transaksi Stok</a></li>
        <li><a href="stock-history.php">Riwayat Stok</a></li>
        <li><a href="reports.php">Laporan Stok</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>

</body>
</html>