<?php
session_start();
include 'includes/db.php';
include 'includes/functions.php';

if (isset($_GET['game_id'])) {
    $game_id = $_GET['game_id'];

    // Veritabanından oyuna ait hesapları çek
    $accounts_query = "SELECT * FROM steam_accounts WHERE game_id = ?";
    $accounts_stmt = $conn->prepare($accounts_query);
    $accounts_stmt->bind_param("i", $game_id);
    $accounts_stmt->execute();
    $accounts_result = $accounts_stmt->get_result();

    if ($accounts_result->num_rows > 0) {
        echo '<table class="table table-bordered">';
        echo '<thead><tr><th>Hesap Adı</th><th>Durum</th><th>VAC Durumu</th><th>Oyunlar</th></tr></thead>';
        echo '<tbody>';
        while ($account = $accounts_result->fetch_assoc()) {
            $status = 'Steam ID Yok';
            $vac_status = '';
            $status_class = 'status-offline';

            if (!empty($account['steam_id'])) {
                $steam_id = $account['steam_id'];

                // Steam API çağrısı
                $api_key = getSteamAPIKey(); 
                $api_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$api_key}&steamids={$steam_id}";
                
                $api_response = file_get_contents($api_url);
                $api_data = json_decode($api_response, true);

                if (isset($api_data['response']['players'][0]['personastate'])) {
                    $status = ($api_data['response']['players'][0]['personastate'] == 1) ? 'Online' : 'Offline';
                    $status_class = ($status == 'Online') ? 'status-online' : 'status-offline';
                }

                // VAC durumu kontrolü yalnızca geçerli bir Steam ID varsa gösterilecek
                if (isset($api_data['response']['players'][0]['vacbanned'])) {
                    $vac_status = $api_data['response']['players'][0]['vacbanned'] ? '<span class="badge badge-danger">VAC Ban</span>' : '<span class="badge badge-success">Temiz</span>';
                } else {
                    $vac_status = 'Bilinmiyor';
                }
            }

            echo '<tr>';
            echo '<td>' . htmlspecialchars($account['steam_username']) . '</td>';
            echo '<td><span class="status-indicator ' . $status_class . '"></span>' . ucfirst($status) . '</td>';
            echo '<td>' . $vac_status . '</td>';
            echo '<td><button type="button" class="btn btn-primary show-games" data-steam-id="' . $account['steam_id'] . '">Bu Hesabın Oyunlarını Göster</button></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>Bu oyuna ait hesap bulunamadı.</p>';
    }

    $accounts_stmt->close();
} else {
    echo '<p>Geçersiz istek.</p>';
}

$conn->close();
?>
