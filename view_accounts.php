<?php
session_start();
include 'includes/db.php';

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendirin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcının hesaplarını ve oynadıkları oyunları al
$accounts_query = "SELECT * FROM steam_accounts WHERE user_id = ?";
$accounts_stmt = $conn->prepare($accounts_query);
$accounts_stmt->bind_param("i", $user_id);
$accounts_stmt->execute();
$accounts_result = $accounts_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Hesaplar ve Oyunlar</title>
    <!-- SB Admin 2 CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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

                <!-- Hesaplar ve Oyunlar -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Hesaplar ve Oyunlar</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($accounts_result->num_rows > 0): ?>
                            <div class="accordion" id="accountAccordion">
                                <?php while ($account = $accounts_result->fetch_assoc()): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $account['id']; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $account['id']; ?>" aria-expanded="true" aria-controls="collapse<?php echo $account['id']; ?>">
                                                <?php echo $account['steam_username']; ?>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $account['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $account['id']; ?>" data-bs-parent="#accountAccordion">
                                            <div class="accordion-body">
                                                <h5>Oyunlar</h5>
                                                <?php
                                                $games_query = "SELECT * FROM account_games WHERE account_id = ? ORDER BY playtime DESC";
                                                $games_stmt = $conn->prepare($games_query);
                                                $games_stmt->bind_param("i", $account['id']);
                                                $games_stmt->execute();
                                                $games_result = $games_stmt->get_result();

                                                if ($games_result->num_rows > 0):
                                                    while ($game = $games_result->fetch_assoc()):
                                                        // Oyun verilerini Steam API'den veya önceden kaydedilmiş verilerden alabilirsiniz
                                                        echo "<p>{$game['game_id']} - Süre: {$game['playtime']} dakika</p>";
                                                    endwhile;
                                                else:
                                                    echo "<p>Bu hesapta henüz oyun yok.</p>";
                                                endif;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p>Henüz hesap eklenmemiş.</p>
                        <?php endif; ?>
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

<!-- SB Admin 2 Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

</body>

</html>
