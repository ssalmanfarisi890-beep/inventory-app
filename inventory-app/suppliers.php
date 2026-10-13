<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'Admin') {
    die('Akses ditolak. Hanya Admin yang boleh mengelola supplier.');
}

$error = '';
$editSupplier = null;

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Tambah, edit, dan hapus supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name === '') {
            $error = 'Nama supplier wajib diisi.';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE suppliers
                         SET name = :name, phone = :phone, address = :address
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        'name' => $name,
                        'phone' => $phone,
                        'address' => $address,
                        'id' => $id
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO suppliers (name, phone, address)
                         VALUES (:name, :phone, :address)'
                    );
                    $stmt->execute([
                        'name' => $name,
                        'phone' => $phone,
                        'address' => $address
                    ]);
                }

                header('Location: suppliers.php');
                exit;
            } catch (PDOException $ex) {
                $error = 'Gagal menyimpan data supplier.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare(
                    'DELETE FROM suppliers WHERE id = :id'
                );
                $stmt->execute(['id' => $id]);

                header('Location: suppliers.php');
                exit;
            } catch (PDOException $ex) {
                $error = 'Supplier gagal dihapus.';
            }
        }
    }
}

// Ambil data untuk diedit
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = $pdo->prepare(
        'SELECT * FROM suppliers WHERE id = :id'
    );
    $stmt->execute(['id' => $id]);
    $editSupplier = $stmt->fetch();
}

// Ambil semua supplier
$stmt = $pdo->query(
    'SELECT * FROM suppliers ORDER BY id DESC'
);
$suppliers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Supplier</title>
</head>
<body>
    <h1>Kelola Supplier</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?= e($error) ?></p>
    <?php endif; ?>

    <h2><?= $editSupplier ? 'Edit Supplier' : 'Tambah Supplier' ?></h2>

    <form method="POST" action="suppliers.php">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id"
               value="<?= e($editSupplier['id'] ?? 0) ?>">

        <label>Nama Supplier</label><br>
        <input type="text" name="name"
               value="<?= e($editSupplier['name'] ?? '') ?>"
               required><br><br>

        <label>Nomor Telepon</label><br>
        <input type="text" name="phone"
               value="<?= e($editSupplier['phone'] ?? '') ?>"><br><br>

        <label>Alamat</label><br>
        <textarea name="address"><?= e($editSupplier['address'] ?? '') ?></textarea><br><br>

        <button type="submit">
            <?= $editSupplier ? 'Simpan Perubahan' : 'Tambah Supplier' ?>
        </button>

        <?php if ($editSupplier): ?>
            <a href="suppliers.php">Batal Edit</a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Daftar Supplier</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>No.</th>
            <th>Nama Supplier</th>
            <th>Telepon</th>
            <th>Alamat</th>
            <th>Aksi</th>
        </tr>

        <?php foreach ($suppliers as $index => $supplier): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= e($supplier['name']) ?></td>
                <td><?= e($supplier['phone'] ?? '') ?></td>
                <td><?= e($supplier['address'] ?? '') ?></td>
                <td>
                    <a href="suppliers.php?edit=<?= (int) $supplier['id'] ?>">
                        Edit
                    </a>

                    <form method="POST" action="suppliers.php"
                          style="display:inline"
                          onsubmit="return confirm('Yakin ingin menghapus supplier ini?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id"
                               value="<?= (int) $supplier['id'] ?>">
                        <button type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (count($suppliers) === 0): ?>
            <tr>
                <td colspan="5">Belum ada data supplier.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>