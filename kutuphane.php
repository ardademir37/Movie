<?php
session_start();
include 'baglanti.php';

if (!isset($_SESSION['kullanici_id'])) { 
    header("Location: giris.php"); 
    exit; 
}
$user_id = $_SESSION['kullanici_id'];
$mevcutSayfa = basename($_SERVER['PHP_SELF']);

// Kütüphaneden Çıkar İşlemi
if (isset($_GET['sil'])) {
    $fid = $_GET['sil'];
    $db->prepare("DELETE FROM kutuphanem WHERE kullanici_id = ? AND film_id = ?")->execute([$user_id, $fid]);
    header("Location: kutuphane.php");
    exit;
}

// Tüm Kütüphaneyi Çek
$sql = "SELECT f.* FROM filmler f JOIN kutuphanem k ON f.id = k.film_id WHERE k.kullanici_id = $user_id ORDER BY k.ekleme_tarihi DESC";
$icerikler = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kütüphanem - FilmFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        .btn-logout { background-color: #f5c518; color: black; width: 32px; height: 32px; border-radius: 5px; display: flex; align-items: center; justify-content: center; }

        /* KONTEYNER */
        .container { padding: 20px 5%; max-width: 1400px; margin: 0 auto; }
        .page-title { font-size: 24px; font-weight: bold; margin-bottom: 25px; border-left: 5px solid #f5c518; padding-left: 15px; color: #fff; }

        /* GRID YAPISI */
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); 
            gap: 20px; 
        }

        /* KARTLAR */
        .card { 
            background: #1f1f1f; border-radius: 8px; overflow: hidden; 
            position: relative; transition: transform 0.3s ease; cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        .card:hover { transform: translateY(-5px); z-index: 2; }
        .card img { width: 100%; height: 270px; object-fit: cover; display: block; }

        /* SİL BUTONU */
        .remove-btn { 
            position: absolute; top: 10px; right: 10px; background: rgba(255, 0, 0, 0.8); 
            color: white; width: 32px; height: 32px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            opacity: 0; transition: 0.3s; z-index: 5;
        }
        /* Masaüstünde hover olunca göster, mobilde (dokunmatik) her zaman göster */
        .card:hover .remove-btn { opacity: 1; }

        /* BOŞ DURUM */
        .empty-state { text-align: center; padding: 100px 20px; color: #555; }
        .empty-state i { font-size: 50px; margin-bottom: 15px; }

        /* MOBİL RESPONSIVE */
        @media (max-width: 768px) {
            body { padding-top: 70px; }
            .nav-links { display: none; }
            .user-name { display: none; }
            .grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; }
            .card img { height: 210px; }
            .remove-btn { opacity: 1; width: 28px; height: 28px; font-size: 12px; } /* Mobilde butonu hep göster */
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
                <li><a href="kutuphane.php" class="active">Listem</a></li>
                <li><a href="gecmis.php">Geçmiş</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></span>
            <a href="cikis.php" class="btn-logout" title="Çıkış Yap"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">İzleme Listem (<?php echo count($icerikler); ?>)</h1>
        
        <?php if(count($icerikler) > 0): ?>
            <div class="grid">
                <?php foreach($icerikler as $f): ?>
                    <div class="card" onclick="window.location.href='film-detay.php?id=<?php echo $f['id']; ?>'">
                        <img src="<?php echo htmlspecialchars($f['resim_url']); ?>" alt="<?php echo htmlspecialchars($f['baslik']); ?>">
                        <a href="kutuphane.php?sil=<?php echo $f['id']; ?>" class="remove-btn" 
                           onclick="event.stopPropagation(); return confirm('Bu içeriği listenizden çıkarmak istediğinize emin misiniz?');" 
                           title="Listeden Çıkar">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-bookmark"></i>
                <p>İzleme listeniz şu an boş.</p>
                <a href="index.php" style="color:#f5c518; display:inline-block; margin-top:15px; font-weight:bold;">İçeriklere Göz At →</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>