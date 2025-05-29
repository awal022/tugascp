<?php
require_once '../config.php';
requireRole(['admin']);
$title = 'Suppliers';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';

// Tambah Supplier
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nama    = escape($_POST['nama']);
    $alamat  = escape($_POST['alamat']);
    $telepon = escape($_POST['telepon']);
    query("INSERT INTO suppliers (nama, alamat, telepon) VALUES ('$nama', '$alamat', '$telepon')");
    $_SESSION['success'] = 'Supplier berhasil ditambahkan';
    header('Location: suppliers.php');
    exit;
}

// Hapus Supplier
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    query("DELETE FROM suppliers WHERE id=$id");
    $_SESSION['success'] = 'Supplier berhasil dihapus';
    header('Location: suppliers.php');
    exit;
}

// Ambil Data
$suppliers = query("SELECT * FROM suppliers ORDER BY created_at DESC");
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Data Suppliers</h1>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <!-- Form Tambah -->
  <div class="card shadow mb-4">
    <div class="card-body">
      <form method="post" class="form-row align-items-end">
        <div class="form-group col-md-4">
          <label>Nama Supplier</label>
          <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="form-group col-md-4">
          <label>Alamat</label>
          <input type="text" name="alamat" class="form-control">
        </div>
        <div class="form-group col-md-3">
          <label>Telepon</label>
          <input type="text" name="telepon" class="form-control">
        </div>
        <div class="form-group col-md-1">
          <button type="submit" name="tambah" class="btn btn-primary">Tambah</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel -->
  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Alamat</th>
            <th>Telepon</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($s = $suppliers->fetch_assoc()): ?>
            <tr>
              <td><?= $s['nama'] ?></td>
              <td><?= $s['alamat'] ?></td>
              <td><?= $s['telepon'] ?></td>
              <td>
                <a href="?hapus=<?= $s['id'] ?>" class="btn btn-sm btn-danger delete-btn">Hapus</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    
  </div>
  <?php include '../includes/footer.php'; ?>
</div>


