<?php
session_start();
include 'includes/db.php';

// Kullanıcı giriş yapmamışsa JSON hata mesajı döndür
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmamış. Lütfen giriş yapın.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// AJAX isteğiyle gelen verileri alın
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_id = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $steam_id = isset($_POST['steam_id']) ? trim($_POST['steam_id']) : '';

    // Hata ayıklama için gelen verileri kontrol et
    if (empty($account_id) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Tüm alanları doldurun.']);
        exit;
    }

    // Veritabanında hesabı güncelle
    $update_query = "UPDATE steam_accounts SET steam_username = ?, steam_password = ?, steam_id = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'SQL hatası: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("sssii", $username, $password, $steam_id, $account_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Hesap güncellenirken bir hata oluştu: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Geçersiz erişim durumu
echo json_encode(['success' => false, 'message' => 'Geçersiz erişim.']);
exit;
?>
