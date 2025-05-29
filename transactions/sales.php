<?php
require_once '../config.php';
requireRole(['admin','staff']);
$title = 'Penjualan';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';

// Proses Hapus
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Ambil data penjualan
    $sale = query("SELECT product_id, jumlah FROM sales WHERE id=$id")->fetch_assoc();
    
    if ($sale) {
        $conn->begin_transaction();
        try {
            // Hapus penjualan
            query("DELETE FROM sales WHERE id=$id");
            
            // Kembalikan stok
            query("UPDATE products SET stok=stok+{$sale['jumlah']} WHERE id={$sale['product_id']}");
            
            $conn->commit();
            $_SESSION['success'] = 'Penjualan berhasil dihapus dan stok dikembalikan';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = 'Gagal menghapus penjualan: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Data penjualan tidak ditemukan';
    }
    header('Location: sales.php');
    exit;
}

// Proses Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $sale_id = (int)$_POST['sale_id'];
    $pid     = (int)$_POST['product_id'];
    $j       = (int)$_POST['jumlah'];
    $tgl     = escape($_POST['tanggal']);
    $nama    = escape($_POST['nama_pembeli']);
    $alamat  = escape($_POST['alamat']);
    $tel     = escape($_POST['telepon']);
    $mid     = (int)$_POST['payment_method_id'];
    $ket     = escape($_POST['keterangan']);

    // Ambil data penjualan lama
    $old_sale = query("SELECT product_id, jumlah FROM sales WHERE id=$sale_id")->fetch_assoc();
    $old_pid = $old_sale['product_id'];
    $old_j = $old_sale['jumlah'];

    // Ambil produk lama dan baru
    $old_prod = query("SELECT stok, harga_jual FROM products WHERE id=$old_pid")->fetch_assoc();
    $new_prod = query("SELECT stok, harga_jual, minimal_pembelian FROM products WHERE id=$pid")->fetch_assoc();

    $conn->begin_transaction();
    try {
        // Kembalikan stok produk lama
        query("UPDATE products SET stok=stok+$old_j WHERE id=$old_pid");
        
        // Validasi stok produk baru
        if ($j > $new_prod['stok']) {
            throw new Exception('Stok tidak mencukupi!');
        }
        
        if ($j < $new_prod['minimal_pembelian']) {
            throw new Exception('Minimal pembelian ' . $new_prod['minimal_pembelian'] . ' ekor!');
        }
        
        // Hitung total baru
        $total = $j * $new_prod['harga_jual'];
        
        // Update penjualan
        query("UPDATE sales SET 
                product_id=$pid, 
                nama_pembeli='$nama', 
                alamat='$alamat', 
                telepon='$tel', 
                payment_method_id=$mid, 
                jumlah=$j, 
                total_harga=$total, 
                tanggal='$tgl', 
                keterangan='$ket'
                WHERE id=$sale_id");
        
        // Kurangi stok produk baru
        query("UPDATE products SET stok=stok-$j WHERE id=$pid");
        
        $conn->commit();
        $_SESSION['success'] = 'Penjualan berhasil diupdate';
        header('Location: sales.php');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header("Location: sales.php?action=edit&id=$sale_id");
        exit;
    }
}

// Proses Tambah
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jual'])) {
    // ... [existing add code] ...
}

