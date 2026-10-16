<?php
session_start();
include 'koneksi.php';

// Pastikan login admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$id = $_SESSION['admin_id'];

$stmt = $conn->prepare("SELECT nama_admin, username, email, notelp, ekskul_dinaungi FROM table_admin WHERE id_admin=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($nama, $username, $email, $notelp, $ekskul);
$stmt->fetch();
$stmt->close();

// Jika ada data yang null, set string kosong agar tidak error
$nama     = $nama ?? "";
$username = $username ?? "";
$email    = $email ?? "";
$notelp   = $notelp ?? "";
$ekskul   = $ekskul ?? "";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin Ekskul</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="global.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 30px 15px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .profile-card {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 35px 30px;
            box-sizing: border-box;
        }
        .profile-card h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1f1f25;
            margin-top: 0;
            margin-bottom: 25px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            border-color: #2d66ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(45, 102, 255, 0.15);
        }
        .form-group input:disabled {
            background-color: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #2d66ff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: #1c4ad6;
        }
        .btn-back {
            display: block;
            text-align: center;
            margin-top: 14px;
            padding: 8px;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .btn-back:hover {
            color: #1f1f25;
        }
        @media (max-width: 480px) {
            body {
                padding: 16px 10px;
            }
            .profile-card {
                padding: 22px 16px;
            }
            .profile-card h1 {
                font-size: 1.3rem;
                margin-bottom: 18px;
            }
            .form-group input {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            .btn-submit {
                padding: 11px;
                font-size: 0.95rem;
            }
        }
        @media (max-width: 375px) {
            body {
                padding: 12px 6px;
            }
            .profile-card {
                padding: 18px 12px;
            }
            .profile-card h1 {
                font-size: 1.15rem;
            }
        }
    </style>
</head>
<body>

<div class="profile-card">
    <h1>Profil Admin Ekskul</h1>

    <form action="proses_update_admin.php" method="POST">
        <div class="form-group">
            <label for="nama">Nama Admin</label>
            <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($nama) ?>" required>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="form-group">
            <label for="notelp">No Telp</label>
            <input type="text" id="notelp" name="notelp" value="<?= htmlspecialchars($notelp) ?>" required>
        </div>

        <div class="form-group">
            <label for="ekskul">Ekskul Dinaungi (Tidak bisa diubah)</label>
            <input type="text" id="ekskul" value="<?= htmlspecialchars($ekskul) ?>" disabled>
        </div>

        <button type="submit" class="btn-submit">💾 Simpan Perubahan</button>
        <a href="dashboard_admin.php" class="btn-back">⬅ Kembali ke Dashboard</a>
    </form>
</div>

</body>
</html>
