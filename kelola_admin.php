<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['superadmin_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
    header("Location: login_superadmin.php");
    exit;
}

$nama_superadmin = $_SESSION['superadmin_nama'] ?? "Superadmin";
// Pastikan query berjalan dengan baik
$result = $conn->query("SELECT * FROM Table_admin ORDER BY id_admin DESC");

// Tambahkan pengecekan jika query gagal
if ($result === FALSE) {
    // Ini bisa diubah menjadi log error atau pesan yang lebih ramah pengguna
    $error_message = "Error database: " . $conn->error;
    $result = null; // Set result menjadi null agar tidak error saat di-fetch
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Admin Ekskul</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
    /* Variabel CSS untuk konsistensi */
    :root {
        --primary-color: #0D47A1;
        --secondary-color: #1976D2;
        --accent-green: #4CAF50;
        --accent-red: #D32F2F;
        --accent-yellow: #FFC107;
        --bg-light: #f4f7f9;
        --card-bg: #ffffff;
        --text-dark: #333;
        --radius-lg: 12px;
        --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

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
        border-left: 4px solid var(--accent-yellow);
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

    .content-card, .table-card {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: var(--shadow-md);
    }

    .title {
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 5px;
        color: var(--text-dark);
    }
    .subtitle {
        color: #666;
        font-size: 0.95rem;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .table-header h2 {
        font-size: 1.4rem;
        color: var(--primary-color);
        font-weight: 700;
    }

    .btn-add {
        background-color: var(--accent-green);
        color: white;
        padding: 10px 15px; 
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: background-color 0.2s;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
        color: var(--text-dark);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.85rem;
    }
    .styled-table tr:hover {
        background-color: #f9f9f9;
    }
    
    /* Action Buttons */
    .action-buttons a {
        margin-right: 8px;
        text-decoration: none;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        transition: opacity 0.2s;
    }
    .btn-edit {
        background-color: var(--secondary-color);
        color: white;
    }
    .btn-delete {
        background-color: var(--accent-red);
        color: white;
    }
    .btn-edit:hover {
        opacity: 0.85;
    }
    .btn-delete:hover {
        opacity: 0.85;
    }

    /* Responsive adjustments */
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
            display: none; 
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
            border-bottom: 3px solid var(--accent-yellow);
        }
        .menu-item.logout {
            margin-top: 0;
            border-top: none;
            background-color: var(--accent-red);
            border-radius: 6px;
            color: white;
        }
        .content-area {
            margin-left: 0; 
            width: 100%;
            padding: 20px 15px;
            box-sizing: border-box;
        }
        .content-card, .table-card {
            padding: 20px 16px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 1.5rem;
        }
        .styled-table, .styled-table thead, .styled-table tbody, .styled-table th, .styled-table td, .styled-table tr { 
            display: block; 
        }
        .styled-table thead tr { 
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        .styled-table tr { 
            border: 1px solid #e5e7eb;
            margin-bottom: 15px;
            border-radius: 12px;
            background: #fff;
            padding: 10px 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            box-sizing: border-box;
        }
        .styled-table td { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            border-bottom: 1px dashed #eee; 
            padding: 8px 0;
            text-align: right;
            font-size: 0.85rem;
            word-break: break-word;
        }
        .styled-table td:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .styled-table td:before { 
            position: static;
            width: auto;
            white-space: nowrap;
            text-align: left;
            font-weight: 700;
            color: var(--primary-color);
            margin-right: 12px;
            flex-shrink: 0;
            font-size: 0.8rem;
        }
        /* Labeling for responsive table */
        .styled-table td:nth-of-type(1):before { content: "No"; }
        .styled-table td:nth-of-type(2):before { content: "Nama Admin"; }
        .styled-table td:nth-of-type(3):before { content: "Username"; }
        .styled-table td:nth-of-type(4):before { content: "Ekskul"; }
        .styled-table td:nth-of-type(5):before { content: "Dibuat Pada"; }
        .styled-table td:nth-of-type(6) { 
            justify-content: flex-end; 
        }
        .styled-table td:nth-of-type(6):before { 
            content: "Aksi"; 
            margin-right: auto;
        }
    }

    @media (max-width: 480px) {
        .content-area {
            padding: 16px 10px;
        }
        .content-card, .table-card {
            padding: 16px 12px;
            border-radius: 10px;
        }
        .title {
            font-size: 1.3rem;
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
        .action-buttons a {
            padding: 5px 8px;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 375px) {
        .content-area {
            padding: 12px 6px;
        }
        .title {
            font-size: 1.15rem;
        }
        .styled-table tr {
            padding: 8px 10px;
        }
    }
</style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="pic/logo1.png" class="sidebar-logo" onerror="this.src='https://placehold.co/60x60/0D47A1/fff?text=Logo';">
            <h2>Superadmin</h2>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_superadmin.php" class="menu-item">🏠 Dashboard</a>
            <a href="kelola_admin.php" class="menu-item active">👥 Kelola Admin</a>
            <a href="kelola_ekskul.php" class="menu-item">📚 Kelola Ekskul</a>
            <a href="data_pendaftar.php" class="menu-item">👤 Data Akun Siswa</a> <!-- BARU DITAMBAHKAN -->
            <a href="logout.php" class="menu-item logout">🚪 Logout</a>
        </nav>
    </aside>

    <main class="content-area">

        <div class="content-card">
            <h1 class="title">Kelola Admin Ekskul</h1>
            <p class="subtitle">Anda dapat menambah, mengubah, atau menghapus admin ekskul.</p>
        </div>

        <div class="table-card">

            <div class="table-header">
                <h2>Daftar Admin</h2>
                <a href="tambah_admin.php" class="btn-add">+ Tambah Admin</a>
            </div>
            
            <?php if (isset($error_message)): ?>
                <p style="color: red; padding: 10px 0;"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Admin</th>
                        <th>Username</th>
                        <th>Ekskul</th>
                        <th>Dibuat Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $no = 1; 
                if ($result && $result->num_rows > 0):
                    while ($r = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($r['nama_admin']) ?></td>
                        <td><?= htmlspecialchars($r['username']) ?></td>
                        <td><?= htmlspecialchars($r['ekskul_dinaungi']) ?></td>
                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                        <td class="action-buttons">
                            <a href="edit_admin.php?id=<?= $r['id_admin'] ?>" class="btn-edit">✏️ Edit</a>
                            <a href="hapus_admin.php?id=<?= $r['id_admin'] ?>" class="btn-delete" onclick="return confirm('Yakin hapus admin ini?')">🗑️ Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; 
                else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Tidak ada data admin ditemukan.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</div>

</body>
</html>