<?php
$host = "localhost";
$port = "3307"; // Bu satırı ekledik
$kullanici = "root"; 
$sifre = ""; // Şifreyi boş bıraktık
$veritabani = "filmflix_db";

try {
    // Bağlantı satırına portu ekledik
    $db = new PDO("mysql:host=$host;port=$port;dbname=$veritabani;charset=utf8", $kullanici, $sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Bağlantı Başarılı!"; 
} catch (PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}
?>