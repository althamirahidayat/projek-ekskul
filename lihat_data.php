<?php
session_start();
// PERHATIAN: Pastikan file koneksi.php tersedia di lingkungan ini
include 'koneksi.php';

// Validasi admin ekskul
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

$admin_ekskul = $_SESSION['admin_ekskul'];

// Ambil status filter (nilai yang ditampilkan di UI)
$status_filter_display = $_GET['status'] ?? 'Menunggu';
// Konversi ke lowercase untuk query database
$status_filter_db = strtolower($status_filter_display); 

// Judul Halaman
$page_title = "Data Pendaftar Ekskul";

// Fungsi untuk membuat query status yang aman
function get_status_class($status) {
    if ($status === 'menunggu') return 'status-pending';
    if ($status === 'diterima') return 'status-accepted';
    return 'status-rejected';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?> - <?= htmlspecialchars($admin_ekskul) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* =======================================================
   DATA PENDAFTAR EKSKUL — MODERN UI
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

.layout-container {
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
    border-left: 5px solid #FFD700; /* Gold accent */
    color: white;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
}

/* ----------------- LOGOUT BUTTON DI SIDEBAR BAWAH ----------------- */
.logout-btn {
    margin-top: auto; /* Push to the bottom */
    border-top: 1px solid rgba(255,255,255,0.3);
    background: #c0392b; /* Darker red for logout */
    padding: 14px 25px;
    text-decoration: none;
    color: white;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}
.logout-btn:hover {
    background: #e74c3c;
}

/* ----------------- CONTENT AREA ----------------- */
.content {
    margin-left: 260px; 
    padding: 40px;
    width: calc(100% - 260px);
    box-sizing: border-box;
}

/* Hapus style top-navbar karena sudah dihapus dari HTML */

h1 {
    font-size: 2.2rem;
    font-weight: 900;
    margin-bottom: 5px;
    color: var(--text-dark);
}

h2 {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-medium);
    margin-top: 0;
    margin-bottom: 30px;
}


/* FILTER BUTTONS (Tabs Style) */
.status-filter {
    margin: 20px 0;
    display: inline-flex; /* Use inline-flex for grouping */
    border-radius: 10px;
    overflow: hidden;
    box-shadow: var(--shadow-light);
}

.status-filter a {
    padding: 10px 20px;
    text-decoration: none;
    font-weight: 600;
    color: var(--text-dark);
    background: #e5e7eb; /* Light gray background */
    transition: background-color 0.2s;
    font-size: 0.95rem;
}

.status-filter a:hover {
    background: #d1d5db;
}

.status-filter .active {
    background: var(--primary); /* Blue primary color for active */
    color: white;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}
.status-filter .active:hover {
    background: var(--primary-hover);
}

/* ----------------- TABEL ----------------- */
.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--bg-card);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-light);
    margin-top: 20px;
}

.data-table th, .data-table td {
    padding: 15px 18px;
    border-bottom: 1px solid #f0f0f0;
    text-align: left;
    font-size: 0.9rem;
}

