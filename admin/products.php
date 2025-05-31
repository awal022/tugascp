<?php
// admin/products.php

require_once __DIR__ . '/../config.php';
requireRole(['admin']);

$title = 'Produk';
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

// Handle edit produk via modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id         = (int)   $_POST['id'];
    $nama       = escape($_POST['nama']);
    $deskripsi  = escape($_POST['deskripsi']);
    $harga_beli = (float) $_POST['harga_beli'];
    $harga_jual = (float) $_POST['harga_jual'];
    $minimal    = (int)   $_POST['minimal_pembelian'];
    $supplier   = (int)   $_POST['supplier_id'];
    $stok_baru  = (int)   $_POST['stok'];
    $updGambar  = '';

    // Dapatkan data lama
    $row = query("SELECT stok, gambar, supplier_id FROM products WHERE id=$id")->fetch_assoc();
    $old_stok     = (int)$row['stok'];
    $old_supplier = (int)$row['supplier_id'];

    // Hitung perubahan stok (jika perlu update supplier stok)
    $stok_change = $stok_baru - $old_stok;

    // Handle upload gambar baru
    if (!empty($_FILES['gambar']['name'])) {
        $newG = uploadGambar();
        if ($newG) {
            // hapus file lama
            if ($row['gambar'] && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
                unlink(__DIR__ . '/../uploads/' . $row['gambar']);
            }
            $updGambar = ", gambar='$newG'";
        }
    }

    $conn->begin_transaction();
    try {
        // Update produk
        query("UPDATE products SET
            nama='$nama',
            deskripsi='$deskripsi',
            harga_beli=$harga_beli,
            harga_jual=$harga_jual,
            minimal_pembelian=$minimal,
            supplier_id=$supplier,
            stok=$stok_baru
            $updGambar
            WHERE id=$id");

        // Jika supplier berganti atau stok berkurang, sesuaikan stok supplier
        if ($supplier !== $old_supplier) {
            // kembalikan stok lama ke supplier lama
            query("UPDATE suppliers SET stok=stok+$old_stok WHERE id=$old_supplier");
            // tambahkan stok baru ke supplier baru
            query("UPDATE suppliers SET stok=stok+$stok_baru WHERE id=$supplier");
        } elseif ($stok_change < 0) {
            // stok berkurang, kembalikan selisih ke supplier
            query("UPDATE suppliers SET stok=stok+" . abs($stok_change) . " WHERE id=$supplier");
        }

        $conn->commit();
        $_SESSION['success'] = 'Produk berhasil diubah';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Gagal mengubah produk: ' . $e->getMessage();
    }
    header('Location: products.php');
    exit;
}

// Handle hapus produk
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $row = query("SELECT gambar, stok, supplier_id FROM products WHERE id=$id")->fetch_assoc();

    $conn->begin_transaction();
    try {
        // kembalikan stok ke supplier
        if ((int)$row['stok'] > 0) {
            query("UPDATE suppliers SET stok=stok+{$row['stok']} WHERE id={$row['supplier_id']}");
        }
        // hapus gambar
        if ($row['gambar'] && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
            unlink(__DIR__ . '/../uploads/' . $row['gambar']);
        }
        // hapus produk
        query("DELETE FROM products WHERE id=$id");

        $conn->commit();
        $_SESSION['success'] = 'Produk berhasil dihapus dan stok dikembalikan';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Gagal menghapus produk: ' . $e->getMessage();
    }
    header('Location: products.php');
    exit;
}

