<?php
session_start();

// Tüm oturum değişkenlerini sil
session_unset();

// Oturumu sonlandır
session_destroy();

// Kullanıcıyı login.php sayfasına yönlendir
header("Location: login.php");
exit;
?>
