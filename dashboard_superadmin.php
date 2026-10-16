<?php
session_start();
// Harap pastikan file koneksi.php ada dan berisi koneksi database ($conn)
include 'koneksi.php'; 

// Cek apakah $conn sudah terdefinisi setelah include
if (!isset($conn)) {
    // Jika koneksi gagal didefinisi, hentikan eksekusi dan berikan pesan error yang jelas
    die("ERROR: Koneksi database (\$conn) tidak ditemukan. Pastikan file 'koneksi.php' berfungsi.");
}

if (!isset($_SESSION['superadmin_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
    echo "<script>alert('Silakan login dulu!'); window.location.href='login_superadmin.php';</script>";
    exit;
}

$nama_superadmin = $_SESSION['superadmin_nama'] ?? 'Pengguna';


// LOGIKA LEADERBOARD EKSKUL 
$error_message = '';

try {
    // Query SQL untuk menghitung jumlah pendaftar per ekskul yang AKTIF SAJA.
    // Menggunakan INNER JOIN dengan table_ekskul untuk memfilter ekskul yang sudah dihapus.
    $sql_leaderboard = "
        SELECT 
            E.nama_ekskul, 
            COUNT(P.nama_ekskul) AS jumlah_pendaftar,
            SUM(CASE WHEN P.status = 'Diterima' THEN 1 ELSE 0 END) AS jumlah_diterima,
            SUM(CASE WHEN P.status = 'Menunggu' THEN 1 ELSE 0 END) AS jumlah_menunggu
        FROM 
            table_ekskul E
        INNER JOIN
            table_pendaftaran P ON E.nama_ekskul = P.nama_ekskul
        GROUP BY 
            E.nama_ekskul
        ORDER BY 
            jumlah_pendaftar DESC;
    ";
    
    $result = $conn->query($sql_leaderboard);

    if ($result === FALSE) {
        throw new Exception("Query Gagal: " . $conn->error);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $leaderboard[] = $row;
        }
    }

} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "Terjadi kesalahan saat mengambil data leaderboard.";
}

// -----------------------------------------------------
// AMBIL DATA RINGKAS UNTUK KARTU INFORMASI
// -----------------------------------------------------
$total_siswa = 0; // Mengganti nama variabel untuk mencerminkan total siswa (Total Akun)
$total_ekskul_aktif = 0;
$total_menunggu = 0;

// Ambil data ekskul untuk tabel di bawah DAN untuk menghitung total aktif
// Query ini dijalankan pertama agar bisa mendapatkan jumlah barisnya
$ekskul_result = $conn->query("SELECT id_ekskul, nama_ekskul, deskripsi, gambar FROM table_ekskul ORDER BY id_ekskul DESC");

// Total Ekskul Aktif (Menggunakan jumlah baris dari hasil query ekskul untuk konsistensi)
if ($ekskul_result) {
    $total_ekskul_aktif = $ekskul_result->num_rows;
    // Mengatur ulang pointer hasil query agar dapat digunakan lagi di bagian HTML (tabel)
    if ($total_ekskul_aktif > 0) {
        $ekskul_result->data_seek(0);
    }
} else {
    // Jika query ekskul gagal
    $total_ekskul_aktif = 0;
}

// Total Siswa Keseluruhan (Mengambil dari table_siswa)
$stmt_total_siswa = $conn->prepare("SELECT COUNT(*) as total FROM table_siswa");
$stmt_total_siswa->execute();
$data_total_siswa = $stmt_total_siswa->get_result()->fetch_assoc();
if ($data_total_siswa) {
    $total_siswa = $data_total_siswa['total'];
}
$stmt_total_siswa->close();

