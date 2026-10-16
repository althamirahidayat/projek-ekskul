<?php
session_start();
session_unset();
session_destroy();
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Super Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =======================================================
   SUPERADMIN LOGIN — MODERN BLUE MINIMALIST DESIGN
======================================================= */

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

.input-group input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    background: var(--input-bg);
    font-size: 15px;
    transition: 0.2s;
    color: var(--text-dark);
}

.input-group input:focus {
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
    <h1 class="title">🔐 Login Super Admin</h1>
    <p class="subtitle">Akses sistem manajemen sekolah</p>

    <form action="proses_loginsuper.php" method="POST" class="login-form">

        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" required autocomplete="username">
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-login">Masuk</button>
    </form>

    <a href="LandingPage.html" class="back-link">← Kembali ke halaman utama</a>
</div>

</body>
</html>