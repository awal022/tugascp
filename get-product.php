<?php
require_once '../config.php';
header('Content-Type: application/json');

// Validasi parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing product ID']);
    exit;
}

$id = (int)$_GET['id'];

// Query database
$stmt = $conn->prepare("
    SELECT id, nama, deskripsi, gambar, harga_beli, harga_jual, stok, minimal_pembelian
    FROM products
    WHERE id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($product = $result->fetch_assoc()) {
    // Tambahkan URL lengkap ke gambar
    $product['gambar_url'] = $product['gambar']
        ? (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
          . $_SERVER['HTTP_HOST'] . '/uploads/' . $product['gambar']
        : null;

    echo json_encode(['product' => $product]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
}
