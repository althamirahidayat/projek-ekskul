<?php
session_start();
// PERHATIAN: Pastikan file koneksi.php tersedia di lingkungan ini
include 'koneksi.php';

// Hapus alert, ganti dengan custom message box jika akses ditolak
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    // Pengganti alert()
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('message-box-text').innerText = '❌ Silakan login sebagai admin ekskul!';
                document.getElementById('message-box').style.display = 'flex';
                document.getElementById('message-box-ok').onclick = function() {
                    window.location.href='login_admin.php';
                };
            });
          </script>";
    exit;
}

$admin_nama   = $_SESSION['admin_nama'] ?? 'Admin';
$admin_ekskul = $_SESSION['admin_ekskul'] ?? 'Tidak Diketahui';

// Fungsi untuk menghitung data peserta
function hitung($conn, $ekskul, $status)
{
    // Menggunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM table_pendaftaran 
                            WHERE nama_ekskul = ? AND status = ?");
    if (!$stmt) {
        // Handle error koneksi/prepare
        error_log("Failed to prepare statement: " . $conn->error);
        return 0;
    }
    $stmt->bind_param("ss", $ekskul, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    return $data;
}

// ====== HITUNG DATA PESERTA ======
$menunggu = hitung($conn, $admin_ekskul, 'menunggu');
$diterima = hitung($conn, $admin_ekskul, 'diterima');
$ditolak  = hitung($conn, $admin_ekskul, 'ditolak');


// AMBIL DATA EKSUL
$ex = null;
$stmt = $conn->prepare("SELECT * FROM table_ekskul WHERE nama_ekskul = ? LIMIT 1");
$stmt->bind_param("s", $admin_ekskul);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $ex = $result->fetch_assoc();
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - <?= htmlspecialchars($admin_ekskul) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* =======================================================
   ADMIN DASHBOARD — MODERN & RESPONSIVE UI (REVISI SIDEBAR)
======================================================= */

/* Variable Colors */
:root {
    --sidebar-bg: #1c3d5a;     /* Dark Navy / Deep Teal */
    --primary: #2d66ff;        /* Bright Blue (Untuk Aksen/Link) */
    --primary-light: #4c87ff;
    --primary-hover: #1c4ad6;
    --bg-page: #f4f7fb;        
    --bg-card: #ffffff;        
    --text-dark: #1f1f25;
    --text-medium: #6b7280;
    --shadow-light: 0 4px 15px rgba(0,0,0,0.08);
    --radius: 12px;

    /* Status Colors */
    --color-waiting: #ffc107;      /* Kuning */
    --color-success: #28a745;      /* Hijau */
    --color-danger: #dc3545;       /* Merah */
}

/* Base and Layout */
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: var(--bg-page);
    color: var(--text-dark);
}

.layout {
    display: flex;
    min-height: 100vh;
}

/* ----------------- SIDEBAR ----------------- */
.sidebar {
    width: 260px;
    background-color: var(--sidebar-bg); 
    color: white;
    height: 100vh;
    position: fixed;
    display: flex;
    flex-direction: column;
    padding: 20px 0;
    box-shadow: 4px 0 15px rgba(0,0,0,0.15);
}

.sidebar-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 0 20px;
}

.sidebar-logo {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: white;
    object-fit: cover;
    border: 3px solid #f0f0f0;
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
}

.sidebar-header h2 {
    margin: 15px 0 5px;
    font-size: 1.5rem;
    font-weight: 800;
}
.sidebar-header p {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 500;
    opacity: 0.9;
}

.sidebar-menu {
    display: flex;
    flex-direction: column;
}

