<?php
session_start();
include 'baglanti.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // 1. E-posta sistemde var mı kontrol et
    $sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE email = ?");
    $sorgu->execute([$email]);
    $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($kullanici) {
        // 2. Rastgele 6 haneli kod üret
        $kod = rand(100000, 999999);

        // 3. Kodu veri tabanına kaydet
        $guncelle = $db->prepare("UPDATE kullanicilar SET reset_kodu = ? WHERE email = ?");
        $guncelle->execute([$kod, $email]);

        // 4. KODU TEXT DOSYASINA YAZ (Simülasyon)
        $dosyaIcerigi = "Tarih: " . date("d.m.Y H:i:s") . "\nE-posta: $email\nOnay Kodu: $kod\n-------------------\n";
        file_put_contents("onay_kodu.txt", $dosyaIcerigi, FILE_APPEND);

        // 5. E-postayı oturuma kaydet ve diğer sayfaya yönlendir
        $_SESSION['reset_email'] = $email;
        header("Location: sifre-yenile.php");
        exit;
    } else {
        $hata = "Bu e-posta adresiyle kayıtlı üye bulunamadı.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifremi Unuttum - FilmFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Önceki tasarımlarla aynı CSS */
        body {
            margin: 0; padding: 0; font-family: 'Arial', sans-serif;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.9)), url('https://image.tmdb.org/t/p/original/or06FN3Dka5tukK1e9sl16pB3iy.jpg');
            background-size: cover; height: 100vh; display: flex; justify-content: center; align-items: center;
        }
        .container {
            width: 400px; background-color: rgba(0,0,0,0.85); padding: 40px;
            border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.8); color: white;
        }
        h2 { color: #f5c518; text-align: center; margin-bottom: 20px; }
        input {
            width: 100%; padding: 15px; margin-bottom: 15px; background: #333; border: 1px solid #444;
            color: white; border-radius: 4px; box-sizing: border-box; outline: none;
        }
        input:focus { border-color: #f5c518; }
        .btn {
            width: 100%; padding: 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 16px;
            background-color: #f5c518; color: black; transition: 0.3s;
        }
        .btn:hover { background-color: #d4a810; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #aaa; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: white; }
        .error { color: #ff4444; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Şifremi Unuttum</h2>
        <p style="text-align: center; color: #ccc; margin-bottom: 20px; font-size: 14px;">
            E-posta adresinizi girin, size bir onay kodu gönderelim.
        </p>

        <?php if(isset($hata)) echo "<p class='error'>$hata</p>"; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="E-posta Adresi" required>
            <button type="submit" class="btn">Kod Gönder</button>
        </form>
        
        <a href="giris.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Giriş Yap'a Dön</a>
    </div>
</body>
</html>