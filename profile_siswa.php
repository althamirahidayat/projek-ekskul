<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['siswa_id'])) {
    header("Location: login_siswa.php");
    exit;
}

// Pastikan koneksi.php sudah terhubung dan $conn tersedia
if (!$conn) {
    // Sebagai fallback atau penanganan error yang lebih baik
    die("Koneksi database gagal."); 
}

$id = $_SESSION['siswa_id'];

// Menggunakan prepared statement untuk keamanan
$stmt = $conn->prepare("SELECT email, notelp, nama_lengkap, kelas, nis FROM table_siswa WHERE id_siswa=?");
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($email, $notelp, $nama, $kelas, $nis);
$stmt->fetch();
$stmt->close();

$email = $email ?? "";
$notelp = $notelp ?? "";
$nama = $nama ?? "";
$kelas = $kelas ?? "";
$nis = $nis ?? "";

// Cek apakah ada pesan sukses atau error dari proses_update_siswa.php
$message = $_SESSION['profile_message'] ?? null;
$message_type = $_SESSION['profile_message_type'] ?? 'info';
unset($_SESSION['profile_message']);
unset($_SESSION['profile_message_type']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Siswa | Edit Data</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Variabel Warna (Konsisten) */
        :root {
            --color-primary: #0D47A1; /* Biru Gelap/Navy */
            --color-secondary: #1976D2; /* Biru Standar */
            --color-accent: #00BCD4; /* Cyan */
            --color-success: #4CAF50;
            --color-danger: #F44336;
            --color-white: #ffffff;
            --color-grey-light: #f4f7fb;
            --color-text-dark: #333333;
            --color-text-medium: #666;
            --shadow-sm: 0 2px 5px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 5px 15px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.15);
            --radius: 12px;
        }

        /* Global Reset & Body */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--color-grey-light);
            color: var(--color-text-dark);
            min-height: 100vh;
        }

        /* Layout Utama (Sidebar + Content) */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* ================================================= */
        /* SIDEBAR (NAVIGASI) */
        /* ================================================= */

        .sidebar {
            width: 260px;
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
            transition: width 0.3s ease;
        }

        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid var(--color-accent);
            object-fit: cover;
            margin-bottom: 10px;
        }

        .sidebar-header h2 {
            font-size: 1.2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            padding: 0 20px;
        }

        .menu-item {
            color: var(--color-white);
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: background-color 0.3s, transform 0.1s;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(3px);
        }

        .menu-item.active {
            background-color: var(--color-secondary);
            box-shadow: var(--shadow-sm);
        }
        
        .menu-item.logout {
            margin-top: auto; 
            background-color: var(--color-danger);
        }
        .menu-item.logout:hover {
             background-color: #D32F2F;
        }


        /* ================================================= */
        /* CONTENT AREA */
        /* ================================================= */

        .content-area {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
            display: flex;
            justify-content: center; /* Pusatkan konten profil di area konten */
            align-items: center;
        }

        /* Container Utama */
        .profile-container {
            width: 100%;
            max-width: 550px;
            /* Hapus centering dari body, sekarang handled oleh content-area */
        }

        /* Card Profil */
        .card {
            background: var(--color-white);
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            width: 100%;
            text-align: center;
            margin: 20px 0; /* Tambahkan margin vertikal agar tidak terlalu mepet */
        }

        .card h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--color-primary);
            margin-bottom: 25px;
            border-bottom: 2px solid var(--color-grey-light);
            padding-bottom: 15px;
        }
        
        /* Message Box */
        .message-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: left;
        }

        .message-box.success {
            background-color: #E8F5E9; /* Light Green */
            color: var(--color-success);
            border: 1px solid var(--color-success);
        }

        .message-box.error {
            background-color: #FFEBEE; /* Light Red */
            color: var(--color-danger);
            border: 1px solid var(--color-danger);
        }


        /* Form Styling */
        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            text-align: left;
        }

        .form-group label {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--color-text-dark);
            margin-bottom: 5px;
            display: block;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:not(.disabled-input):focus {
            outline: none;
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.2);
        }

        /* Input yang di-disable (Data Profil Permanen) */
        .disabled-input {
            background-color: #eeeeee;
            color: var(--color-text-medium);
            cursor: not-allowed;
            border: 1px solid #ccc;
        }
        
        /* Actions Button Group */
        .form-actions {
            margin-top: 10px;
            display: flex;
            gap: 15px;
            justify-content: flex-end; /* Posisikan tombol ke kanan */
        }

        .form-actions button,
        .form-actions a {
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            text-decoration: none; /* Untuk tag <a> */
            text-align: center;
        }

        .btn-submit {
            background-color: var(--color-secondary);
            color: var(--color-white);
            border: none;
            flex-grow: 1; 
            max-width: 200px;
        }

        .btn-submit:hover {
            background-color: var(--color-primary);
            transform: translateY(-1px);
        }

        .btn-back {
            background-color: #E0E0E0; /* Light Gray */
            color: var(--color-text-dark);
            border: 1px solid #BDBDBD;
            flex-grow: 0;
            max-width: 150px;
        }

        .btn-back:hover {
            background-color: #CCCCCC;
        }

        /* ================================================= */
        /* RESPONSIVE (Mobile/Tablet) */
        /* ================================================= */

        @media (max-width: 900px) {
            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                padding: 15px 0;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .sidebar-header {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 20px 15px;
                margin-bottom: 10px;
                border-bottom: none;
            }

            .sidebar-header h2 {
                display: none; /* Sembunyikan judul di mobile */
            }
            .sidebar-logo {
                width: 40px;
                height: 40px;
                margin-right: 15px;
            }

            .sidebar-menu {
                flex-direction: row;
                justify-content: center;
                padding: 0 10px;
                overflow-x: auto;
            }

            .menu-item {
                padding: 10px 15px;
                margin-bottom: 0;
                font-size: 0.9rem;
                white-space: nowrap;
                gap: 5px;
            }
            
            .menu-item.logout {
                margin-top: 0;
            }

            .content-area {
                padding: 20px 15px;
                /* Nonaktifkan centering di mobile agar konten tidak terlalu sempit */
                align-items: flex-start; 
                justify-content: center;
            }

            .card {
                padding: 30px 20px;
                margin: 0; /* Hapus margin vertikal di mobile */
            }
        }
        
        @media (max-width: 550px) {
            .card h1 {
                font-size: 1.7rem;
            }
            .form-actions {
                flex-direction: column-reverse; 
                gap: 10px;
            }
            .form-actions button,
            .form-actions a {
                max-width: 100%;
                flex-grow: 1;
            }
        }
    </style>
