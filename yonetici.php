<?php
session_start();
include 'baglanti.php';

// GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'yonetici') {
    die("Bu sayfaya erişim yetkiniz yok!");
}

// 1. ROL GÜNCELLEME
if (isset($_POST['rol_guncelle'])) {
    $hedef_id = $_POST['kullanici_id'];
    $yeni_rol = $_POST['yeni_rol'];
    $db->prepare("UPDATE kullanicilar SET rol = ? WHERE id = ?")->execute([$yeni_rol, $hedef_id]);
    $mesaj = "Rol güncellendi!";
}

// 2. BANLAMA İŞLEMİ (VE YORUMLARI SİLME)
if (isset($_GET['banla_id'])) {
    $hedef_id = $_GET['banla_id'];
    
    // A) Önce Kullanıcının Yorumlarını Sil
    $db->prepare("DELETE FROM yorumlar WHERE kullanici_id = ?")->execute([$hedef_id]);
    
    // B) Kullanıcıyı Banla (Durum = 0)
    $db->prepare("UPDATE kullanicilar SET durum = 0 WHERE id = ?")->execute([$hedef_id]);
    
    header("Location: yonetici.php?mesaj=banlandi");
    exit;
}

// SADECE AKTİF KULLANICILARI ÇEK
$kullanicilar = $db->query("SELECT * FROM kullanicilar WHERE durum = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli - Kullanıcı Yönetimi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* GENEL AYARLAR */
        * { box-sizing: border-box; }
        body { background-color: #141414; color: white; font-family: 'Segoe UI', sans-serif; padding: 20px; margin: 0; }
        
        /* ÜST MENÜ RESPONSIVE */
        .top-menu { 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid #333; padding-bottom: 20px; margin-bottom: 20px;
            flex-wrap: wrap; gap: 20px;
        }
        .header-left { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .header-left h1 { margin: 0; font-size: 24px; color: #fff; }
        
        .menu-links { display: flex; gap: 15px; flex-wrap: wrap; }
        .menu-links a { color: #f5c518; text-decoration: none; font-weight: bold; font-size: 14px; display: flex; align-items: center; gap: 5px; }
        .menu-links a:hover { color: #fff; }
        
        .btn-ban-list {
            background-color: #f44336; color: white; padding: 10px 15px; 
            text-decoration: none; border-radius: 4px; font-weight: bold; 
            display: inline-flex; align-items: center; gap: 8px; font-size: 14px;
            transition: 0.3s;
        }
        .btn-ban-list:hover { background-color: #d32f2f; transform: translateY(-2px); }

        /* TABLO KONTEYNERİ (KAYDIRMA ÖZELLİĞİ) */
        .table-container { width: 100%; overflow-x: auto; background: #1f1f1f; border-radius: 8px; }
        
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #333; font-size: 14px; }
        th { color: #f5c518; background: #111; text-transform: uppercase; letter-spacing: 1px; }
        tr:hover { background: #252525; }
        
        /* FORM ELEMANLARI */
        .role-form { display: flex; gap: 5px; align-items: center; }
        .role-form select { padding: 8px; background: #333; color: white; border: 1px solid #444; border-radius: 4px; outline: none; }
        .btn-update { background: #2196F3; color: white; border: none; padding: 8px 12px; cursor: pointer; font-weight: bold; border-radius: 4px; transition: 0.2s; }
        .btn-update:hover { background: #1976D2; }
        
        /* DURUM VE BAN BUTONU */
        .status-active { color: #4CAF50; font-weight: bold; border: 1px solid #4CAF50; padding: 3px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; }
        .btn-ban { 
            background: #f44336; color: white; text-decoration: none; padding: 8px 12px; 
            border-radius: 4px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; 
            transition: 0.2s; font-weight: bold;
        }
        .btn-ban:hover { background: #b71c1c; }

        /* BİLGİ MESAJI */
        .alert { background: #1a1a1a; color: #4CAF50; padding: 15px; border-left: 5px solid #4CAF50; margin-bottom: 20px; border-radius: 4px; font-size: 14px; }

        /* MOBİL AYARLAMALARI */
        @media (max-width: 768px) {
            .top-menu { flex-direction: column; align-items: flex-start; }
            .header-left { flex-direction: column; align-items: flex-start; }
            .btn-ban-list { width: 100%; justify-content: center; }
            .menu-links { background: #1a1a1a; padding: 10px; width: 100%; border-radius: 4px; justify-content: space-around; }
            body { padding: 10px; }
        }
    </style>
</head>
<body>

    <div class="top-menu">
        <div class="header-left">
            <h1><i class="fa-solid fa-users-gear"></i> Yönetici Paneli</h1>
            <div class="menu-links">
                <a href="index.php"><i class="fa-solid fa-home"></i> Site</a>
                <a href="admin.php"><i class="fa-solid fa-film"></i> İçerik</a>
                <a href="yorumlar.php"><i class="fa-solid fa-comments"></i> Yorumlar</a>
            </div>
        </div>
        
        <a href="ban-listesi.php" class="btn-ban-list">
            <i class="fa-solid fa-user-lock"></i> Yasaklılar Listesi
        </a>
    </div>

    <?php if(isset($_GET['mesaj'])): ?>
        <div class="alert">
            <i class="fa-solid fa-circle-check"></i> İşlem başarıyla gerçekleşti. Kullanıcı yasaklandı ve tüm yorumları temizlendi.
        </div>
    <?php endif; ?>

    <h2 style="color:#f5c518; font-size: 18px; margin-bottom: 15px;"><i class="fa-solid fa-user-check"></i> Aktif Kullanıcı Yönetimi</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th width="80">ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th width="200">Rol Yetkisi</th>
                    <th width="100">Durum</th>
                    <th width="180">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($kullanicilar as $k): ?>
                <tr>
                    <td><strong style="color:#777;">#<?php echo $k['id']; ?></strong></td>
                    <td style="font-weight: 500;"><?php echo htmlspecialchars($k['ad'] . " " . $k['soyad']); ?></td>
                    <td style="color:#aaa;"><?php echo htmlspecialchars($k['email']); ?></td>
                    <td>
                        <form method="POST" class="role-form">
                            <input type="hidden" name="kullanici_id" value="<?php echo $k['id']; ?>">
                            <select name="yeni_rol">
                                <option value="uye" <?php if($k['rol']=='uye') echo 'selected'; ?>>Üye</option>
                                <option value="admin" <?php if($k['rol']=='admin') echo 'selected'; ?>>Admin</option>
                                <option value="yonetici" <?php if($k['rol']=='yonetici') echo 'selected'; ?>>Yönetici</option>
                            </select>
                            <button type="submit" name="rol_guncelle" class="btn btn-update" title="Güncelle">OK</button>
                        </form>
                    </td>
                    <td><span class="status-active">Aktif</span></td>
                    <td>
                        <?php if($k['rol'] != 'yonetici'): ?>
                            <a href="yonetici.php?banla_id=<?php echo $k['id']; ?>" class="btn-ban" onclick="return confirm('DİKKAT: Bu kullanıcıyı banlarsanız, yaptığı TÜM YORUMLAR DA SİLİNECEKTİR. Onaylıyor musunuz?');">
                                <i class="fa-solid fa-gavel"></i> Banla & Temizle
                            </a>
                        <?php else: ?>
                            <span style="color:#555; font-size:12px; font-style: italic;">Üst Yetkili</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>