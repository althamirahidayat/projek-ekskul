<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['superadmin_id'])) {
    header("Location: login_superadmin.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$conn->query("DELETE FROM Table_admin WHERE id_admin=$id");
echo "<script>alert('✅ Admin dihapus!'); window.location.href='kelola_admin.php';</script>";
?>
