<?php
session_start();
include 'includes/db.php';

// Hata ayıklama modunu etkinleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// AJAX isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Oturum açılmamış. Lütfen giriş yapın.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Hesap ekleme işlemi
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $steam_id = isset($_POST['steam_id']) ? trim($_POST['steam_id']) : '';

        if (empty($game_id) || empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Tüm alanları doldurun.']);
            exit;
        }

        $insert_query = "INSERT INTO steam_accounts (user_id, game_id, steam_username, steam_password, steam_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'SQL hatası: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("iisss", $user_id, $game_id, $username, $password, $steam_id);

        // Burada if bloğunu güncelliyoruz
        if ($stmt->execute()) {
            // Yeni eklenen hesabın ID'sini alın
            $new_account_id = $stmt->insert_id; // Yeni eklenen hesap ID'sini alın
            echo json_encode(['success' => true, 'new_account_id' => $new_account_id]); // JSON yanıtı olarak ID'yi döndürün
        } else {
            echo json_encode(['success' => false, 'message' => 'Hesap eklenirken bir hata oluştu: ' . $stmt->error]);
        }

        $stmt->close();
        $conn->close();
        exit;
    }

    // Hesap silme işlemi
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $account_id = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;
        $game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;

        if (empty($account_id) || empty($game_id)) {
            echo json_encode(['success' => false, 'message' => 'Hesap veya oyun bilgisi eksik.']);
            exit;
        }

        $delete_query = "DELETE FROM steam_accounts WHERE id = ? AND game_id = ?";
        $stmt = $conn->prepare($delete_query);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'SQL hatası: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("ii", $account_id, $game_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Hesap silinirken bir hata oluştu: ' . $stmt->error]);
        }

        $stmt->close();
        $conn->close();
        exit;
    }
}

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Oyunları ve hesapları al
$games_query = "
    SELECT steam_games.*, 
           IFNULL(SUM(account_games.playtime), 0) AS total_playtime,
           (SELECT COUNT(*) FROM steam_accounts WHERE steam_accounts.game_id = steam_games.id) AS account_count
    FROM steam_games
    LEFT JOIN account_games ON steam_games.id = account_games.game_id
    WHERE steam_games.user_id = ?
    GROUP BY steam_games.id
    ORDER BY account_count DESC, total_playtime DESC";
$games_stmt = $conn->prepare($games_query);
$games_stmt->bind_param("i", $user_id);
$games_stmt->execute();
$games_result = $games_stmt->get_result();
?>
<?php
// ... Önceki PHP kodları aynı kalacak

// Oyunları ve hesapları al
$games_query = "
    SELECT steam_games.*, 
           IFNULL(SUM(account_games.playtime), 0) AS total_playtime,
           (SELECT COUNT(*) FROM steam_accounts WHERE steam_accounts.game_id = steam_games.id) AS account_count
    FROM steam_games
    LEFT JOIN account_games ON steam_games.id = account_games.game_id
    WHERE steam_games.user_id = ?
    GROUP BY steam_games.id
    ORDER BY account_count DESC, total_playtime DESC";
