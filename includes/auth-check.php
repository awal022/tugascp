<?php
// Use absolute path instead of relative
require_once __DIR__ . '/../config.php';  // Changed this line

if(!isset($_SESSION['users'])) {
    header("Location: __DIR__ . /../login.php");
    exit();
}
?>