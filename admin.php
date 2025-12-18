<?php
session_start();
include 'baglanti.php';

// GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || ($_SESSION['rol'] != 'admin' && $_SESSION['rol'] != 'yonetici')) {
    die("Bu sayfaya erişim yetkiniz yok!");
}

// SİLME İŞLEMİ
if (isset($_GET['sil_id'])) {
    $sil_id = $_GET['sil_id'];
    $resimBul = $db->prepare("SELECT resim_url, kapak_resmi FROM filmler WHERE id = ?");
    $resimBul->execute([$sil_id]);
    $eski = $resimBul->fetch(PDO::FETCH_ASSOC);
    if ($eski) {
        if (file_exists($eski['resim_url'])) unlink($eski['resim_url']);
        if (file_exists($eski['kapak_resmi'])) unlink($eski['kapak_resmi']);
    }
    $db->prepare("DELETE FROM filmler WHERE id = ?")->execute([$sil_id]);
    $db->prepare("DELETE FROM film_kategoriler WHERE film_id = ?")->execute([$sil_id]);
    header("Location: admin.php?mesaj=silindi");
    exit;
}

// KATEGORİ İŞLEMLERİ
if (isset($_POST['kategori_ekle'])) {
    $yeni_kat = trim($_POST['yeni_kategori_adi']);
    if (!empty($yeni_kat)) {
        $db->prepare("INSERT INTO kategoriler (ad) VALUES (?)")->execute([$yeni_kat]);
    }
}
if (isset($_GET['sil_kat_id'])) {
    $db->prepare("DELETE FROM kategoriler WHERE id = ?")->execute([$_GET['sil_kat_id']]);
    $db->prepare("DELETE FROM film_kategoriler WHERE kategori_id = ?")->execute([$_GET['sil_kat_id']]);
    header("Location: admin.php?mesaj=kategori_silindi");
    exit;
}

// İÇERİK EKLEME
if (isset($_POST['icerik_ekle'])) {
    $baslik = $_POST['baslik'];
    $aciklama = $_POST['aciklama'];
    $tur = $_POST['tur'];
    $secilen_kategoriler = isset($_POST['kategoriler']) ? $_POST['kategoriler'] : [];

    function dosyaYukle($dosya, $hedefKlasor) {
        if (!isset($dosya) || $dosya['error'] != 0) return null;
        if (!file_exists($hedefKlasor)) mkdir($hedefKlasor, 0777, true);
        $hedefYol = $hedefKlasor . time() . "_" . basename($dosya["name"]);
        if (move_uploaded_file($dosya["tmp_name"], $hedefYol)) return $hedefYol;
        return null;
    }

    $resimYolu = dosyaYukle($_FILES['resim'], "uploads/");
    $kapakYolu = dosyaYukle($_FILES['kapak'], "uploads/");

    if ($resimYolu && $kapakYolu) {
        $sql = "INSERT INTO filmler (baslik, aciklama, resim_url, kapak_resmi, tur) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$baslik, $aciklama, $resimYolu, $kapakYolu, $tur]);
        $yeni_film_id = $db->lastInsertId();
        $ekle_kat = $db->prepare("INSERT INTO film_kategoriler (film_id, kategori_id) VALUES (?, ?)");
        foreach ($secilen_kategoriler as $kat_id) { $ekle_kat->execute([$yeni_film_id, $kat_id]); }
        $mesaj = "İçerik kaydedildi!";
    } else { $hata = "Dosya yükleme hatası."; }
}

// LİSTELEME VE FİLTRELEME
$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);

$filtre_sql = "
    SELECT f.*, GROUP_CONCAT(k.ad SEPARATOR ', ') as kategori_isimleri 
    FROM filmler f 
    LEFT JOIN film_kategoriler fk ON f.id = fk.film_id 
    LEFT JOIN kategoriler k ON fk.kategori_id = k.id 
    WHERE 1=1 
";
$params = [];

if (isset($_GET['f_tur']) && $_GET['f_tur'] != '') {
    $filtre_sql .= " AND f.tur = ?";
    $params[] = $_GET['f_tur'];
}

if (isset($_GET['f_kategori']) && $_GET['f_kategori'] != '') {
    $filtre_sql .= " AND f.id IN (SELECT film_id FROM film_kategoriler WHERE kategori_id = ?)";
    $params[] = $_GET['f_kategori'];
}
if (isset($_GET['f_arama']) && $_GET['f_arama'] != '') {
    $filtre_sql .= " AND f.baslik LIKE ?";
    $params[] = "%" . $_GET['f_arama'] . "%";
}

