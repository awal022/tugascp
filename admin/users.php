<?php
require_once '../config.php';
requireRole(['admin']);
$title = 'Users';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';

// Tambah User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $username = escape($_POST['username']);
    $password = MD5($_POST['password']);
    $nama     = escape($_POST['nama']);
    $role     = escape($_POST['role']);
    query("INSERT INTO users (username, password, nama, role) VALUES ('$username', '$password', '$nama', '$role')");
    $_SESSION['success'] = 'User berhasil ditambahkan';
    header('Location: users.php');
    exit;
}

// Hapus User
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    query("DELETE FROM users WHERE id=$id");
    $_SESSION['success'] = 'User berhasil dihapus';
    header('Location: users.php');
    exit;
}

// Ambil Data
$users = query("SELECT * FROM users ORDER BY created_at DESC");
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Data Users</h1>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <!-- Form Tambah -->
  <div class="card shadow mb-4">
    <div class="card-body">
      <form method="post" class="form-row">
        <div class="form-group col-md-3">
          <label>Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group col-md-3">
          <label>Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group col-md-3">
          <label>Nama</label>
          <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="form-group col-md-2">
          <label>Role</label>
          <select name="role" class="form-control" required>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group col-md-1">
          <button type="submit" name="tambah" class="btn btn-primary">Tambah</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Users -->
  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Username</th>
            <th>Nama</th>
            <th>Role</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
              <td><?= $u['username'] ?></td>
              <td><?= $u['nama'] ?></td>
              <td><?= $u['role'] ?></td>
              <td>
                <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                  <a href="?hapus=<?= $u['id'] ?>" class="btn btn-sm btn-danger delete-btn">Hapus</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php include '../includes/footer.php'; ?>
</div>


