<?php
session_start();
// PERHATIAN: Pastikan file koneksi.php tersedia di lingkungan ini
include 'koneksi.php';

// Cek koneksi
if (!isset($conn)) {
    // Memberikan pesan kesalahan yang lebih jelas
    die("ERROR: Koneksi database (\$conn) tidak ditemukan. Pastikan file 'koneksi.php' berfungsi.");
}

// Cek login siswa
if (!isset($_SESSION['nis'])) {
    // Pengganti alert()
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('message-box-text').innerText = '❌ Silakan login dulu sebagai siswa!';
                document.getElementById('message-box').style.display = 'flex';
                document.getElementById('message-box-ok').onclick = function() {
                    window.location.href='login_siswa.php';
                };
            });
          </script>";
    exit;
}

// 1. Ambil NIS dari session
$nisSiswa = $_SESSION['nis']; 

// 2. Ambil Nama Lengkap dan Kelas dari table_siswa
$stmtSiswa = $conn->prepare("SELECT nama_lengkap, kelas FROM table_siswa WHERE nis = ?");
$stmtSiswa->bind_param("i", $nisSiswa);
$stmtSiswa->execute();
$dataSiswa = $stmtSiswa->get_result()->fetch_assoc();
$stmtSiswa->close();

// Pastikan data siswa ada
if (!$dataSiswa) die("❌ Data siswa (nama_lengkap, kelas) tidak ditemukan di database!");

$namaLengkapSiswa = $dataSiswa['nama_lengkap'];
$kelasSiswa = $dataSiswa['kelas'];


// Ambil nama ekskul dari URL
$namaEkskul = $_GET['ekskul'] ?? '';
if ($namaEkskul === '') die("❌ Ekskul tidak ditemukan!");

// Ambil data ekskul LENGKAP.
$stmt = $conn->prepare("SELECT nama_ekskul, deskripsi, link_gform, gambar, link_grup_wa, link_lainnya, link_instagram, link_twitter FROM table_ekskul WHERE nama_ekskul = ?");
$stmt->bind_param("s", $namaEkskul);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Cek jika data ekskul tidak ditemukan
if (!$data) die("❌ Ekskul tidak ditemukan di database!");

// Ambil Link dari data ekskul
$linkGrupWaPersonal = $data['link_grup_wa'] ?? '';  // Kontak Personal
$linkGrupWaEkskul   = $data['link_lainnya'] ?? '';  // Link Group WA Ekskul
$linkInstagram      = $data['link_instagram'] ?? '';
$linkTwitter        = $data['link_twitter'] ?? '';


// Cek status pendaftaran siswa
$check = $conn->prepare("SELECT status, bukti_ss FROM table_pendaftaran WHERE nis=? AND nama_ekskul=?");
$check->bind_param("is", $nisSiswa, $namaEkskul);
$check->execute();
$cekDaftar = $check->get_result()->fetch_assoc();
$check->close();

