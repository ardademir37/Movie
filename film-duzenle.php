<?php
session_start();
include 'baglanti.php';

// Güvenlik
if (!isset($_SESSION['kullanici_id']) || ($_SESSION['rol'] != 'admin' && $_SESSION['rol'] != 'yonetici')) {
    die("Yetkisiz erişim.");
}

if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$id = $_GET['id'];

// ---------------------------------------------------------
// GÜNCELLEME İŞLEMİ
// ---------------------------------------------------------
if (isset($_POST['guncelle'])) {
    $baslik = $_POST['baslik'];
    $aciklama = $_POST['aciklama'];
    $tur = $_POST['tur'];
    
    $yeni_kategoriler = isset($_POST['kategoriler']) ? $_POST['kategoriler'] : [];

    // Eski resimleri çek
    $eskiVeri = $db->prepare("SELECT resim_url, kapak_resmi FROM filmler WHERE id = ?");
    $eskiVeri->execute([$id]);
    $mevcut = $eskiVeri->fetch(PDO::FETCH_ASSOC);

    $resimYolu = $mevcut['resim_url'];
    $kapakYolu = $mevcut['kapak_resmi'];

    function dosyaYukle($dosya, $hedefKlasor) {
        if (!isset($dosya) || $dosya['error'] != 0) return null;
        if (!file_exists($hedefKlasor)) mkdir($hedefKlasor, 0777, true);
        $yeniAd = $hedefKlasor . time() . "_" . basename($dosya["name"]);
        if (move_uploaded_file($dosya["tmp_name"], $yeniAd)) return $yeniAd;
        return null;
    }

    if (!empty($_FILES['resim']['name'])) {
        $yuklenen = dosyaYukle($_FILES['resim'], "uploads/");
        if ($yuklenen) $resimYolu = $yuklenen;
    }
    if (!empty($_FILES['kapak']['name'])) {
        $yuklenen = dosyaYukle($_FILES['kapak'], "uploads/");
        if ($yuklenen) $kapakYolu = $yuklenen;
    }

    $sql = "UPDATE filmler SET baslik=?, aciklama=?, tur=?, resim_url=?, kapak_resmi=? WHERE id=?";
    $db->prepare($sql)->execute([$baslik, $aciklama, $tur, $resimYolu, $kapakYolu, $id]);

    $db->prepare("DELETE FROM film_kategoriler WHERE film_id = ?")->execute([$id]);
    
    $ekle_kat = $db->prepare("INSERT INTO film_kategoriler (film_id, kategori_id) VALUES (?, ?)");
    foreach ($yeni_kategoriler as $kat_id) {
        $ekle_kat->execute([$id, $kat_id]);
    }

    $mesaj = "Güncelleme başarılı!";
    header("Refresh: 2; url=admin.php");
}

// ---------------------------------------------------------
// VERİLERİ ÇEKME
// ---------------------------------------------------------
$film = $db->prepare("SELECT * FROM filmler WHERE id = ?");
$film->execute([$id]);
$film = $film->fetch(PDO::FETCH_ASSOC);

$tum_kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);

