<?php
session_start();
// PERHATIAN: Pastikan file koneksi.php tersedia di lingkungan ini
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Siswa | Pendaftaran Ekskul</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    /* Variabel Warna */
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
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: var(--color-grey-light);
      color: var(--color-text-dark);
      padding: 20px;
    }

    /* Container Utama */
    .auth-container {
      width: 100%;
      max-width: 420px; /* Ukuran yang ideal untuk form login */
      margin: auto;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    /* Card Login */
    .auth-card {
      background: var(--color-white);
      padding: 40px;
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      width: 100%;
      text-align: center;
      transition: transform 0.3s ease-in-out;
    }

    /* Logo */
    .auth-logo {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 3px solid var(--color-secondary);
      margin-bottom: 20px;
      object-fit: cover;
    }

    .auth-title {
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--color-primary);
      margin-bottom: 30px;
    }

    /* Form Styling */
    .auth-form {
      display: flex;
      flex-direction: column;
      gap: 15px;
      text-align: left;
    }

    .auth-form label {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--color-text-dark);
      margin-bottom: -5px;
      display: block;
    }

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
      margin-top: 15px;
      padding: 14px;
      background-color: var(--color-secondary);
      color: var(--color-white);
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background-color 0.3s, transform 0.2s;
    }

    .auth-btn:hover {
      background-color: var(--color-primary);
      transform: translateY(-1px);
    }

    /* Switch Link (Daftar/Kembali) */
    .auth-switch {
      text-align: center;
      margin-top: 20px;
      font-size: 0.95rem;
      color: var(--color-text-medium);
    }

    .auth-switch a {
      color: var(--color-accent);
      text-decoration: none;
      font-weight: 700;
      transition: color 0.3s;
    }

    .auth-switch a:hover {
      color: var(--color-primary);
      text-decoration: underline;
    }

    /* Responsiveness */
    @media (max-width: 500px) {
      body {
        padding: 10px;
      }
      .auth-card {
        padding: 30px 20px;
        border-radius: 8px;
      }
      .auth-title {
        font-size: 1.6rem;
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

      <h1 class="auth-title">Masuk Sebagai Siswa</h1>

      <form action="proses_login_siswa.php" method="POST" class="auth-form">
        <input type="hidden" name="role" value="siswa">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Masukkan Email Anda" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Masukkan Password Anda" required>

        <button type="submit" class="auth-btn">Login</button>

        <p class="auth-switch">
          Belum punya akun? <a href="index.php">Daftar di sini</a> (Link diperbaiki)
        </p>
        <p class="auth-switch" style="margin-top: 5px;">
          <a href="LandingPage.html">← Kembali ke Halaman Utama</a>
        </p>
      </form>

    </div>
  </main>

</body>
</html>