// Handle upload bukti SS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_ss'])) {
    $file = $_FILES['bukti_ss'];
    $targetDir = "uploads/";
    
    // Pastikan folder uploads ada dan memiliki izin tulis (chmod 0777)
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
             echo "<script>
                     document.addEventListener('DOMContentLoaded', function() {
                         showMessage('❌ Gagal membuat folder uploads. Hubungi administrator.');
                     });
                   </script>";
             exit;
        }
    }
    
    // Pengecekan ukuran file (misalnya, maksimal 5MB)
    if ($file['size'] > 5000000) {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showMessage('❌ Ukuran file terlalu besar! Maksimal 5MB.');
                });
              </script>";
        exit;
    }

    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array(strtolower($fileExt), $allowedTypes)) {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showMessage('❌ Hanya file JPG, JPEG, PNG, atau PDF yang diperbolehkan.');
                });
              </script>";
        exit;
    }

    // Membersihkan nama file untuk keamanan
    $safeFileName = preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($file['name']));
    // Membuat nama file yang unik dan aman
    $targetFile = $targetDir . time() . '_' . $nisSiswa . '_' . $safeFileName;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        
        // Cek apakah ada bukti lama yang perlu dihapus (opsional, untuk menghemat ruang)
        if ($cekDaftar && !empty($cekDaftar['bukti_ss']) && file_exists($cekDaftar['bukti_ss'])) {
            // unlink($cekDaftar['bukti_ss']); // Uncomment jika ingin menghapus file lama
        }

        if ($cekDaftar) {
            // Update jika siswa sudah pernah daftar
            $update = $conn->prepare("UPDATE table_pendaftaran SET bukti_ss=?, status='Menunggu' WHERE nis=? AND nama_ekskul=?");
            $update->bind_param("sis", $targetFile, $nisSiswa, $namaEkskul);
            $update->execute();
            $update->close();
        } else {
            // Query INSERT baru, menyertakan nama_lengkap dan kelas
            $insert = $conn->prepare("INSERT INTO table_pendaftaran (nis, nama_lengkap, kelas, nama_ekskul, status, bukti_ss) VALUES (?, ?, ?, ?, 'Menunggu', ?)");
            // i (nis) s (nama_lengkap) s (kelas) s (nama_ekskul) s (bukti_ss)
            $insert->bind_param("issss", $nisSiswa, $namaLengkapSiswa, $kelasSiswa, $namaEkskul, $targetFile);
            $insert->execute();
            $insert->close();
        }
        
        // Perbarui data $cekDaftar setelah berhasil upload
        $cekDaftar = [
            'status' => 'Menunggu',
            'bukti_ss' => $targetFile
        ];

        // Redirect untuk mencegah re-submit form
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showMessage('✅ Bukti berhasil diupload! Status pendaftaran Anda kini: Menunggu.', function() {
                        window.location.href='halaman_ekskul.php?ekskul=".urlencode($namaEkskul)."';
                    });
                });
              </script>";
        exit;
    } else {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showMessage('❌ Upload gagal. Pastikan file tidak rusak dan coba lagi.');
                });
              </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($namaEkskul) ?> | Detail Ekskul</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* =======================================================
   GENERAL STYLES & VARIABLES
======================================================= */
:root {
    --primary-blue: #0D47A1;
    --secondary-blue: #1976D2;
    --accent-green: #4CAF50;
    --accent-red: #F44336;
    --accent-orange: #FF9800;
    --card-bg: #ffffff;
    --bg-light: #f4f7f9;
    --text-dark: #333;
    --text-medium: #6b7280;
    --radius-lg: 12px;
    --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.08);
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
    line-height: 1.6;
    padding: 30px 0;
}

/* Custom Message Box (Ganti alert) */
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
}

.message-content p {
    font-size: 18px;
    margin-bottom: 20px;
    font-weight: 600;
    color: var(--text-dark);
}

.message-content button {
    padding: 10px 25px;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    transition: background-color 0.3s;
}

/* =======================================================
   LAYOUT & CONTAINER
======================================================= */
.container {
    width: 90%;
    max-width: 1000px;
    margin: auto;
    padding: 30px;
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
}

.header-back {
    margin-bottom: 25px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    padding: 8px 15px;
    background: #6c757d;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.2s;
    font-size: 0.9rem;
}

.btn-back:hover {
    background: #5a6268;
}

/* =======================================================
   EXSKUL DETAIL SECTION
======================================================= */
.judul-ekskul {
    font-size: 2.5rem;
    font-weight: 900;
    color: var(--primary-blue);
    margin-bottom: 10px;
    border-bottom: 3px solid var(--secondary-blue);
    padding-bottom: 10px;
}

.exskul-detail {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8faff;
    border-radius: var(--radius-lg);
}

