<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'includes/db.php';

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendirin
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Kayıt formu gönderildiğinde çalışacak kod
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $cafe_name = $_POST['cafe_name'];
    $full_name = $_POST['full_name'];

    // Kullanıcı adı veya diğer alanların boş olup olmadığını kontrol edin
    if (empty($email) || empty($password) || empty($cafe_name) || empty($full_name)) {
        $error_message = "Tüm alanları doldurun.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Lütfen geçerli bir e-posta adresi girin.";
    } else {
        // Şifreyi hashleyin
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Yeni kullanıcıyı veritabanına eklemeden önce benzersizliği kontrol edin
        $check_query = "SELECT * FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "Bu e-posta zaten kullanılıyor.";
        } else {
            // Benzersiz, ekleyebiliriz
            $query = "INSERT INTO users (email, password, cafe_name, full_name, is_approved) VALUES (?, ?, ?, ?, 0)";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ssss", $email, $hashed_password, $cafe_name, $full_name);
                if ($stmt->execute()) {
                    // Kayıt başarılı, kullanıcıya mesaj göster
                    $success_message = "Kaydınız tamamlandı. Şu an onay aşamasındasınız.";
                } else {
                    $error_message = "Kayıt sırasında bir hata oluştu: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "SQL hatası: " . $conn->error;
            }
        }
        $check_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Kayıt Ol</title>
    <!-- SB Admin 2 CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Kayıt Formu -->
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5">

                        <div class="card o-hidden border-0 shadow-lg my-5">
                            <div class="card-body p-0">
                                <!-- Nested Row within Card Body -->
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Kayıt Ol</h1>
                                    </div>

                                    <?php if (isset($success_message)): ?>
                                        <div class="alert alert-success">
                                            <?php echo $success_message; ?>
                                        </div>
                                    <?php elseif (isset($error_message)): ?>
                                        <div class="alert alert-danger">
                                            <?php echo $error_message; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="">
                                        <div class="form-group">
                                            <input type="text" name="full_name" class="form-control form-control-user" placeholder="Adınız Soyadınız" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" name="cafe_name" class="form-control form-control-user" placeholder="Kafe Adınız" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="email" name="email" class="form-control form-control-user" placeholder="Email Adresiniz" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" class="form-control form-control-user" placeholder="Şifre" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Kayıt Ol
                                        </button>
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="forgot-password.php">Şifrenizi mi unuttunuz?</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="login.php">Zaten bir hesabınız var mı? Giriş Yap!</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
        <!-- End of Main Content -->

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- SB Admin 2 Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

</body>

</html>
