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
<title>Kelola Pendaftaran - <?= htmlspecialchars($ekskul) ?></title>
<link rel="stylesheet" href="global.css">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<h1>Daftar Pendaftar Ekskul: <?= htmlspecialchars($ekskul) ?></h1>
<table border="1" cellpadding="10" cellspacing="0">
<tr>
  <th>Nama</th>
  <th>Kelas</th>
  <th>Tanggal Daftar</th>
  <th>Bukti SS</th>
  <th>Status</th>
  <th>Aksi</th>
</tr>
<?php while ($r = $result->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($r['nama']) ?></td>
  <td><?= htmlspecialchars($r['kelas']) ?></td>
  <td><?= htmlspecialchars($r['tanggal_daftar']) ?></td>
  <td>
    <?php if ($r['bukti_ss']): ?>
      <a href="uploads/<?= htmlspecialchars($r['bukti_ss']) ?>" target="_blank">Lihat</a>
    <?php else: ?>
      -
    <?php endif; ?>
  </td>
  <td><?= htmlspecialchars($r['status']) ?></td>
  <td>
    <a href="ubah_status.php?id=<?= $r['id'] ?>&status=Diterima&ekskul=<?= urlencode($ekskul) ?>">Terima</a> |
    <a href="ubah_status.php?id=<?= $r['id'] ?>&status=Ditolak&ekskul=<?= urlencode($ekskul) ?>">Tolak</a>
  </td>
</tr>
<?php endwhile; ?>
</table>
<br>
<a href="dashboard_admin.php">← Kembali ke Dashboard</a>
</body>
</html>
