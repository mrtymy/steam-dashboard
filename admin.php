<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'includes/db.php';
include 'includes/functions.php';

// Yönetici girişi kontrolü (burada bir yönetici giriş mekanizması olmalı)
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php"); // Admin girişi yapmamışsa giriş sayfasına yönlendirin
    exit;
}

// Kullanıcı bilgilerini çek
$users_query = "
    SELECT u.id, u.email, u.cafe_name, u.created_at, u.subscription_start, u.subscription_end,
           (SELECT COUNT(*) FROM steam_games WHERE steam_games.user_id = u.id) AS total_games,
           (SELECT COUNT(*) FROM steam_accounts WHERE steam_accounts.user_id = u.id) AS total_accounts
    FROM users u";
$users_result = $conn->query($users_query);

// Kullanıcıyı etkinleştir (2 haftalık ücretsiz deneme başlatma veya aboneliği başlatma)
if (isset($_POST['activate_user'])) {
    $user_id = $_POST['user_id'];
    $subscription_type = $_POST['subscription_type']; // Ücretsiz, 1 yıllık, 2 yıllık vb.

    // Abonelik başlangıç ve bitiş tarihlerini ayarla
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+' . $subscription_type . ' year'));

    $update_query = "UPDATE users SET subscription_start = ?, subscription_end = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $start_date, $end_date, $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Kullanıcı aboneliği başarıyla başlatıldı.";
    } else {
        $error_message = "Abonelik başlatılırken bir hata oluştu: " . $stmt->error;
    }
    
    $stmt->close();
}

// Kullanıcıyı sil
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Kullanıcı başarıyla silindi.";
    } else {
        $error_message = "Kullanıcı silinirken bir hata oluştu: " . $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Panel</title>
    <!-- SB Admin 2 CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="admin.php">
            <div class="sidebar-brand-text mx-3">Yönetim Paneli</div>
        </a>
        <!-- Admin Menüsü -->
        <li class="nav-item">
            <a class="nav-link" href="admin.php">
                <i class="fas fa-users"></i>
                <span>Kullanıcı Yönetimi</span>
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
                        <a class="nav-link dropdown-toggle" href="admin_logout.php">
                            Çıkış Yap
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Kullanıcı Listesi -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Kullanıcılar</h6>
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
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Kafe Adı</th>
                                    <th>Email</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>Toplam Oyunlar</th>
                                    <th>Toplam Hesaplar</th>
                                    <th>Abonelik Durumu</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['cafe_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                        <td><?php echo $user['total_games']; ?></td>
                                        <td><?php echo $user['total_accounts']; ?></td>
                                        <td>
                                            <?php
                                            if (!empty($user['subscription_start']) && !empty($user['subscription_end'])) {
                                                $subscription_end = new DateTime($user['subscription_end']);
                                                $now = new DateTime();
                                                $remaining_days = $now->diff($subscription_end)->format('%a gün');
                                                echo "Aktif (Bitiş: " . htmlspecialchars($user['subscription_end']) . ")<br>Kalan gün: " . $remaining_days;
                                            } else {
                                                echo "Abonelik Yok";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <!-- Aktivasyon Formu -->
                                            <form method="POST" action="">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="subscription_type" class="form-control mb-2">
                                                    <option value="0.038">Ücretsiz (2 Hafta)</option>
                                                    <option value="1">1 Yıllık</option>
                                                    <option value="2">2 Yıllık</option>
                                                    <!-- Daha fazla seçenek ekleyebilirsiniz -->
                                                </select>
                                                <button type="submit" name="activate_user" class="btn btn-primary btn-sm">Aboneliği Başlat</button>
                                            </form>
                                            <!-- Detaylar Modal -->
                                            <button class="btn btn-info btn-sm show-details" data-user-id="<?php echo $user['id']; ?>">Detaylar</button>
                                            <!-- Düzenle ve Sil -->
                                            <button class="btn btn-warning btn-sm edit-user" data-user-id="<?php echo $user['id']; ?>">Düzenle</button>
                                            <button class="btn btn-danger btn-sm delete-user" data-user-id="<?php echo $user['id']; ?>">Sil</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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

<!-- Detaylar Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Kullanıcı Detayları</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- İstatistikler burada yüklenecek -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
<!-- Kullanıcı Düzenleme Modalı -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Kullanıcı Düzenle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <div class="form-group">
                        <label for="editUserEmail">Email</label>
                        <input type="email" class="form-control" id="editUserEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="editUserCafeName">Kafe Adı</label>
                        <input type="text" class="form-control" id="editUserCafeName" name="cafe_name" required>
                    </div>
                    <input type="hidden" id="editUserId" name="user_id">
                    <button type="submit" class="btn btn-primary">Kaydet</button>
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
<!-- JavaScript Kodu -->
<script>
    $(document).ready(function() {
        // Detaylar butonuna tıklama
        $('.show-details').click(function() {
            var userId = $(this).data('user-id');
            
            // AJAX ile kullanıcı istatistiklerini çek
            $.ajax({
                url: 'get_user_stats.php',
                type: 'GET',
                data: {user_id: userId},
                success: function(response) {
                    $('#detailsModal .modal-body').html(response);
                    $('#detailsModal').modal('show');
                }
            });
        });

        // Kullanıcıyı silme butonuna tıklama
        $('.delete-user').click(function() {
            var userId = $(this).data('user-id');
            if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
                $.ajax({
                    url: 'admin.php',
                    type: 'POST',
                    data: {
                        delete_user: true,
                        user_id: userId
                    },
                    success: function(response) {
                        location.reload(); // Sayfayı yeniden yükleyin
                    }
                });
            }
        });

        // Düzenle butonuna tıklama
        $('.edit-user').click(function() {
            var userId = $(this).data('user-id');
            var userEmail = $(this).data('user-email');
            var userCafeName = $(this).data('user-cafe-name');
            
            // Düzenleme modalını açmadan önce gerekli inputları doldurun
            $('#editUserId').val(userId);
            $('#editUserEmail').val(userEmail);
            $('#editUserCafeName').val(userCafeName);
            
            $('#editUserModal').modal('show'); // Modal'i aç
        });

        // Modal kapatma olayı
        $(document).on('click', '[data-dismiss="modal"], .close', function () {
            $(this).closest('.modal').modal('hide');
        });
    });
</script>

</body>
</html>