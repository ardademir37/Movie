<?php
session_start();
include 'baglanti.php';

// 1. GÜVENLİK
if (!isset($_SESSION['kullanici_id'])) { header("Location: giris.php"); exit; }
$user_id = $_SESSION['kullanici_id'];

// ID KONTROLÜ
if (!isset($_GET['id']) || empty($_GET['id'])) { header("Location: index.php"); exit; }
$film_id = $_GET['id'];

// GEÇMİŞE KAYDET
$kontrol = $db->prepare("SELECT id FROM izleme_gecmisi WHERE kullanici_id = ? AND film_id = ?");
$kontrol->execute([$user_id, $film_id]);
if ($kontrol->rowCount() > 0) {
    $db->prepare("UPDATE izleme_gecmisi SET izleme_tarihi = NOW() WHERE kullanici_id = ? AND film_id = ?")->execute([$user_id, $film_id]);
} else {
    $db->prepare("INSERT INTO izleme_gecmisi (kullanici_id, film_id) VALUES (?, ?)")->execute([$user_id, $film_id]);
}

// VERİLERİ ÇEK
$sqlFilm = "SELECT f.*, GROUP_CONCAT(k.ad SEPARATOR ', ') as kategori_isimleri FROM filmler f LEFT JOIN film_kategoriler fk ON f.id = fk.film_id LEFT JOIN kategoriler k ON fk.kategori_id = k.id WHERE f.id = ? GROUP BY f.id";
$stmt = $db->prepare($sqlFilm);
$stmt->execute([$film_id]);
$film = $stmt->fetch(PDO::FETCH_ASSOC);

// Ortalama Puan
$stmtPuan = $db->prepare("SELECT AVG(puan) as ortalama FROM yorumlar WHERE film_id = ?");
$stmtPuan->execute([$film_id]);
$ortalamaPuan = $stmtPuan->fetch(PDO::FETCH_ASSOC)['ortalama'];
$ortalamaPuan = $ortalamaPuan ? number_format($ortalamaPuan, 1) : "N/A";

// Yorumlar
$sqlYorumlar = "SELECT y.*, k.ad, k.soyad FROM yorumlar y JOIN kullanicilar k ON y.kullanici_id = k.id WHERE y.film_id = ? ORDER BY y.tarih DESC";
$yorumlar = $db->prepare($sqlYorumlar);
$yorumlar->execute([$film_id]);
$yorumlar = $yorumlar->fetchAll(PDO::FETCH_ASSOC);

// Kütüphane Durumu
$kutuphanede_mi = $db->query("SELECT id FROM kutuphanem WHERE kullanici_id=$user_id AND film_id=$film_id")->rowCount();

