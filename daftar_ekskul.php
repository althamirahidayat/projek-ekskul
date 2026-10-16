<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['nis']) || !isset($_SESSION['siswa_nama'])) {
    echo "<script>alert('❌ Silakan login terlebih dahulu!'); window.location='index.php';</script>";
    exit;
}

$nis    = $_SESSION['nis'];
$nama   = $_SESSION['siswa_nama'];
$kelas  = $_SESSION['siswa_kelas'] ?? '';
$notelp = $_SESSION['siswa_telp'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ekskul = trim($_POST['nama_ekskul'] ?? '');
    $bukti_ss = '';

    if ($ekskul === '') {
        echo "<script>alert('❌ Nama ekskul tidak boleh kosong!'); history.back();</script>";
        exit;
    }

    // Buat folder upload
    if (!is_dir('uploads')) mkdir('uploads', 0777, true);

    // Upload screenshot
    if (!empty($_FILES['bukti_ss']['name'])) {
        $file_name = time() . "_" . basename($_FILES['bukti_ss']['name']);
        $target = "uploads/" . $file_name;

        if (move_uploaded_file($_FILES['bukti_ss']['tmp_name'], $target)) {
            $bukti_ss = $file_name;
        }
    }

    // Cek apakah sudah pernah daftar ekskul yang sama
    $cek = $conn->prepare("SELECT id_pendaftaran FROM table_pendaftaran WHERE nis=? AND nama_ekskul=?");
    $cek->bind_param("ss", $nis, $ekskul);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        echo "<script>alert('⚠️ Kamu sudah mendaftar ekskul ini sebelumnya!'); window.location='pilihan.php';</script>";
        exit;
    }
    $cek->close();

    // Simpan pendaftaran
    $stmt = $conn->prepare("
        INSERT INTO table_pendaftaran 
        (nis, nama_lengkap, kelas, nama_ekskul, status, bukti_ss, tanggal_daftar)
        VALUES (?, ?, ?, ?, 'Menunggu', ?, NOW())
    ");
    $stmt->bind_param("sssss", $nis, $nama, $kelas, $ekskul, $bukti_ss);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Pendaftaran ke $ekskul berhasil!'); window.location='pilihan.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal menyimpan pendaftaran!'); history.back();</script>";
    }
}
?>
