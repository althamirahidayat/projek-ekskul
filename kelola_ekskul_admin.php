<?php
session_start();
// PERHATIAN: Pastikan file koneksi.php tersedia di lingkungan ini
include 'koneksi.php';

// Cek login admin ekskul
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

$admin_nama   = $_SESSION['admin_nama'];
$admin_ekskul = $_SESSION['admin_ekskul'];

// Ambil ekskul milik admin saja
$stmt = $conn->prepare("SELECT * FROM table_ekskul WHERE nama_ekskul = ? LIMIT 1");
$stmt->bind_param("s", $admin_ekskul);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Ekskul Saya - <?= htmlspecialchars($admin_ekskul) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* =======================================================
   KELOLA EKSKUL ADMIN — MODERN UI
======================================================= */

/* Variable Colors */
:root {
    --sidebar-bg: #1c3d5a;     /* Dark Navy */
    --primary: #2d66ff;        /* Bright Blue */
    --primary-hover: #1c4ad6;
    --bg-page: #f4f7fb;        
    --bg-card: #ffffff;        
    --text-dark: #1f1f25;
    --text-medium: #6b7280;
    --shadow-light: 0 4px 15px rgba(0,0,0,0.08);
    --radius: 12px;
    --color-waiting: #ffc107; /* Kuning */
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
    object-fit: cover;
    background: white;
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

.sidebar-nav {
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
    border-left: 5px solid #FFD700; /* Gold accent */
    color: white;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
}

/* LOGOUT DIPINDAHKAN KE SINI DI NAV */
.menu-item.logout-item {
    margin-top: 15px; 
    background: #c0392b; /* Dark red for emphasis */
    color: white;
    border-left: 5px solid #ff4d4d; /* Light red border */
}

.menu-item.logout-item:hover {
    background: #e74c3c; /* Lighter red on hover */
    border-left: 5px solid white; /* White border on hover */
}

/* Hapus CSS untuk tombol logout lama */
.logout-btn {
    display: none;
}


/* ----------------- CONTENT AREA ----------------- */
.content-area {
    margin-left: 260px; 
    padding: 40px;
    width: calc(100% - 260px);
    box-sizing: border-box;
}

.header-section {
    margin-bottom: 30px;
}

.header-section h1 {
    font-size: 2.2rem;
    font-weight: 900;
    margin: 0;
}

.header-section .sub {
    font-size: 1.05rem;
    color: var(--text-medium);
    margin-top: 5px;
    font-weight: 500;
}

/* CARD Container */
.card {
    background: var(--bg-card);
    padding: 30px;
    border-radius: 15px;
    box-shadow: var(--shadow-light);
}

/* Styled Table for Info */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 2fr; /* Label (1/3) vs Value (2/3) */
    gap: 20px;
    margin-bottom: 30px;
}

.info-grid .label {
    font-weight: 700;
    color: var(--text-dark);
    font-size: 1rem;
    padding-bottom: 10px;
    border-bottom: 1px dashed #f0f0f0;
}

.info-grid .value {
    color: var(--text-medium);
    font-size: 1rem;
    padding-bottom: 10px;
    border-bottom: 1px dashed #f0f0f0;
}

/* Deskripsi Khusus */
.info-grid .value.description {
    white-space: pre-wrap;
    line-height: 1.6;
}

/* Image Thumbnail */
.thumb-img-container {
    padding: 10px 0;
}

.thumb-img {
    max-width: 300px;
    height: 180px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.no-data {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-medium);
    font-style: italic;
    font-size: 1.1rem;
}

/* Action Button */
.action-area {
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
    text-align: right;
}

.btn-edit {
    padding: 12px 25px;
    background: var(--primary);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 700;
    transition: background-color 0.2s, transform 0.1s;
    box-shadow: 0 4px 10px rgba(45, 102, 255, 0.3);
}

.btn-edit:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
}

