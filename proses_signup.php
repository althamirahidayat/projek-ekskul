<?php
session_start();
include 'koneksi.php';

// Ambil data dari form signup siswa
$nama     = trim($_POST['nama'] ?? '');
$nis      = trim($_POST['nis'] ?? '');
$kelas    = trim($_POST['kelas'] ?? '');
$notelp   = trim($_POST['notelp'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validasi input kosong
if ($nama === '' || $nis === '' || $kelas === '' || $notelp === '' || $email === '' || $password === '') {
    echo "<script>alert('❌ Semua kolom wajib diisi!');history.back();</script>";
    exit;
}

// Cek apakah NIS atau email sudah terdaftar
$cek = $conn->prepare("SELECT id_siswa FROM Table_siswa WHERE nis = ? OR email = ?");
$cek->bind_param("ss", $nis, $email);
$cek->execute();
$cek->store_result();

if ($cek->num_rows > 0) {
    echo "<script>alert('⚠️ NIS atau Email sudah terdaftar!');history.back();</script>";
    exit;
}
$cek->close();

// Simpan data siswa baru
$stmt = $conn->prepare("INSERT INTO table_siswa (nama_lengkap, nis, kelas, notelp, email, password, tanggal_daftar) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("ssssss", $nama, $nis, $kelas, $notelp, $email, $password);

if ($stmt->execute()) {
    echo "<script>alert('✅ Pendaftaran berhasil! Silakan login.');window.location.href='login_siswa.php';</script>";
    exit;
} else {
    echo "<script>alert('❌ Terjadi kesalahan saat menyimpan data.');history.back();</script>";
    exit;
}
?>