$secili_sorgu = $db->prepare("SELECT kategori_id FROM film_kategoriler WHERE film_id = ?");
$secili_sorgu->execute([$id]);
$secili_kategoriler = $secili_sorgu->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Düzenle - <?php echo htmlspecialchars($film['baslik']); ?></title>
    <style>
        body { 
            background-color: #141414; 
            color: white; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .box { 
            background: #1f1f1f; 
            padding: 25px; 
            border-radius: 8px; 
            width: 100%;
            max-width: 500px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
        }
        h2 { color: #f5c518; margin-top: 0; margin-bottom: 20px; font-size: 22px; text-align: center; }
        
        label { display: block; margin-bottom: 8px; color: #f5c518; font-size: 14px; font-weight: bold; }
        
        input[type="text"], select, textarea, input[type="file"] { 
            width: 100%; 
            padding: 12px; 
            margin-bottom: 20px; 
            background: #2b2b2b; 
            color: white; 
            border: 1px solid #444; 
            border-radius: 4px;
            box-sizing: border-box; 
            font-size: 14px;
        }
        
        input:focus, textarea:focus { outline: 1px solid #f5c518; border-color: #f5c518; }

        .btn { 
            width: 100%; 
            padding: 14px; 
            background: #f5c518; 
            color: black; 
            border: none; 
            font-weight: bold; 
            cursor: pointer; 
            border-radius: 4px;
            font-size: 16px;
            transition: 0.3s;
        }
        .btn:hover { background: #d4a810; }

        .checkbox-list { 
            max-height: 150px; 
            overflow-y: auto; 
            background: #252525; 
            border: 1px solid #444; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 20px; 
        }
        .checkbox-item { 
            display: flex; 
            align-items: center; 
            margin-bottom: 8px; 
            cursor: pointer; 
            color: #ddd; 
            font-size: 14px;
        }
        .checkbox-item input { margin-right: 12px; transform: scale(1.2); }
        
        .preview-container { display: flex; align-items: flex-start; gap: 15px; margin-bottom: 10px; }
        .preview { 
            width: 70px; 
            height: auto; 
            border-radius: 4px; 
            border: 1px solid #444; 
            object-fit: cover;
        }
        .alert { 
            background: rgba(0, 128, 0, 0.2); 
            color: #4CAF50; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 20px; 
            text-align: center;
            border: 1px solid #4CAF50;
        }

        /* Mobil Ayarlamalar */
        @media (max-width: 480px) {
            .box { padding: 15px; }
            h2 { font-size: 20px; }
            .btn { padding: 12px; }
        }
    </style>
</head>
<body>

    <div class="box">
        <h2><i class="fa-solid fa-pen-to-square"></i> Filmi Düzenle</h2>
        
        <?php if(isset($mesaj)): ?>
            <div class="alert">
                <i class="fa-solid fa-check-circle"></i> <?php echo $mesaj; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Başlık</label>
            <input type="text" name="baslik" value="<?php echo htmlspecialchars($film['baslik']); ?>" required>
            
            <label>Kategoriler</label>
            <div class="checkbox-list">
                <?php foreach($tum_kategoriler as $k): ?>
                    <?php $isaretli = in_array($k['id'], $secili_kategoriler) ? 'checked' : ''; ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="kategoriler[]" value="<?php echo $k['id']; ?>" <?php echo $isaretli; ?>>
                        <?php echo htmlspecialchars($k['ad']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <label>Tür</label>
            <select name="tur">
                <option value="film" <?php if($film['tur']=='film') echo 'selected'; ?>>Film</option>
                <option value="dizi" <?php if($film['tur']=='dizi') echo 'selected'; ?>>Dizi</option>
            </select>

            <label>Açıklama</label>
            <textarea name="aciklama" rows="4"><?php echo htmlspecialchars($film['aciklama']); ?></textarea>

            <label>Afiş (Mevcut)</label>
            <div class="preview-container">
                <?php if($film['resim_url']): ?>
                    <img src="<?php echo $film['resim_url']; ?>" class="preview" alt="Mevcut Afiş">
                <?php endif; ?>
                <input type="file" name="resim" style="margin-bottom: 0;">
            </div>

            <label>Kapak (Mevcut)</label>
            <div class="preview-container">
                <?php if($film['kapak_resmi']): ?>
                    <img src="<?php echo $film['kapak_resmi']; ?>" class="preview" style="width:120px;" alt="Mevcut Kapak">
                <?php endif; ?>
                <input type="file" name="kapak" style="margin-bottom: 0;">
            </div>

            <button type="submit" name="guncelle" class="btn">Değişiklikleri Kaydet</button>
            
            <a href="admin.php" style="display:block; text-align:center; margin-top:20px; color:#aaa; text-decoration:none; font-size:14px;">
                <i class="fa-solid fa-xmark"></i> İptal ve Geri Dön
            </a>
        </form>
    </div>

</body>
</html>