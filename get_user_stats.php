<?php
session_start();
include 'includes/db.php';
include 'includes/functions.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Kullanıcı oyun ve hesap istatistiklerini al
    $stats_query = "
        SELECT g.game_name, COUNT(a.id) AS play_count, 
               DATE_FORMAT(a.created_at, '%Y-%m-%d') AS play_date
        FROM steam_accounts a
        JOIN steam_games g ON a.game_id = g.id
        WHERE a.user_id = ?
        GROUP BY g.game_name, play_date
        ORDER BY play_count DESC"; // DESC eklenerek sıralama yapılır

    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo '<table class="table table-bordered">';
        echo '<thead><tr><th>Oyun Adı</th><th>Oynanma Sayısı</th><th>Tarih</th></tr></thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['game_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['play_count']) . '</td>';
            echo '<td>' . htmlspecialchars($row['play_date']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>Bu kullanıcı için herhangi bir istatistik bulunamadı.</p>';
    }

    $stmt->close();
} else {
    echo '<p>Geçersiz istek.</p>';
}

$conn->close();
?>

<!-- Modal kapatma işlevselliği -->
<script>
$(document).ready(function() {
    // Modal kapatma olayını dinle
    $('#detailsModal').on('hidden.bs.modal', function () {
        // Modal kapatıldığında içeriği temizleyin
        $(this).find('.modal-body').html('');
    });

    // Kapat düğmesine ve köşedeki X düğmesine tıklamayı aktif hale getirin
    $(document).on('click', '[data-dismiss="modal"]', function () {
        $(this).closest('.modal').modal('hide');
    });
});
</script>