// NAVBAR İÇİN KATEGORİLER
$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($film['baslik']); ?> - FilmFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #141414; color: white; overflow-x: hidden; }
        a { text-decoration: none; color: white; transition: 0.3s; }

        /* NAVBAR */
        header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 20px; position: fixed; width: 100%; top: 0; z-index: 1000;
            background: rgba(0,0,0,0.9); backdrop-filter: blur(10px);
            border-bottom: 1px solid #222;
        }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .logo { color: #f5c518; font-size: 24px; font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 15px; align-items: center; }
        .nav-links a { font-size: 13px; font-weight: bold; color: #e5e5e5; }
        .nav-links a:hover { color: #f5c518; }
        
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .search-box { background: #000; border: 1px solid #444; border-radius: 20px; padding: 5px 12px; display: flex; align-items: center; }
        .search-box input { background: transparent; border: none; color: #ccc; outline: none; font-size: 13px; width: 120px; }
        .btn-logout { background-color: #f5c518; color: black; width: 32px; height: 32px; border-radius: 5px; display: flex; align-items: center; justify-content: center; }

        /* DROPDOWN */
        .dropdown { position: relative; cursor: pointer; }
        .dropdown-content {
            display: none; position: absolute; top: 100%; left: 0;
            background-color: rgba(0, 0, 0, 0.95); min-width: 180px;
            border-top: 3px solid #f5c518; max-height: 50vh; overflow-y: auto;
        }
        .dropdown-content a { padding: 10px 15px; display: block; border-bottom: 1px solid #222; font-size: 13px; }
        .dropdown:hover .dropdown-content { display: block; }

        /* HERO BÖLÜMÜ */
        .hero {
            min-height: 75vh; margin-top: 50px; position: relative;
            background-image: linear-gradient(to right, #141414 30%, rgba(20,20,20,0.6) 100%), 
                              url('<?php echo $film['kapak_resmi']; ?>');
            background-size: cover; background-position: center;
            display: flex; align-items: center; padding: 40px 20px;
        }
        
        .hero-container { display: flex; align-items: center; gap: 40px; z-index: 2; max-width: 1200px; margin: 0 auto; width: 100%; }
        .hero-poster { width: 200px; height: 300px; object-fit: cover; border-radius: 8px; border: 2px solid #333; flex-shrink: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .hero-content { flex: 1; }
        .film-title { font-size: clamp(30px, 5vw, 50px); font-weight: 800; margin-bottom: 10px; color: #f5c518; text-transform: uppercase; line-height: 1.1; }
        
        .meta-data { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; margin-bottom: 25px; color: #ccc; font-size: 14px; }
        .score-box { border: 1px solid #f5c518; padding: 3px 8px; color: #f5c518; border-radius: 4px; font-weight: bold; }

        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-large { padding: 12px 25px; border-radius: 4px; font-weight: bold; cursor: pointer; border: none; font-size: 15px; display: flex; align-items: center; gap: 8px; }
        .btn-play { background: #f5c518; color: black; }
        .btn-list { background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(5px); }
        .btn-list.added { background: #4CAF50; border-color: #4CAF50; }

        /* İÇERİK ALANLARI */
        .description-container, .comments-section { padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
        .desc-title, .section-header { font-size: 20px; color: #f5c518; margin-bottom: 15px; border-left: 4px solid #f5c518; padding-left: 12px; }
        .film-desc-text { font-size: 16px; line-height: 1.7; color: #bbb; background: #1f1f1f; padding: 20px; border-radius: 8px; }

        /* YORUMLAR */
        .comment-form { background: #1f1f1f; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        textarea { width: 100%; padding: 12px; background: #111; color: white; border: 1px solid #333; border-radius: 4px; margin: 10px 0; resize: vertical; }
        .btn-submit { background: #f5c518; color: black; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .comment-card { background: #1a1a1a; padding: 15px; border-radius: 8px; border-left: 3px solid #333; margin-bottom: 15px; }
        .comment-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .comment-date { color: #555; font-size: 11px; margin-top: 10px; display: block; }

        /* PUANLAMA */
        .star-rating { direction: rtl; display: inline-flex; font-size: 20px; }
        .star-rating input { display: none; }
        .star-rating label { color: #444; padding: 0 2px; cursor: pointer; }
        .star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { color: #f5c518; }

        /* MOBİL RESPONSIVE AYARLARI */
        @media (max-width: 768px) {
            header { padding: 10px; }
            .nav-links { display: none; } /* Basitlik için mobilde ana linkleri gizledim veya burger eklenebilir */
            .search-box input { width: 80px; }
            
            .hero { 
                background-image: linear-gradient(to top, #141414 40%, rgba(20,20,20,0.2) 100%), 
                                  url('<?php echo $film['kapak_resmi']; ?>');
                padding-top: 80px; align-items: flex-start;
            }
            .hero-container { flex-direction: column; text-align: center; gap: 20px; }
            .hero-poster { width: 160px; height: 240px; }
            .action-buttons { justify-content: center; }
            .btn-large { width: 100%; justify-content: center; }
            .meta-data { justify-content: center; }
            .description-container, .comments-section { padding: 20px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="nav-left">
            <a href="index.php" class="logo">FilmFlix</a>
            <ul class="nav-links">
                <li><a href="index.php">Ana Sayfa</a></li>
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
                <input type="text" name="arama" placeholder="Ara...">
                <button type="submit" style="background:none; border:none; color:#888; cursor:pointer;"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <a href="cikis.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </header>

    <div class="hero">
        <div class="hero-container">
            <img src="<?php echo $film['resim_url']; ?>" class="hero-poster" alt="Poster">
            
            <div class="hero-content">
                <h1 class="film-title"><?php echo htmlspecialchars($film['baslik']); ?></h1>
                
                <div class="meta-data">
                    <span class="score-box"><i class="fa-solid fa-star"></i> <?php echo $ortalamaPuan; ?></span>
                    <span><i class="fa-solid fa-film"></i> <?php echo ($film['tur'] == 'film' ? 'Film' : 'Dizi'); ?></span>
                    <span style="color:#f5c518;"><?php echo htmlspecialchars($film['kategori_isimleri']); ?></span>
                </div>

                <div class="action-buttons">
                    <button class="btn-large btn-play"><i class="fa-solid fa-play"></i> Hemen İzle</button>
                    
                    <button id="libBtn" class="btn-large btn-list <?php echo $kutuphanede_mi ? 'added' : ''; ?>" onclick="kutuphaneIslem(<?php echo $film_id; ?>)">
                        <i class="fa-solid <?php echo $kutuphanede_mi ? 'fa-check' : 'fa-plus'; ?>"></i> 
                        <span><?php echo $kutuphanede_mi ? 'Listemde' : 'Listeme Ekle'; ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="description-container">
        <div class="desc-title">Özet</div>
        <div class="film-desc-text">
            <?php echo nl2br(htmlspecialchars($film['aciklama'])); ?>
        </div>
    </div>

    <div class="comments-section">
        <div class="section-header">Yorumlar</div>

        <div class="comment-form">
            <form id="yorumFormu">
                <input type="hidden" name="film_id" value="<?php echo $film_id; ?>">
                <input type="hidden" name="action" value="add_comment">
                
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                    <span style="color:#888; font-size:14px;">Puanınız:</span>
                    <div class="star-rating">
                        <?php for($i=10; $i>=1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="puan" value="<?php echo $i; ?>"><label for="star<?php echo $i; ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>

                <textarea name="yorum" rows="3" placeholder="Film hakkında ne düşünüyorsun?" required></textarea>
                <button type="button" onclick="yorumGonder()" class="btn-submit">Gönder</button>
            </form>
        </div>

        <div id="yorumListesi">
            <?php foreach($yorumlar as $y): ?>
                <?php $gorunen_isim = htmlspecialchars($y['ad'] . " " . mb_substr($y['soyad'], 0, 1, 'UTF-8') . "."); ?>
                <div class="comment-card">
                    <div class="comment-header">
                        <span style="font-weight:bold; color:#f5c518;"><?php echo $gorunen_isim; ?></span>
                        <span style="color:gold; font-size:13px;">★ <?php echo $y['puan']; ?>/10</span>
                    </div>
                    <div style="color:#ccc; font-size:14px;"><?php echo nl2br(htmlspecialchars($y['yorum'])); ?></div>
                    <span class="comment-date"><?php echo date("d.m.Y H:i", strtotime($y['tarih'])); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Kütüphane ve Yorum fonksiyonları mevcut halini korur (api.php ile çalışır)
        function kutuphaneIslem(filmId) {
            let btn = document.getElementById('libBtn');
            let icon = btn.querySelector('i');
            let span = btn.querySelector('span');
            let formData = new FormData();
            formData.append('action', 'toggle_library');
            formData.append('film_id', filmId);

            fetch('api.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'added') {
                    btn.classList.add('added');
                    icon.className = 'fa-solid fa-check';
                    span.innerText = 'Listemde';
                } else {
                    btn.classList.remove('added');
                    icon.className = 'fa-solid fa-plus';
                    span.innerText = 'Listeme Ekle';
                }
            });
        }

        function yorumGonder() {
            let form = document.getElementById('yorumFormu');
            let formData = new FormData(form);
            fetch('api.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    location.reload(); // Kolaylık için yenileme (veya dinamik ekleme devam edebilir)
                } else {
                    alert("Lütfen puan seçin ve yorum yazın!");
                }
            });
        }
    </script>
</body>
</html>