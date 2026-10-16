<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['superadmin_id'])) exit;

$id = $_SESSION['superadmin_id'];

$nama = trim($_POST['nama']);
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$notelp = trim($_POST['notelp']);

$stmt = $conn->prepare("UPDATE table_superadmin 
    SET nama_lengkap=?, username=?, email=?, notelp=? 
    WHERE id_superadmin=?");

$stmt->bind_param("ssssi", $nama, $username, $email, $notelp, $id);
$stmt->execute();
$stmt->close();

echo "<script>alert('Profil berhasil diperbarui!'); window.location='profile_superadmin.php';</script>";
