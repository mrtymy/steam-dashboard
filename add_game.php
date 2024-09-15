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

// Oyun ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_game'])) {
    $game_id = mysqli_real_escape_string($conn, $_POST['game_id']);
    $game_type = mysqli_real_escape_string($conn, $_POST['game_type']);
    $game_name = '';
    $game_image = '';

    // Steam API kullanarak oyun adı ve resmini al
    $steam_api_key = 'YOUR_STEAM_API_KEY'; // Steam API anahtarınızı buraya ekleyin
    $steam_url = "https://store.steampowered.com/api/appdetails?appids={$game_id}&key={$steam_api_key}";

    $response = file_get_contents($steam_url);
    $data = json_decode($response, true);

    if ($data && isset($data[$game_id]['data'])) {
        $game_name = $data[$game_id]['data']['name'];
        $game_image = $data[$game_id]['data']['header_image'];
    }

    // Oyun veritabanına kaydedin
    $stmt = $conn->prepare("INSERT INTO steam_games (user_id, game_id, game_name, game_image, game_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $game_id, $game_name, $game_image, $game_type);

    if ($stmt->execute()) {
        $success_message = "Oyun başarıyla eklendi!";
    } else {
        $error_message = "Oyun eklenirken bir hata oluştu: " . $stmt->error;
    }

    $stmt->close();
}

// Oyun düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_game'])) {
    $edit_game_id = $_POST['edit_game_id'];
    $new_game_type = $_POST['new_game_type'];

    $edit_stmt = $conn->prepare("UPDATE steam_games SET game_type = ? WHERE user_id = ? AND game_id = ?");
    $edit_stmt->bind_param("sii", $new_game_type, $user_id, $edit_game_id);
    
    if ($edit_stmt->execute()) {
        $success_message = "Oyun tipi başarıyla güncellendi!";
    } else {
        $error_message = "Oyun tipi güncellenirken bir hata oluştu: " . $edit_stmt->error;
    }

    $edit_stmt->close();
}

// Oyun silme işlemi
if (isset($_GET['delete_game'])) {
    $delete_game_id = $_GET['delete_game'];

    $delete_stmt = $conn->prepare("DELETE FROM steam_games WHERE user_id = ? AND game_id = ?");
    $delete_stmt->bind_param("ii", $user_id, $delete_game_id);
    $delete_stmt->execute();
    $delete_stmt->close();
}

// Kullanıcının oyunlarını al
$games_query = "SELECT * FROM steam_games WHERE user_id = ?";
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
    <title>Oyun Ekle</title>
    <!-- SB Admin 2 CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
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

                <!-- Oyun Ekleme Formu -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Oyun Ekle</h6>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="game_id">Oyun ID</label>
                                <input type="text" name="game_id" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="game_type">Oyun Tipi</label>
                                <select name="game_type" class="form-control">
                                    <option value="free">Free</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>
                            <button type="submit" name="add_game" class="btn btn-primary">Oyun Ekle</button>
                        </form>
                    </div>
                </div>

                <!-- Eklenmiş Oyunlar -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Eklenmiş Oyunlar</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($games_result->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($game = $games_result->fetch_assoc()): ?>
                                    <div class="col-md-3 mb-4">
                                        <div class="card game-card">
                                            <img class="card-img-top" src="<?php echo $game['game_image']; ?>" alt="<?php echo htmlspecialchars($game['game_name']); ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($game['game_name']); ?></h5>
                                                <p><span class="badge <?php echo $game['game_type'] == 'premium' ? 'badge-premium' : 'badge-free'; ?>">
                                                    <?php echo ucfirst($game['game_type']); ?> Oyun
                                                </span></p>
                                                <a href="add_game.php?delete_game=<?php echo $game['game_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Emin misiniz? Bu oyun silinecek.');">Sil</a>
                                                <button class="btn btn-warning btn-sm edit-game" data-toggle="modal" data-target="#editGameModal" data-game-id="<?php echo $game['game_id']; ?>" data-game-type="<?php echo $game['game_type']; ?>">Düzenle</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p>Henüz oyun eklenmemiş.</p>
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

<!-- Düzenleme Modal -->
<div class="modal fade" id="editGameModal" tabindex="-1" aria-labelledby="editGameModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGameModalLabel">Oyun Düzenle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="edit_game_id" id="edit_game_id">
                    <div class="form-group">
                        <label for="new_game_type">Oyun Tipi</label>
                        <select name="new_game_type" id="new_game_type" class="form-control">
                            <option value="free">Free</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                    <button type="submit" name="edit_game" class="btn btn-primary">Güncelle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SB Admin 2 Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<!-- JavaScript -->
<script>
    $(document).ready(function() {
        // Oyun düzenleme modalini doldur
        $('.edit-game').click(function () {
            var gameId = $(this).data('game-id');
            var gameType = $(this).data('game-type');
            
            $('#edit_game_id').val(gameId);
            $('#new_game_type').val(gameType);
             $('#editGameModal').modal('show'); // Modal'i aç
        });
    });
    // Modal kapatma olayını dinle
$(document).on('click', '[data-dismiss="modal"]', function () {
    $(this).closest('.modal').modal('hide');
});


</script>

</body>

</html>