$games_stmt = $conn->prepare($games_query);
$games_stmt->bind_param("i", $user_id);
$games_stmt->execute();
$games_result = $games_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Hesap Ekle</title>
    <!-- SB Admin 2 ve Bootstrap CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
          .game-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .game-image {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .account-list {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .account-list .account-item {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px solid #ccc;
            padding: 5px 0;
            text-align: center; /* Ortalama */
        }

        .account-item input {
            border: none;
            background-color: transparent;
            text-align: center; /* Ortala */
            flex: 1; /* Eşit genişlik sağlar */
            min-width: 80px; /* Minimum genişlik ayarı */
            margin-bottom: 5px; /* Mobilde elemanların üst üste binmemesi için */
        }

        .edit-btn {
            flex: 1; /* Düğme için genişlik kontrolü */
            min-width: 100px; /* Düğmenin minimum genişliği */
        }

        .new-account-form {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
        }

        .new-account-form input {
            margin-bottom: 10px;
        }

        .new-account-form button {
            align-self: flex-start;
        }

        .ajax-message {
            margin-top: 10px;
            display: none;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }

        .ajax-message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .ajax-message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
            <div class="sidebar-brand-text mx-3">Yönetim Paneli</div>
        </a>
        <li class="nav-item">
            <a class="nav-link" href="index.php">
                <i class="fas fa-home"></i>
                <span>Ana Sayfa</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="add_game.php">
                <i class="fas fa-gamepad"></i>
                <span>Oyunlar</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="add_account.php">
                <i class="fas fa-user"></i>
                <span>Hesaplar</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="update_ip.php">
                <i class="fas fa-user"></i>
                <span>IP Güncelle</span>
            </a>
        </li>
    </ul>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="logout.php">
                            Çıkış Yap
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <div class="row">
                    <?php while ($game = $games_result->fetch_assoc()): ?>
                        <!-- Bootstrap grid sınıflarını kullanarak kartları responsive hale getirin -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="game-card">
                                <img class="game-image" src="<?php echo htmlspecialchars($game['game_image']); ?>" alt="<?php echo htmlspecialchars($game['game_name']); ?>">
                                <h5 class="mt-3"><?php echo htmlspecialchars($game['game_name']); ?></h5>
                                <p>Toplam Oynama Süresi: <?php echo $game['total_playtime']; ?> dakika</p>
                                <p>Hesap Sayısı: <?php echo $game['account_count']; ?></p>

                                <!-- Hesaplar -->
                                <div class="account-list">
                                    <?php
                                    $accounts_query = "SELECT * FROM steam_accounts WHERE game_id = ?";
                                    $accounts_stmt = $conn->prepare($accounts_query);
                                    $accounts_stmt->bind_param("i", $game['id']);
                                    $accounts_stmt->execute();
                                    $accounts_result = $accounts_stmt->get_result();
                                    if ($accounts_result->num_rows > 0):
                                        while ($account = $accounts_result->fetch_assoc()): ?>
                                            <div class="account-item">
                                                <input type="text" value="<?php echo htmlspecialchars($account['steam_username']); ?>" readonly>
                                                <input type="text" value="<?php echo htmlspecialchars($account['steam_password']); ?>" readonly>
                                                <input type="text" value="<?php echo htmlspecialchars($account['steam_id']); ?>" readonly>
                                                <button class="btn btn-warning btn-sm edit-btn" data-account-id="<?php echo $account['id']; ?>" data-game-id="<?php echo $game['id']; ?>">Düzenle</button>
                                            </div>
                                        <?php endwhile;
                                    else: ?>
                                        <p>Bu oyunda henüz hesap yok.</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Yeni Hesap Ekleme -->
                                <div class="new-account-form">
                                    <input type="text" id="username_<?php echo $game['id']; ?>" placeholder="Username" class="form-control mb-2">
                                    <input type="password" id="password_<?php echo $game['id']; ?>" placeholder="Password" class="form-control mb-2">
                                    <input type="text" id="steam_id_<?php echo $game['id']; ?>" placeholder="Steam ID" class="form-control mb-2">
                                    <button class="btn btn-success add-account-btn" data-game-id="<?php echo $game['id']; ?>">Ekle</button>
                                </div>

                                <!-- AJAX Message -->
                                <div class="ajax-message" id="ajax-message-<?php echo $game['id']; ?>"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Modal for Edit Account -->
<div class="modal fade" id="editAccountModal" tabindex="-1" role="dialog" aria-labelledby="editAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAccountModalLabel">Hesap Düzenle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Düzenleme Formu İçeriği -->
                <input type="text" id="edit_username" placeholder="Username" class="form-control mb-2">
                <input type="password" id="edit_password" placeholder="Password" class="form-control mb-2">
                <input type="text" id="edit_steam_id" placeholder="Steam ID" class="form-control mb-2">
                <input type="hidden" id="edit_account_id">
                <input type="hidden" id="edit_game_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" id="saveAccountChanges">Değişiklikleri Kaydet</button>
                <button type="button" class="btn btn-danger" id="deleteAccount">Sil</button>
            </div>
        </div>
    </div>
</div>

<!-- SB Admin 2 ve Bootstrap Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<!-- JavaScript for Inline Account Addition, Editing and Deletion -->
<script>
$(document).ready(function() {
    // Add new account via AJAX
    $('.add-account-btn').click(function() {
        var gameId = $(this).data('game-id');
        var username = $('#username_' + gameId).val();
        var password = $('#password_' + gameId).val();
        var steamId = $('#steam_id_' + gameId).val();
        var messageBox = $('#ajax-message-' + gameId);
        var accountList = $(this).closest('.game-card').find('.account-list');

        $.ajax({
            url: 'add_account.php',
            type: 'POST',
            data: {
                action: 'add',
                game_id: gameId,
                username: username,
                password: password,
                steam_id: steamId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageBox.removeClass('error').addClass('success').text('Hesap başarıyla eklendi!').show();
                    // Yeni hesabı dinamik olarak ekle
                    var newAccountItem = '<div class="account-item">' +
                        '<input type="text" value="' + username + '" readonly>' +
                        '<input type="text" value="' + password + '" readonly>' +
                        '<input type="text" value="' + steamId + '" readonly>' +
                        '<button class="btn btn-warning btn-sm edit-btn" data-account-id="' + response.new_account_id + '" data-game-id="' + gameId + '">Düzenle</button>' +
                        '</div>';
                    accountList.append(newAccountItem);

                    // Input alanlarını temizle
                    $('#username_' + gameId).val('');
                    $('#password_' + gameId).val('');
                    $('#steam_id_' + gameId).val('');
                    
                    setTimeout(function() {
                        messageBox.fadeOut();
                    }, 2000);
                } else {
                    messageBox.removeClass('success').addClass('error').text('Hata: ' + response.message).show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Hatası:', error);
                messageBox.removeClass('success').addClass('error').text('Hesap eklenirken bir hata oluştu: ' + error).show();
            }
        });
    });

    // Edit account button click
    $(document).on('click', '.edit-btn', function() {
        var accountId = $(this).data('account-id');
        var gameId = $(this).data('game-id');
        // Mevcut bilgileri doldur
        var username = $(this).siblings('input[type="text"]').eq(0).val();
        var password = $(this).siblings('input[type="text"]').eq(1).val();
        var steamId = $(this).siblings('input[type="text"]').eq(2).val();

        $('#edit_username').val(username);
        $('#edit_password').val(password);
        $('#edit_steam_id').val(steamId);
        $('#edit_account_id').val(accountId);
        $('#edit_game_id').val(gameId);
        
        $('#editAccountModal').modal('show');
    });

    // Save account changes via AJAX
    $('#saveAccountChanges').click(function() {
        var accountId = $('#edit_account_id').val();
        var gameId = $('#edit_game_id').val();
        var username = $('#edit_username').val();
        var password = $('#edit_password').val();
        var steamId = $('#edit_steam_id').val();
        var messageBox = $('#ajax-message-' + gameId);

        $.ajax({
            url: 'edit_account.php',
            type: 'POST',
            data: {
                action: 'edit',
                account_id: accountId,
                username: username,
                password: password,
                steam_id: steamId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageBox.removeClass('error').addClass('success').text('Hesap başarıyla güncellendi!').show();
                    
                    // Mevcut hesap bilgilerinin bulunduğu öğeleri güncelle
                    var accountItem = $('.edit-btn[data-account-id="' + accountId + '"]').closest('.account-item');
                    accountItem.find('input').eq(0).val(username);
                    accountItem.find('input').eq(1).val(password);
                    accountItem.find('input').eq(2).val(steamId);
                    
                    $('#editAccountModal').modal('hide'); // Modalı kapat

                    setTimeout(function() {
                        messageBox.fadeOut();
                    }, 2000);
                } else {
                    messageBox.removeClass('success').addClass('error').text('Hata: ' + response.message).show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Hatası:', error);
                messageBox.removeClass('success').addClass('error').text('Hesap güncellenirken bir hata oluştu: ' + error).show();
            }
        });
    });

    // Delete account
    $('#deleteAccount').click(function() {
        var accountId = $('#edit_account_id').val();
        var gameId = $('#edit_game_id').val();
        var messageBox = $('#ajax-message-' + gameId);

        $.ajax({
            url: 'add_account.php',
            type: 'POST',
            data: {
                action: 'delete',
                account_id: accountId,
                game_id: gameId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageBox.removeClass('error').addClass('success').text('Hesap başarıyla silindi!').show();
                    // Hesabı listeden kaldır
                    $('.edit-btn[data-account-id="' + accountId + '"]').closest('.account-item').remove();
                    $('#editAccountModal').modal('hide'); // Modalı kapat

                    setTimeout(function() {
                        messageBox.fadeOut();
                    }, 2000);
                } else {
                    messageBox.removeClass('success').addClass('error').text('Hata: ' + response.message).show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Hatası:', error);
                messageBox.removeClass('success').addClass('error').text('Hesap silinirken bir hata oluştu: ' + error).show();
            }
        });
    });

    // Modal kapatma işlevselliği
    $('#editAccountModal').on('hidden.bs.modal', function () {
        $(this).find('input').val('');  // Tüm input alanlarını temizle
    });

    // Kapatma butonlarına tıklama olayını dinleyin
    $(document).on('click', '[data-dismiss="modal"]', function() {
        $('#editAccountModal').modal('hide');
    });
});

</script>

</body>

</html>