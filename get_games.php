<?php
session_start();
include 'includes/db.php';

// Tüm oyunları veritabanından çek
$games_query = "SELECT * FROM steam_games";
$games_result = $conn->query($games_query);

if ($games_result->num_rows > 0) {
    echo '<table class="table table-bordered">';
    echo '<thead><tr><th>Oyun Adı</th><th>Oyun ID</th><th>Oyun Resmi</th></tr></thead>';
    echo '<tbody>';
    while ($game = $games_result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($game['game_name']) . '</td>';
        echo '<td>' . htmlspecialchars($game['game_id']) . '</td>';
        echo '<td><img src="' . htmlspecialchars($game['game_image']) . '" alt="Oyun Resmi" style="width:100px;"></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>Henüz eklenmiş bir oyun yok.</p>';
}

$conn->close();
?>
