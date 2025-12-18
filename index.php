<?php
session_start();
include 'baglanti.php';

// 1. GÜVENLİK
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit;
}
$user_id = $_SESSION['kullanici_id'];

// --- DEĞİŞKEN BAŞLATMA (Hataları önlemek için) ---
$mod = 'anasayfa'; 
$filtre_icerik = []; 
$filtre_baslik = "";
// ------------------------------------------------

// A) ARAMA MANTIĞI
if (isset($_GET['arama']) && !empty($_GET['arama'])) {
    $mod = 'liste';
    $kelime = strip_tags($_GET['arama']);
    $filtre_baslik = "\"$kelime\" Sonuçları";
    $sql = "SELECT * FROM filmler WHERE baslik LIKE ? OR aciklama LIKE ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(["%$kelime%", "%$kelime%"]);
    $filtre_icerik = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// B) TÜR SEÇİLDİYSE
elseif (isset($_GET['tur'])) {
    $mod = 'liste';
    $tur = $_GET['tur'];
    $filtre_baslik = ($tur == 'film') ? "Tüm Filmler" : "Tüm Diziler";
    $stmt = $db->prepare("SELECT * FROM filmler WHERE tur = ? ORDER BY id DESC");
    $stmt->execute([$tur]);
    $filtre_icerik = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// C) KATEGORİ SEÇİLDİYSE
elseif (isset($_GET['kategori_id'])) {
    $mod = 'liste';
    $kat_id = $_GET['kategori_id'];
    $k_ad = $db->prepare("SELECT ad FROM kategoriler WHERE id = ?");
    $k_ad->execute([$kat_id]);
    $k_veri = $k_ad->fetch(PDO::FETCH_ASSOC);
    $filtre_baslik = $k_veri ? $k_veri['ad'] . " Kategorisi" : "Kategori";

    $sql = "SELECT f.* FROM filmler f 
            JOIN film_kategoriler fk ON f.id = fk.film_id 
            WHERE fk.kategori_id = ? 
            ORDER BY f.id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$kat_id]);
    $filtre_icerik = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ANA SAYFA VERİLERİ (Sliderlar için)
if ($mod == 'anasayfa') {
    $heroFilm = $db->query("SELECT * FROM filmler ORDER BY RAND() LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$heroFilm) $heroFilm = ['baslik'=>'FilmFlix', 'aciklama'=>'İçerik Bulunmuyor', 'kapak_resmi'=>'', 'id'=>0];

    $kesfetList = $db->query("SELECT * FROM filmler ORDER BY RAND() LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
    $filmList = $db->query("SELECT * FROM filmler WHERE tur='film' ORDER BY RAND() LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
    $diziList = $db->query("SELECT * FROM filmler WHERE tur='dizi' ORDER BY RAND() LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
    $kutuphaneList = $db->query("SELECT f.* FROM filmler f JOIN kutuphanem k ON f.id = k.film_id WHERE k.kullanici_id = $user_id ORDER BY k.ekleme_tarihi DESC")->fetchAll(PDO::FETCH_ASSOC);
    $gecmisList = $db->query("SELECT f.* FROM filmler f JOIN izleme_gecmisi ig ON f.id = ig.film_id WHERE ig.kullanici_id = $user_id ORDER BY ig.izleme_tarihi DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
}

$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #141414; color: white; overflow-x: hidden; padding-bottom: 50px; }
        a { text-decoration: none; color: white; transition: 0.3s; }
        
        /* NAVBAR */
        header { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 10px 5%; position: fixed; width: 100%; top: 0; z-index: 1000; 
            background: rgba(0,0,0,0.9); border-bottom: 1px solid #222; backdrop-filter: blur(10px);
        }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .logo { color: #f5c518; font-size: 24px; font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 15px; }
        .nav-links a { font-size: 13px; font-weight: bold; color: #e5e5e5; }
        .nav-links a:hover, .nav-links a.active { color: #f5c518; }
        
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .search-box { background: #000; border: 1px solid #444; border-radius: 20px; padding: 5px 12px; display: flex; align-items: center; }
        .search-box input { background: transparent; border: none; color: #ccc; outline: none; font-size: 13px; width: 120px; transition: 0.3s; }
        .search-box input:focus { width: 180px; }
        .btn-logout { background-color: #f5c518; color: black; width: 32px; height: 32px; border-radius: 5px; display: flex; align-items: center; justify-content: center; }

        .dropdown { position: relative; cursor: pointer; }
        .dropdown-content { display: none; position: absolute; top: 100%; left: 0; background: #111; min-width: 180px; border-top: 2px solid #f5c518; max-height: 50vh; overflow-y: auto; }
        .dropdown-content a { padding: 12px 20px; display: block; border-bottom: 1px solid #222; font-size: 13px; }
        .dropdown:hover .dropdown-content { display: block; }

        /* HERO */
        .hero { 
            height: 70vh; margin-top: 50px; 
            background-image: linear-gradient(to right, #141414 15%, transparent 100%), 
                              linear-gradient(to top, #141414 5%, transparent 30%), 
                              url('<?php echo $heroFilm['kapak_resmi'] ?? ''; ?>'); 
            background-size: cover; background-position: center; 
            display: flex; flex-direction: column; justify-content: center; 
            padding: 0 5%; margin-bottom: 30px; 
        }
        .hero-title { font-size: clamp(30px, 6vw, 60px); font-weight: 800; margin-bottom: 10px; color: #f5c518; text-transform: uppercase; text-shadow: 2px 2px 15px black; }
        .hero-desc { font-size: clamp(14px, 2vw, 18px); margin-bottom: 25px; max-width: 550px; text-shadow: 1px 1px 5px black; color: #ddd; }
        .btn-hero { padding: 12px 25px; border-radius: 4px; font-weight: bold; border: none; font-size: 15px; margin-right: 10px; display: inline-flex; align-items: center; gap: 8px; }

        /* SLIDER KARTLARI */
        .category-row { margin-bottom: 40px; padding: 0 5%; }
        .section-title { font-size: 20px; font-weight: bold; color: #fff; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .slider-wrapper { position: relative; }
        .slider-container { display: flex; gap: 10px; overflow-x: auto; scroll-behavior: smooth; scrollbar-width: none; padding: 10px 0; }
        .slider-container::-webkit-scrollbar { display: none; }
        
        .movie-card { flex: 0 0 auto; width: 180px; background: #1f1f1f; border-radius: 6px; overflow: hidden; transition: 0.3s; cursor: pointer; position: relative; }
        .movie-card:hover { transform: scale(1.08); z-index: 10; }
        .movie-card img { width: 100%; height: 260px; object-fit: cover; display: block; }
        
        .scroll-btn { position: absolute; top: 0; bottom: 0; width: 40px; background: rgba(0,0,0,0.6); border: none; color: white; cursor: pointer; z-index: 5; opacity: 0; transition: 0.3s; }
        .slider-wrapper:hover .scroll-btn { opacity: 1; }
        .scroll-left { left: -40px; }
        .scroll-right { right: -40px; }

        /* GRID (Arama Sonuçları İçin) */
        .grid-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); 
            gap: 25px; padding: 0 5%; margin-top: 20px; 
        }
        .grid-container .movie-card { width: 100%; } /* Grid içinde kartlar genişler */
        
        .page-header { padding: 80px 5% 20px 5%; border-bottom: 1px solid #333; }
        .add-list-btn { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; border: 1px solid #fff; width: 30px; height: 30px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 12px; }

        @media (max-width: 768px) {
            .nav-links, .user-name, .scroll-btn { display: none; }
            .hero { height: 50vh; }
            .movie-card { width: 140px; }
            .movie-card img { height: 200px; }
            .grid-container { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="nav-left">
            <a href="index.php" class="logo">FilmFlix</a>
            <ul class="nav-links">
                <li><a href="index.php" class="<?php echo ($mod=='anasayfa')?'active':''; ?>">Ana Sayfa</a></li>
                <li class="dropdown">
                    <a>Kategoriler <i class="fa-solid fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <?php foreach($kategoriler as $mk): ?>
                            <a href="index.php?kategori_id=<?php echo $mk['id']; ?>"><?php echo htmlspecialchars($mk['ad']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </li>
                <li><a href="kutuphane.php">Listem</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <form action="index.php" method="GET" class="search-box">
                <input type="text" name="arama" placeholder="Ara..." value="<?php echo htmlspecialchars($_GET['arama'] ?? ''); ?>">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <a href="cikis.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </header>

    <?php if ($mod == 'anasayfa'): ?>
        
        <div class="hero">
            <h1 class="hero-title"><?php echo htmlspecialchars($heroFilm['baslik']); ?></h1>
            <p class="hero-desc"><?php echo mb_substr(strip_tags($heroFilm['aciklama']), 0, 150) . "..."; ?></p>
            <div style="display: flex;">
                <a href="film-detay.php?id=<?php echo $heroFilm['id']; ?>" class="btn-hero" style="background:#f5c518; color:black;">
                    <i class="fa-solid fa-play"></i> İzle
                </a>
            </div>
        </div>

        <?php 
        $bolumler = [
            ['id' => 's1', 'baslik' => 'Keşfet', 'icon' => 'fa-fire', 'liste' => $kesfetList],
            ['id' => 's2', 'baslik' => 'Filmler', 'icon' => 'fa-film', 'liste' => $filmList],
            ['id' => 's3', 'baslik' => 'Diziler', 'icon' => 'fa-tv', 'liste' => $diziList],
            ['id' => 's4', 'baslik' => 'Listem', 'icon' => 'fa-bookmark', 'liste' => $kutuphaneList],
            ['id' => 's5', 'baslik' => 'İzlemeye Devam Et', 'icon' => 'fa-clock-rotate-left', 'liste' => $gecmisList]
        ];

        foreach ($bolumler as $bolum): 
            if (empty($bolum['liste'])) continue;
        ?>
            <div class="category-row">
                <div class="section-title"><i class="fa-solid <?php echo $bolum['icon']; ?>"></i> <?php echo $bolum['baslik']; ?></div>
                <div class="slider-wrapper">
                    <button class="scroll-btn scroll-left" onclick="kaydir('<?php echo $bolum['id']; ?>', -300)"><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="slider-container" id="<?php echo $bolum['id']; ?>">
                        <?php foreach($bolum['liste'] as $f): ?>
                            <div class="movie-card" onclick="location.href='film-detay.php?id=<?php echo $f['id']; ?>'">
                                <img src="<?php echo htmlspecialchars($f['resim_url']); ?>" loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="scroll-btn scroll-right" onclick="kaydir('<?php echo $bolum['id']; ?>', 300)"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        <?php endforeach; ?>

    <?php else: ?>

        <div class="page-header">
            <h2><?php echo htmlspecialchars($filtre_baslik ?? ''); ?></h2>
            <p style="color:#888; margin-top:5px;"><?php echo count($filtre_icerik ?? []); ?> sonuç listeleniyor</p>
        </div>
        
        <?php if(!empty($filtre_icerik)): ?>
            <div class="grid-container">
                <?php foreach($filtre_icerik as $f): ?>
                    <div class="movie-card" onclick="location.href='film-detay.php?id=<?php echo $f['id']; ?>'">
                        <img src="<?php echo htmlspecialchars($f['resim_url']); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:100px 20px; color:#555;">İçerik bulunamadı.</div>
        <?php endif; ?>
        
        <div style="text-align:center; margin-top:40px;">
            <a href="index.php" style="border: 1px solid #444; color:white; padding:10px 25px; border-radius:30px;">Geri Dön</a>
        </div>

    <?php endif; ?>

    <script>
        function kaydir(id, val) {
            document.getElementById(id).scrollBy({ left: val, behavior: 'smooth' });
        }
    </script>
</body>
</html>