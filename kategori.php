<?php
session_start();
include 'baglanti.php';

// Kategori ID gelmediyse ana sayfaya at
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$kat_id = $_GET['id'];

// 1. KATEGORİ ADINI BUL
$sorguKat = $db->prepare("SELECT ad FROM kategoriler WHERE id = ?");
$sorguKat->execute([$kat_id]);
$kategori = $sorguKat->fetch(PDO::FETCH_ASSOC);

if (!$kategori) {
    die("Böyle bir kategori bulunamadı.");
}

// 2. BU KATEGORİDEKİ FİLMLERİ ÇEK (tur = 'film')
$sqlFilm = "SELECT f.* FROM filmler f 
            JOIN film_kategoriler fk ON f.id = fk.film_id 
            WHERE fk.kategori_id = ? AND f.tur = 'film' 
            ORDER BY f.id DESC";
$stmtFilm = $db->prepare($sqlFilm);
$stmtFilm->execute([$kat_id]);
$filmler = $stmtFilm->fetchAll(PDO::FETCH_ASSOC);

// 3. BU KATEGORİDEKİ DİZİLERİ ÇEK (tur = 'dizi')
$sqlDizi = "SELECT f.* FROM filmler f 
            JOIN film_kategoriler fk ON f.id = fk.film_id 
            WHERE fk.kategori_id = ? AND f.tur = 'dizi' 
            ORDER BY f.id DESC";
$stmtDizi = $db->prepare($sqlDizi);
$stmtDizi->execute([$kat_id]);
$diziler = $stmtDizi->fetchAll(PDO::FETCH_ASSOC);

