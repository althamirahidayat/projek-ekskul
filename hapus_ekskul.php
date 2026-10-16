<?php
session_start();
include 'koneksi.php';

// IZINKAN: Superadmin ATAU Admin Ekskul
if (
    !isset($_SESSION['superadmin_id']) &&
    !isset($_SESSION['admin_id'])
) {
    echo "<script>alert('❌ Silakan login dulu!'); window.location.href='login_superadmin.php';</script>";
    exit;
}

$id = $_GET['id'] ?? 0;

// Hapus
$stmt = $conn->prepare("DELETE FROM table_ekskul WHERE id_ekskul=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

echo "<script>alert('🗑️ Ekskul berhasil dihapus!'); window.location='kelola_ekskul.php';</script>";
