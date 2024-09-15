<?php

function getSteamAPIKey() {
    return "462E2F137E37171D8D137A5666AE4910"; // Steam API anahtarınızı buraya ekleyin
}

function getSteamAccountDetails($steam_id) {
    global $api_key; // Global değişkeni kullanıyoruz
    $api_url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key={$api_key}&steamid={$steam_id}&include_appinfo=true&include_played_free_games=true";

    $response = file_get_contents($api_url);
    $account_data = json_decode($response, true);

    return $account_data['response']['games'] ?? null;
}

function getSteamStatus($steam_id) {
    global $api_key; // Global değişkeni kullanıyoruz
    $api_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$api_key}&steamids={$steam_id}";

    $response = file_get_contents($api_url);
    $data = json_decode($response, true);

    if (!empty($data['response']['players'])) {
        $player = $data['response']['players'][0];
        if ($player['personastate'] == 1) { // Online durumu kontrol edin
            return ['status' => 'Online', 'class' => 'status-online'];
        } else {
            return ['status' => 'Offline', 'class' => 'status-offline'];
        }
    }

    return ['status' => 'Steam ID Yok', 'class' => 'status-offline'];
}
function get_base_url($conn) {
    $query = "SELECT setting_value FROM settings WHERE setting_name = 'base_url' LIMIT 1";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['setting_value'];
}


$api_key = "462E2F137E37171D8D137A5666AE4910"; // Steam API anahtarınızı buraya ekleyin
?>


