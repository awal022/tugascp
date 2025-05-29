<?php
require 'config.php';
requireLogin();
$title='Dashboard';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>
  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
            Total Penjualan
          </div>
          <div class="h5 mb-0 font-weight-bold text-gray-800">
            <?= query("SELECT COUNT(*) AS c FROM sales")->fetch_assoc()['c'] ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include 'includes/footer.php'; ?>
</div>

