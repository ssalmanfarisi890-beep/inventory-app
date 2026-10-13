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

$error = '';

// Proses tambah, edit, dan hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $price = $_POST['price'] ?? '';
        $minimumStock = $_POST['minimum_stock'] ?? '';

        if (
            $sku === '' || $name === '' || $categoryId <= 0 ||
            !is_numeric($price) || (float) $price < 0 ||
            !ctype_digit((string) $minimumStock)
        ) {
            $error = 'Data produk tidak valid. Periksa kembali semua kolom.';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE products
                         SET sku=:sku, name=:name, category_id=:category_id,
                             price=:price, minimum_stock=:minimum_stock
                         WHERE id=:id'
                    );
                    $stmt->execute([
                        'sku' => $sku,
                        'name' => $name,
                        'category_id' => $categoryId,
                        'price' => $price,
                        'minimum_stock' => (int) $minimumStock,
                        'id' => $id
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO products
                         (sku, name, category_id, price, stock, minimum_stock)
                         VALUES (:sku, :name, :category_id, :price, 0, :minimum_stock)'
                    );
                    $stmt->execute([
                        'sku' => $sku,
                        'name' => $name,
                        'category_id' => $categoryId,
                        'price' => $price,
                        'minimum_stock' => (int) $minimumStock
                    ]);
                }

                header('Location: products.php');
                exit;
            } catch (PDOException $ex) {
                $error = 'Gagal menyimpan. SKU mungkin sudah digunakan atau kategori tidak valid.';
            }
        }
    }

    if ($action === 'delete') {
        try {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id=:id');
            $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
            header('Location: products.php');
            exit;
        } catch (PDOException $ex) {
            $error = 'Produk tidak dapat dihapus karena memiliki relasi atau riwayat transaksi.';
        }
    }
}

// Kategori
$categories = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name'
)->fetchAll();

// Data produk untuk diedit
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id=:id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editProduct = $stmt->fetch();
}

// Pencarian dan pengurutan
$search = trim($_GET['search'] ?? '');

$sortOptions = [
    'name' => 'p.name',
    'sku' => 'p.sku',
    'price' => 'p.price',
    'stock' => 'p.stock'
];

$sort = $_GET['sort'] ?? 'name';
if (!isset($sortOptions[$sort])) {
    $sort = 'name';
}

$order = strtoupper($_GET['order'] ?? 'ASC');
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'ASC';
}

$where = '';
$params = [];

if ($search !== '') {
    $where = ' WHERE p.name LIKE :search OR p.sku LIKE :search';
    $params['search'] = '%' . $search . '%';
}

// Hitung jumlah produk
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM products p' . $where
);
$stmt->execute($params);
$totalProductsFound = (int) $stmt->fetchColumn();

// Pagination
$perPage = 5;
$totalPages = max(1, (int) ceil($totalProductsFound / $perPage));
$page = max(1, (int) ($_GET['page'] ?? 1));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Ambil produk pada halaman aktif
$sql = 'SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id'
        . $where
        . ' ORDER BY ' . $sortOptions[$sort] . ' ' . $order
        . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk</title>
</head>
<body>
    <h1>Kelola Produk</h1>
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?= e($error) ?></p>
    <?php endif; ?>

    <h2><?= $editProduct ? 'Edit Produk' : 'Tambah Produk' ?></h2>

    <?php if (count($categories) === 0): ?>
        <p>Tambahkan kategori terlebih dahulu di
            <a href="categories.php">Kelola Kategori</a>.
        </p>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id"
                   value="<?= (int) ($editProduct['id'] ?? 0) ?>">

            <label>SKU</label><br>
            <input name="sku" required
                   value="<?= e($editProduct['sku'] ?? '') ?>"><br><br>

            <label>Nama Produk</label><br>
            <input name="name" required
                   value="<?= e($editProduct['name'] ?? '') ?>"><br><br>

            <label>Kategori</label><br>
            <select name="category_id" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"
                        <?= (int) ($editProduct['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Harga</label><br>
            <input type="number" name="price" min="0" step="0.01"
                   required value="<?= e($editProduct['price'] ?? '') ?>"><br><br>

            <label>Minimum Stok</label><br>
            <input type="number" name="minimum_stock" min="0" step="1"
                   required value="<?= e($editProduct['minimum_stock'] ?? 5) ?>"><br><br>

            <?php if ($editProduct): ?>
                <p>Stok saat ini: <?= (int) $editProduct['stock'] ?> unit.
                   Ubah stok melalui menu Transaksi Stok.</p>
            <?php endif; ?>

            <button type="submit">
                <?= $editProduct ? 'Simpan Perubahan' : 'Tambah Produk' ?>
            </button>

            <?php if ($editProduct): ?>
                <a href="products.php">Batal Edit</a>
            <?php endif; ?>
        </form>
    <?php endif; ?>

    <hr>
    <h2>Daftar Produk</h2>

    <form method="GET">
        <input name="search" placeholder="Cari nama atau SKU..."
               value="<?= e($search) ?>">

        <select name="sort">
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nama</option>
            <option value="sku" <?= $sort === 'sku' ? 'selected' : '' ?>>SKU</option>
            <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>Harga</option>
            <option value="stock" <?= $sort === 'stock' ? 'selected' : '' ?>>Stok</option>
        </select>

        <select name="order">
            <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>Naik</option>
            <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>Turun</option>
        </select>

        <button type="submit">Terapkan</button>
        <a href="products.php">Reset</a>
    </form>

    <br>

    <table border="1" cellpadding="8">
        <tr>
            <th>No.</th>
            <th>SKU</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Minimum</th>
            <th>Aksi</th>
        </tr>

        <?php foreach ($products as $i => $product): ?>
            <tr>
                <td><?= $offset + $i + 1 ?></td>
                <td><?= e($product['sku']) ?></td>
                <td><?= e($product['name']) ?></td>
                <td><?= e($product['category_name']) ?></td>
                <td>Rp <?= number_format((float) $product['price'], 0, ',', '.') ?></td>
                <td><?= (int) $product['stock'] ?></td>
                <td><?= (int) $product['minimum_stock'] ?></td>
                <td>
                    <a href="?edit=<?= (int) $product['id'] ?>">Edit</a>

                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id"
                               value="<?= (int) $product['id'] ?>">
                        <button type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (count($products) === 0): ?>
            <tr><td colspan="8">Produk tidak ditemukan.</td></tr>
        <?php endif; ?>
    </table>

    <p>
        Menampilkan <?= count($products) ?> dari <?= $totalProductsFound ?>
        produk. Halaman <?= $page ?> dari <?= $totalPages ?>.
    </p>

    <?php if ($page > 1): ?>
        <a href="?<?= e(http_build_query([
            'search' => $search,
            'sort' => $sort,
            'order' => $order,
            'page' => $page - 1
        ])) ?>">Sebelumnya</a>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?<?= e(http_build_query([
            'search' => $search,
            'sort' => $sort,
            'order' => $order,
            'page' => $page + 1
        ])) ?>">Berikutnya</a>
    <?php endif; ?>
</body>
</html>