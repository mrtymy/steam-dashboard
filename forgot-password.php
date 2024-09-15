<?php
session_start();
include 'includes/db.php';
include 'includes/functions.php'; // Fonksiyonları içeren dosyayı dahil edin

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // PHPMailer autoload dosyasını dahil edin

// Eğer kullanıcı zaten giriş yapmışsa, ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Şifremi Unuttum formu gönderildiğinde çalışacak kod
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // E-posta formatını kontrol et
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Lütfen geçerli bir e-posta adresi girin.";
    } else {
        // Kullanıcıyı e-posta ile bul
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Şifre sıfırlama bağlantısını oluştur
            $reset_token = bin2hex(random_bytes(16)); // 32 karakterlik rastgele bir token oluştur
            $base_url = get_base_url($conn); // Dinamik base URL'yi al
            $reset_url = $base_url . "reset-password.php?token=" . $reset_token; // Güncellenmiş URL

            // Kullanıcıyı veritabanında güncelle (reset_token ekleyin)
            $update_query = "UPDATE users SET reset_token = ? WHERE email = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ss", $reset_token, $email);
            $update_stmt->execute();

            // PHPMailer ile e-posta gönderme
            $mail = new PHPMailer(true);
            try {
                // Sunucu ayarları
                $mail->isSMTP();
                $mail->Host       = 'smtp.hostinger.com'; // Hostinger SMTP sunucu adresi
                $mail->SMTPAuth   = true;
                $mail->Username   = 'verify@vip.deniznetcafe.com'; // SMTP kullanıcı adı (tam e-posta adresi)
                $mail->Password   = 'IBm5hx8i~1Da'; // SMTP şifresi (e-posta hesabının şifresi)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL kullanımı
                $mail->Port       = 465; // SSL için genellikle 465 kullanılır

                // Karakter setini ayarlayın
                $mail->CharSet = 'UTF-8'; // Türkçe karakterler için UTF-8 ayarı

                // Alıcılar
                $mail->setFrom('verify@vip.deniznetcafe.com', 'Deniz Net Cafe'); // Gönderen adresi ve adı
                $mail->addAddress($email); // Alıcının e-posta adresi

                // İçerik
                $mail->isHTML(true);
                $mail->Subject = 'Şifre Sıfırlama Talebi'; // Türkçe karakterler içeren konu başlığı
                $mail->Body    = "Merhaba, <br><br>Şifrenizi sıfırlamak için lütfen aşağıdaki bağlantıya tıklayın:<br><a href='" . $reset_url . "'>Şifreyi Sıfırla</a>";

                $mail->send();
                $success_message = "Şifre sıfırlama talimatları e-posta adresinize gönderildi.";
            } catch (Exception $e) {
                $error_message = "E-posta gönderilemedi. Hata: {$mail->ErrorInfo}";
            }
        } else {
            $error_message = "Bu e-posta adresiyle kayıtlı bir kullanıcı bulunamadı.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        /* Stil ayarları burada */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .forgot-password-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .forgot-password-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .forgot-password-header h1 {
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

<div class="forgot-password-container">
    <div class="forgot-password-header">
        <h1>Şifremi Unuttum</h1>
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

    <form action="forgot-password.php" method="post">
        <input type="email" class="form-control" id="email" name="email" placeholder="E-posta Adresi" required>
        <button type="submit" class="btn-reset">Şifre Sıfırlama Talebi Gönder</button>
    </form>

    <hr>
    <div class="text-center">
        <a class="small" href="login.php">Giriş Yap</a>
    </div>
</div>

</body>
</html>
