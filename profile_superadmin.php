<?php
session_start();
include 'koneksi.php';

// Pastikan hanya superadmin yg boleh masuk
if (!isset($_SESSION['superadmin_id'])) {
    echo "<script>alert('❌ Silakan login sebagai Superadmin!'); window.location='login_superadmin.php';</script>";
    exit;
}

// --- AMBIL DATA DARI SESSION SECARA AMAN ---
$nama     = $_SESSION['superadmin_nama']     ?? "";
$username = $_SESSION['superadmin_username'] ?? "";
$email    = $_SESSION['superadmin_email']    ?? "";
$notelp   = $_SESSION['superadmin_notelp']   ?? "";

// --- SIMPAN PERUBAHAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaBaru   = trim($_POST['nama']);
    $emailBaru  = trim($_POST['email']);
    $notelpBaru = trim($_POST['notelp']);

    $id = $_SESSION['superadmin_id'];

    $stmt = $conn->prepare("
        UPDATE table_admin 
        SET nama_admin = ?, email = ?, notelp = ?
        WHERE id_admin = ?
    ");
    $stmt->bind_param("sssi", $namaBaru, $emailBaru, $notelpBaru, $id);
    $stmt->execute();
    $stmt->close();

    // Update session
    $_SESSION['superadmin_nama']   = $namaBaru;
    $_SESSION['superadmin_email']  = $emailBaru;
    $_SESSION['superadmin_notelp'] = $notelpBaru;

    echo "<script>alert('✅ Profil berhasil diperbarui!'); window.location='profile_superadmin.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Superadmin</title>
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="superadmin.css">
</head>
<body>

<h1>Profil Superadmin</h1>

<form method="post">
    <label>Nama Lengkap</label>
    <input type="text" name="nama" value="<?= htmlspecialchars($nama) ?>" required>

    <label>Username</label>
    <input type="text" value="<?= htmlspecialchars($username) ?>" disabled>

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label>No Telp</label>
    <input type="text" name="notelp" value="<?= htmlspecialchars($notelp) ?>" required>

    <button type="submit" class="btn-submit">Simpan</button>
    <a href="dashboard_superadmin.php" class="btn-kembali">Kembali</a>
</form>

</body>
</html>
