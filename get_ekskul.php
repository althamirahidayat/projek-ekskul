<?php
include 'koneksi.php';

$result = $conn->query("SELECT id, nama_ekskul FROM Table_ekskul ORDER BY nama_ekskul ASC");
$ekskul = [];

while ($row = $result->fetch_assoc()) {
    $ekskul[] = $row;
}

header('Content-Type: application/json');
echo json_encode($ekskul);
