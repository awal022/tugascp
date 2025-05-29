<?php
session_start();
$host='localhost'; 
$user='root'; 
$pass=''; 
$db='qurban_app';
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die('Koneksi gagal: '.$conn->connect_error);

function query($sql) {
    global $conn;
    return $conn->query($sql);
}
function escape($v) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars($v));
}
function requireLogin() {
    if(!isset($_SESSION['user'])) {
        header('Location: login.php'); exit;
    }
}
function requireRole($roles) {
    requireLogin();
    if(!in_array($_SESSION['user']['role'],$roles)) {
        header('Location: index.php'); exit;
    }
}
function uploadGambar() {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $namaFile = $_FILES['gambar']['name'];
    $ukuranFile = $_FILES['gambar']['size'];
    $error = $_FILES['gambar']['error'];
    $tmpName = $_FILES['gambar']['tmp_name'];

    $ekstensiValid = ['jpg','jpeg','png','gif'];
    $ekstensi = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

    if (!in_array($ekstensi, $ekstensiValid)) return false;
    if ($ukuranFile > 2_000_000) return false;
    if ($error !== 0) return false;

    $namaBaru = uniqid() . '.' . $ekstensi;
    if (move_uploaded_file($tmpName, $uploadDir . $namaBaru)) return $namaBaru;

    return false;
}


?>
