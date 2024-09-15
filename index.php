<?php
session_start();
include 'includes/db.php';
include 'includes/functions.php';

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendirin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcının bilgilerini ve abonelik durumunu al
$user_query = "SELECT full_name, cafe_name, email, subscription_start, subscription_end FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = $user_result->fetch_assoc();

// Kullanıcının oyunlarını al ve oynama sürelerine göre sırala
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

// Kullanıcının toplam oyun ve hesap sayısını al
$total_games_query = "SELECT COUNT(*) as total_games FROM steam_games WHERE user_id = ?";
$total_games_stmt = $conn->prepare($total_games_query);
$total_games_stmt->bind_param("i", $user_id);
$total_games_stmt->execute();
$total_games_result = $total_games_stmt->get_result();
$total_games = $total_games_result->fetch_assoc()['total_games'];

$total_accounts_query = "SELECT COUNT(*) as total_accounts FROM steam_accounts WHERE user_id = ?";
$total_accounts_stmt = $conn->prepare($total_accounts_query);
$total_accounts_stmt->bind_param("i", $user_id);
$total_accounts_stmt->execute();
$total_accounts_result = $total_accounts_stmt->get_result();
$total_accounts = $total_accounts_result->fetch_assoc()['total_accounts'];

// En çok kullanılan oyunu bul
$most_used_game_query = "
    SELECT g.game_name, COUNT(a.id) AS play_count
    FROM steam_games g
    JOIN steam_accounts a ON a.game_id = g.id
    WHERE g.user_id = ?
    GROUP BY g.game_name
    ORDER BY play_count DESC
    LIMIT 1";
$most_used_game_stmt = $conn->prepare($most_used_game_query);
$most_used_game_stmt->bind_param("i", $user_id);
$most_used_game_stmt->execute();
$most_used_game_result = $most_used_game_stmt->get_result();
$most_used_game = $most_used_game_result->fetch_assoc()['game_name'] ?? 'Henüz kullanılmadı';

$conn->close();

// Abonelik kalan gün sayısını hesapla
$remaining_days = 0;
if (!empty($user_info['subscription_end'])) {
    $end_date = new DateTime($user_info['subscription_end']);
    $current_date = new DateTime();
    $remaining_days = $current_date->diff($end_date)->format('%a');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard</title>
    <!-- SB Admin 2 CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .status-online {
            background-color: green;
        }

        .status-offline {
            background-color: red;
        }

        .game-card {
            cursor: pointer;
            transition: transform 0.2s;
        }

        .game-card:hover {
            transform: scale(1.05);
        }

        /* Abonelik ve Çıkış düğmeleri için stil */
        .subscription-info {
            background-color: #f39c12; /* Turuncu arka plan */
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            margin-right: 15px;
            display: inline-block;
        }

        .logout-btn {
            background-color: #e74c3c; /* Kırmızı arka plan */
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #c0392b; /* Daha koyu kırmızı */
        }

        /* Modal boyutunu küçültmek için eklenen stil */
        .small-modal .modal-dialog {
            max-width: 400px; /* Modal genişliği */
            font-size: 12px; /* Yazı boyutu */
        }

        /* Oyun etiketleri */
        .badge-premium {
            background-color: #e74c3c; /* Premium oyun etiketi için kırmızı */
            color: white;
        }

        .badge-free {
            background-color: #3498db; /* Ücretsiz oyun etiketi için mavi */
            color: white;
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
                <span>Oyun Ekle</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="add_account.php">
                <i class="fas fa-user"></i>
                <span>Hesap Ekle</span>
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
                <!-- Topbar Bilgileri -->
                <ul class="navbar-nav ml-auto">
                    <!-- Abonelik Bilgisi -->
                    <li class="nav-item dropdown no-arrow mx-1">
                        <div class="nav-link subscription-info">
                            Abonelik Kalan Gün: <?php echo $remaining_days; ?>
                        </div>
                    </li>
                    <!-- Çıkış Yap -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link logout-btn" href="logout.php">
                            Çıkış Yap
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Oyunlar -->
                <div class="row">
                    <div class="col-md-5">
                        <div class="row">
                            <?php if ($games_result->num_rows > 0): ?>
                                <?php while ($game = $games_result->fetch_assoc()): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card game-card" data-game-id="<?php echo $game['id']; ?>">
                                            <img class="card-img-top" src="<?php echo $game['game_image']; ?>" alt="<?php echo htmlspecialchars($game['game_name']); ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($game['game_name']); ?></h5>
                                                <p>Toplam Oynama Süresi: <?php echo $game['total_playtime']; ?> dakika</p>
                                                <span class="badge <?php echo $game['game_type'] == 'premium' ? 'badge-premium' : 'badge-free'; ?>">
                                                    <?php echo ucfirst($game['game_type']); ?> Oyun
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>Henüz oyun eklenmemiş.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Hesaplar -->
                    <div class="col-md-7">
                        <div class="card shadow mt-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Hesaplar</h6>
                            </div>
                            <div class="card-body" id="account-table">
                                <p>Lütfen bir oyun seçin.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Modal -->
<div class="modal fade small-modal" id="gamesModal" tabindex="-1" aria-labelledby="gamesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gamesModalLabel">Hesap Oyunları</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Oyunlar burada yüklenecek -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- SB Admin 2 Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<!-- JavaScript Kodu -->
<script>
    $(document).ready(function() {
        // Oyun kartına tıklama olayını dinle
        $('.game-card').click(function() {
            var gameId = $(this).data('game-id');
            
            // AJAX ile hesapları çek ve sağdaki tabloya ekle
            $.ajax({
                url: 'get_accounts.php',
                type: 'GET',
                data: {game_id: gameId},
                success: function(response) {
                    $('#account-table').html(response);
                }
            });
        });

        // Oyunları göster butonuna tıklama olayı için
        $(document).on('click', '.show-games', function() {
            var steamId = $(this).data('steam-id');

            // steamId'yi string olarak kontrol edin ve trim fonksiyonunu kullanmadan önce kontrol edin
            if (typeof steamId !== 'string' || !steamId.trim()) {
                return; // Eğer steamId geçersizse işlemi durdur
            }

            // Bu kısımda steam ID'ye göre oyunları getirecek bir AJAX işlemi yapın
            $.ajax({
                url: 'get_games_by_steamid.php',
                type: 'GET',
                data: { steam_id: steamId },
                success: function(response) {
                    if (response.includes('error')) {
                        // Eğer Steam ID yanlışsa veya profil gizli ise
                        $('#gamesModal .modal-body').html('<p>Steam ID\'niz yanlış ya da Steam profiliniz gizli. Nasıl düzelteceğinizi öğrenmek için <a href="/destek.html" target="_blank">tıklayın</a>.</p>');
                    } else {
                        // Eğer oyunlar başarıyla geldiyse, oyunları modalda gösterin
                        $('#gamesModal .modal-body').html(response);
                    }
                    $('#gamesModal').modal('show');
                },
                error: function() {
                    $('#gamesModal .modal-body').html('<p>Bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p>');
                    $('#gamesModal').modal('show');
                }
            });
        });

        // Modal kapatma olayını dinle
        $('.modal').on('hidden.bs.modal', function () {
            // Modal kapatıldığında içeriği temizleyin
            $(this).find('.modal-body').html('');
        });

        // Kapat düğmesine ve köşedeki X düğmesine tıklamayı aktif hale getirin
        $(document).on('click', '[data-dismiss="modal"]', function () {
            $(this).closest('.modal').modal('hide');
        });
    });
</script>
</body>

</html>