.gambar-ekskul {
    width: 250px;
    height: 250px;
    object-fit: cover;
    border-radius: var(--radius-lg);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.deskripsi h3 {
    font-size: 1.2rem;
    color: var(--text-dark);
    margin-top: 0;
    margin-bottom: 8px;
    font-weight: 700;
}
.deskripsi p {
    margin-bottom: 15px;
    font-size: 1rem;
    color: var(--text-medium);
}

.btn-form {
    display: inline-block;
    padding: 12px 25px;
    margin-top: 10px;
    background-color: var(--accent-green);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    transition: background-color 0.3s, transform 0.2s;
    text-transform: uppercase;
    box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
}

.btn-form:hover {
    background-color: #388E3C;
    transform: translateY(-2px);
}

/* Social Media Section Styles */
.medsos-links {
    margin-top: 25px;
    padding-top: 15px;
    border-top: 1px dashed #ccc;
}

.medsos-links-row {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 10px;
}

.btn-medsos {
    display: inline-flex;
    align-items: center;
    padding: 10px 18px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
}

.btn-medsos:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
}

/* Instagram Gradient */
.btn-medsos.instagram {
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

/* Twitter/X Color */
.btn-medsos.twitter {
    background: #1DA1F2; /* Old Twitter Blue, adjust if using black X */
}

.btn-medsos.wa-personal {
    background: #075E54;
}

/* =======================================================
   STATUS & UPLOAD SECTION
======================================================= */
.section-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--primary-blue);
    margin-bottom: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.status-box {
    padding: 20px;
    border-radius: var(--radius-lg);
    margin-bottom: 30px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
    font-size: 1.1rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.status-text-row {
    display: flex;
    align-items: center;
    gap: 15px;
}

.status-icon {
    font-size: 2rem;
}

.status-menunggu { background-color: #fff3e0; color: var(--accent-orange); border: 1px solid var(--accent-orange); }
.status-diterima { background-color: #e8f5e9; color: var(--accent-green); border: 1px solid var(--accent-green); }
.status-ditolak { background-color: #ffebee; color: var(--accent-red); border: 1px solid var(--accent-red); }
.status-belum-daftar { background-color: #e3f2fd; color: var(--secondary-blue); border: 1px solid var(--secondary-blue); }

/* Tombol WA Group (Hanya muncul jika Diterima) */
.btn-wa-group {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    background-color: #25D366; /* WhatsApp Green */
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    transition: background-color 0.3s, transform 0.2s;
    box-shadow: 0 4px 10px rgba(37, 211, 102, 0.3);
}
.btn-wa-group:hover {
    background-color: #128C7E;
    transform: translateY(-2px);
}
.btn-wa-group span {
    margin-right: 8px;
    font-size: 1.2rem;
}

/* Upload Form */
.upload-bukti {
    background: #ffffff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: var(--radius-lg);
    margin-top: 20px;
}
.upload-bukti h3 {
    font-size: 1.1rem;
    margin-bottom: 15px;
    color: var(--primary-blue);
}

.upload-bukti form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
}

input[type="file"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    width: 100%;
    max-width: 350px;
    background: #f9f9f9;
}

.btn-submit {
    padding: 10px 20px;
    background-color: var(--secondary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.1s;
    box-shadow: 0 4px 10px rgba(25, 118, 210, 0.3);
}

.btn-submit:hover:not(:disabled) {
    background-color: var(--primary-blue);
    transform: translateY(-1px);
}

.btn-submit:disabled {
    background: #b0bec5;
    cursor: not-allowed;
    box-shadow: none;
}

.bukti-link {
    font-size: 0.95rem;
    margin-bottom: 10px;
}
.bukti-link a {
    color: var(--secondary-blue);
    font-weight: 600;
}

/* =======================================================
   RESPONSIVENESS
======================================================= */
@media (max-width: 768px) {
    .container {
        width: 100%;
        padding: 20px 15px;
        border-radius: 0;
    }
    .judul-ekskul {
        font-size: 2rem;
    }
    .exskul-detail {
        flex-direction: column;
        text-align: center;
        padding: 15px;
        gap: 20px;
    }
    .gambar-ekskul {
        width: 100%;
        max-width: 300px;
        height: auto;
        margin: 0 auto;
    }
    .upload-bukti form {
        align-items: stretch;
    }
    .status-text-row {
        flex-direction: row;
    }
    .medsos-links-row {
        justify-content: center;
    }
}
</style>
</head>
<body>

<!-- Custom Message Box (Ganti alert) -->
<div id="message-box" class="message-box">
    <div class="message-content">
        <p id="message-box-text"></p>
        <button id="message-box-ok">OK</button>
    </div>
</div>

<div class="container">

    <div class="header-back">
        <a href="pilihan.php" class="btn-back"><span>&larr;</span> Kembali ke Daftar Ekskul</a>
    </div>

    <h1 class="judul-ekskul"><?= htmlspecialchars($data['nama_ekskul']) ?></h1>

    <div class="exskul-detail">
        <!-- Tampilkan Gambar Ekskul -->
        <img 
            src="uploads/<?= htmlspecialchars($data['gambar']) ?>" 
            class="gambar-ekskul" 
            onerror="this.onerror=null;this.src='https://placehold.co/250x250/C5CAE9/0D47A1?text=Ekskul';"
            alt="<?= htmlspecialchars($data['nama_ekskul']) ?>">
        
        <div class="deskripsi">
            <h3>Deskripsi Ekskul:</h3>
            <p style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($data['deskripsi'])) ?></p>

            <h3>Info Pendaftaran:</h3>
            <?php if (!empty($data['link_gform'])): ?>
                <p>Silakan klik tombol di bawah untuk mengisi formulir pendaftaran Google Form.</p>
                <a href="<?= htmlspecialchars($data['link_gform']) ?>" target="_blank" class="btn-form">📝 Isi Form Pendaftaran</a>
            <?php else: ?>
                <p>Saat ini belum ada link pendaftaran yang tersedia. Harap hubungi Admin Ekskul melalui kontak yang tersedia.</p>
            <?php endif; ?>

            <!-- NEW: Medsos & Contact Section -->
            <?php if (!empty($linkInstagram) || !empty($linkTwitter) || !empty($linkGrupWaPersonal)): ?>
            <div class="medsos-links">
                <h3 style="margin:0;">Hubungi dan Ikuti Kami:</h3>
                <div class="medsos-links-row">
                    
                    <!-- WA Personal (Link Grup Wa kolom lama) -->
                    <?php if (!empty($linkGrupWaPersonal)): ?>
                        <a href="<?= htmlspecialchars($linkGrupWaPersonal) ?>" target="_blank" class="btn-medsos wa-personal" title="Hubungi Admin Ekskul via WhatsApp">
                            <span>&#128172;</span> Chat Admin
                        </a>
                    <?php endif; ?>

                    <!-- Instagram -->
                    <?php if (!empty($linkInstagram)): ?>
                        <a href="<?= htmlspecialchars($linkInstagram) ?>" target="_blank" class="btn-medsos instagram" title="Kunjungi Instagram Ekskul">
                            <span>&#128248;</span> Instagram
                        </a>
                    <?php endif; ?>

                    <!-- Twitter/X -->
                    <?php if (!empty($linkTwitter)): ?>
                        <a href="<?= htmlspecialchars($linkTwitter) ?>" target="_blank" class="btn-medsos twitter" title="Kunjungi Twitter/X Ekskul">
                            <span>&#128038;</span> Twitter/X
                        </a>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- STATUS PENDAFTARAN -->
    <h2 class="section-title">Status Pendaftaran Anda</h2>

    <?php 
    $statusClass = 'status-belum-daftar';
    $statusIcon = '❓';
    $statusText = 'Anda belum terdaftar untuk ekskul ini. Silakan isi form di atas.';
    $isAccepted = false; // Flag untuk mengecek apakah diterima

    if ($cekDaftar) {
        if ($cekDaftar['status'] === 'Menunggu') {
            $statusClass = 'status-menunggu';
            $statusIcon = '🕒';
            $statusText = 'Pendaftaran Anda sedang ditinjau oleh Admin Ekskul. Mohon ditunggu.';
        } elseif ($cekDaftar['status'] === 'Diterima') {
            $statusClass = 'status-diterima';
            $statusIcon = '✅';
            $statusText = 'Selamat! Anda telah resmi diterima di Ekskul ini. Silakan bergabung dengan grup WhatsApp.';
            $isAccepted = true; // Set flag diterima
        } elseif ($cekDaftar['status'] === 'Ditolak') {
            $statusClass = 'status-ditolak';
            $statusIcon = '❌';
            $statusText = 'Maaf, pendaftaran Anda ditolak. Hubungi Admin jika ada pertanyaan.';
        }
    }
    ?>
    
    <div class="status-box <?= $statusClass ?>">
        <div class="status-text-row">
            <span class="status-icon"><?= $statusIcon ?></span>
            <p style="font-weight: 700;"><?= $statusText ?></p>
        </div>
        
        <!-- LOGIKA TAMPILAN LINK GRUP WA EKSKUL (HANYA JIKA DITERIMA) -->
        <?php if ($isAccepted && !empty($linkGrupWaEkskul)): ?>
            <!-- Link Grup WA Ekskul menggunakan kolom link_lainnya -->
            <a href="<?= htmlspecialchars($linkGrupWaEkskul) ?>" target="_blank" class="btn-wa-group">
                <span>&#128172;</span> Gabung Grup WhatsApp Ekskul
            </a>
        <?php elseif ($isAccepted && empty($linkGrupWaEkskul)): ?>
             <p style="font-size:0.9rem; color:var(--text-dark); font-weight: 500;">(Admin belum memasukkan link grup WA Ekskul. Harap tunggu atau hubungi Admin Ekskul.)</p>
        <?php endif; ?>
    </div>

    <!-- UPLOAD BUKTI -->
    <div class="upload-bukti">
        <h3>Upload Bukti (Wajib setelah mengisi form)</h3>
        <p class="bukti-link">
            <?php if (!empty($cekDaftar['bukti_ss'])): ?>
                Bukti terakhir Anda sudah diupload. <a href="<?= htmlspecialchars($cekDaftar['bukti_ss']) ?>" target="_blank">Lihat File</a>
            <?php else: ?>
                Anda belum mengupload bukti screenshot pengisian form.
            <?php endif; ?>
        </p>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="bukti_ss" id="bukti_ss" accept=".jpg,.jpeg,.png,.pdf" required>
            <!-- Tombol submit dinonaktifkan secara default -->
            <button type="submit" class="btn-submit" id="submitBtn" disabled>📤 Submit Bukti Pendaftaran</button> 
            <p style="font-size:0.85rem; color:#777;">*Hanya menerima file JPG, PNG, atau PDF (Maks. 5MB).</p>
        </form>
    </div>

</div>

<script>
// Logic untuk Custom Message Box
function showMessage(text, callback) {
    const box = document.getElementById('message-box');
    const boxText = document.getElementById('message-box-text');
    const boxOk = document.getElementById('message-box-ok');
    
    boxText.innerText = text;
    box.style.display = 'flex';
    
    // Clear previous listener
    boxOk.onclick = null; 

    boxOk.onclick = function() {
        box.style.display = 'none';
        if (callback && typeof callback === 'function') {
            callback();
        }
    };
}

// Logic untuk mengaktifkan tombol submit
const fileInput = document.getElementById('bukti_ss');
const submitBtn = document.getElementById('submitBtn');

// Mengaktifkan tombol submit HANYA JIKA file telah dipilih.
fileInput.addEventListener('change', function() {
    submitBtn.disabled = !fileInput.value;
});

// Mengecek jika ada pesan yang harus ditampilkan saat DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi showMessage dipanggil di bagian PHP jika ada error atau sukses upload
});
</script>

</body>
</html>