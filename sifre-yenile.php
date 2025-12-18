<?php
session_start();
include 'baglanti.php';

// Eğer e-posta oturumda yoksa başa döndür
if (!isset($_SESSION['reset_email'])) {
    header("Location: sifremi-unuttum.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $girilenKod = $_POST['kod'];
    $yeniSifre = $_POST['yeni_sifre'];
    $email = $_SESSION['reset_email'];

    // 1. Kodu ve E-postayı kontrol et
    $sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE email = ? AND reset_kodu = ?");
    $sorgu->execute([$email, $girilenKod]);
    $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($kullanici) {
        // 2. Şifreyi güncelle ve kodu sil (güvenlik için)
        $hashliSifre = password_hash($yeniSifre, PASSWORD_DEFAULT);
        
        $guncelle = $db->prepare("UPDATE kullanicilar SET sifre = ?, reset_kodu = NULL WHERE email = ?");
        $guncelle->execute([$hashliSifre, $email]);

        // Başarılı uyarısı
        echo "
        <script>
            alert('Şifreniz başarıyla güncellendi! Giriş yapabilirsiniz.');
            window.location.href = 'giris.php';
        </script>";
        
        // Oturumu temizle
        unset($_SESSION['reset_email']);
        exit;
    } else {
        $hata = "Girdiğiniz onay kodu hatalı!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Şifre Belirle - FilmFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Tasarım diğer sayfalarla aynı */
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
        
        /* Şifre ve Kod Alanları */
        input {
            width: 100%; padding: 15px; margin-bottom: 15px; background: #333; border: 1px solid #444;
            color: white; border-radius: 4px; box-sizing: border-box; outline: none;
        }
        input:focus { border-color: #f5c518; }

        .password-wrapper { position: relative; width: 100%; }
        .password-wrapper i {
            position: absolute; right: 15px; top: 15px; color: #aaa; cursor: pointer;
        }

        .btn {
            width: 100%; padding: 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 16px;
            background-color: #f5c518; color: black; transition: 0.3s;
        }
        .btn:hover { background-color: #d4a810; }
        .error { color: #ff4444; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Yeni Şifre Belirle</h2>
        <p style="text-align: center; color: #ccc; margin-bottom: 20px; font-size: 14px;">
            Lütfen onay kodunu ve yeni şifrenizi girin.
        </p>

        <?php if(isset($hata)) echo "<p class='error'>$hata</p>"; ?>

        <form method="POST">
            <input type="text" name="kod" placeholder="6 Haneli Onay Kodu" required maxlength="6" autocomplete="off">
            
            <div class="password-wrapper">
                <input type="password" name="yeni_sifre" id="yeniSifre" placeholder="Yeni Şifre" required>
                <i class="fa-solid fa-eye" onclick="sifreGoster()"></i>
            </div>

            <button type="submit" class="btn">Şifreyi Güncelle</button>
        </form>
    </div>

    <script>
        function sifreGoster() {
            var input = document.getElementById("yeniSifre");
            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }
    </script>
</body>
</html>