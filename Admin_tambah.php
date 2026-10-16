<?php
session_start();
include 'koneksi.php';

if(!isset($_SESSION['admin_id'])){
    echo "<script>alert('Silakan login dulu!'); window.location.href='login_admin.php';</script>";
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nama = trim($_POST['nama_ekskul']);
    $deskripsi = trim($_POST['deskripsi']);
    $link = trim($_POST['link_gform']);

    // Upload gambar (jika ada)
    $gambar = "";
    if(!empty($_FILES['gambar']['name'])){
        $gambar = basename($_FILES['gambar']['name']);
        $target_path = "uploads/" . $gambar;
        move_uploaded_file($_FILES['gambar']['tmp_name'], $target_path);
    }

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO Table_ekskul (nama_ekskul, deskripsi, link_gform, gambar, tanggal_dibuat) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $nama, $deskripsi, $link, $gambar);
    $stmt->execute();
    $stmt->close();

    // === BAGIAN OTOMATIS BUAT FILE BARU ===
    $nama_file = strtolower(str_replace(' ', '_', $nama)) . '.php';
    $file_path = __DIR__ . '/' . $nama_file;

    // TEMPLATE PAGE (mengambil template yang kamu kasih)
    $page_content = <<<PHP
<?php
include 'koneksi.php';
session_start();

if (!isset(\$_SESSION['siswa_nama'])) {
    header("Location: index.php");
    exit;
}

// Ambil data ekskul dari database
\$nama_ekskul = "{$nama}";
\$stmt = \$conn->prepare("SELECT * FROM Table_ekskul WHERE nama_ekskul=?");
\$stmt->bind_param("s", \$nama_ekskul);
\$stmt->execute();
\$data = \$stmt->get_result()->fetch_assoc();
\$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars(\$nama_ekskul) ?> - SMKN 1 Kota Bekasi</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <div class="profile">
      <img src="web ekskul pic/Home (Logo)2.png" alt="Logo Sekolah SMKN 1 Bekasi" class="logo" />
      <div>
        <h1><?= htmlspecialchars(\$nama_ekskul) ?></h1>
        <h2>SMK Negeri 1 Kota Bekasi</h2>
      </div>
    </div>
    <a href="pilihan.php" class="btn-home">HOME</a>
  </header>

  <main class="ekskul-content">
    <h2 class="title"><?= htmlspecialchars(\$nama_ekskul) ?></h2>
    <div class="content-box">
      <img src="uploads/<?= htmlspecialchars(\$data['gambar'] ?? '') ?>" alt="<?= htmlspecialchars(\$nama_ekskul) ?>" class="ekskul-img" />
      <div class="ekskul-desc">
        <p><?= htmlspecialchars(\$data['deskripsi'] ?? 'Belum ada deskripsi.') ?></p>
        <hr><br>
        <a href="<?= htmlspecialchars(\$data['link_gform'] ?? '#') ?>" target="_blank" class="btn-form">Isi Google Form</a>
        <br><br>

        <form action="daftar_ekskul.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="nama_ekskul" value="<?= htmlspecialchars(\$nama_ekskul) ?>">
          <label>Upload Bukti Screenshot (Setelah Isi GForm):</label><br>
          <input type="file" name="bukti_ss" accept="image/*" required><br><br>
          <button type="submit" class="btn-daftar">DAFTAR</button>
        </form>
      </div>
    </div>
  </main>
</body>
</html>
PHP;

    // Simpan halaman otomatis
    file_put_contents($file_path, $page_content);

    echo "<script>
        alert('✅ Ekskul baru berhasil ditambahkan dan halaman {$nama_file} sudah dibuat!');
        window.location='dashboard_admin.php';
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Ekskul</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="global.css">
<style>
    body {
        font-family: 'Inter', sans-serif;
        background: #f4f7fb;
        margin: 0;
        padding: 30px 15px;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        box-sizing: border-box;
    }
    .form-container { 
        width: 100%; 
        max-width: 500px; 
        margin: auto; 
        padding: 30px; 
        background: #ffffff;
        border: 1px solid #e5e7eb; 
        border-radius: 14px; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        box-sizing: border-box;
    }
    .form-container h1 {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1f1f25;
        margin-top: 0;
        margin-bottom: 20px;
        text-align: center;
    }
    label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    input[type="text"], input[type="url"], textarea { 
        width: 100%; 
        padding: 10px 12px; 
        margin-bottom: 15px; 
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }
    input[type="text"]:focus, input[type="url"]:focus, textarea:focus {
        border-color: #2d66ff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(45, 102, 255, 0.15);
    }
    input[type="file"] {
        width: 100%;
        max-width: 100%;
        margin-bottom: 20px;
        font-size: 0.9rem;
        box-sizing: border-box;
    }
    textarea {
        resize: vertical;
        min-height: 100px;
    }
    .btn-submit, .btn-kembali { 
        display: inline-block;
        padding: 11px 20px; 
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.95rem;
        text-decoration: none; 
        text-align: center;
        box-sizing: border-box;
        transition: background-color 0.2s;
    }
    .btn-submit { 
        background-color: #198754; 
        color: #fff; 
        border: none; 
        cursor: pointer;
        width: 100%;
        margin-bottom: 10px;
    }
    .btn-submit:hover {
        background-color: #157347;
    }
    .btn-kembali { 
        background-color: transparent; 
        color: #6c757d; 
        border: 1px solid #d1d5db; 
        width: 100%;
    }
    .btn-kembali:hover {
        background-color: #f3f4f6;
        color: #1f1f25;
    }
    @media (max-width: 480px) {
        body {
            padding: 16px 10px;
        }
        .form-container {
            padding: 20px 15px;
            border-radius: 10px;
        }
        .form-container h1 {
            font-size: 1.25rem;
        }
        input[type="text"], input[type="url"], textarea {
            padding: 9px 10px;
            font-size: 0.9rem;
        }
    }
    @media (max-width: 375px) {
        body {
            padding: 12px 6px;
        }
        .form-container {
            padding: 16px 12px;
        }
    }
</style>
</head>
<body>
<main class="form-container">
    <h1>Tambah Ekskul Baru</h1>
    <form method="post" enctype="multipart/form-data">
        <label>Nama Ekskul</label>
        <input type="text" name="nama_ekskul" placeholder="Contoh: Paskibra" required>

        <label>Deskripsi</label>
        <textarea name="deskripsi" placeholder="Tuliskan deskripsi ekskul..." required></textarea>

        <label>Link Google Form</label>
        <input type="url" name="link_gform" placeholder="https://docs.google.com/forms/...">

        <label>Gambar Ekskul</label>
        <input type="file" name="gambar">

        <button type="submit" class="btn-submit">Tambah Ekskul</button>
        <a href="dashboard_admin.php" class="btn-kembali">Kembali</a>
    </form>
</main>
</body>
</html>
