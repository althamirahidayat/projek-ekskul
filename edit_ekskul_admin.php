<?php
session_start();
// Pastikan file koneksi sudah termasuk dan terhubung ke database
include 'koneksi.php';

// ---- KONFIGURASI ROLE & AUTHENTIKASI ----
$isSuperadmin = isset($_SESSION['superadmin_id']) && ($_SESSION['role'] ?? '') === 'superadmin';
$isAdminEkskul = isset($_SESSION['admin_id']) && ($_SESSION['role'] ?? '') === 'admin';

if (!$isSuperadmin && !$isAdminEkskul) {
    // Gunakan fungsi JS showMessage untuk notifikasi dan redirect
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showMessage('❌ Silakan login terlebih dahulu!', 'login_superadmin.php');
            });
          </script>";
    exit;
}

// Tentukan URL kembali berdasarkan role
$back_url = $isSuperadmin ? 'kelola_ekskul.php' : 'kelola_ekskul_admin.php';

// ---- AMBIL DATA EKSKUL UNTUK DIEDIT ----
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = null;
$error_message = '';

if ($isAdminEkskul && $id === 0) {
    // Admin Ekskul tanpa ID di URL, ambil ID dari session
    $adminEkskul = $_SESSION['admin_ekskul'] ?? '';
    if (!empty($adminEkskul)) {
        $stmt = $conn->prepare("SELECT * FROM table_ekskul WHERE nama_ekskul = ? LIMIT 1");
        $stmt->bind_param("s", $adminEkskul);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$data) {
            $error_message = '❌ Ekskul milik Anda belum didaftarkan atau tidak ditemukan!';
        } else {
            $id = (int)$data['id_ekskul'];
        }
    } else {
        $error_message = '❌ Sesi admin ekskul tidak valid. Harap login ulang.';
    }
} 
// Superadmin atau Admin Ekskul dengan ID valid
elseif ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM table_ekskul WHERE id_ekskul = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        $error_message = '❌ Ekskul tidak ditemukan!';
    }

    // Admin Ekskul: verifikasi kepemilikan
    if ($isAdminEkskul && isset($data['nama_ekskul']) && ($_SESSION['admin_ekskul'] ?? '') !== $data['nama_ekskul']) {
        $error_message = '❌ Ekskul yang ingin Anda edit bukan milik Anda!';
    }
} else {
    $error_message = '❌ ID Ekskul tidak valid.';
}

// Jika terjadi error saat pengambilan data, tampilkan pesan dan keluar
if ($error_message) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showMessage('$error_message', '$back_url');
            });
          </script>";
    exit;
}


// ---- HELPER: MENGUBAH LINK WA KE NOMOR UNTUK DISPLAY DI FORM ----
/** * Membalikkan format 'https://wa.me/6281...' menjadi '081...' untuk input user.
 */
function get_wa_number_from_link($link) {
    if (empty($link)) return '';
    
    // Hapus prefix wa.me
    $number = preg_replace('/^https?:\/\/(www\.)?wa\.me\//i', '', $link);
    
    // Ganti prefix '62' dengan '0' jika ada
    if (substr($number, 0, 2) === '62') {
        return '0' . substr($number, 2);
    }
    return $number; // Jika format sudah aneh, kembalikan saja apa adanya
}

// Ambil nomor telepon untuk ditampilkan di input form
$wa_number_display = get_wa_number_from_link($data['link_grup_wa'] ?? '');