.menu-item {
    padding: 14px 25px;
    text-decoration: none;
    color: rgba(255,255,255,0.9);
    border-left: 5px solid transparent;
    transition: all 0.2s;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.menu-item:hover {
    background: rgba(255,255,255,0.15);
    color: white;
}

.menu-item.active {
    background: rgba(255,255,255,0.1); 
    border-left: 5px solid #FFD700; 
    color: white;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
}

/* LOGOUT DIPINDAHKAN KE ATAS MENU */
.menu-item.logout-item {
    /* Memberikan sedikit jarak dari menu di atasnya */
    margin-top: 15px; 
    background: #c0392b; /* Dark red for emphasis */
    color: white;
    /* Border kiri diganti warna putih agar kontras */
    border-left: 5px solid #ff4d4d;
}

.menu-item.logout-item:hover {
    background: #e74c3c; /* Lighter red on hover */
    border-left: 5px solid white; /* White border on hover */
}


/* ----------------- CONTENT AREA ----------------- */
.content-area {
    margin-left: 260px; /* Jarak dari sidebar */
    padding: 40px;
    width: calc(100% - 260px);
}

/* GREETING BOX */
.greet-box {
    background: var(--bg-card); /* Warna Putih */
    color: var(--text-dark);
    padding: 30px;
    border-radius: 15px;
    box-shadow: var(--shadow-light);
    margin-bottom: 30px;
    border-left: 8px solid var(--primary); /* Aksen warna primer */
}

.greet-title {
    margin: 0;
    font-size: 2.2rem;
    font-weight: 900;
}

.name {
    color: var(--primary); /* Primary blue for emphasis */
}

.greet-sub {
    margin-top: 5px;
    font-size: 1.05rem;
    font-weight: 500;
    color: var(--text-medium);
}

/* STATS ROW */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.stat-card {
    background: var(--bg-card);
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    box-shadow: var(--shadow-light);
    border-bottom: 5px solid transparent; 
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.stat-title {
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 12px;
}

.stat-number {
    font-size: 3rem;
    font-weight: 900;
    line-height: 1;
    margin-top: 10px;
}

/* Specific Card Styles */
.stat-card.waiting {
    border-color: var(--color-waiting);
}
.stat-card.waiting .stat-number { color: var(--color-waiting); }

.stat-card.success {
    border-color: var(--color-success);
}
.stat-card.success .stat-number { color: var(--color-success); }

.stat-card.danger {
    border-color: var(--color-danger);
}
.stat-card.danger .stat-number { color: var(--color-danger); }

/* INFO CARD EKSKUL */
.info-card {
    background: var(--bg-card);
    padding: 30px;
    border-radius: 15px;
    box-shadow: var(--shadow-light);
    margin-top: 30px;
}

.card-title {
    margin: 0 0 20px;
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--text-dark);
}

.info-flex {
    display: flex;
    gap: 30px;
    align-items: flex-start;
}

.info-image {
    flex-shrink: 0;
}

.info-image img {
    width: 250px;
    height: 180px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.no-img {
    width: 250px;
    height: 180px;
    background: #f0f0f0;
    border-radius: 12px;
    display:flex;
    justify-content:center;
    align-items:center;
    color:var(--text-medium);
    font-size: 0.9rem;
    font-style: italic;
    border: 1px dashed #ccc;
}

.info-desc {
    flex-grow: 1;
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--text-medium);
}

/* Custom Message Box (Ganti alert) */
.message-box {
    display: none; 
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
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

/* Responsiveness */
@media (max-width: 1024px) {
    .content-area {
        margin-left: 240px;
        padding: 30px 20px;
        width: calc(100% - 240px);
        box-sizing: border-box;
    }
    .stats-row {
        gap: 15px;
    }
}

@media (max-width: 768px) {
    .layout {
        flex-direction: column;
    }
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding: 10px 0;
        flex-direction: row;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        z-index: 10;
        box-sizing: border-box;
    }
    .sidebar-header {
        display: none; /* Sembunyikan header di mobile */
    }
    .sidebar-menu {
        flex-direction: row;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        width: 100%;
        margin-top: 0;
        justify-content: flex-start;
        padding: 0 10px;
        gap: 6px;
    }
    .menu-item {
        padding: 9px 13px;
        border-left: none;
        border-bottom: 3px solid transparent;
        flex-shrink: 0;
        font-size: 0.85rem;
        border-radius: 6px;
    }
    .menu-item.active {
        border-bottom: 3px solid #FFD700;
        border-left: none;
    }
    
    /* Logout item di mobile tetap dapat diakses */
    .menu-item.logout-item {
        display: inline-flex;
        margin-top: 0;
        margin-left: auto;
        border-bottom: none;
        background: #c0392b;
        color: white;
    }
    
    .content-area {
        margin-left: 0;
        width: 100%;
        padding: 20px 15px;
        box-sizing: border-box;
    }

    .stats-row {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
    }

    .greet-title {
        font-size: 1.8rem;
    }

    .info-flex {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    .info-image {
        width: 100%;
        display: flex;
        justify-content: center;
    }
    .info-image img, .no-img {
        width: 100%;
        max-width: 300px;
        height: auto;
    }
    .info-desc {
        text-align: left;
    }
}

@media (max-width: 480px) {
    .content-area {
        padding: 16px 12px;
    }
    .greet-box {
        padding: 20px 15px;
        border-radius: 12px;
    }
    .greet-title {
        font-size: 1.5rem;
    }
    .greet-sub {
        font-size: 0.95rem;
    }
    .stats-row {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .stat-card {
        padding: 18px 14px;
        border-radius: 12px;
    }
    .stat-number {
        font-size: 2.5rem;
    }
    .info-card {
        padding: 20px 15px;
        border-radius: 12px;
    }
    .card-title {
        font-size: 1.4rem;
    }
    .message-content {
        max-width: 90%;
        padding: 20px 15px;
    }
}

@media (max-width: 375px) {
    .sidebar-menu {
        padding: 0 6px;
        gap: 4px;
    }
    .menu-item {
        padding: 8px 10px;
        font-size: 0.78rem;
    }
    .content-area {
        padding: 12px 8px;
    }
    .greet-box {
        padding: 16px 12px;
    }
    .greet-title {
        font-size: 1.3rem;
    }
    .stat-number {
        font-size: 2.2rem;
    }
    .info-card {
        padding: 16px 12px;
    }
    .card-title {
        font-size: 1.25rem;
    }
}
</style>

</head>

<body>

<!-- Custom Message Box -->
<div id="message-box" class="message-box">
    <div class="message-content">
        <p id="message-box-text"></p>
        <button id="message-box-ok">OK</button>
    </div>
</div>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        
        <!-- WRAPPER ATAS (Header & Menu) -->
        <div>
            <div class="sidebar-header">
                <!-- Fallback image for logo -->
                <img src="pic/logo1.png" onerror="this.onerror=null;this.src='https://placehold.co/65x65/1c3d5a/ffffff?text=E'" class="sidebar-logo">
                <h2>Admin Ekskul</h2>
                <p><?= htmlspecialchars($admin_ekskul) ?></p>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard_admin.php" class="menu-item active">
                    <span class="icon">🏠</span> Dashboard
                </a>
                <a href="kelola_ekskul_admin.php" class="menu-item">
                    <span class="icon">📚</span> Kelola Ekskul
                </a>
                
                <!-- LOGOUT DIPINDAHKAN KE SINI -->
                <a href="logout.php" class="menu-item logout-item">
                    <span class="icon">🚪</span> Logout
                </a>
            </nav>
        </div>
        
        <!-- Logut bawah yang lama sudah dihapus -->
    </aside>


    <!-- CONTENT -->
    <main class="content-area">

        <!-- GREETING -->
        <div class="greet-box">
            <h1 class="greet-title">👋 Selamat Datang, <span class="name"><?= htmlspecialchars($admin_nama) ?></span>!</h1>
            <p class="greet-sub">Ini adalah dashboard pengelolaan untuk ekskul <b><?= htmlspecialchars($admin_ekskul) ?></b>.</p>
        </div>


        <!-- 3 CARD STATISTIK -->
        <div class="stats-row">

            <!-- Card Menunggu (Kuning) -->
            <div onclick="window.location.href='lihat_data.php?filter=menunggu'" class="stat-card waiting">
                <div class="stat-title">MENUNGGU KONFIRMASI</div>
                <div class="stat-number"><?= $menunggu ?></div>
                <small style="color:var(--text-medium); display:block; margin-top:5px;">Klik untuk lihat detail</small>
            </div>

            <!-- Card Diterima (Hijau) -->
            <div onclick="window.location.href='lihat_data.php?filter=diterima'" class="stat-card success">
                <div class="stat-title">SUDAH DITERIMA</div>
                <div class="stat-number"><?= $diterima ?></div>
                <small style="color:var(--text-medium); display:block; margin-top:5px;">Peserta yang disetujui</small>
            </div>

            <!-- Card Ditolak (Merah) -->
            <div onclick="window.location.href='lihat_data.php?filter=ditolak'" class="stat-card danger">
                <div class="stat-title">DITOLAK</div>
                <div class="stat-number"><?= $ditolak ?></div>
                <small style="color:var(--text-medium); display:block; margin-top:5px;">Peserta yang ditolak</small>
            </div>

        </div>


        <!-- INFO EKSUL -->
        <div class="info-card">
            <h2 class="card-title">📝 Informasi Ekskul <?= htmlspecialchars($admin_ekskul) ?></h2>

            <div class="info-flex">
                <div class="info-image">
                    <?php 
                    $gambar_path = "uploads/" . ($ex['gambar'] ?? '');
                    if (!empty($ex['gambar']) && file_exists($gambar_path)): 
                    ?>
                        <!-- Fallback menggunakan placeholder jika gambar tidak ditemukan -->
                        <img src="<?= htmlspecialchars($gambar_path) ?>" onerror="this.onerror=null;this.src='https://placehold.co/250x180/e5e7eb/4b5563?text=Ekskul%20Image%20Not%20Found'">
                    <?php else: ?>
                        <div class="no-img">Tidak ada gambar ekskul</div>
                    <?php endif; ?>
                </div>

                <div class="info-desc">
                    <p style="font-weight: 600; color: var(--text-dark);">Deskripsi Singkat:</p>
                    <?= nl2br(htmlspecialchars($ex['deskripsi'] ?? 'Deskripsi ekskul ini belum ditambahkan. Silakan kelola ekskul untuk menambahkannya.')) ?>
                    <p style="margin-top: 15px;">
                        <a href="kelola_ekskul_admin.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                            → Edit Detail Ekskul
                        </a>
                    </p>
                </div>
            </div>
        </div>

    </main>

</div>

</body>
</html>