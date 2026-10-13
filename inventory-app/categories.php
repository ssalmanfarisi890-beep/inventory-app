<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'Admin') {
    die('Akses ditolak. Hanya Admin yang boleh mengelola kategori.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Nama kategori wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO categories (name, description)
                 VALUES (:name, :description)'
            );
            $stmt->execute([
                'name' => $name,
                'description' => $description
            ]);

            header('Location: categories.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan kategori. Nama mungkin sudah digunakan.';
        }
    }
}

$stmt = $pdo->query('SELECT * FROM categories ORDER BY id DESC');
$categories = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori</title>
</head>
<body>
    <h1>Kelola Kategori</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <h2>Tambah Kategori</h2>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama Kategori</label><br>
        <input type="text" name="name" required><br><br>

        <label>Deskripsi</label><br>
        <textarea name="description"></textarea><br><br>

        <button type="submit">Simpan Kategori</button>
    </form>

    <hr>

    <h2>Daftar Kategori</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>No.</th>
            <th>Nama Kategori</th>
            <th>Deskripsi</th>
        </tr>

        <?php foreach ($categories as $index => $category): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= e($category['name']) ?></td>
                <td><?= e($category['description'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if (count($categories) === 0): ?>
            <tr>
                <td colspan="3">Belum ada kategori.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>