// ---- LOGIKA UPDATE DATA (POST METHOD) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama           = trim($_POST['nama_ekskul']);
    $deskripsi      = trim($_POST['deskripsi']);
    $link_gform     = trim($_POST['link_gform']);
    $link_wa_input  = trim($_POST['link_wa_perwakilan']); // Input nomor perwakilan
    $gambar         = $data['gambar']; // Gambar lama
    $link_grup_wa   = $data['link_grup_wa']; // Default/nilai lama

    // --- Pemrosesan Nomor WhatsApp Perwakilan (untuk kolom link_grup_wa) ---
    if (!empty($link_wa_input)) {
        $cleaned_number = preg_replace('/[^0-9]/', '', $link_wa_input); // Hapus semua non-angka

        // Konversi 0... menjadi 62...
        if (substr($cleaned_number, 0, 1) === '0') {
            $cleaned_number = '62' . substr($cleaned_number, 1);
        } elseif (substr($cleaned_number, 0, 2) !== '62' && strlen($cleaned_number) > 5) {
            // Asumsi ini adalah nomor lokal dan prepend 62
            $cleaned_number = '62' . $cleaned_number; 
        }

        // Simpan sebagai link wa.me personal
        $link_grup_wa = "https://wa.me/{$cleaned_number}";
    } else {
        // Jika input dikosongkan, reset link di database menjadi NULL
        $link_grup_wa = null;
    }
    
    // --- Pemrosesan Tautan Sosial Media dan Grup Link ---
    $link_instagram   = trim($_POST['link_instagram'] ?? '');
    $link_twitter     = trim($_POST['link_twitter'] ?? '');
    // NEW: Ambil nilai dari input link grup ekskul
    $link_grup_ekskul = trim($_POST['link_grup_ekskul'] ?? ''); 

    // Set ke NULL jika kosong, agar kompatibel dengan database
    $link_instagram = empty($link_instagram) ? null : $link_instagram;
    $link_twitter   = empty($link_twitter) ? null : $link_twitter;
    // Gunakan input link grup ekskul untuk mengisi kolom link_lainnya
    $link_lainnya   = empty($link_grup_ekskul) ? null : $link_grup_ekskul; 


    // --- Pemrosesan Gambar Baru ---
    if (!is_dir("uploads")) mkdir("uploads");
    $success = false;

    if (!empty($_FILES['gambar']['name'])) {
        $nm = time() . "_" . basename($_FILES['gambar']['name']);
        $ext = strtolower(pathinfo($nm, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg','jpeg','png','gif'])) {
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], "uploads/$nm")) {
                $gambar = $nm;
            } else {
                $error_message = '❌ Gagal mengunggah gambar baru.';
            }
        } else {
            $error_message = '❌ Format file tidak didukung. Hanya JPG, JPEG, PNG, GIF yang diizinkan.';
        }
    }

    // --- Eksekusi Update Database ---
    if (!$error_message) {
        $stmt = $conn->prepare("UPDATE table_ekskul SET nama_ekskul=?, deskripsi=?, link_gform=?, link_grup_wa=?, gambar=?, link_instagram=?, link_twitter=?, link_lainnya=? WHERE id_ekskul=?");
        // Binding parameter baru (link_instagram, link_twitter, link_lainnya)
        $stmt->bind_param("ssssssssi", $nama, $deskripsi, $link_gform, $link_grup_wa, $gambar, $link_instagram, $link_twitter, $link_lainnya, $id); 
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error_message = '❌ Gagal memperbarui data: Terjadi kesalahan database. Pastikan semua kolom tautan (link_instagram, link_twitter, link_lainnya) ada di tabel ekskul Anda.';
        }
        $stmt->close();
    }
    
    // Tentukan pesan sukses/gagal dan redirect
    if ($success) {
        // ✅ Sukses: Gunakan showMessage dengan delay (2000ms = 2 detik) untuk auto-redirect
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showMessage('✅ Data berhasil diperbarui!', '$back_url', 2000); 
                });
              </script>";
    } else {
        // ❌ Error: Gunakan showMessage tanpa delay (memerlukan klik OK)
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showMessage('$error_message', '$back_url');
                });
              </script>";
    }
    exit;
}

// Tampilkan form HTML di bawah ini

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Ekskul: <?= htmlspecialchars($data['nama_ekskul'] ?? 'Ekskul') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* =======================================================
   EDIT EKSKUL ADMIN — MODERN UI
======================================================= */

:root {
    --primary: #2d66ff;
    --primary-hover: #1c4ad6;
    --bg-page: #f4f7fb;
    --bg-card: #ffffff;
    --text-dark: #1f1f25;
    --text-medium: #6b7280;
    --shadow-light: 0 8px 25px rgba(0,0,0,0.1);
    --radius: 12px;
}

body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: var(--bg-page);
    color: var(--text-dark);
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    padding: 40px 20px;
    box-sizing: border-box;
}

.card-container {
    max-width: 600px;
    width: 100%;
    margin: auto;
}

h1 {
    font-size: 2rem;
    font-weight: 800;
    color: var(--text-dark);
    margin-bottom: 25px;
    text-align: center;
}

.edit-card {
    background: var(--bg-card);
    padding: 35px;
    border-radius: 15px;
    box-shadow: var(--shadow-light);
    display: flex;
    flex-direction: column;
}

