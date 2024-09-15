<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'includes/db.php';

$response = [];
$secretKey = 'ee3a8e3c58e304ce5aa69db24e60184996cca9e25ba7992e24c4016c9d251fca'; // Sabit key
$action = isset($_GET['action']) ? $_GET['action'] : '';

// LOGIN İŞLEMİ
switch ($action) {
    case 'login':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!empty($email) && !empty($password)) {
            $query = "SELECT * FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $issuedAt = time();
                $expirationTime = $issuedAt + (60 * 60 * 24 * 365 * 10); // 10 yıl geçerli token
                $payload = [
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'iat' => $issuedAt,
                    'exp' => $expirationTime
                ];

                $jwt = JWT::encode($payload, $secretKey, 'HS256');
                $response['token'] = $jwt;
                $response['success'] = true;
            } else {
                $response['error'] = 'Invalid email or password.';
            }
        } else {
            $response['error'] = 'Email or password is missing.';
        }
        break;

    // OYUN VE HESAP VERİLERİNİ ÇEKME
    case 'getGamesAndAccounts':
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];

            try {
                $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
                $user_id = $decoded->user_id;

                $games_query = "
                    SELECT steam_games.*, 
                           IFNULL(SUM(account_games.playtime), 0) AS total_playtime
                    FROM steam_games
                    LEFT JOIN account_games ON steam_games.id = account_games.game_id
                    WHERE steam_games.user_id = ?
                    GROUP BY steam_games.id
                    ORDER BY total_playtime DESC";
                
                $games_stmt = $conn->prepare($games_query);
                $games_stmt->bind_param("i", $user_id);
                $games_stmt->execute();
                $games_result = $games_stmt->get_result();

                $games = [];
                while ($game = $games_result->fetch_assoc()) {
                    $games[] = [
                        'id' => $game['id'],
                        'game_name' => $game['game_name'],
                        'game_image' => $game['game_image'],
                        'total_playtime' => $game['total_playtime'],
                        'game_type' => $game['game_type']
                    ];
                }

                // Hesapları çek
                $accounts_query = "SELECT * FROM steam_accounts WHERE user_id = ?";
                $accounts_stmt = $conn->prepare($accounts_query);
                $accounts_stmt->bind_param("i", $user_id);
                $accounts_stmt->execute();
                $accounts_result = $accounts_stmt->get_result();

                $accounts = [];
                while ($account = $accounts_result->fetch_assoc()) {
                    $accounts[] = [
                        'id' => $account['id'],
                        'game_id' => $account['game_id'],
                        'username' => $account['steam_username'],
                        'password' => $account['steam_password'],  // steam_password alanı kullanılıyor
                        'status' => $account['status'] ?? 'Offline'
                    ];
                }

                $response['games'] = $games;
                $response['accounts'] = $accounts;

            } catch (Exception $e) {
                $response['error'] = 'Token decode edilemedi: ' . $e->getMessage();
            }
        } else {
            $response['error'] = 'Authorization token not provided.';
        }
        break;

   // HESAP DURUMUNU GÜNCELLEME
case 'updateAccountStatus':
    $username = $_POST['username'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (!empty($username)) {
        // Hesap durumunu güncelle
        $query = "UPDATE steam_accounts SET status = ? WHERE steam_username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $status, $username);

        // SQL sorgusunu çalıştır ve hata kontrolü yap
        if (!$stmt->execute()) {
            error_log("SQL hatası: " . $stmt->error, 3, "C:/path/to/your/custom_error.log");
            $response['error'] = 'Hesap durumu güncellenemedi.';
        } else {
            $response['success'] = true;
            $response['message'] = 'Hesap durumu başarıyla güncellendi.';
        }

    } else {
        $response['error'] = 'Kullanıcı adı sağlanmadı.';
    }
    break;
    
}

// JSON formatında yanıt döndür
header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