</head>
<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <!-- Ganti src dengan path logo Anda -->
            <img 
                src="pic/logo1.png" 
                onerror="this.onerror=null;this.src='https://placehold.co/60x60/0D47A1/ffffff?text=SMK'"
                alt="Logo Sekolah" 
                class="sidebar-logo">
            <h2>Ekskul SMKN 1</h2>
        </div>
        <nav class="sidebar-menu">
            <a href="pilihan.php" class="menu-item">🏠 Dashboard</a>
            <!-- Tandai link ini sebagai aktif -->
            <a href="profile_siswa.php" class="menu-item active">👤 Profil</a> 
            <a href="logout.php" class="menu-item logout">🚪 Logout</a>
        </nav>
    </aside>

    <!-- CONTENT AREA -->
    <main class="content-area">
        <div class="profile-container">
            <div class="card">
                <h1>Profil Siswa 🧑‍🎓</h1>
                
                <?php if ($message): ?>
                    <div class="message-box <?= htmlspecialchars($message_type) ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form action="proses_update_siswa.php" method="POST" class="profile-form">

                    <!-- Data yang Tidak Boleh Diubah -->
                    <div class="form-group">
                        <label for="nis">NIS (Nomor Induk Siswa)</label>
                        <input type="text" id="nis" value="<?= htmlspecialchars($nis) ?>" disabled class="disabled-input" title="NIS tidak dapat diubah.">
                    </div>

                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" value="<?= htmlspecialchars($nama) ?>" disabled class="disabled-input" title="Nama tidak dapat diubah. Silakan hubungi admin jika terdapat kesalahan.">
                    </div>

                    <div class="form-group">
                        <label for="kelas">Kelas</label>
                        <input type="text" id="kelas" value="<?= htmlspecialchars($kelas) ?>" disabled class="disabled-input" title="Kelas tidak dapat diubah.">
                    </div>

                    <!-- Data yang Dapat Diubah -->
                    <div class="form-group">
                        <label for="email">Email (Dapat Diubah)</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Masukkan email aktif Anda" required>
                    </div>

                    <div class="form-group">
                        <label for="notelp">No Telp (Dapat Diubah)</label>
                        <input type="tel" id="notelp" name="notelp" value="<?= htmlspecialchars($notelp) ?>" placeholder="Masukkan nomor telepon aktif" required pattern="[0-9]{10,14}" title="Nomor telepon harus berupa angka antara 10 hingga 14 digit.">
                    </div>
                    
                    <div class="form-actions">
                        <a href="pilihan.php" class="btn-back">Kembali ke Dashboard</a>
                        <button type="submit" class="btn-submit">Simpan Perubahan</button>
                    </div>

                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>