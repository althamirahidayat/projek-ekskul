<?php
session_start();
// Pastikan file koneksi.php tersedia di lingkungan ini
include 'koneksi.php';

// Handler untuk pesan notifikasi
$message = null;
$message_type = null;

// Validasi superadmin (menggunakan custom message box jika akses ditolak)
if (!isset($_SESSION['superadmin_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('message-box-text').innerText = '❌ Akses ditolak! Anda harus login sebagai Superadmin.';
                document.getElementById('message-box').style.display = 'flex';
                document.getElementById('message-box-ok').onclick = function() {
                    window.location.href='login_superadmin.php';
                };
            });
          </script>";
    exit;
}

// AMBIL ID ADMIN
$id = (int)($_GET['id'] ?? 0);

// DATA ADMIN SAAT INI
$stmt = $conn->prepare("SELECT id_admin, username, role, id_ekskul_diampu FROM table_admin WHERE id_admin=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    // Tampilkan pesan error jika admin tidak ditemukan
    $message = '❌ Admin tidak ditemukan!';
    $message_type = 'error';
    // Menampilkan pesan error segera
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('message-box-text').innerText = '❌ Admin tidak ditemukan!';
                document.getElementById('message-box').style.display = 'flex';
                document.getElementById('message-box-ok').onclick = function() {
                    window.location.href='kelola_admin.php'; // Redirect setelah OK
                };
            });
          </script>";
    exit;
}

// LIST EKSKUL
// PERHATIAN: Pastikan koneksi ($conn) dan query berjalan lancar
$ekskul_list = $conn->query("SELECT id_ekskul, nama_ekskul FROM table_ekskul ORDER BY nama_ekskul ASC");


// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $role = trim($_POST['role']);
    $password = $_POST['password'] ?? '';
    $id_ekskul_diampu = (int)($_POST['id_ekskul_diampu'] ?? 0);

    $query = "UPDATE table_admin SET username=?, role=?, id_ekskul_diampu=?";
    $params = "ssi";
    $values = [$username, $role, $id_ekskul_diampu];

    if (!empty($password)) {
        // !!! PERHATIAN PENTING !!!
        // Kode ini menyimpan password dalam teks biasa (plain text) sesuai permintaan.
        // INI SANGAT TIDAK AMAN DAN TIDAK DISARANKAN!
        // Untuk keamanan, kembalikan ke: $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query .= ", password=?";
        $params .= "s";
        $values[] = $password; 
    }

    $query .= " WHERE id_admin=?";
    $params .= "i";
    $values[] = $id;

    $upd = $conn->prepare($query);
    // Menggunakan call_user_func_array untuk bind_param
    // Solusi yang lebih aman adalah menggunakan reflection atau cara lain yang menghindari dynamic parameter passing, 
    // tetapi untuk kompatibilitas, kita gunakan pendekatan ini (asumsi environment mendukungnya).
    $upd->bind_param($params, ...$values);
    
    if ($upd->execute()) {
        $message = '✅ Data admin berhasil diperbarui!';
        $message_type = 'success';
        $upd->close(); // Tutup statement setelah sukses
        // Redirect setelah sukses
        header("Location: kelola_admin.php");
        exit;
    } else {
        $message = '❌ Gagal memperbarui data admin: ' . $upd->error;
        $message_type = 'error';
        $upd->close(); // Tutup statement setelah gagal
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Admin: <?= htmlspecialchars($data['username']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ===========================================================
   CSS GABUNGAN: TEMA MODERN BLUE DASHBOARD
   (Disesuaikan untuk form Edit)
=========================================================== */

/* Variabel Warna */
:root {
    --bg-page: #eaf0ff;        /* Light Blue Background */
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
    max-width: 650px;
    margin-top: 50px;
    position: relative;
    padding-bottom: 20px;
}

.card {
    background: var(--bg-card);
    padding: 40px;
    border-radius: 20px;
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
    background-color: var(--bg-card);
}

input:focus,
select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.2);
    outline: none;
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
    background: #f7f9fc;
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
    animation: fadeIn 0.3s;
}

.message-content p {
    font-size: 18px;
    margin-bottom: 20px;
    font-weight: 600;
    color: var(--text-dark);
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
        margin-top: 70px;
    }
    .card {
        padding: 25px;
        border-radius: 15px;
    }
    .card h2 {
        font-size: 24px;
    }
    .back-btn {
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .btn-submit {
        font-size: 16px;
        padding: 12px 20px;
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

        <h2>Edit Admin: <?= htmlspecialchars($data['username']) ?></h2>

        <form method="POST" action="">

            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($data['username']) ?>" required>

            <label for="role">Role Pengguna</label>
            <select id="role" name="role" required>
                <option value="superadmin" <?= $data['role'] == 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                <option value="admin" <?= $data['role'] == 'admin' ? 'selected' : '' ?>>Admin Ekskul</option>
            </select>

            <label for="id_ekskul_diampu">Ekskul yang Diampu</label>
            <select id="id_ekskul_diampu" name="id_ekskul_diampu">
                <option value="0" <?= $data['id_ekskul_diampu'] == 0 ? 'selected' : '' ?>>-- Tidak Menauingi Ekskul --</option>
                <?php if ($ekskul_list && $ekskul_list->num_rows > 0): ?>
                    <?php $ekskul_list->data_seek(0); // Reset pointer ?>
                    <?php while ($ekskul = $ekskul_list->fetch_assoc()): ?>
                        <option value="<?= $ekskul['id_ekskul'] ?>" 
                            <?= $data['id_ekskul_diampu'] == $ekskul['id_ekskul'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ekskul['nama_ekskul']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <label for="password">Password Baru</label>
            <input type="password" id="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
            <small style="color: red; font-weight: 600; margin-top: -10px;">* PERINGATAN: Password akan disimpan dalam teks biasa (plain text)!</small>


            <button type="submit" class="btn-submit">
                💾 Simpan Perubahan Data
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
                // Jika sukses, redirect ke halaman kelola
                <?php if ($message_type === 'success'): ?>
                    window.location.href='kelola_admin.php';
                <?php endif; ?>
            };
        <?php endif; ?>
    });
</script>

</body>
</html>