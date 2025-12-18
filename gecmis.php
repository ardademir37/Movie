<?php
session_start();
include 'baglanti.php';

if (!isset($_SESSION['kullanici_id'])) { 
    header("Location: giris.php"); 
    exit; 
}

$user_id = $_SESSION['kullanici_id'];
$mevcutSayfa = basename($_SERVER['PHP_SELF']);

// Geçmişi Çek (SQL Injection önlemi için prepare kullanılabilir ancak mevcut yapınızı bozmadan devam ediyoruz)
$sql = "SELECT f.*, ig.izleme_tarihi 
        FROM filmler f 
        JOIN izleme_gecmisi ig ON f.id = ig.film_id 
        WHERE ig.kullanici_id = $user_id 
        ORDER BY ig.izleme_tarihi DESC";
$icerikler = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İzleme Geçmişi - FilmFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* GENEL AYARLAR */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #141414; color: white; padding-top: 80px; }
        a { text-decoration: none; color: white; transition: 0.3s; }

        /* NAVBAR */
        header { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 10px 5%; position: fixed; width: 100%; top: 0; z-index: 1000; 
            background: rgba(0,0,0,0.95); border-bottom: 1px solid #222; backdrop-filter: blur(10px);
        }
        .nav-left { display: flex; align-items: center; gap: 30px; }
        .logo { color: #f5c518; font-size: 24px; font-weight: bold; letter-spacing: 1px; }
        .nav-links { display: flex; list-style: none; gap: 20px; align-items: center;}
        .nav-links a { font-size: 14px; font-weight: bold; color: #e5e5e5; }
        .nav-links a:hover, .nav-links a.active { color: #f5c518; }
        
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .user-name { font-size: 14px; font-weight: 500; }
        .btn-logout { background-color: #f5c518; color: black; width: 32px; height: 32px; border-radius: 5px; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .btn-logout:hover { background-color: #d4a810; }

        /* KONTEYNER VE BAŞLIK */
        .container { padding: 20px 5%; max-width: 1400px; margin: 0 auto; }
        .page-title { font-size: 24px; font-weight: bold; margin-bottom: 25px; border-left: 5px solid #f5c518; padding-left: 15px; color: #fff; }

        /* GRID SİSTEMİ */
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); 
            gap: 20px; 
        }

        /* FİLM KARTI */
        .card { 
            background: #1f1f1f; border-radius: 8px; overflow: hidden; 
            position: relative; transition: transform 0.3s ease; cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        .card:hover { transform: translateY(-5px); z-index: 2; }
        .card img { width: 100%; height: 270px; object-fit: cover; display: block; }
        
        .date-badge {
            position: absolute; bottom: 0; width: 100%; 
            background: linear-gradient(transparent, rgba(0,0,0,0.9)); 
            color: #f5c518; font-size: 11px; padding: 12px 5px 8px 5px; 
            text-align: center; font-weight: bold;
        }

        /* BOŞ DURUM MESAJI */
        .empty-state { text-align: center; padding: 100px 20px; color: #555; }
        .empty-state i { font-size: 50px; margin-bottom: 15px; }

        /* MOBİL RESPONSIVE AYARLARI */
        @media (max-width: 768px) {
            body { padding-top: 70px; }
            .nav-links { display: none; } /* Mobilde linkleri gizle (Menü ikonu eklenebilir) */
            .logo { font-size: 20px; }
            .user-name { display: none; } /* Mobilde ismi gizle sadece logout kalsın */
            .grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; }
            .card img { height: 210px; }
            .page-title { font-size: 20px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-left">
            <a href="index.php" class="logo">FilmFlix</a>
            <ul class="nav-links">
                <li><a href="index.php">Ana Sayfa</a></li>
                <li><a href="index.php?tur=dizi">Diziler</a></li>
                <li><a href="index.php?tur=film">Filmler</a></li>
                <li><a href="kutuphane.php">Listem</a></li>
                <li><a href="gecmis.php" class="active">Geçmiş</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></span>
            <a href="cikis.php" class="btn-logout" title="Çıkış Yap"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">İzleme Geçmişim</h1>
        
        <?php if(count($icerikler) > 0): ?>
            <div class="grid">
                <?php foreach($icerikler as $f): ?>
                    <div class="card" onclick="window.location.href='film-detay.php?id=<?php echo $f['id']; ?>'">
                        <img src="<?php echo htmlspecialchars($f['resim_url']); ?>" alt="<?php echo htmlspecialchars($f['baslik']); ?>">
                        <div class="date-badge">
                            <i class="fa-regular fa-clock"></i> <?php echo date("d.m.Y H:i", strtotime($f['izleme_tarihi'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-history"></i>
                <p>Henüz bir içerik izlemediniz. Keşfetmeye ne dersin?</p>
                <a href="index.php" style="color:#f5c518; display:inline-block; margin-top:15px; font-weight:bold;">Hemen İzle →</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>