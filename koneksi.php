<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "ekskul_database";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}
?>
