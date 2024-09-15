<?php
include 'includes/db.php';
include 'includes/functions.php'; // Fonksiyonları içeren dosyayı dahil edin

if (isset($_GET['steam_id'])) {
    $steam_id = $_GET['steam_id'];

    // Steam API anahtarını al
    $api_key = getSteamAPIKey();

    // Steam API URL'sini oluştur
    $api_url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key={$api_key}&steamid={$steam_id}&include_appinfo=true&format=json";

    // API'den yanıt alınıyor
    $api_response = file_get_contents($api_url);
    $games_data = json_decode($api_response, true);

    // API'den geçersiz yanıt veya oyun verisi alınamadığında hata mesajı göster
    if (!$api_response || empty($games_data['response']['games'])) {
        echo '<p>Steam ID Hatalı veya API yanıtı alınamadı.</p>';
    } else {
        $games = $games_data['response']['games'];

        // Oyunları oynama süresine göre sıralama
        usort($games, function($a, $b) {
            return $b['playtime_forever'] - $a['playtime_forever'];
        });

        echo '<ul class="list-group">';
        foreach ($games as $game) {
            echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
            echo '<span class="badge badge-warning text-white p-2">' . htmlspecialchars($game['name']) . '</span>';
            echo '<span class="badge badge-primary badge-pill">' . $game['playtime_forever'] . ' dakika</span>';
            echo '</li>';
        }
        echo '</ul>';
    }

} else {
    echo '<p>Geçersiz istek.</p>';
}
?>
