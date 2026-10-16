<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['superadmin_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
    echo "<script>alert('❌ Silakan login sebagai superadmin!'); window.location.href='login_superadmin.php';</script>";
    exit;
}

$result = $conn->query("SELECT * FROM table_ekskul ORDER BY id_ekskul DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kelola Ekskul</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
    body { background-color: #f4f7f9; color: #333; }

    .layout { display: flex; min-height: 100vh; }

    /* === SAMAKAN SIDEBAR DENGAN DASHBOARD SUPERADMIN === */

.sidebar {
    width: 250px;
    background-color: #0D47A1;
    color: white;
    padding: 20px 0;
    display: flex;
    flex-direction: column;
    box-shadow: 4px 0 10px rgba(0,0,0,0.1);
    position: fixed;
    height: 100vh;
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

/* >>> INI BAGIAN YANG BIKIN TULISAN SUPERAADMIN PERSIS <<< */
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
    color: rgba(255,255,255,0.85);
    font-weight: 600;
    transition: background-color 0.2s, color 0.2s;
    border-left: 4px solid transparent;
}

.menu-item:hover {
    background-color: rgba(0, 0, 0, 0.2);
    color: white;
}

.menu-item.active {
    background-color: #1976D2;
    border-left: 4px solid #FFC107;
    color: white;
}

.menu-item.logout {
    margin-top: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

    .content-area { flex-grow: 1; margin-left: 250px; padding: 30px; }
    .content-card, .table-card {
        background: white; border-radius: 12px; padding: 30px; margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 5px; }
    .sub-greet { color: #666; font-size: 0.95rem; margin-top: 5px; }

    .table-header {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
    }
    .table-header h2 { font-size: 1.4rem; font-weight: 700; }
    .btn-add {
        background-color: #4CAF50; color: white; padding: 10px 15px;
        border-radius: 8px; font-weight: 600; text-decoration: none; transition: 0.2s;
    }
    .btn-add:hover { background-color: #43A047; }

    .styled-table { width: 100%; border-collapse: collapse; text-align: left; }
    .styled-table th, .styled-table td {
        padding: 12px 15px; border-bottom: 1px solid #ddd;
    }
    .styled-table th {
        background-color: #f0f0f0; font-weight: 700; text-transform: uppercase; font-size: 0.85rem;
    }
    .styled-table tr:hover { background-color: #f9f9f9; }

    .thumb {
        width: 70px; height: 70px; object-fit: cover; border-radius: 8px;
        border: 1px solid #ddd;
    }

    .btn-link {
        padding: 6px 10px; background-color: #0288D1; color: white;
        border-radius: 6px; text-decoration: none; font-size: 0.85rem;
    }

    .btn-edit, .btn-delete {
        padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;
        font-weight: 600; margin-right: 5px;
    }
    .btn-edit { background-color: #1976D2; color: white; }
    .btn-delete { background-color: #D32F2F; color: white; }
    .btn-edit:hover { background-color: #1565C0; }
    .btn-delete:hover { background-color: #B71C1C; }
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
            <a href="kelola_admin.php" class="menu-item">👥 Kelola Admin</a>
            <a href="kelola_ekskul.php" class="menu-item active">📚 Kelola Ekskul</a>
            <a href="data_pendaftar.php" class="menu-item">👤 Data Akun Siswa</a> <!-- BARU DITAMBAHKAN -->
            <a href="logout.php" class="menu-item logout">🚪 Logout</a>
        </nav>
    </aside>

    <main class="content-area">

        <div class="content-card">
            <h1>Kelola Ekskul</h1>
            <p class="sub-greet">Atur data seluruh ekskul di sekolah.</p>
        </div>

        <div class="table-card">

            <div class="table-header">
                <h2>Daftar Ekskul</h2>
                <a href="tambah_ekskul.php" class="btn-add">+ Tambah Ekskul</a>
            </div>

            <table class="styled-table">
                <tr>
                    <th>No</th>
                    <th>Nama Ekskul</th>
                    <th>Deskripsi</th>
                    <th>Link Form</th>
                    <th>Gambar</th>
                    <th>Aksi</th>
                </tr>

                <?php $no = 1; while ($r = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>

                    <td><?= htmlspecialchars($r['nama_ekskul']) ?></td>

                    <td style="max-width:260px;">
                        <?= nl2br(substr(htmlspecialchars($r['deskripsi']), 0, 80)) ?>...
                    </td>

                    <td>
                        <?php if ($r['link_gform']): ?>
                            <a href="<?= htmlspecialchars($r['link_gform']) ?>" target="_blank" class="btn-link">Buka</a>
                        <?php else: ?><i>-</i><?php endif; ?>
                    </td>

                    <td>
                        <?php if ($r['gambar'] && file_exists("uploads/".$r['gambar'])): ?>
                            <img src="uploads/<?= $r['gambar'] ?>" class="thumb">
                        <?php else: ?><i>-</i><?php endif; ?>
                    </td>

                    <td>
                        <a href="edit_ekskul_admin.php?id=<?= $r['id_ekskul'] ?>" class="btn-edit">✏️</a>
                        <a href="hapus_ekskul.php?id=<?= $r['id_ekskul'] ?>" class="btn-delete"
                           onclick="return confirm('Hapus ekskul ini?')">🗑️</a>
                    </td>
                </tr>
                <?php endwhile; ?>

            </table>

        </div>

    </main>

</div>

</body>
</html>