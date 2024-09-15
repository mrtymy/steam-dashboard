<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_ip = $_POST['new_ip'];
    $user_id = $_SESSION['user_id'];

    // IP adresinin başka bir kullanıcı tarafından kullanılıp kullanılmadığını kontrol edin
    $ip_check_query = "SELECT * FROM users WHERE allowed_ip = ? AND id != ?";
    $ip_check_stmt = $conn->prepare($ip_check_query);
    $ip_check_stmt->bind_param("si", $new_ip, $user_id);
    $ip_check_stmt->execute();
    $ip_check_result = $ip_check_stmt->get_result();

    if ($ip_check_result->num_rows > 0) {
        echo "Bu IP adresi başka bir kullanıcı tarafından kullanılmaktadır. Lütfen başka bir IP adresi girin.";
    } else {
        // IP adresi başka bir kullanıcı tarafından kullanılmıyor, güncellemeye devam edin
        $update_ip_query = "UPDATE users SET allowed_ip = ? WHERE id = ?";
        $update_ip_stmt = $conn->prepare($update_ip_query);
        $update_ip_stmt->bind_param("si", $new_ip, $user_id);

        if ($update_ip_stmt->execute()) {
            echo "IP adresiniz başarıyla güncellendi.";
        } else {
            echo "IP adresi güncellenirken bir hata oluştu.";
        }

        $update_ip_stmt->close();
    }

    $ip_check_stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IP Adresi Güncelle</title>
</head>
<body>
    <form method="POST" action="update_ip.php">
        <label for="new_ip">Yeni IP Adresiniz:</label>
        <input type="text" name="new_ip" id="new_ip" required>
        <button type="submit">Güncelle</button>
    </form>
</body>
</html>
