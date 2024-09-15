<?php
session_start();
include 'includes/db.php';

// Eğer admin zaten giriş yapmışsa admin paneline yönlendirin
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit;
}

// Giriş formu gönderildiğinde çalışacak kod
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Admin bilgilerini kontrol edin
    $query = "SELECT * FROM admins WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        // Admin oturumunu başlat
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: admin.php");
        exit;
    } else {
        $error_message = "Kullanıcı adı veya şifre hatalı.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Giriş Yap</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        /* Genel Stil Ayarları */
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
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h1>Admin Giriş Yap</h1>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form action="admin_login.php" method="post">
        <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı Adı" required>
        <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
        <button type="submit" class="btn-login">Giriş Yap</button>
    </form>

</div>

</body>
</html>
