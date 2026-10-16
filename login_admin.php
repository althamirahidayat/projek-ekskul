<?php
session_start();
include 'koneksi.php';

// Jika admin sudah login langsung ke dashboard
if (!empty($_SESSION['admin_id']) && ($_SESSION['role'] ?? '') === 'admin') {

    // Gunakan try-catch atau pengecekan untuk memastikan koneksi ada
    if (isset($conn)) {
        $check = $conn->prepare("SELECT id_admin FROM table_admin WHERE id_admin=? AND role='admin'");
        $check->bind_param("i", $_SESSION['admin_id']);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            header("Location: dashboard_admin.php");
            exit;
        } else {
            // Admin ID ada di sesi tapi data di DB tidak valid/role berubah
            session_unset();
            session_destroy();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin Ekskul</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

:root {
    --primary: #2d66ff;
    --primary-hover: #1c4ad6;
    --text-dark: #1f2a44; /* Dark title/text */
    --text-medium: #5f6680; /* Subtitle/label */
    --border-color: #c9cfe1;
    --input-bg: #f7f9ff;
    --card-bg: #ffffffee;
    --radius: 12px;
}

body {
    margin: 0;
    padding: 0;
    font-family: "Inter", sans-serif;
    /* Background gradient modern blue */
    background: linear-gradient(135deg, #eaf1ff, #c7d4ff);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* CARD */
.login-card {
    width: 380px;
    background: var(--card-bg);
    padding: 32px;
    border-radius: 18px;
    box-shadow: 0 8px 28px rgba(0,0,0,0.12);
    backdrop-filter: blur(6px);
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* TITLE */
.title {
    text-align: center;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 4px;
}

.subtitle {
    text-align: center;
    font-size: 14px;
    color: var(--text-medium);
    margin-bottom: 28px;
}

/* INPUT GROUP */
.input-group {
    margin-bottom: 20px;
}

.input-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 6px;
}

/* Style untuk semua input text, password, dan select */
.input-group input, .input-group select {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    background: var(--input-bg);
    font-size: 15px;
    transition: 0.2s;
    color: var(--text-dark);
    /* Menghilangkan panah default pada select di beberapa browser */
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232d66ff'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 18px;
}

.input-group input:focus, .input-group select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(45,102,255,0.2);
    outline: none;
}

/* BUTTON */
.btn-login {
    width: 100%;
    padding: 12px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.25s ease;
}

.btn-login:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(45,102,255,0.28);
}

/* BACK LINK */
.back-link {
    display: block;
    margin-top: 18px;
    text-align: center;
    font-size: 14px;
    color: var(--text-medium);
    text-decoration: none;
    transition: 0.2s;
}

.back-link:hover {
    color: var(--primary);
    text-decoration: underline;
}

/* Responsiveness */
@media (max-width: 450px) {
    .login-card {
        width: 90%;
        padding: 25px;
    }
}

</style>
</head>
<body>

<div class="login-card">
    <h1 class="title">🧑‍💻 Login Admin Ekskul</h1>
    <p class="subtitle">Masuk untuk mengelola data ekstrakurikuler</p>

    <form action="proses_login.php" method="POST" class="login-form" autocomplete="off">

        <div class="input-group">
            <label for="username-input">Username</label>
            <input type="text" id="username-input" name="username" required autocomplete="username">
        </div>

        <div class="input-group">
            <label for="ekskul-select">Ekskul yang Dinaungi</label>
            <!-- Diubah menjadi SELECT dropdown -->
            <select id="ekskul-select" name="ekskul_dinaungi" required>
                <option value="" disabled selected>Memuat daftar ekskul...</option>
            </select>
        </div>

        <div class="input-group">
            <label for="password-input">Password</label>
            <input type="password" id="password-input" name="password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-login">Masuk</button>
    </form>

    <a href="LandingPage.html" class="back-link">← Kembali ke Halaman Utama</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ekskulSelect = document.getElementById('ekskul-select');

    // Fungsi untuk mengambil dan mengisi daftar ekskul
    function fetchAndPopulateEkskul() {
        // Tampilkan status loading awal
        ekskulSelect.innerHTML = '<option value="" disabled selected>Memuat daftar ekskul...</option>';
        ekskulSelect.disabled = true;

        fetch('get_ekskul.php')
            .then(response => {
                // Check jika respons tidak OK (misal 404, 500)
                if (!response.ok) {
                    throw new Error('Gagal mengambil data dari server. Status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                ekskulSelect.disabled = false;
                
                // Cek jika ada error di data JSON (dari PHP)
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Kosongkan dan buat opsi default
                ekskulSelect.innerHTML = '<option value="" disabled selected>Pilih Ekskul Anda</option>';

                if (data.length > 0) {
                    data.forEach(ekskul => {
                        const option = document.createElement('option');
                        // Kita kirimkan nama ekskul sebagai value saat form disubmit
                        option.value = ekskul.nama_ekskul; 
                        option.textContent = ekskul.nama_ekskul;
                        ekskulSelect.appendChild(option);
                    });
                } else {
                    ekskulSelect.innerHTML = '<option value="" disabled selected>Tidak ada ekskul ditemukan.</option>';
                    ekskulSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error in fetch:', error.message);
                ekskulSelect.innerHTML = '<option value="" disabled selected>Gagal memuat: ' + error.message + '</option>';
                ekskulSelect.disabled = true;
            }); 
    }

    // Panggil fungsi saat DOM selesai dimuat
    fetchAndPopulateEkskul();
});
</script>

</body>
</html>