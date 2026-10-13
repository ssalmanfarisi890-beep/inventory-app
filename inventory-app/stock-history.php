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

$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? '';

$sql = "SELECT sm.id, sm.type, sm.quantity, sm.note,
               sm.created_at, p.sku, p.name AS product_name,
               u.name AS user_name
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        JOIN users u ON sm.user_id = u.id
        WHERE 1=1";

$params = [];

if ($search !== '') {
    $sql .= " AND (p.name LIKE :search OR p.sku LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if (in_array($type, ['IN', 'OUT'], true)) {
    $sql .= " AND sm.type = :type";
    $params['type'] = $type;
}

$sql .= " ORDER BY sm.created_at DESC, sm.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Stok</title>
</head>
<body>
    <h1>Riwayat Transaksi Stok</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <h2>Filter Riwayat</h2>

    <form method="GET">
        <input type="text" name="search"
               placeholder="Cari nama produk atau SKU"
               value="<?= e($search) ?>">

        <select name="type">
            <option value="">Semua Transaksi</option>
            <option value="IN" <?= $type === 'IN' ? 'selected' : '' ?>>
                Stok Masuk
            </option>
            <option value="OUT" <?= $type === 'OUT' ? 'selected' : '' ?>>
                Stok Keluar
            </option>
        </select>

        <button type="submit">Filter</button>
        <a href="stock-history.php">Reset</a>
    </form>

    <br>

    <table border="1" cellpadding="8">
        <tr>
            <th>No.</th>
            <th>Tanggal</th>
            <th>SKU</th>
            <th>Produk</th>
            <th>Jenis</th>
            <th>Jumlah</th>
            <th>Pengguna</th>
            <th>Keterangan</th>
        </tr>

        <?php foreach ($movements as $i => $movement): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($movement['created_at']) ?></td>
                <td><?= e($movement['sku']) ?></td>
                <td><?= e($movement['product_name']) ?></td>
                <td>
                    <?= $movement['type'] === 'IN'
                        ? 'Stok Masuk'
                        : 'Stok Keluar' ?>
                </td>
                <td><?= (int) $movement['quantity'] ?></td>
                <td><?= e($movement['user_name']) ?></td>
                <td><?= e($movement['note'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if (count($movements) === 0): ?>
            <tr>
                <td colspan="8">Belum ada riwayat transaksi.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>