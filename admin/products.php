<?php
// admin/products.php

require_once __DIR__ . '/../config.php';
requireRole(['admin']);

$title = 'Produk';

// Includes SB Admin 2 template
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';

// Handle tambah produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama       = escape($_POST['nama']);
    $deskripsi  = escape($_POST['deskripsi']);
    $harga_beli = (float) $_POST['harga_beli'];
    $harga_jual = (float) $_POST['harga_jual'];
    $minimal    = (int)   $_POST['minimal_pembelian'];
    $supplier   = (int)   $_POST['supplier_id'];
    $gambar     = uploadGambar();

    if ($gambar) {
        query("INSERT INTO products 
            (nama, deskripsi, gambar, harga_beli, harga_jual, stok, minimal_pembelian, supplier_id)
            VALUES
            ('$nama', '$deskripsi', '$gambar', $harga_beli, $harga_jual, 0, $minimal, $supplier)
        ");
        $_SESSION['success'] = 'Produk berhasil ditambahkan';
    } else {
        $_SESSION['error'] = 'Gagal upload gambar';
    }
    header('Location: products.php');
    exit;
}

// Handle edit produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id         = (int) $_POST['id'];
    $nama       = escape($_POST['nama']);
    $deskripsi  = escape($_POST['deskripsi']);
    $harga_beli = (float) $_POST['harga_beli'];
    $harga_jual = (float) $_POST['harga_jual'];
    $minimal    = (int)   $_POST['minimal_pembelian'];
    $supplier   = (int)   $_POST['supplier_id'];
    $stok_baru  = (int)   $_POST['stok']; // Ambil nilai stok baru
    $updGambar  = '';
    
    // Dapatkan stok lama
    $old_stok = query("SELECT stok FROM products WHERE id=$id")->fetch_assoc()['stok'];
    
    // Hitung perubahan stok
    $stok_change = $stok_baru - $old_stok;

    if (!empty($_FILES['gambar']['name'])) {
        $newG = uploadGambar();
        if ($newG) {
            // hapus file lama
            $old = query("SELECT gambar FROM products WHERE id=$id")->fetch_assoc()['gambar'];
            if ($old && file_exists(__DIR__ . '/../uploads/' . $old)) {
                unlink(__DIR__ . '/../uploads/' . $old);
            }
            $updGambar = ", gambar='$newG'";
        }
    }

    // Update produk dengan stok baru
    query("UPDATE products SET
        nama='$nama',
        deskripsi='$deskripsi',
        harga_beli=$harga_beli,
        harga_jual=$harga_jual,
        minimal_pembelian=$minimal,
        supplier_id=$supplier,
        stok=$stok_baru
        $updGambar
        WHERE id=$id
    ");
    
    // Jika stok berkurang, tambahkan ke supplier
    if ($stok_change < 0) {
        $amount = abs($stok_change);
        query("UPDATE suppliers SET stok = stok + $amount WHERE id = $supplier");
    }
    
    $_SESSION['success'] = 'Produk berhasil diubah';
    header('Location: products.php');
    exit;
}

// Handle hapus produk
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $row = query("SELECT gambar, stok, supplier_id FROM products WHERE id=$id")->fetch_assoc();
    
    // Kembalikan stok ke supplier
    if ($row['stok'] > 0) {
        $supplier_id = $row['supplier_id'];
        query("UPDATE suppliers SET stok = stok + {$row['stok']} WHERE id = $supplier_id");
    }
    
    // Hapus gambar
    if ($row['gambar'] && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
        unlink(__DIR__ . '/../uploads/' . $row['gambar']);
    }
    
    query("DELETE FROM products WHERE id=$id");
    $_SESSION['success'] = 'Produk berhasil dihapus dan stok dikembalikan';
    header('Location: products.php');
    exit;
}