.btn-link {
    color: var(--primary);
    text-decoration: underline;
    font-weight: 600;
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
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        z-index: 10;
        box-sizing: border-box;
    }
    .sidebar-header {
        display: none; 
    }
    .sidebar-nav {
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
    
    .info-grid {
        grid-template-columns: 1fr; /* Stacked columns on mobile */
        gap: 12px;
    }
    .info-grid .label, .info-grid .value {
        border-bottom: none;
        padding: 4px 0;
    }
    .info-grid > div {
        border-bottom: 1px dashed #f0f0f0;
        padding-bottom: 10px;
    }
    .info-grid .value.description {
        padding-top: 0;
    }
    .info-grid .label {
        margin-top: 8px;
        font-size: 0.9rem;
    }
    .action-area {
        text-align: center;
    }
    .thumb-img {
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .content-area {
        padding: 16px 12px;
    }
    .card {
        padding: 20px 15px;
        border-radius: 12px;
    }
    .header-section h1 {
        font-size: 1.6rem;
    }
    .header-section .sub {
        font-size: 0.95rem;
    }
    .btn-edit {
        width: 100%;
        display: block;
        text-align: center;
        box-sizing: border-box;
    }
    .thumb-img {
        width: 100%;
        height: auto;
        max-height: 200px;
    }
    .message-content {
        max-width: 90%;
        padding: 20px 15px;
    }
}

@media (max-width: 375px) {
    .sidebar-nav {
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
    .card {
        padding: 15px 10px;
        border-radius: 10px;
    }
    .header-section h1 {
        font-size: 1.35rem;
    }
    .info-grid .label, .info-grid .value {
        font-size: 0.88rem;
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

            <nav class="sidebar-nav">
                <a href="dashboard_admin.php" class="menu-item">🏠 Dashboard</a>
                <a href="kelola_ekskul_admin.php" class="menu-item active">📚 Kelola Ekskul</a>
                
                <!-- LOGOUT DIPINDAHKAN KE SINI -->
                <a href="logout.php" class="menu-item logout-item">🚪 Logout</a>
                
                <!-- "Data Pendaftar" telah dihilangkan dari sini -->
            </nav>
        </div>

        <!-- LOGOUT PALING BAWAH (Dihilangkan, diganti dengan display: none di CSS) -->
        <a href="logout.php" class="logout-btn">🚪 Logout</a>

    </aside>


    <!-- CONTENT -->
    <main class="content-area">

        <div class="header-section">
            <h1>Kelola Ekskul: <?= htmlspecialchars($admin_ekskul) ?></h1>
            <p class="sub">Admin Penanggung Jawab: <span style="font-weight: 600;"><?= htmlspecialchars($admin_nama); ?></span></p>
        </div>

        <div class="card">

            <?php if ($data): ?>
                
                <div class="info-grid">

                    <!-- Nama Ekskul -->
                    <div class="label">Nama Ekskul</div>
                    <div class="value" style="font-weight: 700; color: var(--primary);"><?= htmlspecialchars($data['nama_ekskul']) ?></div>
                    
                    <!-- Deskripsi -->
                    <div class="label">Deskripsi</div>
                    <div class="value description">
                        <?= nl2br(htmlspecialchars($data['deskripsi'])) ?>
                    </div>
                    
                    <!-- Link WA Perwakilan (link_grup_wa di DB digunakan untuk wa.me) -->
                    <div class="label">Kontak Perwakilan (WA Personal)</div>
                    <div class="value">
                        <?php if (!empty($data['link_grup_wa'])): ?>
                            <a href="<?= htmlspecialchars($data['link_grup_wa']) ?>" target="_blank" class="btn-link">📞 Chat WhatsApp</a>
                        <?php else: ?>
                            <i style="color:var(--color-waiting); font-weight: 500;">Belum ada kontak WA perwakilan.</i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- NEW: Link Grup WA Ekskul (menggunakan kolom link_lainnya) -->
                    <div class="label">Link Grup WA Ekskul</div>
                    <div class="value">
                        <?php if (!empty($data['link_lainnya'])): ?>
                            <a href="<?= htmlspecialchars($data['link_lainnya']) ?>" target="_blank" class="btn-link">🔗 Gabung Grup WhatsApp</a>
                        <?php else: ?>
                            <i style="color:var(--color-waiting); font-weight: 500;">Belum ada link grup WhatsApp.</i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- NEW: Link Instagram -->
                    <div class="label">Link Instagram</div>
                    <div class="value">
                        <?php if (!empty($data['link_instagram'])): ?>
                            <a href="<?= htmlspecialchars($data['link_instagram']) ?>" target="_blank" class="btn-link">📸 Kunjungi Instagram</a>
                        <?php else: ?>
                            <i style="color:var(--color-waiting); font-weight: 500;">Belum ada link Instagram.</i>
                        <?php endif; ?>
                    </div>

                    <!-- NEW: Link Twitter/X -->
                    <div class="label">Link Twitter/X</div>
                    <div class="value">
                        <?php if (!empty($data['link_twitter'])): ?>
                            <a href="<?= htmlspecialchars($data['link_twitter']) ?>" target="_blank" class="btn-link">🐦 Kunjungi Twitter/X</a>
                        <?php else: ?>
                            <i style="color:var(--color-waiting); font-weight: 500;">Belum ada link Twitter/X.</i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Link Form -->
                    <div class="label">Link Formulir Pendaftaran</div>
                    <div class="value">
                        <?php if (!empty($data['link_gform'])): ?>
                            <a href="<?= htmlspecialchars($data['link_gform']) ?>" target="_blank" class="btn-link">🌐 Buka Formulir (Google Form)</a>
                        <?php else: ?>
                            <i style="color:var(--color-waiting); font-weight: 500;">Belum ada link formulir!</i>
                        <?php endif; ?>
                    </div>

                    <!-- Gambar -->
                    <div class="label">Gambar Ekskul</div>
                    <div class="value thumb-img-container">
                        <?php 
                        $gambar_path = "uploads/" . ($data['gambar'] ?? '');
                        if (!empty($data['gambar']) && file_exists($gambar_path)): 
                        ?>
                            <img src="<?= htmlspecialchars($gambar_path) ?>" class="thumb-img" alt="Gambar Ekskul: <?= htmlspecialchars($data['nama_ekskul']) ?>" onerror="this.onerror=null;this.src='https://placehold.co/300x180/e5e7eb/4b5563?text=Image%20Not%20Found'">
                        <?php else: ?>
                            <i style="color:var(--color-waiting); font-weight: 500;">Gambar belum diunggah.</i>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="action-area">
                    <!-- Link Edit mengarah ke edit_ekskul_admin.php. Tidak perlu ID karena admin hanya mengelola satu -->
                    <a href="edit_ekskul_admin.php" class="btn-edit">✏️ Edit Detail Ekskul</a>
                </div>

            <?php else: ?>
                <p class="no-data">Ekskul Anda belum didaftarkan oleh Superadmin. Silakan hubungi administrator utama.</p>
            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>