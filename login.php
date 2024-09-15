<?php
session_start();
include 'includes/db.php';

// Eğer kullanıcı zaten giriş yapmışsa, ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Giriş formu gönderildiğinde çalışacak kod
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

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

        // Eğer kullanıcı bulunduysa ve şifre doğruysa
        if ($user && password_verify($password, $user['password'])) {
            // Kullanıcının abonelik durumunu kontrol et
            $now = new DateTime();
            if (!empty($user['subscription_start']) && !empty($user['subscription_end'])) {
                $subscription_end = new DateTime($user['subscription_end']);

                if ($now < $subscription_end) {
                    // Abonelik aktif, oturum başlat
                    $_SESSION['user_id'] = $user['id'];
                    header("Location: index.php");
                    exit;
                } else {
                    // Abonelik süresi dolmuş
                    $error_message = "Üyeliğinizin süresi dolmuştur. Lütfen aboneliğinizi yenileyin.";
                }
            } else {
                // Abonelik başlamamışsa veya onaylanmamışsa
                $error_message = "Üyeliğiniz onay aşamasındadır.";
            }
        } else {
            // Yanlış giriş bilgisi
            $error_message = "E-posta adresi veya şifre hatalı.";
        }
    }
}
?>

<!-- Hata mesajlarını eklemek için HTML kısmı -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
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

        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
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

        .btn-login {
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

        .btn-login:hover {
            background-color: #0056b3;
        }

        .alert-danger {
            background-color: #d9534f;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
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

<div class="login-container">
    <div class="login-header">
        <h1>Giriş Yap</h1>
    </div>

    <!-- Hata mesajı ekleme -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="post">
        <input type="email" class="form-control" id="email" name="email" placeholder="E-posta Adresi" required>
        <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
        <button type="submit" class="btn-login">Giriş Yap</button>
    </form>

    <hr>
    <div class="text-center">
        <a class="small" href="forgot-password.php">Şifrenizi mi unuttunuz?</a>
        <br>
        <a class="small" href="register.php">Yeni üye olun</a>
    </div>
</div>

</body>
</html>
