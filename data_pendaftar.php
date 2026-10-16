<?php
session_start();
// Harap pastikan file koneksi.php ada dan berisi koneksi database ($conn)
include 'koneksi.php'; 

// Cek koneksi dan otentikasi
if (!isset($conn)) {
    die("ERROR: Koneksi database (\$conn) tidak ditemukan. Pastikan file 'koneksi.php' berfungsi.");
}

if (!isset($_SESSION['superadmin_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
    echo "<script>alert('Silakan login dulu!'); window.location.href='login_superadmin.php';</script>";
    exit;
}

$nama_superadmin = $_SESSION['superadmin_nama'] ?? 'Superadmin';
$daftar_siswa = [];
$error_message = '';

try {
    // Query SQL untuk mengambil semua data siswa (akun)
    // Sesuai dengan struktur DB: nis, nama_lengkap, notelp, email.
    // Kolom dan sub-query status pendaftaran terakhir telah dihapus untuk menghindari error.
    $sql_siswa = "
        SELECT 
            S.id_siswa,
            S.nis,             -- Diubah dari nisn
            S.nama_lengkap,    -- Diubah dari nama_siswa
            S.kelas,
            S.notelp,          -- Diubah dari nomor_telepon
            S.email,           -- Kolom email ditambahkan
            S.tanggal_daftar
        FROM 
            table_siswa S
        ORDER BY 
            S.tanggal_daftar DESC;
    ";
    
    $result = $conn->query($sql_siswa);

    if ($result === FALSE) {
        // Blok Debugging untuk menampilkan error MySQL yang spesifik
        throw new Exception("Query Gagal: " . $conn->error . " | SQL: " . $sql_siswa);
    }
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $daftar_siswa[] = $row;
        }
    }

} catch (Exception $e) {
    // Tampilkan pesan error yang lebih detail di UI
    error_log("Database Error: " . $e->getMessage());
    $error_message = "Terjadi kesalahan saat mengambil data akun siswa. Detail Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Akun Siswa</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-color: #0D47A1;
        --secondary-color: #1976D2;
        --accent-green: #4CAF50;
        --accent-orange: #FF9800;
        --accent-red: #E53935;
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

    /* Card Styling */
    .data-card {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: var(--shadow-md);
    }

    .data-card h1 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 20px;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }

    /* Table Styling */
    .styled-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.9rem;
        min-width: 1100px; /* Disesuaikan karena satu kolom dihapus */
    }
    .styled-table th, .styled-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        vertical-align: middle;
    }
    .styled-table th {
        background-color: var(--secondary-color);
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
    }
    .styled-table tr:nth-child(even) {
        background-color: #f8faff;
    }
    .styled-table tr:hover {
        background-color: #e3f2fd;
    }

    /* Status Badges */
    .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
    }
    .status-active {
        background-color: #E8F5E9; 
        color: var(--accent-green);
        border: 1px solid var(--accent-green);
    }
    .status-inactive {
        background-color: #FFEBEE;
        color: var(--accent-red);
        border: 1px solid var(--accent-red);
    }
    .status-pending {
        background-color: #FFF3E0; 
        color: var(--accent-orange);
        border: 1px solid var(--accent-orange);
    }

    /* Responsive Design */
    .table-responsive {
        overflow-x: auto;
    }
    
    @media (max-width: 768px) {
        .layout {
            flex-direction: column;
        }
        .sidebar {
            position: relative;
            width: 100%;
            height: auto;
            flex-direction: row;
            justify-content: center;
            align-items: center;
        }
        .sidebar-header {
            display: none; 
        }
        .sidebar-menu {
            display: flex;
            flex-direction: row;
            gap: 10px;
            padding: 0 10px;
            overflow-x: auto;
        }
        .menu-item {
            padding: 8px 12px;
            white-space: nowrap;
        }
        .content-area {
            margin-left: 0; 
            padding: 20px 15px;
        }
        .styled-table {
            font-size: 0.8rem;
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
            <a href="dashboard_superadmin.php" class="menu-item">🏠 Dashboard</a>
            <a href="kelola_admin.php" class="menu-item">👥 Kelola Admin</a>
            <a href="kelola_ekskul.php" class="menu-item">📚 Kelola Ekskul</a>
            <a href="data_akun_siswa.php" class="menu-item active">👤 Data Akun Siswa</a>
            <a href="logout.php" class="menu-item logout">🚪 Logout</a>
        </nav>
    </aside>

    <!-- CONTENT -->
    <main class="content-area">

        <div class="data-card">
            <h1>👤 Data Akun Siswa Terdaftar</h1>
            <p style="margin-bottom: 20px; color: #666;">Total Akun Siswa: <strong><?= number_format(count($daftar_siswa)) ?></strong></p>

            <?php if (!empty($error_message)): ?>
                <!-- Tampilkan pesan error yang lebih spesifik jika terjadi kesalahan database -->
                <p style="color: red; font-weight: 600; margin-bottom: 20px;">
                    ❌ Terjadi kesalahan saat mengambil data akun siswa.
                    <br>
                    <small>Detail: <?= htmlspecialchars($error_message) ?></small>
                </p>
            <?php elseif (empty($daftar_siswa)): ?>
                <p>Belum ada siswa yang mendaftar atau membuat akun di sistem.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Daftar</th>
                                <th>NIS</th>
                                <th>Nama Lengkap</th>
                                <th>Kelas</th>
                                <th>Telepon (No. Telp)</th>
                                <th>Email</th>
                                <th>Status Akun</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($daftar_siswa as $siswa): 
                                
                                // Status Akun (Diasumsikan selalu aktif saat ini)
                                $status_akun = 'Aktif';
                                $akun_class = 'status-active';
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date('d M Y', strtotime($siswa['tanggal_daftar'])) ?></td>
                                    <td><?= htmlspecialchars($siswa['nis']) ?></td>
                                    <td><strong><?= htmlspecialchars($siswa['nama_lengkap']) ?></strong></td>
                                    <td><?= htmlspecialchars($siswa['kelas']) ?></td>
                                    <td><?= htmlspecialchars($siswa['notelp']) ?></td>
                                    <td><?= htmlspecialchars($siswa['email']) ?></td>
                                    <td>
                                        <span class="badge <?= $akun_class ?>">
                                            <?= htmlspecialchars($status_akun) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>

</div>
</body>
</html>