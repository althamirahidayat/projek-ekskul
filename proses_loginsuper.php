<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id_superadmin, nama_lengkap, username, password FROM Table_superadmin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $data = $res->fetch_assoc();

        // VERIFIKASI TANPA HASH (langsung dibandingkan)
        if ($password === $data['password']) {

            $_SESSION['superadmin_id'] = $data['id_superadmin'];
            $_SESSION['superadmin_nama'] = $data['nama_lengkap'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role'] = 'superadmin';

            header("Location: dashboard_superadmin.php");
            exit;

        } else {
            echo "<script>alert('❌ Password salah!');window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('❌ Username tidak ditemukan!');window.history.back();</script>";
        exit;
    }
}
?>
 