// Ambil data
$products  = query("SELECT p.*, s.nama AS supplier
                    FROM products p
                    LEFT JOIN suppliers s ON p.supplier_id = s.id
                    ORDER BY p.nama ASC");
$suppliers = query("SELECT * FROM suppliers ORDER BY nama ASC");
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Manajemen Produk</h1>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <!-- Form Tambah Produk -->
  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <strong>Tambah Produk Baru</strong>
    </div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Nama</label>
            <input type="text" name="nama" class="form-control" required>
          </div>
          <div class="form-group col-md-3">
            <label>Harga Beli</label>
            <input type="number" name="harga_beli" class="form-control" step="0.01" required>
          </div>
          <div class="form-group col-md-3">
            <label>Harga Jual</label>
            <input type="number" name="harga_jual" class="form-control" step="0.01" required>
          </div>
          <div class="form-group col-md-2">
            <label>Min. Pembelian</label>
            <input type="number" name="minimal_pembelian" class="form-control" value="1" min="1" required>
          </div>
          <div class="form-group col-md-1">
            <label>&nbsp;</label>
            <button type="submit" name="tambah" class="btn btn-success btn-block">Simpan</button>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Supplier</label>
            <select name="supplier_id" class="form-control" required>
              <option value="">-- Pilih Supplier --</option>
              <?php while ($s = $suppliers->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>"><?= $s['nama'] ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label>Gambar</label>
            <input type="file" name="gambar" class="form-control-file" required>
          </div>
          <div class="form-group col-md-3">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="2"></textarea>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Produk -->
<div class="card shadow mb-4">
  <div class="card-body table-responsive">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Gambar</th>
          <th>Nama</th>
          <th>Harga Beli</th>
          <th>Harga Jual</th>
          <th>Stok</th>
          <th>Min. Beli</th>
          <th>Supplier</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($p = $products->fetch_assoc()): ?>
          <tr>
            <td>
              <?php if ($p['gambar']): ?>
                <img src="../uploads/<?= $p['gambar'] ?>" class="img-thumbnail" style="width:80px;height:80px;">
              <?php endif; ?>
            </td>
            <td><?= $p['nama'] ?></td>
            <td>Rp<?= number_format($p['harga_beli'],0,',','.') ?></td>
            <td>Rp<?= number_format($p['harga_jual'],0,',','.') ?></td>
            <td><?= $p['stok'] ?></td>
            <td><?= $p['minimal_pembelian'] ?></td>
            <td><?= $p['supplier'] ?: '-' ?></td>
            <td>
              <!-- Pastikan semua atribut data ada -->
              <button class="btn btn-sm btn-warning edit-btn" 
                      data-id="<?= $p['id'] ?>"
                      data-nama="<?= htmlspecialchars($p['nama']) ?>"
                      data-deskripsi="<?= htmlspecialchars($p['deskripsi']) ?>"
                      data-harga_beli="<?= $p['harga_beli'] ?>"
                      data-harga_jual="<?= $p['harga_jual'] ?>"
                      data-minimal="<?= $p['minimal_pembelian'] ?>"
                      data-supplier="<?= $p['supplier_id'] ?>"
                      data-stok="<?= $p['stok'] ?>"> <!-- Pastikan atribut ini ada -->
                Edit
              </button>
              <a href="?hapus=<?= $p['id'] ?>" class="btn btn-sm btn-danger delete-btn" onclick="return confirm('Hapus produk dan kembalikan stok?')">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Edit Produk -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Produk</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit_id">
        <div class="form-group">
          <label>Nama</label>
          <input type="text" name="nama" id="edit_nama" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Deskripsi</label>
          <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label>Stok</label>
          <input type="number" name="stok" id="edit_stok" class="form-control" min="0" required>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Harga Beli</label>
            <input type="number" name="harga_beli" id="edit_harga_beli" class="form-control" step="0.01" required>
          </div>
          <div class="form-group col-md-6">
            <label>Harga Jual</label>
            <input type="number" name="harga_jual" id="edit_harga_jual" class="form-control" step="0.01" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Min. Pembelian</label>
            <input type="number" name="minimal_pembelian" id="edit_minimal" class="form-control" min="1" required>
          </div>
          <div class="form-group col-md-6">
            <label>Supplier</label>
            <select name="supplier_id" id="edit_supplier" class="form-control" required>
              <option value="">-- Pilih --</option>
              <?php
              $suppliers2 = query("SELECT * FROM suppliers ORDER BY nama ASC");
              while ($s2 = $suppliers2->fetch_assoc()):
              ?>
                <option value="<?= $s2['id'] ?>"><?= $s2['nama'] ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Ganti Gambar</label>
          <input type="file" name="gambar" class="form-control-file">
          <small class="form-text text-muted">Kosongkan jika tidak ingin mengganti.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/assets/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
  // Pastikan menggunakan event delegation yang benar
  $(document).on('click', '.edit-btn', function() {
    // Ambil semua data dari tombol yang diklik
    var id = $(this).data('id');
    var nama = $(this).data('nama');
    var deskripsi = $(this).data('deskripsi');
    var harga_beli = $(this).data('harga_beli');
    var harga_jual = $(this).data('harga_jual');
    var minimal = $(this).data('minimal');
    var supplier = $(this).data('supplier');
    var stok = $(this).data('stok');
    
    // Set nilai ke form modal
    $('#edit_id').val(id);
    $('#edit_nama').val(nama);
    $('#edit_deskripsi').val(deskripsi);
    $('#edit_harga_beli').val(harga_beli);
    $('#edit_harga_jual').val(harga_jual);
    $('#edit_minimal').val(minimal);
    $('#edit_supplier').val(supplier);
    $('#edit_stok').val(stok);
    
    // Tampilkan modal
    $('#editModal').modal('show');
  });

  // Konfirmasi hapus
  $('.delete-btn').click(function(e) {
    if (!confirm('Yakin hapus produk dan kembalikan stok?')) {
      e.preventDefault();
    }
  });
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';