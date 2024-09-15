<?php
session_start();
include 'includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // PHPMailer autoload dosyasını dahil edin

// Token kontrolü
if (isset($_GET['token'])) {
    $reset_token = $_GET['token'];

    // Veritabanından token kontrolü
    $query = "SELECT * FROM users WHERE reset_token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $reset_token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Yeni şifre formu gönderildiğinde
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Şifrelerin eşleşip eşleşmediğini kontrol et
            if ($new_password !== $confirm_password) {
                $error_message = "Şifreler eşleşmiyor. Lütfen tekrar deneyin.";
            } else {
                // Şifreyi hashleyip güncelle
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $update_query = "UPDATE users SET password = ?, reset_token = NULL WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $hashed_password, $user['id']);
                $update_stmt->execute();

                // Başarılı mesajı ve yönlendirme
                $success_message = "Şifreniz başarıyla güncellendi. Giriş sayfasına yönlendiriliyorsunuz...";
                header("Refresh: 3; url=login.php");
            }
        }
    } else {
        $error_message = "Geçersiz veya süresi dolmuş bir token.";
    }
} else {
    $error_message = "Geçersiz istek.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırla</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .reset-password-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .reset-password-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-password-header h1 {
            font-size: 28px;
            color: #333;
        }

        .form-control {
            background-color: #f3f4f6;
            border: 2px solid #ddd;
            color: #333;
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-reset {
            background-color: #007bff;
            border: none;
            color: #fff;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .btn-reset:hover {
            background-color: #0056b3;
        }

        .alert-danger, .alert-success {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }

        .alert-danger {
            background-color: #d9534f;
            color: #fff;
        }

        .alert-success {
            background-color: #5cb85c;
            color: #fff;
        }

        .small {
            color: #007bff;
            text-decoration: none;
        }

        .small:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="reset-password-container">
    <div class="reset-password-header">
        <h1>Şifre Sıfırla</h1>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($success_message)): ?>
        <form action="" method="post">
            <input type="password" class="form-control" name="new_password" placeholder="Yeni Şifre" required>
            <input type="password" class="form-control" name="confirm_password" placeholder="Şifreyi Onayla" required>
            <button type="submit" class="btn-reset">Şifreyi Güncelle</button>
        </form>
    <?php endif; ?>

</div>

</body>
</html>
