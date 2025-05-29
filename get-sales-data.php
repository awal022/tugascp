<?php
require_once '../config.php';
header('Content-Type: application/json');

// Ambil data penjualan 6 bulan terakhir, per bulan
$sql = "
    SELECT
        DATE_FORMAT(tanggal, '%Y-%m') AS periode,
        SUM(total_harga) AS total
    FROM sales
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY periode
    ORDER BY periode ASC
";

$result = query($sql);
$labels = [];
$data   = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['periode'];
    $data[]   = (float)$row['total'];
}

echo json_encode([
    'labels' => $labels,
    'data'   => $data
]);