/* Form Elements */
label {
    font-weight: 600;
    color: var(--text-dark);
    margin-top: 15px;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

input[type="text"],
input[type="url"],
textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

input:focus,
textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(45, 102, 255, 0.2);
    outline: none;
}

textarea {
    resize: vertical;
    min-height: 120px;
    line-height: 1.6;
}

input[type="file"] {
    padding: 10px 0;
    font-size: 0.95rem;
}

/* Image Preview */
.image-preview {
    margin-top: 5px;
    margin-bottom: 10px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    text-align: center;
}

.image-preview img {
    max-width: 100%;
    max-height: 180px;
    width: auto;
    border-radius: 6px;
    object-fit: cover;
}

.no-image {
    color: var(--text-medium);
    font-style: italic;
    font-size: 0.9rem;
}

/* Buttons */
.btn-submit {
    margin-top: 30px;
    padding: 14px 20px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.1s;
    box-shadow: 0 4px 15px rgba(45, 102, 255, 0.4);
}

.btn-submit:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
}

.btn-back {
    display: block;
    text-align: center;
    margin-top: 15px;
    padding: 10px;
    color: var(--text-medium);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.btn-back:hover {
    color: var(--text-dark);
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

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

@media (max-width: 768px) {
    body {
        padding: 25px 15px;
    }
    h1 {
        font-size: 1.5rem;
        margin-bottom: 18px;
    }
    .edit-card {
        padding: 25px 20px;
    }
}

@media (max-width: 480px) {
    body {
        padding: 16px 12px;
    }
    h1 {
        font-size: 1.25rem;
        margin-bottom: 14px;
        word-break: break-word;
    }
    .edit-card {
        padding: 18px 14px;
        border-radius: 12px;
    }
    input[type="text"],
    input[type="url"],
    textarea {
        font-size: 0.95rem;
        padding: 10px;
    }
    input[type="file"] {
        max-width: 100%;
        font-size: 0.85rem;
    }
    .btn-submit {
        padding: 12px 16px;
        font-size: 0.95rem;
    }
    .message-content {
        padding: 20px 16px;
    }
}

@media (max-width: 375px) {
    body {
        padding: 12px 8px;
    }
    h1 {
        font-size: 1.15rem;
    }
    .edit-card {
        padding: 14px 10px;
    }
    input[type="text"],
    input[type="url"],
    textarea {
        font-size: 0.9rem;
        padding: 8px;
    }
    .btn-submit {
        font-size: 0.9rem;
        padding: 11px 14px;
    }
}
</style>

</head>
<body>

<!-- Custom Message Box -->
<div id="message-box" class="message-box">
    <div class="message-content">
        <p id="message-box-text"></p>
        <button id="message-box-ok" class="btn-primary">OK</button>
    </div>
</div>

<div class="card-container">

    <h1>Edit Detail Ekskul: <?= htmlspecialchars($data['nama_ekskul'] ?? 'Ekskul') ?></h1>

    <form method="post" enctype="multipart/form-data" class="edit-card">

        <label for="nama_ekskul">Nama Ekskul</label>
        <input type="text" id="nama_ekskul" name="nama_ekskul" value="<?= htmlspecialchars($data['nama_ekskul'] ?? '') ?>" required>

        <label for="deskripsi">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" rows="6" required><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>
        
        <!-- Input untuk Nomor WhatsApp Perwakilan (Disimpan di kolom link_grup_wa) -->
        <label for="link_wa_perwakilan">Nomor WhatsApp Perwakilan (untuk Link WA.me Personal)</label>
        <input type="text" id="link_wa_perwakilan" name="link_wa_perwakilan" 
               value="<?= htmlspecialchars($wa_number_display) ?>" 
               placeholder="Masukkan nomor WhatsApp perwakilan ekskul (hanya angka, cth: 081234567890)">
        <small style="color: var(--text-medium); font-size: 0.85rem; margin-top: 5px;">*Ini akan diubah menjadi link personal `wa.me/62...` agar siswa dapat menghubungi perwakilan.</small>

        <!-- Input untuk Link Grup Ekskul (Disimpan di kolom link_lainnya) -->
        <label for="link_grup_ekskul">Link Grup WhatsApp Ekskul (Link Undangan)</label>
        <input type="url" id="link_grup_ekskul" name="link_grup_ekskul" 
               value="<?= htmlspecialchars($data['link_lainnya'] ?? '') ?>" 
               placeholder="Contoh: https://chat.whatsapp.com/invitecode">
        <small style="color: var(--text-medium); font-size: 0.85rem; margin-top: 5px;">*Masukkan URL lengkap undangan grup WhatsApp. Ini akan menggantikan "Link Lainnya".</small>

        <label for="link_gform">Link Google Form (URL Pendaftaran)</label>
        <input type="url" id="link_gform" name="link_gform" value="<?= htmlspecialchars($data['link_gform'] ?? '') ?>" placeholder="Contoh: https://forms.gle/xyz123">

        <!-- START: Bagian Tautan Media Sosial (hanya Instagram dan Twitter) -->
        <div style="border-top: 1px solid #e5e7eb; padding-top: 25px; margin-top: 25px;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin-bottom: 10px;">Tautan Sosial Media Lain (Opsional)</h3>
            <small style="color: var(--text-medium); font-size: 0.85rem; display: block; margin-bottom: 15px;">Link Grup WA telah dipindahkan ke atas. Bagian ini untuk Instagram/Twitter saja.</small>

            <!-- Instagram Link -->
            <label for="link_instagram">Instagram (URL Profil)</label>
            <input type="url" id="link_instagram" name="link_instagram" 
                   value="<?= htmlspecialchars($data['link_instagram'] ?? '') ?>" 
                   placeholder="Contoh: https://instagram.com/nama_ekskul">

            <!-- Twitter/X Link -->
            <label for="link_twitter">Twitter / X (URL Profil)</label>
            <input type="url" id="link_twitter" name="link_twitter" 
                   value="<?= htmlspecialchars($data['link_twitter'] ?? '') ?>" 
                   placeholder="Contoh: https://twitter.com/nama_ekskul">
        </div>
        <!-- END: Bagian Tautan Media Sosial -->
        
        <label>Gambar Ekskul Saat Ini</label>
        <div class="image-preview">
            <?php if (!empty($data['gambar'])): ?>
                <!-- Pastikan path ke gambar benar -->
                <img src="uploads/<?= htmlspecialchars($data['gambar']) ?>" alt="Gambar Ekskul Saat Ini">
            <?php else: ?>
                <p class="no-image">(Tidak ada gambar yang terpasang)</p>
            <?php endif; ?>
        </div>

        <label for="gambar_baru">Upload Gambar Baru (JPG/PNG/GIF)</label>
        <input type="file" id="gambar_baru" name="gambar" accept=".jpg, .jpeg, .png, .gif">
        
        <button type="submit" class="btn-submit">💾 Simpan Perubahan</button>

        <a href="<?= htmlspecialchars($back_url) ?>" class="btn-back">⬅ Kembali ke Pengelolaan Ekskul</a>

    </form>

</div>

<script>
    /**
     * Fungsi untuk menampilkan custom message box dan melakukan redirect.
     * Menerima parameter autoRedirectDelay untuk mengaktifkan redirect otomatis.
     */
    function showMessage(text, redirectUrl, autoRedirectDelay = null) {
        const msgBox = document.getElementById('message-box');
        const msgText = document.getElementById('message-box-text');
        const msgOk = document.getElementById('message-box-ok');
        
        msgText.innerHTML = text; // Menggunakan innerHTML agar bisa menampilkan ikon
        msgBox.style.display = 'flex';
        
        if (autoRedirectDelay) {
            // Pesan sukses: Sembunyikan tombol OK dan set timer untuk auto-redirect
            msgOk.style.display = 'none'; 
            msgText.innerHTML += `<p style="font-size: 0.9em; margin-top: 10px; color: var(--text-medium);">Mengalihkan dalam ${autoRedirectDelay / 1000} detik...</p>`;

            setTimeout(function() {
                window.location.href = redirectUrl;
            }, autoRedirectDelay);
        } else {
            // Pesan error/peringatan: Tampilkan tombol OK dan tunggu klik
            msgOk.style.display = 'block'; 
            msgOk.onclick = function() {
                // Sembunyikan box setelah OK diklik
                msgBox.style.display = 'none'; 
                // Lakukan redirect
                window.location.href = redirectUrl;
            };
        }
    }

    // Hanya untuk memanggil showMessage jika ada error dari PHP yang belum di-handle
    // Bagian ini sudah di-handle oleh PHP di atas, tapi ini adalah template standar
    // yang harus ada jika ada logic error di awal script.
</script>

</body>
</html>