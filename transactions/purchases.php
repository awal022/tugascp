<?php
require_once '../config.php';
requireRole(['admin','staff']);
$title = 'Pembelian';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/navbar.php';

// Proses Tambah
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['beli'])) {
    $pid = (int)$_POST['product_id'];
    $sid = (int)$_POST['supplier_id'];
    $j   = (int)$_POST['jumlah'];
    $tgl = escape($_POST['tanggal']);
    // Hitung total
    $hb  = query("SELECT harga_beli FROM products WHERE id=$pid")->fetch_assoc()['harga_beli'];
    $total = $j * $hb;

    // Simpan & update stok
    $conn->begin_transaction();
    try {
        query("INSERT INTO purchases (product_id, supplier_id, jumlah, total_harga, tanggal) VALUES($pid,$sid,$j,$total,'$tgl')");
        query("UPDATE products SET stok=stok+$j WHERE id=$pid");
        $conn->commit();
        $_SESSION['success'] = 'Pembelian berhasil disimpan';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Gagal menyimpan pembelian: ' . $e->getMessage();
    }
    header('Location: purchases.php');
    exit;
}

// Proses Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id  = (int)$_POST['id'];
    $pid = (int)$_POST['product_id'];
    $sid = (int)$_POST['supplier_id'];
    $j   = (int)$_POST['jumlah'];
    $tgl = escape($_POST['tanggal']);
    
    // Ambil data lama untuk perbandingan
    $old = query("SELECT * FROM purchases WHERE id=$id")->fetch_assoc();
    $old_pid = $old['product_id'];
    $old_j   = $old['jumlah'];
    
    // Hitung total baru
    $hb    = query("SELECT harga_beli FROM products WHERE id=$pid")->fetch_assoc()['harga_beli'];
    $total = $j * $hb;

    $conn->begin_transaction();
    try {
        // Update pembelian
        query("UPDATE purchases SET product_id=$pid, supplier_id=$sid, jumlah=$j, total_harga=$total, tanggal='$tgl' WHERE id=$id");
        
        // Atur stok sesuai perubahan
        if ($old_pid == $pid) {
            // Produk sama, sesuaikan selisih
            $selisih = $j - $old_j;
            if ($selisih != 0) {
                query("UPDATE products SET stok=stok+$selisih WHERE id=$pid");
            }
        } else {
            // Produk berbeda, kembalikan stok lama dan tambahkan stok baru
            query("UPDATE products SET stok=stok-$old_j WHERE id=$old_pid");
            query("UPDATE products SET stok=stok+$j WHERE id=$pid");
        }
        $conn->commit();
        $_SESSION['success'] = 'Pembelian berhasil diupdate';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Gagal mengupdate pembelian: ' . $e->getMessage();
    }
    header('Location: purchases.php');
    exit;
}

// Proses Hapus
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $purchase = query("SELECT * FROM purchases WHERE id=$id")->fetch_assoc();
    if ($purchase) {
        $pid = $purchase['product_id'];
        $j   = $purchase['jumlah'];
        $conn->begin_transaction();
        try {
            query("DELETE FROM purchases WHERE id=$id");
            query("UPDATE products SET stok=stok-$j WHERE id=$pid");
            $conn->commit();
            $_SESSION['success'] = 'Pembelian berhasil dihapus';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = 'Gagal menghapus pembelian: ' . $e->getMessage();
        }
    }
    header('Location: purchases.php');
    exit;
}

// Ambil Data
$purchases = query(
  "SELECT pu.*, pr.nama AS produk, su.nama AS supplier
   FROM purchases pu
   JOIN products pr ON pu.product_id = pr.id
   JOIN suppliers su ON pu.supplier_id = su.id
   ORDER BY pu.tanggal DESC"
);
$products  = query("SELECT * FROM products");
$suppliers = query("SELECT * FROM suppliers");
?>

