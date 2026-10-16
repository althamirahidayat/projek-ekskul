<?php
session_start();
include 'koneksi.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    echo "<script>alert('❌ Username dan password wajib diisi!'); window.history.back();</script>";
    exit;
}

$stmt = $conn->prepare("SELECT id_admin, nama_lengkap, username, password, ekskul_dinaungi FROM Table_admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();

    if (password_verify($password, $data['password'])) {
        $_SESSION['admin_id'] = $data['id_admin'];
        $_SESSION['admin_nama'] = $data['nama_lengkap'];
        $_SESSION['admin_ekskul'] = $data['ekskul_dinaungi'];

        echo "<script>
            alert('✅ Login berhasil! Selamat datang, {$data['nama_lengkap']}');
            window.location.href='dashboard_admin.php';
        </script>";
        exit;
    } else {
        echo "<script>alert('❌ Password salah!'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('❌ Username tidak ditemukan!'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
?>