// Total Pendaftaran Menunggu (Tetap dari table_pendaftaran)
$stmt_menunggu = $conn->prepare("SELECT COUNT(*) as total FROM table_pendaftaran WHERE status = 'Menunggu'");
$stmt_menunggu->execute();
$data_menunggu = $stmt_menunggu->get_result()->fetch_assoc();
if ($data_menunggu) {
    $total_menunggu = $data_menunggu['total'];
}
$stmt_menunggu->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Superadmin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-color: #0D47A1;
        --secondary-color: #1976D2;
        --accent-green: #4CAF50;
        --accent-orange: #FF9800;
        --bg-light: #f4f7f9;
        --card-bg: #ffffff;
        --text-dark: #333;
        --radius-lg: 12px;
        --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    /* Global Reset */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Inter', sans-serif;
    }
    
    body {
        background-color: var(--bg-light);
        color: var(--text-dark);
    }

    /* Layout Utama */
    .layout {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar Styling */
    .sidebar {
        width: 250px;
        background-color: var(--primary-color);
        color: white;
        padding: 20px 0;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        height: 100vh;
        z-index: 10;
    }

    .sidebar-header {
        text-align: center;
        margin-bottom: 30px;
        padding: 0 20px;
    }
    .sidebar-logo {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin-bottom: 10px;
        object-fit: cover;
        background-color: white;
    }
    .sidebar-header h2 {
        font-weight: 700;
        font-size: 1.2rem;
    }

    .sidebar-menu {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    .menu-item {
        padding: 15px 20px;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 600;
        transition: background-color 0.2s, color 0.2s;
        border-left: 4px solid transparent;
    }
    .menu-item:hover {
        background-color: rgba(0, 0, 0, 0.2);
        color: white;
    }
    .menu-item.active {
        background-color: var(--secondary-color);
        border-left: 4px solid #FFC107; /* Highlight Yellow */
        color: white;
    }
    .menu-item.logout {
        margin-top: auto;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Content Area Styling */
    .content-area {
        flex-grow: 1;
        margin-left: 250px; 
        padding: 30px;
        width: calc(100% - 250px);
    }

    /* General Card Styling */
    .content-card, .table-card, .leaderboard-section {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: var(--shadow-md);
    }

    /* Halo Text */
    .halo-text {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 5px;
    }
    .name-highlight {
        color: var(--primary-color);
    }
    .sub-greet {
        color: #666;
        font-size: 0.95rem;
    }

    /* ------------------------------------------- */
    /* NEW: Info Ringkas (Summary Cards) Styling */
    /* ------------------------------------------- */
    .card-container { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
        gap: 20px; 
        margin-bottom: 30px; 
    }
    .card-link {
        text-decoration: none;
        color: inherit;
    }
    .card { 
        background: var(--card-bg); 
        padding: 20px; 
        border-radius: var(--radius-lg); 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        transition: transform 0.3s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    /* Hanya tambahkan hover untuk kartu yang merupakan link */
    .card-link .card:hover { 
        transform: translateY(-3px); 
        cursor: pointer; 
    }
    .card h2 { 
        font-size: 1.1rem; 
        color: var(--secondary-color); 
        margin-bottom: 5px; 
        font-weight: 600;
    }
    .card p { 
        font-size: 2.5rem; 
        font-weight: 900; 
        color: var(--primary-color); 
    }
    .card.menunggu p { 
        color: var(--accent-orange); 
    }
    .card-footer {
        font-size: 0.8rem;
        color: #666;
        margin-top: 10px;
        font-style: italic;
    }

    /* ------------------------------------------- */
    /* Leaderboard Section Styling */
    /* ------------------------------------------- */
    .leaderboard-section {
        overflow-x: auto; /* Agar responsif di mobile */
    }
    .leaderboard-section h2 {
        font-size: 1.5rem;
        color: var(--primary-color);
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        font-weight: 700;
    }
    .leaderboard-table {
        width: 100%;
        min-width: 650px; 
        border-collapse: collapse;
        text-align: left;
    }
    .leaderboard-table th, .leaderboard-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
    }
    .leaderboard-table th {
        background-color: var(--secondary-color);
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.85rem;
    }
    .leaderboard-table tr:nth-child(even) {
        background-color: #f8faff;
    }
    .leaderboard-table tr:hover {
        background-color: #e3f2fd;
    }
    
    /* Rank number styling */
    .rank {
        font-weight: 900;
        text-align: center;
        width: 50px;
        transition: color 0.3s;
    }
    /* === STYLING UNTUK ANGKA RANKING (Top 3) === */
    .rank-1 { color: #FFD700; font-size: 1.2rem; } /* Emas (Gold) */
    .rank-2 { color: #C0C0C0; font-size: 1.1rem; } /* Perak (Silver) */
    .rank-3 { color: #CD7F32; font-size: 1.05rem; } /* Perunggu (Bronze) */
    
    /* === STYLING UNTUK SELURUH BARIS (ROW) TOP 3 === */
    .leaderboard-table .row-rank-1 { 
        background-color: #FFECB3 !important; /* Light Gold */
        font-weight: 700;
        border-left: 8px solid #FFD700;
    } 
    .leaderboard-table .row-rank-2 { 
        background-color: #E0E0E0 !important; /* Light Silver */
        font-weight: 600;
        border-left: 8px solid #C0C0C0;
    } 
    .leaderboard-table .row-rank-3 { 
        background-color: #F8D8C4 !important; /* Light Bronze/Copper */
        font-weight: 600;
        border-left: 8px solid #CD7F32;
    } 

    /* Adjust hover effect for top rows */
    .leaderboard-table tr.row-rank-1:hover {
        background-color: #FFE082 !important; /* Darker Gold hover */
    }
    .leaderboard-table tr.row-rank-2:hover {
        background-color: #BDBDBD !important; /* Darker Silver hover */
    }
    .leaderboard-table tr.row-rank-3:hover {
        background-color: #D2A88A !important; /* Darker Bronze hover */
    }
    
    .count-total { color: var(--text-dark); font-weight: 700; text-align: center; }
    .count-accepted { color: var(--accent-green); font-weight: 700; text-align: center; }
    .count-pending { color: var(--accent-orange); font-weight: 700; text-align: center; }
    
    /* Ekskul List Table Styling (Original) */
    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .table-header h2 {
        font-size: 1.4rem;
    }
    .btn-add {
        background-color: var(--accent-green);
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: background-color 0.2s;
    }
    .btn-add:hover {
        background-color: #43A047;
    }

    .styled-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .styled-table th, .styled-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
    }
    .styled-table th {
        background-color: #f0f0f0;
        color: #333;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.85rem;
    }
    .styled-table tr:hover {
        background-color: #f9f9f9;
    }
    .styled-table img {
        display: block;
        margin: auto 0;
    }
    
    .table-card {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .styled-table {
        min-width: 480px;
    }
    
    /* Responsive Design */
    @media (max-width: 1024px) {
        .content-area {
            padding: 25px 20px;
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
            padding: 10px 15px;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .sidebar-header {
            margin-bottom: 0;
            display: flex;
            align-items: center;
            padding: 0;
        }
        .sidebar-logo {
            width: 45px;
            height: 45px;
            margin-bottom: 0;
        }
        .sidebar-header h2 {
            display: none; /* Hide title for mobile space */
        }
        .sidebar-menu {
            display: flex; 
            flex-direction: row;
            overflow-x: auto;
            gap: 5px;
            padding: 0 5px;
            align-items: center;
        }
        .menu-item {
            padding: 8px 12px;
            white-space: nowrap;
            font-size: 0.85rem;
            border-left: none;
            border-bottom: 3px solid transparent;
        }
        .menu-item.active {
            border-left: none;
            border-bottom: 3px solid #FFC107;
        }
        .menu-item.logout {
            margin-top: 0;
            border-top: none;
            background-color: #c0392b;
            border-radius: 6px;
            color: white;
        }
        .content-area {
            margin-left: 0; 
            width: 100%;
            padding: 20px 15px;
            box-sizing: border-box;
        }
        .content-card, .table-card, .leaderboard-section {
            padding: 20px 16px;
            margin-bottom: 20px;
        }
        .halo-text {
            font-size: 1.5rem;
        }
        .card-container {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .content-area {
            padding: 16px 10px;
        }
        .content-card, .table-card, .leaderboard-section {
            padding: 16px 12px;
            border-radius: 10px;
        }
        .halo-text {
            font-size: 1.3rem;
        }
        .sub-greet {
            font-size: 0.85rem;
        }
        .card-container {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .card p {
            font-size: 2rem;
        }
        .table-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        .btn-add {
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }
        .menu-item {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 375px) {
        .content-area {
            padding: 12px 6px;
        }
        .halo-text {
            font-size: 1.15rem;
        }
        .sidebar-logo {
            width: 36px;
            height: 36px;
        }
    }
</style>

</head>

<body>
<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="pic/logo1.png" class="sidebar-logo" onerror="this.onerror=null;this.src='https://placehold.co/60x60/0D47A1/ffffff?text=Logo';" alt="Logo Sekolah">
            <h2>Superadmin</h2>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_superadmin.php" class="menu-item active">🏠 Dashboard</a>
            <a href="kelola_admin.php" class="menu-item">👥 Kelola Admin</a>
            <a href="kelola_ekskul.php" class="menu-item">📚 Kelola Ekskul</a>
            <a href="data_pendaftar.php" class="menu-item">👤 Data Akun Siswa</a> <!-- Tautan ke Data Akun Siswa -->
            <a href="logout.php" class="menu-item logout">🚪 Logout</a>
        </nav>
    </aside>

    <!-- CONTENT -->
    <main class="content-area">

        <!-- 1. KARTU UCAPAN SELAMAT DATANG -->
        <div class="content-card">
            <h1 class="halo-text">👋 Halo, <span class="name-highlight"><?= htmlspecialchars($nama_superadmin) ?></span></h1>
            <p class="sub-greet">Anda adalah Superadmin sistem ekskul. Berikut ringkasan data pendaftaran.</p>
        </div>

        <!-- 2. INFO RINGKAS (KARTU STATISTIK) -->
        <div class="card-container">
            
            <!-- KARTU 1: TOTAL AKUN SISWA (HANYA INI YANG BISA DIKLIK, MENUJU DATA PENDAFTAR) -->
            <a href="data_pendaftar.php" class="card-link">
                <div class="card" style="border-bottom: 4px solid var(--primary-color);">
                    <h2>Total Akun Siswa</h2>
                    <p><?= number_format($total_siswa) ?></p>
                    <div class="card-footer">
                        Klik untuk melihat semua data pendaftar
                    </div>
                </div>
            </a>
            
            <!-- KARTU 2: EKSKUL AKTIF (STATIS) -->
            <div class="card" style="border-bottom: 4px solid var(--accent-green);">
                <h2>Ekskul Aktif</h2>
                <p><?= number_format($total_ekskul_aktif) ?></p>
            </div>

            <!-- KARTU 3: PENDAFTARAN MENUNGGU (STATIS - TIDAK ADA TAG <a>) -->
            <div class="card menunggu" style="border-bottom: 4px solid var(--accent-orange);">
                <h2>Pendaftaran Menunggu</h2>
                <p><?= number_format($total_menunggu) ?></p>
                
            </div>
            
        </div>

        <!-- 3. LEADERBOARD EKSKUL -->
        <div class="leaderboard-section">
            <h2>🏆 Leaderboard Ekskul Berdasarkan Jumlah Pendaftaran</h2>
            
            <?php if (!empty($error_message)): ?>
                <p style="color: red; font-weight: 600;"><?= htmlspecialchars($error_message) ?></p>
            <?php elseif (empty($leaderboard)): ?>
                <p>Belum ada pendaftaran ekskul yang tercatat di sistem.</p>
            <?php else: ?>
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th class="rank">#</th>
                            <th>Nama Ekskul</th>
                            <th class="count-total">Total Pendaftar</th>
                            <th class="count-accepted">Diterima</th>
                            <th class="count-pending">Menunggu Konfirmasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 0;
                        foreach ($leaderboard as $item): 
                            $rank++;
                            $rank_class = '';
                            $row_class = ''; // Variabel untuk kelas background baris
                            if ($rank == 1) {
                                $rank_class = 'rank-1';
                                $row_class = 'row-rank-1'; // Emas
                            }
                            else if ($rank == 2) {
                                $rank_class = 'rank-2';
                                $row_class = 'row-rank-2'; // Perak
                            }
                            else if ($rank == 3) {
                                $rank_class = 'rank-3';
                                $row_class = 'row-rank-3'; // Perunggu
                            }
                        ?>
                            <!-- Terapkan kelas baris di sini -->
                            <tr class="<?= $row_class ?>"> 
                                <td class="rank <?= $rank_class ?>"><?= $rank ?></td>
                                <td><?= htmlspecialchars($item['nama_ekskul']) ?></td>
                                <td class="count-total"><?= number_format($item['jumlah_pendaftar']) ?></td>
                                <td class="count-accepted"><?= number_format($item['jumlah_diterima']) ?></td>
                                <td class="count-pending"><?= number_format($item['jumlah_menunggu']) ?></td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 4. DAFTAR EKSKUL TERDAFTAR (Original Section) -->
        <div class="table-card">
            <div class="table-header">
                <h2>Daftar Ekskul Terdaftar</h2>
                <a href="tambah_ekskul.php" class="btn-add">+ Tambah Ekskul</a>
            </div>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Ekskul</th>
                        <th>Deskripsi</th>
                        <th>Gambar</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $no = 1; 
                if ($ekskul_result && $ekskul_result->num_rows > 0): 
                    // Penting: Reset pointer result sebelum loop
                    $ekskul_result->data_seek(0);
                    while ($row = $ekskul_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nama_ekskul']) ?></td>
                        <td><?= htmlspecialchars(substr($row['deskripsi'], 0, 40)) ?>...</td>
                        <td>
                            <img src="uploads/<?= htmlspecialchars($row['gambar']) ?>" 
                                 width="65" 
                                 style="border-radius:8px;"
                                 onerror="this.onerror=null;this.src='https://placehold.co/65x65/E0E0E0/333333?text=No+Img';"
                                 alt="Gambar Ekskul">
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" style="text-align:center;">Belum ada ekskul.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</div>
</body>
</html>