// Menü için kategorileri tekrar çekelim
$menuKategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">  
    <title><?php echo $kategori['ad']; ?> - FilmFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* GENEL AYARLAR (Index ile uyumlu) */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #141414; color: white; padding-top: 80px; } /* Header payı */
        a { text-decoration: none; color: white; transition: 0.3s; }

        /* NAVBAR (Aynı Tasarım) */
        header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 50px; position: fixed; width: 100%; top: 0; z-index: 1000;
            background-color: #141414; border-bottom: 1px solid #333;
        }
        .logo { color: #f5c518; font-size: 28px; font-weight: bold; margin-right: 40px; }
        nav ul { display: flex; list-style: none; align-items: center; }
        nav ul li { margin-right: 20px; }
        nav ul li a { font-size: 14px; opacity: 0.9; font-weight: bold; color: #e5e5e5; }
        nav ul li a:hover { color: #f5c518; }

        /* DROPDOWN MENÜ (SCROLL ÖZELLİKLİ) */
        .dropdown { position: relative; height: 100%; display: flex; align-items: center; }
        
        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: rgba(0, 0, 0, 0.95);
            min-width: 240px; /* Genişliği biraz artırdım */
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.5);
            z-index: 1001;
            border-top: 3px solid #f5c518;
            border-radius: 0 0 4px 4px;
            padding-top: 10px;
            
            /* İŞTE SİHİRLİ KODLAR BURADA */
            max-height: 60vh; /* Ekran yüksekliğinin %60'ını geçmesin */
            overflow-y: auto; /* İçeri sığmazsa kaydırma çubuğu çıksın */
            overflow-x: hidden; /* Yanlara taşmasın */
        }
        
        /* Menü içindeki linkler */
        .dropdown-content a {
            color: #ddd;
            padding: 12px 20px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: 0.2s;
            border-bottom: 1px solid #222;
        }
        .dropdown-content a:last-child { border-bottom: none; }
        
        .dropdown-content a:hover { 
            background-color: #f5c518; 
            color: #000; 
            font-weight: bold;
            padding-left: 25px; 
        }
        
        /* MENÜ İÇİN ÖZEL SCROLLBAR TASARIMI (Chrome, Safari, Edge) */
        .dropdown-content::-webkit-scrollbar {
            width: 8px; /* Çubuğun genişliği */
        }
        .dropdown-content::-webkit-scrollbar-track {
            background: #1a1a1a; /* Çubuğun arka planı */
            border-radius: 0 0 4px 0;
        }
        .dropdown-content::-webkit-scrollbar-thumb {
            background: #555; /* Kaydıran kısım (Gri) */
            border-radius: 4px;
        }
        .dropdown-content::-webkit-scrollbar-thumb:hover {
            background: #f5c518; /* Üzerine gelince Sarı olsun */
        }

        /* Hover olunca göster */
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown > a::after { content: ' \25BC'; font-size: 10px; margin-left: 5px; color: #f5c518; }

        /* SAYFA İÇERİĞİ */
        .container { padding: 40px 50px; }
        .page-title { 
            font-size: 36px; font-weight: bold; margin-bottom: 40px; 
            color: #f5c518; border-bottom: 1px solid #333; padding-bottom: 10px;
        }

        .section-header { font-size: 24px; font-weight: bold; margin-bottom: 20px; margin-top: 40px; display: flex; align-items: center; gap: 10px;}
        .section-header i { color: #f5c518; font-size: 18px; }

        /* GRID YAPISI (Izgara) */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 30px;
        }

        .card {
            background: #1f1f1f; border-radius: 4px; overflow: hidden;
            transition: transform 0.3s; cursor: pointer; position: relative;
        }
        .card:hover { transform: scale(1.05); z-index: 10; box-shadow: 0 0 15px rgba(245, 197, 24, 0.3); }
        .card img { width: 100%; height: 270px; object-fit: cover; }
        .card-info { padding: 10px; }
        .card-title { font-size: 14px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .empty-msg { color: #777; font-style: italic; padding: 20px; background: #1f1f1f; border-radius: 4px; }
    </style>
</head>
<body>

    <header>
        <div style="display:flex; align-items:center;">
            <div class="logo">FilmFlix</div>
            <nav>
                <ul>
                    <li><a href="index.php">Ana Sayfa</a></li>
                    
                    <li class="dropdown">
                        <a href="#">Kategoriler <span style="font-size:10px; color:#f5c518;"></span></a>
                        <div class="dropdown-content">
                            <?php foreach($menuKategoriler as $mk): ?>
                                <a href="kategori.php?id=<?php echo $mk['id']; ?>"><?php echo $mk['ad']; ?></a>
                            <?php endforeach; ?>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
        <div style="color:#aaa; font-size:14px;">Merhaba, <?php echo isset($_SESSION['ad_soyad']) ? $_SESSION['ad_soyad'] : 'Misafir'; ?></div>
    </header>

    <div class="container">
        
        <h1 class="page-title"><?php echo $kategori['ad']; ?> Kategorisi</h1>

        <div class="section-header">
            <i class="fa-solid fa-film"></i> Filmler
        </div>
        
        <?php if(count($filmler) > 0): ?>
            <div class="grid">
                <?php foreach($filmler as $f): ?>
                    <div class="card" onclick="window.location.href='film-detay.php?id=<?php echo $f['id']; ?>'">
                        <img src="<?php echo $f['resim_url']; ?>" alt="<?php echo $f['baslik']; ?>">
                        <div class="card-info">
                            <div class="card-title"><?php echo $f['baslik']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-msg">Bu kategoride henüz film bulunmuyor.</div>
        <?php endif; ?>


        <div class="section-header">
            <i class="fa-solid fa-tv"></i> Diziler
        </div>

        <?php if(count($diziler) > 0): ?>
            <div class="grid">
                <?php foreach($diziler as $d): ?>
                    <div class="card" onclick="window.location.href='film-detay.php?id=<?php echo $d['id']; ?>'">
                        <img src="<?php echo $d['resim_url']; ?>" alt="<?php echo $d['baslik']; ?>">
                        <div class="card-info">
                            <div class="card-title"><?php echo $d['baslik']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-msg">Bu kategoride henüz dizi bulunmuyor.</div>
        <?php endif; ?>

    </div>

</body>
</html>