<?php
session_start();
// Pastikan file koneksi.php tersedia di lingkungan ini
include 'koneksi.php';

// Validasi superadmin
if (!isset($_SESSION['superadmin_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
    // Penggunaan JavaScript kustom untuk menggantikan alert()
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('message-box-text').innerText = '❌ Silakan login sebagai superadmin!';
                document.getElementById('message-box').style.display = 'flex';
                document.getElementById('message-box-ok').onclick = function() {
                    window.location.href='login_superadmin.php';
                };
            });
          </script>";
    // Menambahkan fallback HTML jika JS gagal dimuat
    echo "
        <div style='text-align: center; padding: 50px;'>
            <p>❌ Silakan login sebagai superadmin!</p>
            <a href='login_superadmin.php'>Klik di sini untuk login</a>
        </div>
    ";
    exit;
}

// Handler untuk pesan notifikasi
$message = null;
$message_type = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_admin']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $notelp   = trim($_POST['notelp']);
    // === REVISI: Mengambil password tanpa hashing ===
    $password = trim($_POST['password']); 
    $ekskul   = trim($_POST['ekskul_dinaungi']);

    // Pastikan password tidak kosong
    if (empty($password)) {
        $message = 'Password tidak boleh kosong.';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO table_admin 
            (nama_admin, username, email, notelp, password, role, ekskul_dinaungi, created_at) 
            VALUES (?, ?, ?, ?, ?, 'admin', ?, NOW())
        ");
        // Password disisipkan sebagai string biasa
        $stmt->bind_param("ssssss", $nama, $username, $email, $notelp, $password, $ekskul);

        if ($stmt->execute()) {
            $message = '✅ Admin berhasil ditambahkan!';
            $message_type = 'success';
            // Redirect setelah sukses (agar tidak terjadi resubmission)
            header("Location: kelola_admin.php");
            exit;
        } else {
            // Cek jika error karena duplikasi (misalnya username)
             if ($conn->errno === 1062) {
                $message = '❌ Gagal: Username atau Email sudah terdaftar.';
            } else {
                $message = '❌ Gagal menambah admin. Coba lagi. Error: ' . $conn->error;
            }
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Admin Ekskul</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ===========================================================
   CSS GABUNGAN: TEMA MODERN BLUE DASHBOARD
=========================================================== */

/* Variabel Warna (Konsisten dengan Dashboard) */
:root {
    --bg-page: #eaf0ff;        /* Light Blue Background */
    --bg-navbar: #1f2a44;      /* Navy */
    --bg-card: #ffffff;        
    --primary: #2d66ff;        /* Bright Blue */
    --primary-hover: #1c4ad6;
    --primary-rgb: 45, 102, 255;
    --text-dark: #1f1f25;
    --text-medium: #4a4a50;
    --border-color: #d6d7db;
    --shadow-card: 0 8px 30px rgba(0,0,0,0.1);
    --radius: 12px;
    font-family: 'Inter', sans-serif;
}

/* --- BASE & LAYOUT --- */
body {
    margin: 0;
    padding: 0;
    background-color: var(--bg-page);
    color: var(--text-dark);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

/* --- PAGE WRAPPER & CARD --- */
.page-wrapper {
    width: 100%;
    max-width: 650px; /* Lebar optimal untuk form */
    margin-top: 50px; /* Untuk memberi ruang pada tombol Kembali */
    position: relative;
    padding-bottom: 20px;
}

.card {
    background: var(--bg-card);
    padding: 40px;
    border-radius: 20px; /* Lebih rounded */
    box-shadow: var(--shadow-card);
    border: 1px solid #e3e6f0;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.card h2 {
    font-size: 28px;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 30px;
    border-bottom: 3px solid var(--primary);
    display: inline-block;
    padding-bottom: 8px;
}

/* --- FORM STYLING --- */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

label {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 5px;
    display: block;
    font-size: 14px;
}

input[type="text"],
input[type="email"],
input[type="password"],
select {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 10px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 16px;
    color: var(--text-dark);
    transition: all 0.3s;
    background-color: var(--bg-card); /* Mengubah ini agar fokus terlihat lebih baik */
}

input:focus,
select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.2);
    outline: none;
    /* background-color: var(--bg-card); Dihapus karena sudah default */
}

/* --- BUTTONS --- */
.back-btn {
    position: absolute;
    top: -50px;
    left: 0;
    background: var(--bg-card);
    color: var(--text-medium);
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.back-btn:hover {
    background: var(--bg-secondary);
    color: var(--primary);
    transform: translateY(-2px);
}

.btn-submit {
    margin-top: 20px;
    padding: 15px 25px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 6px 15px rgba(var(--primary-rgb), 0.4);
}

.btn-submit:hover {
    background: var(--primary-hover);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.5);
}

/* --- MESSAGE BOX (Custom Alert) --- */
.message-box {
    display: none; /* Default hidden */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.message-content {
    background: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    max-width: 350px;
    width: 90%;
    box-sizing: border-box;
    animation: fadeIn 0.3s;
}

.message-content p {
    font-size: 18px;
    margin-bottom: 20px;
    font-weight: 600;
    color: var(--text-dark);
    word-break: break-word;
}

.message-content button {
    padding: 10px 25px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    transition: background-color 0.3s;
}

.message-content button:hover {
    background: var(--primary-hover);
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

/* --- RESPONSIVENESS --- */
@media (max-width: 700px) {
    .page-wrapper {
        margin-top: 20px;
    }
    .card {
        padding: 25px 20px;
        border-radius: 15px;
    }
    .card h2 {
        font-size: 22px;
        margin-bottom: 20px;
    }
    .back-btn {
        position: static;
        display: inline-block;
        margin-bottom: 15px;
        transform: none;
        width: 100%;
        text-align: center;
        box-sizing: border-box;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .btn-submit {
        font-size: 16px;
        padding: 12px 20px;
    }
}

@media (max-width: 480px) {
    body {
        padding: 15px 10px;
    }
    .card {
        padding: 20px 15px;
        border-radius: 12px;
    }
    .card h2 {
        font-size: 19px;
        margin-bottom: 16px;
    }
    input[type="text"],
    input[type="email"],
    input[type="password"],
    select {
        padding: 10px 12px;
        font-size: 14px;
    }
    .btn-submit {
        padding: 11px 16px;
        font-size: 15px;
    }
    .message-content {
        padding: 20px 15px;
    }
}

@media (max-width: 375px) {
    body {
        padding: 10px 6px;
    }
    .card {
        padding: 16px 10px;
    }
    .card h2 {
        font-size: 17px;
    }
}
</style>
</head>
<body>

<!-- Custom Message Box (Menggantikan alert()) -->
<div id="message-box" class="message-box">
    <div class="message-content">
        <p id="message-box-text"></p>
        <button id="message-box-ok">OK</button>
    </div>
</div>

<div class="page-wrapper">

    <!-- Tombol Kembali Dibuat Floating -->
    <a href="kelola_admin.php" class="back-btn">← Kembali ke Kelola Admin</a>

    <div class="card">
        <h2>Registrasi Admin Ekskul Baru</h2>

        <form action="" method="POST">
            
            <!-- Group 1 -->
            <label for="nama_admin">Nama Lengkap Admin</label>
            <input type="text" id="nama_admin" name="nama_admin" placeholder="Contoh: Rendi Hidayat" required>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Digunakan untuk Login" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Contoh: admin.rendi@smkn1.sch.id" required>
            
            <!-- Group 2 -->
            <label for="notelp">Nomor Telepon</label>
            <input type="text" id="notelp" name="notelp" placeholder="Contoh: 0812xxxxxx" required>

            <label for="password">Password (Password Default)</label>
            <input type="text" id="password" name="password" placeholder="Masukkan password" required> 

            <label for="ekskul_dinaungi">Pilih Ekskul Yang Dinaungi</label>
            <select id="ekskul_dinaungi" name="ekskul_dinaungi" required>
                <?php
                // Query untuk mengambil daftar ekskul dari database
                $ex = $conn->query("SELECT nama_ekskul FROM table_ekskul ORDER BY nama_ekskul ASC");
                if ($ex && $ex->num_rows > 0) {
                    while ($row = $ex->fetch_assoc()) {
                        echo "<option value='".htmlspecialchars($row['nama_ekskul'])."'>"
                             .htmlspecialchars($row['nama_ekskul'])."</option>";
                    }
                } else {
                    echo "<option value='' disabled>-- Tidak ada Ekskul terdaftar --</option>";
                }
                ?>
            </select>

            <button type="submit" class="btn-submit">
                💾 Simpan Admin Baru
            </button>

        </form>
    </div>

</div>

<script>
    // Menampilkan custom message box jika ada pesan dari PHP
    document.addEventListener('DOMContentLoaded', function() {
        const messageBox = document.getElementById('message-box');
        const messageText = document.getElementById('message-box-text');
        const messageOkBtn = document.getElementById('message-box-ok');

        <?php if ($message): ?>
            messageText.innerText = "<?= $message_type === 'success' ? '✅ ' : '❌ ' ?> <?= htmlspecialchars($message) ?>";
            messageBox.style.display = 'flex';
            
            messageOkBtn.onclick = function() {
                messageBox.style.display = 'none';
            };
        <?php endif; ?>
    });
</script>

</body>
</html>