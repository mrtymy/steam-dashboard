<?php
include 'includes/db.php';

if (isset($_POST['game_id'])) {
    $game_id = $_POST['game_id'];

    // Belirtilen oyunun hesaplarını al
    $accounts_query = "SELECT * FROM steam_accounts WHERE game_id = ?";
    $accounts_stmt = $conn->prepare($accounts_query);
    $accounts_stmt->bind_param("i", $game_id);
    $accounts_stmt->execute();
    $accounts_result = $accounts_stmt->get_result();

    if ($accounts_result->num_rows > 0) {
        while ($account = $accounts_result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $account['steam_username'] . '</td>';
            echo '<td>' . $account['steam_password'] . '</td>';
            echo '<td>' . ($account['steam_id'] ?: 'Steam ID Yok') . '</td>';
            echo '<td><span class="status-indicator ' . ($account['steam_id'] ? 'status-online' : 'status-offline') . '"></span>' . ($account['steam_id'] ? 'Online' : 'Steam ID Yok') . '</td>';
            echo '<td>Oyunları Göster</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">Bu oyuna ait hesap yok.</td></tr>';
    }

    $accounts_stmt->close();
}
?>