$filtre_sql .= " GROUP BY f.id ";

$siralama = isset($_GET['siralama']) ? $_GET['siralama'] : 'yeni';
switch ($siralama) {
    case 'isim_az': $filtre_sql .= " ORDER BY f.baslik ASC"; break;
    case 'isim_za': $filtre_sql .= " ORDER BY f.baslik DESC"; break;
    case 'eski': $filtre_sql .= " ORDER BY f.id ASC"; break;
    case 'yeni': default: $filtre_sql .= " ORDER BY f.id DESC"; break;
}

$stmt = $db->prepare($filtre_sql);
$stmt->execute($params);
$filmler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #141414; color: white; font-family: 'Segoe UI', sans-serif; padding: 20px; margin: 0; }
        a { color: #f5c518; text-decoration: none; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 15px; flex-wrap: wrap; gap: 15px;}
        .header-links { display: flex; flex-wrap: wrap; gap: 15px; }
        .header-links a { font-weight: bold; font-size: 16px; color: #ddd; }
        .header-links a:hover { color: #f5c518; }

        .container { display: flex; gap: 20px; flex-wrap: wrap; }
        .left-column { flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 20px; }
        .right-column { flex: 2; min-width: 350px; }
        
        .panel-box { background: #1f1f1f; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        h2 { margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; font-size: 18px; color: #f5c518; }
        
        input, select, textarea { width: 100%; padding: 12px; margin-bottom: 15px; background: #2b2b2b; color: white; border: 1px solid #444; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .btn { width: 100%; padding: 12px; background: #f5c518; color: black; border: none; font-weight: bold; cursor: pointer; border-radius: 4px; transition: 0.3s; font-size: 14px; }
        .btn:hover { background: #d4a810; }
        .btn-small { width: auto; padding: 8px 15px; font-size: 13px; }
        
        .checkbox-list { max-height: 150px; overflow-y: auto; background: #252525; border: 1px solid #444; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .checkbox-item { display: block; margin-bottom: 5px; cursor: pointer; font-size: 14px; }
        
        .cat-list { list-style: none; padding: 0; max-height: 150px; overflow-y: auto; border: 1px solid #333; padding: 10px; background: #252525; margin: 0; }
        .cat-list li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #333; font-size: 14px; }
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; background: #252525; padding: 15px; border-radius: 4px; flex-wrap: wrap; align-items: center; }
        .filter-bar > * { flex: 1; min-width: 150px; margin-bottom: 0 !important; }
        .filter-bar .btn-small { flex: 0 0 auto; }

        /* Responsive Table */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; font-size: 14px; }
        th { color: #f5c518; background: #1a1a1a; white-space: nowrap; }
        tr:hover { background: #252525; }
        
        .thumb { width: 40px; height: 60px; object-fit: cover; border-radius: 4px; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 11px; background: #444; }
        
        .action-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .action-link { font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 5px; white-space: nowrap; }

        /* Mobil Ayarlamaları */
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { text-align: center; justify-content: center; }
            .header-links { justify-content: center; }
            .left-column, .right-column { flex: 1 1 100%; min-width: 0; }
            .filter-bar > * { flex: 1 1 100%; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1 style="margin:0; color:#f5c518; font-size: 24px;">Admin Paneli</h1>
        <div class="header-links">
            <a href="index.php"><i class="fa-solid fa-home"></i> Site</a>
            <a href="yonetici.php"><i class="fa-solid fa-users"></i> Kullanıcılar</a>
            <a href="yorumlar.php"><i class="fa-solid fa-comments"></i> Yorumlar</a>
        </div>
    </div>

    <?php if(isset($mesaj)) echo "<p style='background:green; color:white; padding:10px; border-radius:4px; font-size:14px;'>$mesaj</p>"; ?>
    <?php if(isset($hata)) echo "<p style='background:red; color:white; padding:10px; border-radius:4px; font-size:14px;'>$hata</p>"; ?>

    <div class="container">
        
        <div class="left-column">
            <div class="panel-box">
                <h2><i class="fa-solid fa-tags"></i> Kategori Yönetimi</h2>
                <form method="POST" style="margin-bottom: 15px; display:flex; gap:10px; flex-wrap: wrap;">
                    <input type="text" name="yeni_kategori_adi" placeholder="Yeni Kategori" required style="margin-bottom:0; flex:1;">
                    <button type="submit" name="kategori_ekle" class="btn btn-small" style="height: 45px;">Ekle</button>
                </form>
                <ul class="cat-list">
                    <?php foreach($kategoriler as $k): ?>
                        <li>
                            <?php echo htmlspecialchars($k['ad']); ?> 
                            <a href="admin.php?sil_kat_id=<?php echo $k['id']; ?>" style="color:#f44336;" onclick="return confirm('Silinsin mi?');"><i class="fa-solid fa-trash"></i></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="panel-box">
                <h2><i class="fa-solid fa-plus-circle"></i> Yeni İçerik Ekle</h2>
                <form method="POST" enctype="multipart/form-data">
                    <label style="display:block; margin-bottom:5px; font-size:14px;">Başlık</label> 
                    <input type="text" name="baslik" required>
                    
                    <label style="display:block; margin-bottom:5px; font-size:14px;">Kategoriler</label>
                    <div class="checkbox-list">
                        <?php foreach($kategoriler as $k): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="kategoriler[]" value="<?php echo $k['id']; ?>"> <?php echo htmlspecialchars($k['ad']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <label style="display:block; margin-bottom:5px; font-size:14px;">Tür</label>
                    <select name="tur">
                        <option value="film">Film</option>
                        <option value="dizi">Dizi</option>
                    </select>
                    
                    <label style="display:block; margin-bottom:5px; font-size:14px;">Açıklama</label> 
                    <textarea name="aciklama" rows="3"></textarea>
                    
                    <label style="display:block; margin-bottom:5px; font-size:14px;">Afiş (Dikey)</label>
                    <input type="file" name="resim" required accept="image/*" style="margin-bottom:10px;">
                    
                    <label style="display:block; margin-bottom:5px; font-size:14px;">Kapak (Yatay)</label>
                    <input type="file" name="kapak" required accept="image/*">
                    
                    <button type="submit" name="icerik_ekle" class="btn">Kaydet</button>
                </form>
            </div>
        </div>

        <div class="right-column panel-box">
            <h2><i class="fa-solid fa-list"></i> İçerik Listesi</h2>
            
            <form method="GET" class="filter-bar">
                <input type="text" name="f_arama" placeholder="Ara..." value="<?php echo $_GET['f_arama'] ?? ''; ?>">
                
                <select name="f_tur">
                    <option value="">Tüm Türler</option>
                    <option value="film" <?php if(isset($_GET['f_tur']) && $_GET['f_tur']=='film') echo 'selected'; ?>>Sadece Filmler</option>
                    <option value="dizi" <?php if(isset($_GET['f_tur']) && $_GET['f_tur']=='dizi') echo 'selected'; ?>>Sadece Diziler</option>
                </select>

                <select name="f_kategori">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach($kategoriler as $k): ?>
                        <option value="<?php echo $k['id']; ?>" <?php if(isset($_GET['f_kategori']) && $_GET['f_kategori'] == $k['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($k['ad']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div style="display: flex; gap: 5px;">
                    <button type="submit" class="btn btn-small" style="background:#f5c518;">Filtrele</button>
                    <a href="admin.php" class="btn btn-small" style="background:#444; color:white; line-height: 25px; text-align: center;">Sıfırla</a>
                </div>
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="60">Resim</th>
                            <th>Başlık</th>
                            <th>Kategoriler</th>
                            <th>Tür</th>
                            <th width="150">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($filmler) > 0): ?>
                            <?php foreach($filmler as $f): ?>
                            <tr>
                                <td><?php if(!empty($f['resim_url'])): ?><img src="<?php echo $f['resim_url']; ?>" class="thumb"><?php endif; ?></td>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($f['baslik']); ?></td>
                                <td style="font-size:12px; color:#ccc;"><?php echo !empty($f['kategori_isimleri']) ? htmlspecialchars($f['kategori_isimleri']) : '-'; ?></td>
                                <td><span class="badge"><?php echo strtoupper($f['tur']); ?></span></td>
                                <td>
                                    <div class="action-group">
                                        <a href="film-duzenle.php?id=<?php echo $f['id']; ?>" class="action-link" style="color:#2196F3;"><i class="fa-solid fa-pen-to-square"></i> Düzenle</a>
                                        <a href="admin.php?sil_id=<?php echo $f['id']; ?>" class="action-link" style="color:#f44336;" onclick="return confirm('Silinsin mi?');"><i class="fa-solid fa-trash"></i> Sil</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:40px; color:#777;">Kayıt bulunamadı.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>