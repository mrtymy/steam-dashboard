<?php
$servername = "193.203.168.45";
$username = "u840142664_steam_admin";  // Varsayılan MySQL kullanıcı adı
$password = ":2nTGHbFF";      // Varsayılan MySQL şifresi
$dbname = "u840142664_steam_manager";  // Veritabanı adı

// Veritabanına bağlantı
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}
?>