.data-table th {
    background: #f8fafc;
    color: var(--text-medium);
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.data-table tr:last-child td {
    border-bottom: none;
}

/* Status Badge */
.status-badge {
    padding: 6px 10px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: capitalize;
}

.status-pending { 
    background: #fff3cd; 
    color: #8a6d3b; 
    border: 1px solid var(--color-waiting);
}
.status-accepted { 
    background: #d4edda; 
    color: #155724; 
    border: 1px solid var(--color-success);
}
.status-rejected { 
    background: #f8d7da; 
    color: #721c24; 
    border: 1px solid var(--color-danger);
}

/* Action Buttons */
.action-group {
    display: flex;
    gap: 8px;
}
.btn-action {
    padding: 8px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    transition: background-color 0.2s;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn-accept {
    background: var(--color-success);
    color: white;
}
.btn-accept:hover {
    background: #218838;
}

/* Button to open confirmation box */
.btn-reject-trigger {
    background: var(--color-danger);
    color: white;
}
.btn-reject-trigger:hover {
    background: #c82333;
}

/* Custom Message Box (Ganti alert/confirm) */
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
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    transition: background-color 0.3s;
    margin: 0 5px;
}

.message-content .btn-primary {
    background: var(--primary);
    color: white;
}
.message-content .btn-secondary {
    background: #e0e0e0;
    color: var(--text-dark);
}

/* Responsiveness */
@media (max-width: 1024px) {
    .content {
        margin-left: 260px;
        padding: 30px 20px;
        width: calc(100% - 260px);
    }
}

@media (max-width: 768px) {
    .layout-container {
        flex-direction: column;
    }
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding: 10px 0;
        flex-direction: row;
        justify-content: space-around;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        z-index: 10;
    }
    .sidebar-header {
        display: none; 
    }
    .sidebar-menu {
        flex-direction: row;
        overflow-x: auto;
        width: 100%;
        margin-top: 0;
    }
    .menu-item {
        padding: 10px 15px;
        border-left: none;
        border-bottom: 3px solid transparent;
        flex-shrink: 0;
        font-size: 0.85rem;
    }
    .menu-item.active {
        border-bottom: 3px solid #FFD700;
        border-left: none;
    }
    .logout-btn {
        display: none; /* Sembunyikan di mobile, menu utama lebih diprioritaskan */
    }
    
    .content {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }
    /* Tambahkan margin atas untuk konten agar tidak terlalu mepet dengan sidebar di mobile */
    .content > * {
        margin-top: 20px;
    }

    .data-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    .data-table thead {
        display: none;
    }
    .data-table tbody, .data-table tr, .data-table td {
        display: block;
        width: 100%;
    }
    .data-table tr {
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 10px;
    }
    .data-table td {
        border-bottom: 1px dashed #eee;
        text-align: right;
        padding: 10px 15px;
        position: relative;
    }
    .data-table td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        font-weight: 700;
        color: var(--text-dark);
        text-transform: uppercase;
        font-size: 0.75rem;
    }
    /* Tambahan style untuk No Telp di mobile */
    .data-table td[data-label="No Telp"] a {
        font-weight: 700;
        color: var(--primary);
    }
}
</style>

</head>
<body>

<!-- Custom Message Box for General Alert/Auth Error -->
<div id="message-box" class="message-box">
    <div class="message-content">
        <p id="message-box-text"></p>
        <button id="message-box-ok" class="btn-primary">OK</button>
    </div>
</div>

<!-- Custom Confirmation Box for Reject Action -->
<div id="confirm-reject-box" class="message-box">
    <div class="message-content">
        <p>Yakin ingin menolak pendaftaran ini?</p>
        <div style="display:flex; justify-content:center;">
            <button id="confirm-reject-cancel" class="btn-secondary">Batal</button>
            <a href="#" id="confirm-reject-link" class="btn-action btn-reject-trigger">Ya, Tolak</a>
        </div>
    </div>
</div>


