<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION['siswa_nama'])) {
    header("Location: index.php");
    exit;
}

// Ambil data ekskul dari database
$nama_ekskul = "ballet";
$stmt = $conn->prepare("SELECT * FROM Table_ekskul WHERE nama_ekskul=?");
$stmt->bind_param("s", $nama_ekskul);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($nama_ekskul) ?> - SMKN 1 Kota Bekasi</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <div class="profile">
      <img src="web ekskul pic/Home (Logo)2.png" alt="Logo Sekolah SMKN 1 Bekasi" class="logo" />
      <div>
        <h1><?= htmlspecialchars($nama_ekskul) ?></h1>
        <h2>SMK Negeri 1 Kota Bekasi</h2>
      </div>
    </div>
    <a href="pilihan.php" class="btn-home">HOME</a>
  </header>

  <main class="ekskul-content">
    <h2 class="title"><?= htmlspecialchars($nama_ekskul) ?></h2>
    <div class="content-box">
      <img src="uploads/<?= htmlspecialchars($data['gambar'] ?? '') ?>" alt="<?= htmlspecialchars($nama_ekskul) ?>" class="ekskul-img" />
      <div class="ekskul-desc">
        <p><?= htmlspecialchars($data['deskripsi'] ?? 'Belum ada deskripsi.') ?></p>
        <hr><br>
        <a href="<?= htmlspecialchars($data['link_gform'] ?? '#') ?>" target="_blank" class="btn-form">Isi Google Form</a>
        <br><br>

        <form action="daftar_ekskul.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="nama_ekskul" value="<?= htmlspecialchars($nama_ekskul) ?>">
          <label>Upload Bukti Screenshot (Setelah Isi GForm):</label><br>
          <input type="file" name="bukti_ss" accept="image/*" required><br><br>
          <button type="submit" class="btn-daftar">DAFTAR</button>
        </form>
      </div>
    </div>
  </main>
</body>
</html>