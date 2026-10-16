<?php
session_start();
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun Siswa | Pendaftaran Ekskul</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    /* Variabel Warna (Sama dengan Login Page) */
    :root {
      --color-primary: #0D47A1; /* Biru Gelap/Navy */
      --color-secondary: #1976D2; /* Biru Standar */
      --color-accent: #00BCD4; /* Cyan untuk highlight */
      --color-white: #ffffff;
      --color-grey-light: #f4f7fb; /* Latar belakang halaman */
      --color-text-dark: #333333;
      --color-text-medium: #666;
      --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.15);
      --radius: 12px;
    }

    /* Global Reset */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
    }

    body {
      /* Agar bisa di-scroll jika form panjang */
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: flex-start; /* Posisikan di atas agar ada ruang scroll */
      background-color: var(--color-grey-light);
      color: var(--color-text-dark);
      padding: 40px 20px; /* Tambahkan padding agar tidak mepet */
    }

    /* Container Utama */
    .auth-container {
      width: 100%;
      max-width: 480px; /* Sedikit lebih lebar untuk form yang banyak field */
      margin: auto;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    /* Card Daftar */
    .auth-card {
      background: var(--color-white);
      padding: 40px;
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      width: 100%;
      text-align: center;
    }

    /* Logo */
    .auth-logo {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 3px solid var(--color-secondary);
      margin-bottom: 15px;
      object-fit: cover;
    }

    .auth-title {
      font-size: 2rem;
      font-weight: 800;
      color: var(--color-primary);
      margin-bottom: 5px;
    }
    
    .auth-subtitle {
        font-size: 1rem;
        font-weight: 600;
        color: var(--color-text-medium);
        margin-bottom: 30px;
    }

    /* Form Styling */
    .auth-form {
      display: flex;
      flex-direction: column;
      gap: 15px; /* Jarak antar elemen input */
      text-align: left;
    }

    .auth-form label {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--color-text-dark);
      margin-bottom: -5px;
      display: block;
    }

    .auth-form input[type="text"],
    .auth-form input[type="tel"],
    .auth-form input[type="email"],
    .auth-form input[type="password"] {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .auth-form input:focus {
      outline: none;
      border-color: var(--color-accent);
      box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.2);
    }

    /* Tombol Submit */
    .auth-btn {
      margin-top: 20px;
      padding: 14px;
      background-color: var(--color-accent); /* Menggunakan accent color untuk daftar */
      color: var(--color-white);
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background-color 0.3s, transform 0.2s;
    }

    .auth-btn:hover {
      background-color: #0097A7; /* Darker Cyan */
      transform: translateY(-1px);
    }

    /* Switch Link (Login/Kembali) */
    .auth-switch {
      text-align: center;
      margin-top: 25px;
      font-size: 0.95rem;
      color: var(--color-text-medium);
    }

    .auth-switch a {
      color: var(--color-secondary);
      text-decoration: none;
      font-weight: 700;
      transition: color 0.3s;
    }

    .auth-switch a:hover {
      color: var(--color-primary);
      text-decoration: underline;
    }

    /* Responsiveness */
    @media (max-width: 550px) {
      body {
        padding: 20px 10px;
      }
      .auth-card {
        padding: 30px 20px;
        border-radius: 10px;
      }
      .auth-title {
        font-size: 1.8rem;
      }
    }

    @media (max-width: 480px) {
      body {
        padding: 15px 10px;
      }
      .auth-card {
        padding: 25px 16px;
      }
      .auth-title {
        font-size: 1.55rem;
      }
      .auth-subtitle {
        font-size: 0.9rem;
        margin-bottom: 20px;
      }
      .auth-logo {
        width: 68px;
        height: 68px;
        margin-bottom: 12px;
      }
    }

    @media (max-width: 375px) {
      body {
        padding: 10px 6px;
      }
      .auth-card {
        padding: 20px 12px;
        border-radius: 8px;
      }
      .auth-title {
        font-size: 1.35rem;
      }
      .auth-subtitle {
        font-size: 0.82rem;
        margin-bottom: 16px;
      }
      .auth-logo {
        width: 58px;
        height: 58px;
      }
      .auth-form {
        gap: 12px;
      }
      .auth-form label {
        font-size: 0.85rem;
      }
      .auth-form input[type="text"],
      .auth-form input[type="tel"],
      .auth-form input[type="email"],
      .auth-form input[type="password"] {
        padding: 10px 12px;
        font-size: 0.9rem;
      }
      .auth-btn {
        padding: 12px;
        font-size: 0.95rem;
      }
      .auth-switch {
        font-size: 0.85rem;
      }
    }
  </style>
</head>

<body>

  <main class="auth-container">
    <div class="auth-card">

      <!-- Logo SMK - Menggunakan placeholder jika gambar asli tidak ditemukan -->
      <img 
        src="pic/logo1.png" 
        onerror="this.onerror=null;this.src='https://placehold.co/80x80/0D47A1/ffffff?text=SMK'"
        alt="Logo Sekolah" 
        class="auth-logo">

      <h1 class="auth-title">Daftar Akun Siswa</h1>
      <h2 class="auth-subtitle">SMKN 1 KOTA BEKASI</h2>

      <form action="proses_signup.php" method="POST" class="auth-form" autocomplete="off">

        <!-- Nama Lengkap -->
        <label for="nama">Nama Lengkap</label>
        <input type="text" id="nama" name="nama" placeholder="Contoh: Budi Santoso" required>

        <!-- NIS (Nomor Induk Siswa) -->
        <label for="nis">NIS (Nomor Induk Siswa)</label>
        <input type="text" id="nis" name="nis" placeholder="Contoh: 1234567890" required pattern="\d{6,10}" title="NIS harus berupa angka antara 6 hingga 10 digit.">

        <!-- Kelas -->
        <label for="kelas">Kelas</label>
        <!-- Saran: Menggunakan Select atau pattern yang lebih spesifik untuk Kelas, namun menggunakan text sesuai permintaan awal -->
        <input type="text" id="kelas" name="kelas" placeholder="Contoh: XII RPL 1" required>

        <!-- No. Telp -->
        <label for="notelp">No. Telp (Aktif)</label>
        <input type="tel" id="notelp" name="notelp" placeholder="Contoh: 081234567890" required pattern="[0-9]{10,14}" title="Nomor telepon harus berupa angka dan valid.">

        <!-- Email -->
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Contoh: budi.santoso@students.smk.ac.id" required>

        <!-- Password -->
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">

        <button type="submit" class="auth-btn">Daftar Akun</button>

        <p class="auth-switch">
          Sudah punya akun? <a href="login_siswa.php">Login di sini</a>
        </p>
      </form>

    </div>
  </main>

</body>
</html>