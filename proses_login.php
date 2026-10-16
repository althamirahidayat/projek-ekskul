<?php
session_start();
include 'koneksi.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

$stmt = $conn->prepare("SELECT id_admin, nama_admin, password, role, ekskul_dinaungi FROM table_admin WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $nama, $pw, $role, $ekskul);
    $stmt->fetch();

    if ($password === $pw) {
        $_SESSION['admin_id'] = $id;
        $_SESSION['admin_nama'] = $nama;
        $_SESSION['role'] = $role;
        $_SESSION['admin_ekskul'] = $ekskul;

        if ($role === 'superadmin') {
            echo "<script>alert('✅ Login Superadmin berhasil!'); window.location.href='dashboard_superadmin.php';</script>";
        } else {
            echo "<script>alert('✅ Login Admin Ekskul berhasil!'); window.location.href='dashboard_admin.php';</script>";
        }
        exit;
    } else {
        echo "<script>alert('❌ Password salah!'); history.back();</script>";
    }
} else {
    echo "<script>alert('❌ Username tidak ditemukan!'); history.back();</script>";
}
?>