<div class="container-fluid">
  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pembelian Kambing</h1>
  </div>

  <!-- Alerts -->
  <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <!-- Daftar Pembelian -->
  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Daftar Pembelian</h6>
      <div class="text-muted small">Showing <?= $purchases->num_rows ?> entries</div>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-bordered" width="100%">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Produk</th>
            <th>Supplier</th>
            <th>Jumlah</th>
            <th>Total (Rp)</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($r = $purchases->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['tanggal']) ?></td>
            <td><?= htmlspecialchars($r['produk']) ?></td>
            <td><?= htmlspecialchars($r['supplier']) ?></td>
            <td><?= (int)$r['jumlah'] ?></td>
            <td><?= number_format($r['total_harga'],0,',','.') ?></td>
            <td>
              <button class="btn btn-sm btn-primary edit-btn" 
                data-id="<?= $r['id'] ?>"
                data-product="<?= $r['product_id'] ?>"
                data-supplier="<?= $r['supplier_id'] ?>"
                data-jumlah="<?= $r['jumlah'] ?>"
                data-tanggal="<?= $r['tanggal'] ?>"
                data-toggle="modal" data-target="#editModal">
                <i class="fas fa-edit"></i> Edit
              </button>
              <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus pembelian ini? Stok akan dikurangi.')">
                <i class="fas fa-trash"></i> Hapus
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form Tambah Pembelian -->
  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">Tambah Pembelian Baru</h6>
    </div>
    <div class="card-body">
      <form method="post">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Produk</label>
            <select name="product_id" class="form-control" required>
              <option value="">-- Pilih Kambing --</option>
              <?php mysqli_data_seek($products,0); while($p=$products->fetch_assoc()): ?>
                <option value="<?= $p['id']?>"><?= htmlspecialchars($p['nama']) ?> (Stok: <?= $p['stok']?>)</option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group col-md-6">
            <label>Supplier</label>
            <select name="supplier_id" class="form-control" required>
              <option value="">-- Pilih Supplier --</option>
              <?php mysqli_data_seek($suppliers,0); while($s=$suppliers->fetch_assoc()): ?>
                <option value="<?= $s['id']?>"><?= htmlspecialchars($s['nama']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Jumlah</label>
            <input type="number" name="jumlah" class="form-control" min="1" value="1" required>
          </div>
          <div class="form-group col-md-6">
            <label>Tanggal</label>
            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d')?>" required>
          </div>
        </div>
        <button type="submit" name="beli" class="btn btn-success">
          <i class="fas fa-save"></i> Simpan Pembelian
        </button>
      </form>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Pembelian</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="post">
          <input type="hidden" name="id" id="editId">
          <div class="modal-body">
            <div class="form-group">
              <label>Produk</label>
              <select name="product_id" id="editProduct" class="form-control" required>
                <option value="">-- Pilih Kambing --</option>
                <?php $pe=query("SELECT * FROM products"); while($pp=$pe->fetch_assoc()): ?>
                  <option value="<?= $pp['id']?>" data-stok="<?= $pp['stok']?>"><?= htmlspecialchars($pp['nama']) ?> (Stok: <?= $pp['stok']?>)</option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Supplier</label>
              <select name="supplier_id" id="editSupplier" class="form-control" required>
                <option value="">-- Pilih Supplier --</option>
                <?php $se=query("SELECT * FROM suppliers"); while($ss=$se->fetch_assoc()): ?>
                  <option value="<?= $ss['id']?>"><?= htmlspecialchars($ss['nama']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Jumlah</label>
              <input type="number" name="jumlah" id="editJumlah" class="form-control" min="1" required>
              <small id="stokInfo" class="form-text text-muted"></small>
            </div>
            <div class="form-group">
              <label>Tanggal</label>
              <input type="date" name="tanggal" id="editTanggal" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include '../includes/footer.php'; ?>
</div>

<!-- Scripts -->
<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    function updateStokInfo() {
        var stok = $('#editProduct option:selected').data('stok');
        $('#stokInfo').text('Stok tersedia: ' + stok);
    }
    
    $('.edit-btn').click(function() {
        $('#editId').val($(this).data('id'));
        $('#editProduct').val($(this).data('product')).change();
        $('#editSupplier').val($(this).data('supplier'));
        $('#editJumlah').val($(this).data('jumlah'));
        $('#editTanggal').val($(this).data('tanggal'));
    });
    $('#editProduct').on('change', updateStokInfo);
});
</script>