// Ambil Data untuk Tabel
$sales = query("
  SELECT s.*, p.nama AS produk, p.gambar, pm.nama AS metode
  FROM sales s
  JOIN products p ON s.product_id=p.id
  JOIN payment_methods pm ON s.payment_method_id=pm.id
  ORDER BY s.tanggal DESC
");

$products = query("SELECT * FROM products WHERE stok>0");
$methods  = query("SELECT * FROM payment_methods");

// Ambil data untuk edit
$edit_mode = false;
$edit_data = [];
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $edit_data = query("
        SELECT s.*, p.nama AS produk_nama 
        FROM sales s
        JOIN products p ON s.product_id = p.id
        WHERE s.id = $edit_id
    ")->fetch_assoc();
    
    if ($edit_data) {
        $edit_mode = true;
    }
}
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Penjualan Kambing</h1>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <!-- Tabel Penjualan -->
  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Gambar</th>
            <th>Tanggal</th>
            <th>Produk</th>
            <th>Pembeli</th>
            <th>Jumlah</th>
            <th>Total (Rp)</th>
            <th>Metode</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
          <?php while ($s = $sales->fetch_assoc()): ?>
            <tr>
                <td><img src="../uploads/<?= $s['gambar'] ?>" 
                    class="img-thumbnail" style="width:60px;height:60px;"></td>
              <td><?= $s['tanggal'] ?></td>
              <td><?= $s['produk'] ?></td>
              <td><?= $s['nama_pembeli'] ?></td>
              <td><?= $s['jumlah'] ?> ekor</td>
              <td><?= number_format($s['total_harga'],0,',','.') ?></td>
              <td><?= $s['metode'] ?></td>
              <td>
                <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" 
                   onclick="return confirm('Hapus penjualan ini? Stok akan dikembalikan')">Hapus</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form Tambah/Edit Penjualan -->
  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">
        <?= $edit_mode ? 'Edit Penjualan' : 'Tambah Penjualan Baru' ?>
      </h6>
    </div>
    <div class="card-body">
      <form method="post" class="form-row align-items-end">
        <?php if ($edit_mode): ?>
          <input type="hidden" name="sale_id" value="<?= $edit_data['id'] ?>">
        <?php endif; ?>
        
        <div class="form-group col-md-4">
          <label>Produk</label>
          <?php if ($edit_mode): ?>
            <input type="hidden" name="product_id" value="<?= $edit_data['product_id'] ?>">
            <input type="text" class="form-control" value="<?= $edit_data['produk_nama'] ?>" readonly>
          <?php else: ?>
            <select name="product_id" class="form-control" id="produk_select" required>
              <option value="">-- Pilih Kambing --</option>
              <?php while ($p = $products->fetch_assoc()): ?>
                <option value="<?= $p['id'] ?>" 
                  <?= $edit_mode && $edit_data['product_id'] == $p['id'] ? 'selected' : '' ?>
                  data-gambar="<?= $p['gambar'] ?>">
                  <?= $p['nama'] ?> (Stok: <?= $p['stok'] ?>)
                </option>
              <?php endwhile; ?>
            </select>
            <div id="preview_gambar" class="mt-2">
                <img src="" id="gambar_preview" style="display:none;width:100px;height:100px;" class="img-thumbnail">
            </div>
          <?php endif; ?>
        </div>
        
        <div class="form-group col-md-2">
          <label>Jumlah</label>
          <input type="number" name="jumlah" class="form-control" min="1" 
                 value="<?= $edit_mode ? $edit_data['jumlah'] : '1' ?>" required>
        </div>
        
        <div class="form-group col-md-3">
          <label>Metode Pembayaran</label>
          <select name="payment_method_id" class="form-control" required>
            <option value="">-- Pilih Metode --</option>
            <?php 
            $methods->data_seek(0); // Reset pointer
            while ($m = $methods->fetch_assoc()): ?>
              <option value="<?= $m['id'] ?>"
                <?= $edit_mode && $edit_data['payment_method_id'] == $m['id'] ? 'selected' : '' ?>>
                <?= $m['nama'] ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="form-group col-md-3">
          <label>Tanggal</label>
          <input type="date" name="tanggal" class="form-control" 
                 value="<?= $edit_mode ? $edit_data['tanggal'] : date('Y-m-d') ?>" required>
        </div>
        
        <div class="form-group col-md-6">
          <label>Nama Pembeli</label>
          <input type="text" name="nama_pembeli" class="form-control" 
                 value="<?= $edit_mode ? $edit_data['nama_pembeli'] : '' ?>" required>
        </div>
        
        <div class="form-group col-md-6">
          <label>Alamat</label>
          <textarea name="alamat" class="form-control" rows="2"><?= $edit_mode ? $edit_data['alamat'] : '' ?></textarea>
        </div>
        
        <div class="form-group col-md-6">
          <label>Telepon</label>
          <input type="text" name="telepon" class="form-control" 
                 value="<?= $edit_mode ? $edit_data['telepon'] : '' ?>">
        </div>
        
        <div class="form-group col-md-6">
          <label>Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="2"><?= $edit_mode ? $edit_data['keterangan'] : '' ?></textarea>
        </div>
        
        <div class="form-group col-md-12">
          <?php if ($edit_mode): ?>
            <button type="submit" name="update" class="btn btn-warning">Update Penjualan</button>
            <a href="sales.php" class="btn btn-secondary">Batal</a>
          <?php else: ?>
            <button type="submit" name="jual" class="btn btn-primary">Simpan Penjualan</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
    
  </div>
  <?php include '../includes/footer.php'; ?>
</div>


<script>
  <?php if (!$edit_mode): ?>
  document.getElementById('produk_select').addEventListener('change', function() {
    const gambar = this.options[this.selectedIndex].dataset.gambar;
    const img = document.getElementById('gambar_preview');
    if (gambar) {
      img.src = '../uploads/' + gambar;
      img.style.display = 'block';
    } else {
      img.style.display = 'none';
    }
  });
  
  // Trigger change on page load if there's selected product
  const initialSelect = document.getElementById('produk_select');
  if (initialSelect.value) {
    initialSelect.dispatchEvent(new Event('change'));
  }
  <?php endif; ?>
</script>