<?php
session_start();
session_destroy(); // Oturumu bitir
header("Location: giris.php"); // Giriş sayfasına yönlendir
exit;
?>