<?php
session_start();
include 'koneksi.php';

// Pastikan login admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$id = $_SESSION['admin_id'];

$stmt = $conn->prepare("SELECT username, email, notelp, ekskul_dinaungi FROM table_admin WHERE id_admin=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($username, $email, $notelp, $ekskul);
$stmt->fetch();
$stmt->close();

// Jika ada data yang null, set string kosong agar tidak error
$username = $username ?? "";
$email    = $email ?? "";
$notelp   = $notelp ?? "";
$ekskul   = $ekskul ?? "";

?>
<!DOCTYPE html>
<html>
<head><title>Profil Admin Ekskul</title>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="global.css">
</head>
<body>

<h1>Profil Admin Ekskul</h1>

<form action="proses_update_admin.php" method="POST">

<label>Nama Admin</label>
<input type="text" name="nama" value="<?= htmlspecialchars($nama) ?>" required>

<label>Username</label>
<input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required>

<label>Email</label>
<input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

<label>No Telp</label>
<input type="text" name="notelp" value="<?= htmlspecialchars($notelp) ?>" required>

<label>Ekskul Dinaungi (Tidak bisa diubah)</label>
<input type="text" value="<?= htmlspecialchars($ekskul) ?>" disabled>

<button type="submit">Simpan</button>
<a href="dashboard_admin.php">Kembali</a>

</form>

</body>
</html>
