<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('Silakan login dulu!'); window.location='login_admin.php';</script>";
    exit;
}

$ekskul = $_GET['ekskul'] ?? '';

$stmt = $conn->prepare("SELECT * FROM Table_pendaftaran WHERE ekskul=? ORDER BY tanggal_daftar DESC");
$stmt->bind_param("s", $ekskul);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pendaftaran - <?= htmlspecialchars($ekskul) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="global.css">
<link rel="stylesheet" href="admin.css">
<style>
    body {
        font-family: 'Inter', sans-serif;
        background: #f4f7fb;
        margin: 0;
        padding: 30px 15px;
        color: #1f1f25;
    }
    .page-title {
        font-size: 1.6rem;
        font-weight: 800;
        margin-bottom: 20px;
    }
    .styled-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
    }
    .styled-table th, .styled-table td {
        padding: 12px 14px;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 0.9rem;
    }
    .styled-table th {
        background: #f8fafc;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        color: #4b5563;
    }
    .btn-action-accept {
        display: inline-block;
        padding: 5px 10px;
        background: #28a745;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .btn-action-reject {
        display: inline-block;
        padding: 5px 10px;
        background: #dc3545;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .btn-back-link {
        display: inline-block;
        margin-top: 20px;
        text-decoration: none;
        color: #2d66ff;
        font-weight: 600;
        font-size: 0.95rem;
    }
    .btn-back-link:hover {
        text-decoration: underline;
    }
    @media (max-width: 480px) {
        body {
            padding: 15px 10px;
        }
        .page-title {
            font-size: 1.3rem;
        }
    }
</style>
</head>
<body>
<div class="admin-container">
    <h1 class="page-title">Daftar Pendaftar Ekskul: <?= htmlspecialchars($ekskul) ?></h1>
    <div class="table-responsive">
        <table class="styled-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Tanggal Daftar</th>
                <th>Bukti SS</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($r = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($r['nama_lengkap'] ?? $r['nama'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['kelas'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['tanggal_daftar'] ?? '') ?></td>
                <td>
                    <?php if (!empty($r['bukti_ss'])): ?>
                        <a href="uploads/<?= htmlspecialchars($r['bukti_ss']) ?>" target="_blank" style="color: #2d66ff; font-weight: 600;">Lihat</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><strong><?= htmlspecialchars($r['status'] ?? '') ?></strong></td>
                <td>
                    <a href="ubah_status.php?id=<?= $r['id_pendaftaran'] ?? $r['id'] ?>&status=Diterima&ekskul=<?= urlencode($ekskul) ?>" class="btn-action-accept">Terima</a>
                    <a href="ubah_status.php?id=<?= $r['id_pendaftaran'] ?? $r['id'] ?>&status=Ditolak&ekskul=<?= urlencode($ekskul) ?>" class="btn-action-reject">Tolak</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px; color: #6b7280;">Belum ada data pendaftar.</td>
            </tr>
        <?php endif; ?>
        </tbody>
        </table>
    </div>
    <a href="dashboard_admin.php" class="btn-back-link">← Kembali ke Dashboard</a>
</div>
</body>
</html>
