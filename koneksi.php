<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = "sql309.infinityfree.com";
$user = "if0_43191829";
$pass = "UZXzZZ9cQ4IE";
$db   = "if0_43191829_ekskul_database";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}
?>