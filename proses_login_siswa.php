<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Cek apakah email ada di database
    $stmt = $conn->prepare("SELECT id_siswa, nis, nama_lengkap, kelas, notelp, email, password 
                            FROM table_siswa 
                            WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Jika email ditemukan
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();

        // Password tanpa hash (sesuai permintaan)
        if ($password === $data['password']) {

            // Set session
            $_SESSION['siswa_id']    = $data['id_siswa'];
            $_SESSION['nis']         = $data['nis'];
            $_SESSION['siswa_nama']  = $data['nama_lengkap'];
            $_SESSION['siswa_kelas'] = $data['kelas'];
            $_SESSION['siswa_telp']  = $data['notelp'];
            $_SESSION['siswa_email'] = $data['email'];

            echo "<script>
                alert('✅ Login berhasil!');
                window.location.href='pilihan.php';
            </script>";
            exit;
        } else {
            echo "<script>alert('❌ Password salah!');history.back();</script>";
            exit;
        }

    } else {
        echo "<script>alert('❌ Email tidak ditemukan!');history.back();</script>";
        exit;
    }
}
?>
