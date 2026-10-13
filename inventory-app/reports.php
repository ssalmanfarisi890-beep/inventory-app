<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Ringkasan inventaris
$totalProducts = (int) $pdo->query(
    'SELECT COUNT(*) FROM products'
)->fetchColumn();

$totalStock = (int) $pdo->query(
    'SELECT COALESCE(SUM(stock), 0) FROM products'
)->fetchColumn();

$totalValue = (float) $pdo->query(
    'SELECT COALESCE(SUM(price * stock), 0) FROM products'
)->fetchColumn();

$lowStock = (int) $pdo->query(
    'SELECT COUNT(*) FROM products WHERE stock <= minimum_stock'
)->fetchColumn();

// Daftar produk dan status stok
$stmt = $pdo->query(
    'SELECT p.sku, p.name, c.name AS category_name,
            p.price, p.stock, p.minimum_stock,
            (p.price * p.stock) AS inventory_value
     FROM products p
     JOIN categories c ON p.category_id = c.id
     ORDER BY p.name ASC'
);

$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Inventaris</title>
</head>
<body>
    <h1>Laporan Inventaris</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <h2>Ringkasan</h2>
    <ul>
        <li>Total jenis produk: <?= $totalProducts ?></li>
        <li>Total seluruh stok: <?= $totalStock ?> unit</li>
        <li>Nilai persediaan: Rp <?= number_format($totalValue, 0, ',', '.') ?></li>
        <li>Produk stok menipis: <?= $lowStock ?></li>
    </ul>

    <h2>Daftar Persediaan</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>No.</th>
            <th>SKU</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Minimum Stok</th>
            <th>Nilai Persediaan</th>
            <th>Status</th>
        </tr>

        <?php foreach ($products as $i => $product): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($product['sku']) ?></td>
                <td><?= e($product['name']) ?></td>
                <td><?= e($product['category_name']) ?></td>
                <td>Rp <?= number_format((float) $product['price'], 0, ',', '.') ?></td>
                <td><?= (int) $product['stock'] ?></td>
                <td><?= (int) $product['minimum_stock'] ?></td>
                <td>Rp <?= number_format((float) $product['inventory_value'], 0, ',', '.') ?></td>
                <td>
                    <?= (int) $product['stock'] <= (int) $product['minimum_stock']
                        ? 'Stok Menipis'
                        : 'Stok Aman' ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (count($products) === 0): ?>
            <tr>
                <td colspan="9">Belum ada data produk.</td>
            </tr>
        <?php endif; ?>
    </table>

    <br>
    <button onclick="window.print()">Cetak Laporan</button>
</body>
</html>