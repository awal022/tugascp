<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="/qurban-app/index.php">
    <div class="sidebar-brand-text mx-3">Setia 1 Farm</div>
  </a>
  <hr class="sidebar-divider my-0">

  <!-- Dashboard -->
  <li class="nav-item <?= basename($_SERVER['PHP_SELF'])=='index.php' ? 'active' : '' ?>">
    <a class="nav-link" href="/qurban-app/index.php">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <?php if($_SESSION['user']['role']=='admin'): ?>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Admin</div>

    <!-- Produk -->
    <li class="nav-item <?= basename($_SERVER['PHP_SELF'])=='products.php' ? 'active' : '' ?>">
      <a class="nav-link" href="/qurban-app/admin/products.php">
        <i class="fas fa-fw fa-database"></i>
        <span>Produk</span>
      </a>
    </li>

    <!-- Suppliers -->
    <li class="nav-item <?= basename($_SERVER['PHP_SELF'])=='suppliers.php' ? 'active' : '' ?>">
      <a class="nav-link" href="/qurban-app/admin/suppliers.php">
        <i class="fas fa-fw fa-truck"></i>
        <span>Suppliers</span>
      </a>
    </li>

    <!-- Users -->
    <li class="nav-item <?= basename($_SERVER['PHP_SELF'])=='users.php' ? 'active' : '' ?>">
      <a class="nav-link" href="/qurban-app/admin/users.php">
        <i class="fas fa-fw fa-users"></i>
        <span>Users</span>
      </a>
    </li>
  <?php endif; ?>

  <hr class="sidebar-divider">
  <div class="sidebar-heading">Transaksi</div>

  <!-- Pembelian -->
  <li class="nav-item <?= basename($_SERVER['PHP_SELF'])=='purchases.php' ? 'active' : '' ?>">
    <a class="nav-link" href="/qurban-app/transactions/purchases.php">
      <i class="fas fa-fw fa-cart-plus"></i>
      <span>Pembelian</span>
    </a>
  </li>

  <!-- Penjualan -->
  <li class="nav-item <?= basename($_SERVER['PHP_SELF'])=='sales.php' ? 'active' : '' ?>">
    <a class="nav-link" href="/qurban-app/transactions/sales.php">
      <i class="fas fa-fw fa-cash-register"></i>
      <span>Penjualan</span>
    </a>
  </li>

  <hr class="sidebar-divider">
  <!-- Logout -->
  <li class="nav-item">
    <a class="nav-link" href="/qurban-app/logout.php">
      <i class="fas fa-fw fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </li>
</ul>
