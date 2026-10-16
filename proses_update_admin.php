<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_id'])) exit;

$id = $_SESSION['admin_id'];

$nama = trim($_POST['nama']);
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$notelp = trim($_POST['notelp']);

$stmt = $conn->prepare("UPDATE table_admin 
    SET nama_admin=?, username=?, email=?, notelp=? 
    WHERE id_admin=?");

$stmt->bind_param("ssssi", $nama, $username, $email, $notelp, $id);
$stmt->execute();
$stmt->close();

echo "<script>alert('Profil berhasil diperbarui!'); window.location='profile_admin.php';</script>";
