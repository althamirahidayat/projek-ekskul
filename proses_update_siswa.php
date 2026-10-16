<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['siswa_id'])) exit;

$id = $_SESSION['siswa_id'];

$email = trim($_POST['email']);
$notelp = trim($_POST['notelp']);

$stmt = $conn->prepare("UPDATE table_siswa 
    SET email=?, notelp=? 
    WHERE id_siswa=?");

$stmt->bind_param("ssi", $email, $notelp, $id);
$stmt->execute();
$stmt->close();

echo "<script>alert('Profil berhasil diperbarui!'); window.location='profile_siswa.php';</script>";
