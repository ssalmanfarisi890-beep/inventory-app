<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $type = $_POST['type'] ?? '';
    $quantityInput = $_POST['quantity'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if (
        $productId <= 0 ||
        !in_array($type, ['IN', 'OUT'], true) ||
        !ctype_digit((string) $quantityInput) ||
        (int) $quantityInput <= 0
    ) {
        $error = 'Produk, jenis transaksi, dan jumlah harus valid.';
    } else {
        $quantity = (int) $quantityInput;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT id, stock FROM products WHERE id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }

            $currentStock = (int) $product['stock'];

            if ($type === 'OUT' && $quantity > $currentStock) {
                throw new RuntimeException(
                    'Stok tidak cukup. Stok tersedia: ' . $currentStock
                );
            }

            if ($type === 'IN') {
                $stmt = $pdo->prepare(
                    'UPDATE products SET stock = stock + :quantity
                     WHERE id = :id'
                );
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE products SET stock = stock - :quantity
                     WHERE id = :id'
                );
            }

            $stmt->execute([
                'quantity' => $quantity,
                'id' => $productId
            ]);

            $stmt = $pdo->prepare(
                'INSERT INTO stock_movements
                 (product_id, user_id, type, quantity, note)
                 VALUES (:product_id, :user_id, :type, :quantity, :note)'
            );

            $stmt->execute([
                'product_id' => $productId,
                'user_id' => $_SESSION['user_id'],
                'type' => $type,
                'quantity' => $quantity,
                'note' => $note
            ]);

            $pdo->commit();
            header('Location: stock.php?success=1');
            exit;

        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $ex instanceof RuntimeException
                ? $ex->getMessage()
                : 'Transaksi gagal disimpan. Periksa kembali data.';
        }
    }
}

if (isset($_GET['success'])) {
    $success = 'Transaksi stok berhasil disimpan.';
}

$products = $pdo->query(
    'SELECT id, sku, name, stock FROM products ORDER BY name'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Stok</title>
</head>
<body>
    <h1>Transaksi Stok</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p style="color:green;"><?= e($success) ?></p>
    <?php endif; ?>

    <h2>Catat Transaksi</h2>

    <?php if (count($products) === 0): ?>
        <p>Belum ada produk. Tambahkan produk terlebih dahulu.</p>
    <?php else: ?>
        <form method="POST">
            <label>Produk</label><br>
            <select name="product_id" required>
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>">
                        <?= e($product['sku']) ?> -
                        <?= e($product['name']) ?> (Stok: <?= (int) $product['stock'] ?>)
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Jenis Transaksi</label><br>
            <select name="type" required>
                <option value="IN">Stok Masuk</option>
                <option value="OUT">Stok Keluar</option>
            </select><br><br>

            <label>Jumlah</label><br>
            <input type="number" name="quantity" min="1" step="1" required><br><br>

            <label>Keterangan</label><br>
            <textarea name="note"></textarea><br><br>

            <button type="submit">Simpan Transaksi</button>
        </form>
    <?php endif; ?>
</body>
</html>