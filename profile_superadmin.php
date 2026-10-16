<?php
session_start();
include 'koneksi.php';

// Pastikan hanya superadmin yg boleh masuk
if (!isset($_SESSION['superadmin_id'])) {
    echo "<script>alert('❌ Silakan login sebagai Superadmin!'); window.location='login_superadmin.php';</script>";
    exit;
}

// --- AMBIL DATA DARI SESSION SECARA AMAN ---
$nama     = $_SESSION['superadmin_nama']     ?? "";
$username = $_SESSION['superadmin_username'] ?? "";
$email    = $_SESSION['superadmin_email']    ?? "";
$notelp   = $_SESSION['superadmin_notelp']   ?? "";

// --- SIMPAN PERUBAHAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaBaru   = trim($_POST['nama']);
    $emailBaru  = trim($_POST['email']);
    $notelpBaru = trim($_POST['notelp']);

    $id = $_SESSION['superadmin_id'];

    $stmt = $conn->prepare("
        UPDATE table_admin 
        SET nama_admin = ?, email = ?, notelp = ?
        WHERE id_admin = ?
    ");
    $stmt->bind_param("sssi", $namaBaru, $emailBaru, $notelpBaru, $id);
    $stmt->execute();
    $stmt->close();

    // Update session
    $_SESSION['superadmin_nama']   = $namaBaru;
    $_SESSION['superadmin_email']  = $emailBaru;
    $_SESSION['superadmin_notelp'] = $notelpBaru;

    echo "<script>alert('✅ Profil berhasil diperbarui!'); window.location='profile_superadmin.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Superadmin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="superadmin.css">
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
            color: #1f1f25;
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
            color: #0D47A1;
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
            border-color: #0D47A1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.15);
        }
        .form-group input:disabled {
            background-color: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #0D47A1;
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
            background: #1976D2;
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
    <h1>Profil Superadmin</h1>

    <form method="post">
        <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($nama) ?>" required>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" value="<?= htmlspecialchars($username) ?>" disabled>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="form-group">
            <label for="notelp">No Telp</label>
            <input type="text" id="notelp" name="notelp" value="<?= htmlspecialchars($notelp) ?>" required>
        </div>

        <button type="submit" class="btn-submit">💾 Simpan Perubahan</button>
        <a href="dashboard_superadmin.php" class="btn-back">⬅ Kembali ke Dashboard</a>
    </form>
</div>

</body>
</html>
