<?php
session_start();
include 'koneksi.php';



$admin_ekskul = $_SESSION['admin_ekskul'];
$id_pendaftaran = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

$allowed_status = ['Diterima', 'Ditolak', 'Menunggu'];
if (!in_array($status, $allowed_status)) {
    echo "<script>alert('❌ Status tidak valid!'); window.history.back();</script>";
    exit;
}

$cek = $conn->prepare("SELECT nama_ekskul FROM table_pendaftaran WHERE id_pendaftaran=?");
$cek->bind_param("i",$id_pendaftaran);
$cek->execute();
$data = $cek->get_result()->fetch_assoc();
$cek->close();

if (!$data || $data['nama_ekskul'] !== $admin_ekskul) {
    echo "<script>alert('🚫 Anda tidak memiliki hak untuk mengubah status pendaftar ini!'); window.history.back();</script>";
    exit;
}

$stmt = $conn->prepare("UPDATE table_pendaftaran SET status=? WHERE id_pendaftaran=?");
$stmt->bind_param("si",$status,$id_pendaftaran);
if ($stmt->execute()) {
    echo "<script>alert('✅ Status berhasil diubah menjadi $status!'); window.location.href='lihat_data.php';</script>";
} else {
    echo "<script>alert('❌ Gagal mengubah status!'); window.history.back();</script>";
}
$stmt->close();
$conn->close();
