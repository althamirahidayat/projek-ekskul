<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['siswa_nama']) || !isset($_SESSION['nis'])) {
    // Arahkan ke halaman login jika sesi tidak ditemukan
    header("Location: login_siswa.php");
    exit();
}

// PERHATIAN: Pastikan file koneksi.php tersedia
include 'koneksi.php';

$siswa_nama  = $_SESSION['siswa_nama'];
$siswa_kelas = $_SESSION['siswa_kelas'] ?? 'Belum Ditentukan';
$siswa_nis   = $_SESSION['nis'];

// =================================================================
// 1. LOGIC UNTUK NOTIFIKASI STATUS AKTIF (Menunggu dan Diterima)
// =================================================================
$waiting_pendaftarans = []; // Untuk notifikasi alert
$accepted_pendaftarans = []; // Untuk kartu akses grup WA

if (isset($conn)) {
    // Ambil semua pendaftaran yang statusnya Diterima atau Menunggu
    $q_active = $conn->prepare("
        SELECT 
            tp.nama_ekskul, 
            tp.status, 
            tp.tanggal_daftar,
            te.link_grup_wa  
        FROM table_pendaftaran tp
        JOIN table_ekskul te ON tp.nama_ekskul = te.nama_ekskul
        WHERE tp.nis=? AND tp.status IN ('Menunggu', 'Diterima')
        ORDER BY tp.tanggal_daftar DESC
    ");
    $q_active->bind_param("s", $siswa_nis);
    $q_active->execute();
    $res_active = $q_active->get_result();
    if ($res_active && $res_active->num_rows > 0) {
        while ($row = $res_active->fetch_assoc()) {
            if (strtolower($row['status']) === 'diterima') {
                $accepted_pendaftarans[] = $row;
            } else {
                // Menunggu
                $waiting_pendaftarans[] = $row;
            }
        }
    }
    $q_active->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | Pendaftaran Ekskul</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Variabel Warna (Konsisten dengan halaman Login/Daftar) */
        :root {
            --color-primary: #0D47A1; /* Biru Gelap/Navy */
            --color-secondary: #1976D2; /* Biru Standar */
            --color-accent: #00BCD4; /* Cyan */
            --color-success: #4CAF50;
            --color-warning: #FFC107;
            --color-danger: #F44336;
            --color-white: #ffffff;
            --color-grey-light: #f4f7fb;
            --color-text-dark: #333333;
            --shadow-sm: 0 2px 5px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 5px 15px rgba(0, 0, 0, 0.15);
            --radius: 10px;
        }

        /* Global Styling */
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
            flex-grow: 1; 
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
            margin-top: 20px; 
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
        }
        
        .halo-text {
            background-color: var(--color-white);
            padding: 20px 25px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            font-size: 1.1rem;
            color: var(--color-primary);
            line-height: 1.5;
        }
        
        .halo-text b {
            color: var(--color-text-dark);
            font-weight: 800;
        }

        /* ------------------------------------------------- */
        /* STATUS NOTIFICATION CARD (Menunggu & Diterima) */
        /* ------------------------------------------------- */
        .notification-container {
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .status-notification-card {
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            font-size: 1.05rem;
            border-left: 5px solid;
            background-color: var(--color-white);
            position: relative; /* Penting untuk tombol tutup */
            transition: opacity 0.3s ease, max-height 0.3s ease, padding 0.3s ease, margin 0.3s ease, border 0.3s ease; /* Tambah border */
            overflow: hidden; /* Penting untuk transisi max-height */
            max-height: 500px; /* Nilai awal untuk transisi */
        }
        
        /* State saat ditutup */
        .status-notification-card.hidden {
            opacity: 0 !important;
            max-height: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
            border-width: 0 !important;
        }

        .status-notification-card.status-menunggu {
            border-color: var(--color-warning);
            background-color: #fffde7;
        }

        /* Styling untuk tombol tutup */
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #999;
            cursor: pointer;
            line-height: 1;
            padding: 5px;
            transition: color 0.2s, background-color 0.2s;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.7;
        }

        .close-btn:hover {
            color: var(--color-danger);
            background-color: rgba(244, 67, 54, 0.1);
            opacity: 1;
        }
        
        /* New styling for Accepted Links container */
        .accepted-links-card {
            border-color: var(--color-success);
            background-color: var(--color-white);
            border: 1px solid #e0e0e0;
            border-left: 5px solid var(--color-success);
            padding: 30px;
        }

        .accepted-links-card h3 {
            color: var(--color-success);
            margin-bottom: 15px;
            font-size: 1.3rem;
            border-bottom: 1px dashed #e0e0e0;
            padding-bottom: 10px;
        }
        
        .accepted-link-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f4f4f4;
        }
        .accepted-link-item:last-child {
            border-bottom: none;
        }

        .notif-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: var(--color-text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notif-message-text {
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .notif-message-warning { color: #8a6d3b; }
        .notif-message-danger { color: #a94442; }

        .btn-action.btn-wa {
            background-color: #25D366; 
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-action.btn-wa:hover {
            background-color: #1DA851;
        }
        /* ------------------------------------------------- */
        
        .content-card {
            background-color: var(--color-white);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }
        
        .content-card h2 {
            font-size: 1.5rem;
            color: var(--color-secondary);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--color-grey-light);
            padding-bottom: 10px;
        }

        /* EKSKUL GRID */
        .ekskul-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .ekskul-card {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            background-color: var(--color-grey-light);
            border-radius: var(--radius);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
            border: 1px solid #e0e0e0;
        }

        .ekskul-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-color: var(--color-accent);
        }

        .ekskul-thumb {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-bottom: 3px solid var(--color-secondary);
        }
        
        .ekskul-card h3 {
            font-size: 1rem;
            color: var(--color-text-dark);
            padding: 15px 10px;
            font-weight: 700;
        }
        
        .no-data {
            color: var(--color-text-medium);
            grid-column: 1 / -1; 
            text-align: center;
            padding: 20px;
            background-color: #fff3e0;
            border-left: 5px solid var(--color-warning);
            border-radius: 5px;
        }


        /* RIWAYAT TABLE (Sama seperti sebelumnya) */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            border-radius: var(--radius);
            overflow: hidden; 
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: var(--color-secondary);
            color: var(--color-white);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        tr:nth-child(even) {
            background-color: var(--color-grey-light);
        }
        
        tr:hover td {
            background-color: #e3f2fd;
        }

        td {
            border-bottom: 1px solid #eeeeee;
        }
        
        td a {
            color: var(--color-accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        td a:hover {
            color: var(--color-primary);
            text-decoration: underline;
        }
        
        /* Status styling */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: capitalize;
        }
        .status-badge.diterima {
            background-color: #E8F5E9;
            color: var(--color-success);
            border: 1px solid var(--color-success);
        }
        .status-badge.menunggu {
            background-color: #FFFDE7;
            color: var(--color-warning);
            border: 1px solid var(--color-warning);
        }
        .status-badge.ditolak {
            background-color: #FFEBEE;
            color: var(--color-danger);
            border: 1px solid var(--color-danger);
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
            .sidebar > div {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 100%;
            }
            .sidebar-header {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 20px 15px;
                margin-bottom: 10px;
                border-bottom: none;
                width: 100%;
            }

            .sidebar-header h2 {
                display: none; 
            }
            .sidebar-logo {
                width: 40px;
                height: 40px;
                margin-right: 15px;
                margin-bottom: 0;
            }

            .sidebar-menu {
                flex-direction: row;
                justify-content: center;
                padding: 0 10px;
                overflow-x: auto;
                flex-grow: unset;
                width: 100%;
            }

            .menu-item {
                padding: 10px 15px;
                margin-bottom: 0;
                font-size: 0.9rem;
                white-space: nowrap;
                flex-shrink: 0;
            }
            
            .menu-item.logout {
                margin-top: 0;
                margin-left: 10px;
            }

            .content-area {
                padding: 20px 15px;
            }
            
            .halo-text {
                font-size: 1rem;
                padding: 15px;
            }
            
            .notification-container {
                gap: 10px;
                margin-bottom: 20px;
            }
            .status-notification-card {
                padding: 15px;
                font-size: 0.9rem;
            }
            .notif-title {
                font-size: 1rem;
            }
            .btn-action.btn-wa {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
            
            .accepted-links-card {
                padding: 20px;
            }
            .accepted-link-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
                padding: 10px 0;
            }

            /* Tablet View - Grid 2 kolom */
            .ekskul-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            /* Table Responsive */
            table, thead, tbody, th, td, tr { 
                display: block; 
            }
            
            thead tr { 
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            tr { border: 1px solid #ccc; margin-bottom: 10px; border-radius: var(--radius); overflow: hidden; }
            
            td { 
                border: none;
                border-bottom: 1px solid #eee; 
                position: relative;
                padding-left: 50%; 
                text-align: right;
            }
            
            td:before { 
                position: absolute;
                top: 0;
                left: 6px;
                width: 45%; 
                padding: 12px 0;
                white-space: nowrap;
                content: attr(data-label);
                font-weight: 700;
                text-align: left;
                color: var(--color-primary);
            }
            
            /* Menyesuaikan label untuk mode mobile */
            td:nth-of-type(1):before { content: "Ekskul"; }
            td:nth-of-type(2):before { content: "Status"; }
            td:nth-of-type(3):before { content: "Tanggal"; }
            td:nth-of-type(4):before { content: "Bukti"; }

            .close-btn {
                font-size: 1.2rem;
                width: 25px;
                height: 25px;
            }
        }

        @media (max-width: 500px) {
             .ekskul-grid {
                grid-template-columns: 1fr; 
            }
        }
    </style>
</head>
<body>
<div class="layout">

    <aside class="sidebar">
        <div>
            <div class="sidebar-header">
                <img 
                    src="pic/logo1.png" 
                    onerror="this.onerror=null;this.src='https://placehold.co/60x60/0D47A1/ffffff?text=SMK'"
                    alt="Logo Sekolah" 
                    class="sidebar-logo">
                <h2>Ekskul SMKN 1</h2>
            </div>
            <nav class="sidebar-menu">
                <a href="pilihan.php" class="menu-item active">🏠 Dashboard</a>
                <a href="profile_siswa.php" class="menu-item">👤 Profil</a>
                <a href="logout.php" class="menu-item logout">🚪 Logout</a>
            </nav>
        </div>
    </aside>

    <main class="content-area">
        <div class="halo-text">
            Hai 👋, <b><?= htmlspecialchars($siswa_nama) ?></b>! Kamu terdaftar di NIS <b><?= htmlspecialchars($siswa_nis) ?></b> dan merupakan murid kelas <b><?= htmlspecialchars($siswa_kelas) ?></b>.
        </div>

        <?php if (!empty($waiting_pendaftarans)): ?>
        <div class="notification-container" id="waiting-notifications">
            <?php foreach ($waiting_pendaftarans as $pendaftaran): ?>
                <?php 
                    // Membuat ID unik untuk notifikasi ini agar bisa dilacak di localStorage
                    $ekskul_name = htmlspecialchars($pendaftaran['nama_ekskul']);
                    $card_id = 'waiting-' . urlencode($ekskul_name);
                    $tanggal = htmlspecialchars(date('d M Y', strtotime($pendaftaran['tanggal_daftar'])));
                ?>
                <div class="status-notification-card status-menunggu" id="<?= $card_id ?>">
                    <button class="close-btn" onclick="dismissNotification('<?= $card_id ?>')">×</button>

                    <p class="notif-title">
                        ⏳ Status Pendaftaran Ekskul
                    </p>
                    <p class="notif-message-text notif-message-warning">
                        Pendaftaran Ekskul <b><?= $ekskul_name ?></b> Anda **MASIH MENUNGGU** konfirmasi. 
                        (Tgl Daftar: <?= $tanggal ?>). Cek kembali secara berkala.
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($accepted_pendaftarans)): ?>
        <div class="notification-container accepted-links-card">
            <h3>Akses Grup Ekskul yang Diterima 🎉</h3>
            <?php foreach ($accepted_pendaftarans as $pendaftaran): ?>
                <?php 
                    $ekskul_name = htmlspecialchars($pendaftaran['nama_ekskul']);
                    $link = htmlspecialchars($pendaftaran['link_grup_wa']);
                ?>
                <div class="accepted-link-item">
                    <b><?= $ekskul_name ?></b>
                    <?php if (!empty($link)): ?>
                        <a href="<?= $link ?>" target="_blank" class="btn-action btn-wa">
                            Gabung Grup WhatsApp 💬
                        </a>
                    <?php else: ?>
                        <span class="notif-message-warning" style="font-size:0.9rem;">Link WA belum tersedia.</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="content-card">
            <h2>Pilih Ekskul</h2>
            <div class="ekskul-grid">
                <?php
                // Pastikan koneksi berhasil sebelum query
                if (isset($conn)) {
                    $result = $conn->query("SELECT * FROM table_ekskul ORDER BY tanggal_dibuat DESC");
                    if ($result && $result->num_rows > 0) {
                        while ($r = $result->fetch_assoc()) {
                            $nama   = htmlspecialchars($r['nama_ekskul']);
                            $gambar = !empty($r['gambar']) ? 'uploads/'.htmlspecialchars($r['gambar']) : 'https://placehold.co/600x400/1976D2/ffffff?text='.urlencode($nama);
                            
                            echo "<a class='ekskul-card' href='halaman_ekskul.php?ekskul=".urlencode($nama)."'>
                                        <img src='$gambar' 
                                            onerror=\"this.onerror=null;this.src='https://placehold.co/600x400/1976D2/ffffff?text=".urlencode($nama)."'\"
                                            class='ekskul-thumb' alt='$nama'>
                                        <h3>$nama</h3>
                                    </a>";
                        }
                    } else {
                        echo "<p class='no-data'>Belum ada ekskul tersedia saat ini.</p>";
                    }
                } else {
                    echo "<p class='no-data'>⚠️ Koneksi database gagal. Tidak dapat memuat daftar ekskul.</p>";
                }
                ?>
            </div>
        </div>

        <div class="content-card">
            <h2>Riwayat Pendaftaran</h2>
            <table>
                <thead>
                    <tr>
                        <th>Ekskul</th>
                        <th>Status</th>
                        <th>Tanggal Daftar</th>
                        <th>Bukti Pendaftaran</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (isset($conn)) {
                    // Ambil semua riwayat termasuk yang Ditolak, untuk tabel riwayat
                    $q = $conn->prepare("SELECT nama_ekskul, status, tanggal_daftar, bukti_ss
                                             FROM table_pendaftaran
                                             WHERE nis=? ORDER BY tanggal_daftar DESC");
                    $q->bind_param("s", $siswa_nis);
                    $q->execute();
                    $res = $q->get_result();
                    if ($res->num_rows > 0) {
                        while ($r = $res->fetch_assoc()) {
                            $status_class = strtolower(str_replace(' ', '', $r['status']));
                            $status_display = htmlspecialchars($r['status']);
                            
                            echo "<tr>
                                        <td data-label='Ekskul'>".htmlspecialchars($r['nama_ekskul'])."</td>
                                        <td data-label='Status'><span class='status-badge {$status_class}'>{$status_display}</span></td>
                                        <td data-label='Tanggal Daftar'>".htmlspecialchars($r['tanggal_daftar'])."</td>
                                        <td data-label='Bukti Pendaftaran'>".
                                            (!empty($r['bukti_ss']) ? 
                                                "<a target='_blank' href='uploads/".htmlspecialchars($r['bukti_ss'])."'>Lihat Bukti</a>" : 
                                                "Tidak Ada").
                                        "</td>
                                    </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='no-data'>Anda belum mendaftar ke ekskul manapun.</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='no-data'>⚠️ Koneksi database gagal. Tidak dapat memuat riwayat.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<script>
    const STORAGE_KEY = 'dismissed_waiting_notifications';

    // 1. Fungsi untuk menyimpan status penutupan notifikasi di localStorage
    function dismissNotification(cardId) {
        const card = document.getElementById(cardId);
        if (card) {
            // Ambil data yang sudah ada atau buat objek kosong
            let dismissed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            // Set status notifikasi ini menjadi true (telah ditutup)
            dismissed[cardId] = true;
            localStorage.setItem(STORAGE_KEY, JSON.stringify(dismissed));

            // Tambahkan class hidden untuk transisi penutupan yang mulus (CSS)
            card.classList.add('hidden');
            
            // Hapus dari DOM setelah transisi selesai (300ms)
            setTimeout(() => {
                card.remove(); 
                
                // Cek jika semua notifikasi sudah ditutup, sembunyikan container-nya
                const container = document.getElementById('waiting-notifications');
                if (container && container.children.length === 0) {
                    container.style.display = 'none';
                }
            }, 300); 
        }
    }

    // 2. Fungsi untuk menyaring (filter) notifikasi saat halaman dimuat
    function filterNotifications() {
        const dismissed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        const cards = document.querySelectorAll('.status-notification-card.status-menunggu');
        let hasVisibleCard = false;

        cards.forEach(card => {
            const cardId = card.id;
            
            if (dismissed[cardId]) {
                // Sembunyikan notifikasi yang sudah ditutup sebelumnya
                card.style.display = 'none'; 
                card.remove(); // Hapus sekalian dari DOM
            } else {
                hasVisibleCard = true;
            }
        });
        
        // Sembunyikan container jika tidak ada notifikasi yang terlihat
        const container = document.getElementById('waiting-notifications');
        if (container && !hasVisibleCard) {
            container.style.display = 'none';
        }
    }

    // Jalankan fungsi filter saat DOM selesai dimuat
    document.addEventListener('DOMContentLoaded', filterNotifications);
</script>
</body>
</html>