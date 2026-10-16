<?php
session_start();
// Pastikan file koneksi.php tersedia di lingkungan ini
include 'koneksi.php';

// Handler untuk pesan notifikasi
$message = null;
$message_type = null;

// Validasi superadmin
if (!isset($_SESSION['superadmin_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
    // Penggunaan JavaScript kustom untuk menggantikan alert()
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('message-box-text').innerText = '❌ Silakan login sebagai superadmin!';
                document.getElementById('message-box').style.display = 'flex';
                document.getElementById('message-box-ok').onclick = function() {
                    window.location.href='login_superadmin.php';
                };
            });
          </script>";
    // Menambahkan fallback HTML jika JS gagal dimuat
    echo "
        <div style='text-align: center; padding: 50px;'>
            <p>❌ Silakan login sebagai superadmin!</p>
            <a href='login_superadmin.php'>Klik di sini untuk login</a>
        </div>
    ";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama           = trim($_POST['nama_ekskul']);
    $deskripsi      = trim($_POST['deskripsi']);
    $link_gform     = trim($_POST['link_gform']);
    
    // --- Variabel Baru berdasarkan struktur DB 11 kolom ---
    $wa_contact_num     = trim($_POST['wa_contact']); 
    $link_grup_wa_val   = trim($_POST['link_grup_wa']); 
    $link_instagram_val = trim($_POST['link_instagram']);
    $link_twitter_val   = trim($_POST['link_twitter']);
    $link_lainnya_val   = trim($_POST['link_lainnya']);

    $gambar         = ""; // Default kosong

    // --- Pemrosesan Nomor WhatsApp Perwakilan (untuk wa_contact) ---
    $wa_contact = null;
    if (!empty($wa_contact_num)) {
        // Hapus semua karakter non-digit
        $cleaned_number = preg_replace('/[^0-9]/', '', $wa_contact_num);

        // Jika nomor diawali '0', ubah menjadi '62'
        if (substr($cleaned_number, 0, 1) === '0') {
            $cleaned_number = '62' . substr($cleaned_number, 1);
        } elseif (substr($cleaned_number, 0, 2) !== '62' && strlen($cleaned_number) > 5) {
            // Asumsi jika tidak ada prefix dan panjang cukup, tambahkan '62'
            $cleaned_number = '62' . $cleaned_number;
        }
        $wa_contact = $cleaned_number; // Simpan nomor yang sudah dibersihkan dan diformat internasional
    }

    // --- File Upload Handling ---
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }
    
    // Periksa apakah ada gambar yang diupload
    if (empty($_FILES['gambar']['name']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        $message = '❌ Gambar ekskul wajib diunggah!';
        $message_type = 'error';
    } else {
        $file_tmp  = $_FILES['gambar']['tmp_name'];
        $file_name = time() . "_" . basename($_FILES['gambar']['name']);
        $target    = "uploads/" . $file_name;

        $allowed_ext = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_ext)) {
            if (move_uploaded_file($file_tmp, $target)) {
                $gambar = $file_name;
            } else {
                $message = '❌ Gagal mengunggah file gambar.';
                $message_type = 'error';
            }
        } else {
            $message = '❌ Format gambar tidak valid! (Hanya .jpg, .jpeg, .png, .gif)';
            $message_type = 'error';
        }
    }


    // --- Database Insertion (Hanya jika tidak ada error upload) ---
    if ($message_type !== 'error' && !empty($gambar)) {
        // --- Perbarui Statement SQL untuk 9 Kolom yang Diinput (total 11) ---
        $stmt = $conn->prepare("
            INSERT INTO table_ekskul (
                nama_ekskul, deskripsi, link_gform, gambar, 
                link_grup_wa, wa_contact, link_instagram, link_twitter, link_lainnya
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Parameter binding: 9 string
        $stmt->bind_param("sssssssss", 
            $nama, 
            $deskripsi, 
            $link_gform, 
            $gambar, 
            $link_grup_wa_val, // link_grup_wa
            $wa_contact,       // wa_contact (nomor perwakilan)
            $link_instagram_val, 
            $link_twitter_val, 
            $link_lainnya_val
        );

        if ($stmt->execute()) {
            $message = '✅ Ekskul berhasil ditambahkan!';
            $message_type = 'success';
            // Redirect setelah sukses untuk menghindari resubmission
            header("Location: kelola_ekskul.php");
            exit;
        } else {
            $message = '❌ Gagal menambah ekskul ke database. Coba lagi. Error: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Ekskul Baru</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ===========================================================
   CSS GABUNGAN: TEMA MODERN BLUE DASHBOARD
=========================================================== */

/* Variabel Warna */
:root {
    --bg-page: #eaf0ff;        /* Light Blue Background */
    --bg-navbar: #1f2a44;      /* Navy */
    --bg-card: #ffffff;        
    --primary: #2d66ff;        /* Bright Blue */
    --primary-hover: #1c4ad6;
    --primary-rgb: 45, 102, 255;
    --text-dark: #1f1f25;
    --text-medium: #4a4a50;
    --border-color: #d6d7db;
    --shadow-card: 0 8px 30px rgba(0,0,0,0.1);
    --radius: 12px;
    font-family: 'Inter', sans-serif;
}

/* --- BASE & LAYOUT --- */
body {
    margin: 0;
    padding: 0;
    background-color: var(--bg-page);
    color: var(--text-dark);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

/* --- PAGE WRAPPER & CARD --- */
.page-wrapper {
    width: 100%;
    max-width: 700px; /* Sedikit lebih lebar untuk deskripsi */
    margin-top: 50px;
    position: relative;
    padding-bottom: 20px;
}

.card {
    background: var(--bg-card);
    padding: 40px;
    border-radius: 20px;
    box-shadow: var(--shadow-card);
    border: 1px solid #e3e6f0;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.card h2 {
    font-size: 28px;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 30px;
    border-bottom: 3px solid var(--primary);
    display: inline-block;
    padding-bottom: 8px;
}

/* --- FORM STYLING --- */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

label {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 5px;
    display: block;
    font-size: 14px;
}

input[type="text"],
input[type="url"],
input[type="email"],
textarea,
input[type="file"] {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 10px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 16px;
    color: var(--text-dark);
    transition: all 0.3s;
    background-color: var(--bg-card);
}

textarea {
    resize: vertical;
    min-height: 120px;
    font-family: 'Inter', sans-serif;
}

input:focus,
textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.2);
    outline: none;
}

/* Penyesuaian untuk input file */
input[type="file"] {
    padding: 10px 15px;
    cursor: pointer;
    background-color: #f7f9fc;
    border: 1px dashed var(--border-color);
}
input[type="file"]:focus {
    border: 1px dashed var(--primary);
}

/* --- BUTTONS --- */
.back-btn {
    position: absolute;
    top: -50px;
    left: 0;
    background: var(--bg-card);
    color: var(--text-medium);
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.back-btn:hover {
    background: var(--bg-secondary);
    color: var(--primary);
    transform: translateY(-2px);
}

.btn-submit {
    margin-top: 20px;
    padding: 15px 25px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 6px 15px rgba(var(--primary-rgb), 0.4);
}

.btn-submit:hover {
    background: var(--primary-hover);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.5);
}

/* --- MESSAGE BOX (Custom Alert) --- */
.message-box {
    display: none; /* Default hidden */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
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
    width: 90%;
    box-sizing: border-box;
    animation: fadeIn 0.3s;
}

.message-content p {
    font-size: 18px;
    margin-bottom: 20px;
    font-weight: 600;
    color: var(--text-dark);
    word-break: break-word;
}

.message-content button {
    padding: 10px 25px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    transition: background-color 0.3s;
}

.message-content button:hover {
    background: var(--primary-hover);
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

/* --- RESPONSIVENESS --- */
@media (max-width: 700px) {
    .page-wrapper {
        margin-top: 20px;
    }
    .card {
        padding: 25px 20px;
        border-radius: 15px;
    }
    .card h2 {
        font-size: 22px;
        margin-bottom: 20px;
    }
    .back-btn {
        position: static;
        display: inline-block;
        margin-bottom: 15px;
        transform: none;
        width: 100%;
        text-align: center;
        box-sizing: border-box;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .btn-submit {
        font-size: 16px;
        padding: 12px 20px;
    }
}

@media (max-width: 480px) {
    body {
        padding: 15px 10px;
    }
    .card {
        padding: 20px 15px;
        border-radius: 12px;
    }
    .card h2 {
        font-size: 19px;
        margin-bottom: 16px;
    }
    input[type="text"],
    input[type="url"],
    input[type="email"],
    textarea,
    input[type="file"] {
        padding: 10px 12px;
        font-size: 14px;
    }
    .btn-submit {
        padding: 11px 16px;
        font-size: 15px;
    }
    .message-content {
        padding: 20px 15px;
    }
}

@media (max-width: 375px) {
    body {
        padding: 10px 6px;
    }
    .card {
        padding: 16px 10px;
    }
    .card h2 {
        font-size: 17px;
    }
}
</style>
</head>
<body>

<!-- Custom Message Box (Menggantikan alert()) -->
<div id="message-box" class="message-box">
    <div class="message-content">
        <p id="message-box-text"></p>
        <button id="message-box-ok">OK</button>
    </div>
</div>

<div class="page-wrapper">

    <!-- Tombol Kembali Dibuat Floating -->
    <a href="kelola_ekskul.php" class="back-btn">← Kembali ke Kelola Ekskul</a>

    <div class="card">
        <h2>Tambah Ekskul Baru</h2>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <label for="nama_ekskul">Nama Ekskul</label>
            <input type="text" id="nama_ekskul" name="nama_ekskul" placeholder="Contoh: Futsal, Pramuka, KIR" required>

            <label for="deskripsi">Deskripsi Singkat Ekskul</label>
            <textarea id="deskripsi" name="deskripsi" placeholder="Jelaskan secara singkat tentang kegiatan ekskul ini..." required></textarea>

            <label for="link_gform">Link Google Form Pendaftaran</label>
            <input type="url" id="link_gform" name="link_gform" placeholder="Contoh: https://forms.gle/xxxxxxxxxxxx" required>
            
            <hr style="border: 1px dashed var(--border-color); margin: 15px 0;">
            <h3 style="font-size: 1.2rem; margin-bottom: 10px; color: var(--primary);">Data Kontak & Media Sosial</h3>

            <!-- wa_contact (Nomor Perwakilan) -->
            <label for="wa_contact">Nomor WhatsApp Perwakilan (Cth: 081234567890)</label>
            <input type="text" id="wa_contact" name="wa_contact" placeholder="Masukkan nomor WhatsApp perwakilan ekskul (hanya angka)">
            <small style="color: var(--text-medium); margin-top: -10px;">*Nomor ini akan diformat ke link wa.me untuk dihubungi siswa.</small>

            <!-- link_grup_wa -->
            <label for="link_grup_wa">Link Grup WhatsApp (Link Invite)</label>
            <input type="url" id="link_grup_wa" name="link_grup_wa" placeholder="Contoh: https://chat.whatsapp.com/GrupInviteLink">

            <!-- link_instagram -->
            <label for="link_instagram">Link Instagram</label>
            <input type="url" id="link_instagram" name="link_instagram" placeholder="Contoh: https://www.instagram.com/nama_ekskul">

            <!-- link_twitter -->
            <label for="link_twitter">Link Twitter/X</label>
            <input type="url" id="link_twitter" name="link_twitter" placeholder="Contoh: https://twitter.com/nama_ekskul">
            
            <!-- link_lainnya -->
            <label for="link_lainnya">Link Lainnya (Contoh: TikTok, Telegram, Website)</label>
            <input type="url" id="link_lainnya" name="link_lainnya" placeholder="Masukkan link media sosial/website lain">
            
            <hr style="border: 1px dashed var(--border-color); margin: 15px 0;">

            <label for="gambar">Gambar/Logo Ekskul</label>
            <input type="file" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.gif" required>

            <button type="submit" class="btn-submit">
                ✨ Simpan Data Ekskul
            </button>

        </form>
    </div>

</div>

<script>
    // Menampilkan custom message box jika ada pesan dari PHP
    document.addEventListener('DOMContentLoaded', function() {
        const messageBox = document.getElementById('message-box');
        const messageText = document.getElementById('message-box-text');
        const messageOkBtn = document.getElementById('message-box-ok');

        <?php if ($message): ?>
            messageText.innerText = "<?= $message_type === 'success' ? '✅ ' : '❌ ' ?> <?= htmlspecialchars($message) ?>";
            messageBox.style.display = 'flex';
            
            messageOkBtn.onclick = function() {
                messageBox.style.display = 'none';
                // Jika sukses, biarkan redirect PHP yang bekerja. Jika error, hanya tutup box.
            };
        <?php endif; ?>
    });
</script>

</body>
</html>