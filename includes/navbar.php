<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
  <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3"><i class="fa fa-bars"></i></button>
  <ul class="navbar-nav ml-auto">
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-toggle="dropdown">
        <span class="mr-2 text-gray-600 small"><?= $_SESSION['user']['nama'] ?></span>
        <i class="fas fa-user-circle fa-lg"></i>
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
        <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout</a>
      </div>
    </li>
  </ul>
</nav>
