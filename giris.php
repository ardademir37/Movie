<?php
session_start();
include 'baglanti.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $sifre = $_POST['sifre'];

    $sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE email = ?");
    $sorgu->execute([$email]);
    $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
        if ($kullanici['durum'] == 0) {
            $hata = "Hesabınız yönetici tarafından askıya alınmıştır.";
        } else {
            $_SESSION['kullanici_id'] = $kullanici['id'];
            $_SESSION['ad_soyad'] = $kullanici['ad'] . " " . $kullanici['soyad'];
            $_SESSION['rol'] = $kullanici['rol'];
            header("Location: index.php");
            exit;
        }
    } else {
        $hata = "E-posta veya şifre hatalı!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - FilmFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.9)), url('https://image.tmdb.org/t/p/original/or06FN3Dka5tukK1e9sl16pB3iy.jpg');
            background-size: cover; background-position: center;
            min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: 20px; box-sizing: border-box;
        }
        
        .logo { color: #f5c518; font-size: 32px; font-weight: bold; margin-bottom: 20px; text-shadow: 2px 2px 10px rgba(0,0,0,0.5); }

        .container {
            display: flex; width: 100%; max-width: 850px; background-color: rgba(0,0,0,0.85);
            border-radius: 10px; overflow: hidden; box-shadow: 0 0 30px rgba(0,0,0,0.9);
            border: 1px solid #333;
        }

        .login-section, .register-promo {
            flex: 1; padding: 45px; display: flex; flex-direction: column; justify-content: center;
        }

        .register-promo { border-left: 1px solid #333; background: rgba(20,20,20,0.5); }
        
        h2 { color: #f5c518; margin-bottom: 25px; margin-top: 0; font-size: 28px; }
        
        input {
            width: 100%; padding: 15px; margin-bottom: 15px; background: #222; border: 1px solid #444;
            color: white; border-radius: 4px; box-sizing: border-box; outline: none; transition: 0.3s;
        }
        input:focus { border-color: #f5c518; background: #333; }

        .password-wrapper { position: relative; width: 100%; }
        .password-wrapper i {
            position: absolute; right: 15px; top: 18px; color: #888; cursor: pointer; z-index: 10;
        }
        
        .btn {
            width: 100%; padding: 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 16px;
            transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-yellow { background-color: #f5c518; color: black; }
        .btn-yellow:hover { background-color: #d4a810; transform: translateY(-2px); }
        
        .btn-outline { 
            border: 2px solid #f5c518; background: transparent; color: #f5c518; 
            text-decoration: none; text-align: center; display: block; 
        }
        .btn-outline:hover { background: #f5c518; color: black; }

        .error { 
            background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 10px; 
            border-radius: 4px; margin-bottom: 20px; font-size: 14px; text-align: center;
            border: 1px solid #ff4444;
        }

        /* RESPONSIVE AYARLAR */
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .register-promo { border-left: none; border-top: 1px solid #333; padding: 30px 45px; }
            .login-section { padding: 35px 30px; }
            .logo { font-size: 28px; margin-bottom: 15px; }
            h2 { font-size: 24px; }
        }

        @media (max-width: 480px) {
            body { padding: 10px; }
            .login-section, .register-promo { padding: 25px 20px; }
        }
    </style>
</head>
<body>
    <div class="logo">FilmFlix</div>

    <div class="container">
        <div class="login-section">
            <h2>Giriş Yap</h2>
            <?php if(isset($hata)) echo "<div class='error'>$hata</div>"; ?>
            
            <form method="POST">
                <input type="email" name="email" placeholder="E-posta Adresi" required>
                
                <div class="password-wrapper">
                    <input type="password" name="sifre" id="sifreInput" placeholder="Şifre" required>
                    <i class="fa-solid fa-eye" id="toggleBtn" onclick="sifreGosterGizle()"></i>
                </div>

                <button type="submit" class="btn btn-yellow">Giriş Yap</button>
            </form>
            
            <div style="margin-top:15px; text-align: center;">
                <a href="sifremi-unuttum.php" style="color:#aaa; font-size:13px; text-decoration: none;">Şifremi Unuttum</a>
            </div>
        </div>
        
        <div class="register-promo">
            <h2>FilmFlix'e yeni misiniz?</h2>
            <p style="margin-bottom: 25px; line-height: 1.6; color: #ccc;">
                Hemen şimdi ücretsiz hesabınızı oluşturun ve binlerce film ve diziyi keşfetmeye başlayın.
            </p>
            <a href="kayit.php" class="btn btn-outline">Şimdi Kaydol</a>
        </div>
    </div>

    <script>
        function sifreGosterGizle() {
            var input = document.getElementById("sifreInput");
            var ikon = document.getElementById("toggleBtn");
            
            if (input.type === "password") {
                input.type = "text";
                ikon.classList.remove("fa-eye");
                ikon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                ikon.classList.remove("fa-eye-slash");
                ikon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>