// Ambil data
$products  = query("SELECT p.*, s.nama AS supplier, p.supplier_id FROM products p
                    LEFT JOIN suppliers s ON p.supplier_id=s.id
                    ORDER BY p.nama ASC");
$suppliers = query("SELECT * FROM suppliers ORDER BY nama ASC");
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Manajemen Produk</h1>

  <!-- Alerts -->
  <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <!-- Form Tambah Produk -->
  <div class="card shadow mb-4">
    <div class="card-header py-3"><strong>Tambah Produk Baru</strong></div>
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
          <div class="form-group col-md-1 d-flex align-items-end">
            <button type="submit" name="tambah" class="btn btn-success btn-block">Simpan</button>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Supplier</label>
            <select name="supplier_id" class="form-control" required>
              <option value="">-- Pilih Supplier --</option>
              <?php while ($s = $suppliers->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
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
      <table class="table table-bordered" width="100%">
        <thead>
          <tr>
            <th>Gambar</th><th>Nama</th><th>Harga Beli</th><th>Harga Jual</th>
            <th>Stok</th><th>Min. Beli</th><th>Supplier</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($p = $products->fetch_assoc()): ?>
          <tr>
            <td><?php if ($p['gambar']): ?><img src="../uploads/<?= htmlspecialchars($p['gambar']) ?>" class="img-thumbnail" style="width:60px;height:60px;"><?php endif; ?></td>
            <td><?= htmlspecialchars($p['nama']) ?></td>
            <td>Rp<?= number_format($p['harga_beli'],0,',','.') ?></td>
            <td>Rp<?= number_format($p['harga_jual'],0,',','.') ?></td>
            <td><?= $p['stok'] ?></td>
            <td><?= $p['minimal_pembelian'] ?></td>
            <td><?= htmlspecialchars($p['supplier'] ?? '-') ?></td>
            <td>
              <button class="btn btn-sm btn-warning edit-btn"
                data-id="<?= $p['id'] ?>"
                data-nama="<?= htmlspecialchars($p['nama']) ?>"
                data-deskripsi="<?= htmlspecialchars($p['deskripsi']) ?>"
                data-harga_beli="<?= $p['harga_beli'] ?>"
                data-harga_jual="<?= $p['harga_jual'] ?>"
                data-minimal_pembelian="<?= $p['minimal_pembelian'] ?>"
                data-supplier_id="<?= $p['supplier_id'] ?>"
                data-stok="<?= $p['stok'] ?>"
                >Edit</button>
              <a href="?hapus=<?= $p['id'] ?>" class="btn btn-sm btn-danger delete-btn">Hapus</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Edit Produk -->
  <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form method="post" enctype="multipart/form-data" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Produk</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <div class="form-group"><label>Nama</label><input type="text" name="nama" id="edit_nama" class="form-control" required></div>
          <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="2"></textarea></div>
          <div class="form-group"><label>Stok</label><input type="number" name="stok" id="edit_stok" class="form-control" min="0" required></div>
          <div class="form-row">
            <div class="form-group col-md-6"><label>Harga Beli</label><input type="number" name="harga_beli" id="edit_harga_beli" class="form-control" step="0.01" required></div>
            <div class="form-group col-md=6"><label>Harga Jual</label><input type="number" name="harga_jual" id="edit_harga_jual" class="form-control" step="0.01" required></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6"><label>Min. Pembelian</label><input type="number" name="minimal_pembelian" id="edit_minimal" class="form-control" min="1" required></div>
            <div class="form-group col-md-6"><label>Supplier</label><select name="supplier_id" id="edit_supplier" class="form-control" required><option value="">-- Pilih Supplier --</option><?php
              $sup2 = query("SELECT * FROM suppliers ORDER BY nama ASC"); while($s2=$sup2->fetch_assoc()): ?><option value="<?= $s2['id'] ?>"><?= htmlspecialchars($s2['nama']) ?></option><?php endwhile; ?></select></div>
          </div>
          <div class="form-group"><label>Ganti Gambar</label><input type="file" name="gambar" class="form-control-file"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
$(document).on('click', '.edit-btn', function() {
  $('#edit_id').val($(this).data('id'));
  $('#edit_nama').val($(this).data('nama'));
  $('#edit_deskripsi').val($(this).data('deskripsi'));
  $('#edit_stok').val($(this).data('stok'));
  $('#edit_harga_beli').val($(this).data('harga_beli'));
  $('#edit_harga_jual').val($(this).data('harga_jual'));
  $('#edit_minimal').val($(this).data('minimal_pembelian'));
  $('#edit_supplier').val($(this).data('supplier_id'));
  $('#editModal').modal('show');
});

$(document).on('click', '.delete-btn', function() {
  return confirm('Yakin hapus produk dan kembalikan stok?');
});
</script>