<div class="layout-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-header">
                <img src="pic/logo1.png" onerror="this.onerror=null;this.src='https://placehold.co/65x65/1c3d5a/ffffff?text=E'" class="sidebar-logo">
                <h2>Admin Ekskul</h2>
                <p><?= htmlspecialchars($admin_ekskul) ?></p>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard_admin.php" class="menu-item">🏠 Dashboard</a>
                <a href="kelola_ekskul_admin.php" class="menu-item">📚 Kelola Ekskul</a>
            </nav>
        </div>
        
        <!-- Tombol Logout dikembalikan ke sidebar paling bawah -->
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="content">

        <!-- Header Konten -->
        <h1><?= $page_title ?></h1>
        <h2>Ekskul: <?= htmlspecialchars($admin_ekskul) ?></h2>

        <!-- FILTER BUTTONS -->
        <div class="status-filter">
            <a href="?status=Menunggu" class="<?= $status_filter_display === 'Menunggu' ? 'active' : '' ?>">⏳ Menunggu</a>
            <a href="?status=Diterima" class="<?= $status_filter_display === 'Diterima' ? 'active' : '' ?>">✅ Diterima</a>
            <a href="?status=Ditolak" class="<?= $status_filter_display === 'Ditolak' ? 'active' : '' ?>">❌ Ditolak</a>
        </div>

        <!-- TABEL -->
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th>No Telp</th> 
                        <th>Tanggal Daftar</th>
                        <th>Bukti Screenshot</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                // Query untuk mengambil data pendaftar dengan JOIN ke table_siswa untuk mendapatkan notelp
                $stmt = $conn->prepare("
                    SELECT 
                        tp.id_pendaftaran, 
                        tp.nama_lengkap, 
                        tp.kelas, 
                        tp.tanggal_daftar, 
                        tp.bukti_ss, 
                        tp.status,
                        ts.notelp  
                    FROM table_pendaftaran tp
                    JOIN table_siswa ts ON tp.nis = ts.nis 
                    WHERE tp.nama_ekskul = ? AND tp.status = ?
                    ORDER BY tp.tanggal_daftar DESC
                ");
                
                $stmt->bind_param("ss", $admin_ekskul, $status_filter_db);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($res->num_rows > 0):
                    while ($r = $res->fetch_assoc()):
                        $status_db = strtolower($r['status']);
                        // Format nomor telepon untuk link WhatsApp 
                        $notelp_wa_link = preg_replace('/^0/', '62', $r['notelp']);
                ?>

                <tr>
                    <td data-label="Nama Lengkap"><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                    <td data-label="Kelas"><?= htmlspecialchars($r['kelas']) ?></td>
                    
                    <td data-label="No Telp">
                        <?php if ($r['notelp']): ?>
                            <a href="https://wa.me/<?= htmlspecialchars($notelp_wa_link) ?>" target="_blank" style="text-decoration: none; color: var(--color-success); font-weight: 600;">
                                <?= htmlspecialchars($r['notelp']) ?>
                            </a>
                        <?php else: ?>
                            <span style='color:var(--color-danger);'>N/A</span>
                        <?php endif; ?>
                    </td>

                    <td data-label="Tanggal Daftar"><?= htmlspecialchars(date('d M Y', strtotime($r['tanggal_daftar']))) ?></td>

                    <td data-label="Bukti Screenshot">
                        <?= $r['bukti_ss']
                            ? "<a href='uploads/".htmlspecialchars($r['bukti_ss'])."' target='_blank' style='color:var(--primary); font-weight:600;'>Lihat Bukti</a>"
                            : "<span style='color:var(--text-medium);'>Tidak ada</span>" ?>
                    </td>

                    <td data-label="Status">
                        <span class="status-badge <?= get_status_class($status_db) ?>">
                            <?= htmlspecialchars($r['status']) ?>
                        </span>
                    </td>

                    <td data-label="Aksi">
                        <div class="action-group">
                        <?php if ($status_filter_db === 'menunggu'): ?>
                            <!-- Aksi untuk status Menunggu (Terima / Tolak) -->
                            <a href="ubah_status.php?id=<?= $r['id_pendaftaran'] ?>&status=Diterima" class="btn-action btn-accept">
                                Terima
                            </a>
                            <a href="#" class="btn-action btn-reject-trigger" 
                               data-id="<?= $r['id_pendaftaran'] ?>"
                               onclick="openRejectConfirm(event, <?= $r['id_pendaftaran'] ?>);">
                               Tolak
                            </a>
                        <?php elseif ($status_filter_db === 'ditolak'): ?>
                            <!-- Aksi untuk status Ditolak (Opsi Terima Kembali) -->
                            <a href="ubah_status.php?id=<?= $r['id_pendaftaran'] ?>&status=Diterima" class="btn-action btn-accept">
                                ✅ Terima Kembali
                            </a>
                        <?php else: // Diterima ?>
                            <!-- Aksi untuk status Diterima -->
                            <i>Sudah diterima</i>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <?php
                    endwhile;
                else:
                    $message = $status_filter_display === 'Menunggu' ? 'Belum ada pendaftar baru yang menunggu konfirmasi.' : 'Tidak ada data pendaftar dengan status ' . htmlspecialchars($status_filter_display) . '.';
                    // Colspan diatur ke 7 karena ada 7 kolom di tabel
                    echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:var(--text-medium);'>{$message}</td></tr>";
                endif;
                $stmt->close();
                ?>
                </tbody>
            </table>
        </div> <!-- End of overflow-x div -->

    </div>
</div>

<script>
    // Fungsi untuk membuka custom confirmation box
    function openRejectConfirm(event, id) {
        event.preventDefault(); // Mencegah link diaktifkan
        const confirmBox = document.getElementById('confirm-reject-box');
        const confirmLink = document.getElementById('confirm-reject-link');
        const cancelBtn = document.getElementById('confirm-reject-cancel');
        
        // Atur link Tolak
        confirmLink.href = "ubah_status.php?id=" + id + "&status=Ditolak";
        
        // Tampilkan box
        confirmBox.style.display = 'flex';

        // Event listener untuk tombol Batal
        cancelBtn.onclick = () => {
            confirmBox.style.display = 'none';
        };

        // Event listener untuk klik di luar box (opsional, tapi bagus untuk UX)
        confirmBox.onclick = (e) => {
            if (e.target === confirmBox) {
                confirmBox.style.display = 'none';
            }
        };
    }
</script>

</body>
</html>