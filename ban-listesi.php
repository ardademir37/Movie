<?php
session_start();
include 'baglanti.php';

// GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'yonetici') {
    die("Bu sayfaya erişim yetkiniz yok!");
}

// BANI KALDIRMA İŞLEMİ
if (isset($_GET['bani_ac_id'])) {
    $hedef_id = $_GET['bani_ac_id'];
    
    // Kullanıcıyı Aktif Yap (Durum = 1)
    $db->prepare("UPDATE kullanicilar SET durum = 1 WHERE id = ?")->execute([$hedef_id]);
    
    header("Location: ban-listesi.php?mesaj=acildi");
    exit;
}

// SADECE BANLI KULLANICILARI ÇEK (durum = 0)
$yasaklilar = $db->query("SELECT * FROM kullanicilar WHERE durum = 0 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yasaklılar Listesi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #141414; color: white; font-family: 'Segoe UI', sans-serif; padding: 20px; margin: 0; }
        
        /* Header Responsive Ayarı */
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding-bottom: 20px; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .header h1 { margin: 0; color: #f44336; font-size: 24px; }
        
        .btn-back { 
            background-color: #333; color: white; padding: 10px 20px; 
            text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px;
            display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;
        }
        .btn-back:hover { background-color: #555; }

        /* Tablo Responsive Konteyner */
        .table-container { width: 100%; overflow-x: auto; background: #1f1f1f; border-radius: 8px; }
        
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #f44336; background: #111; font-size: 14px; text-transform: uppercase; }
        tr:hover { background: #252525; }
        
        .status-banned { color: #f44336; font-weight: bold; border: 1px solid #f44336; padding: 2px 8px; border-radius: 4px; font-size: 11px; }
        
        .btn-unban { 
            background: #4CAF50; color: white; text-decoration: none; 
            padding: 8px 15px; border-radius: 4px; font-size: 13px; font-weight: bold; 
            display: inline-flex; align-items: center; gap: 5px; transition: 0.3s;
        }
        .btn-unban:hover { background-color: #388E3C; }

        /* Mesaj Kutusu */
        .alert { color:#4CAF50; background:#1a1a1a; padding:15px; border-left:4px solid #4CAF50; margin-bottom: 20px; border-radius: 4px; font-size: 14px; }

        /* Mobil Düzenlemeler */
        @media (max-width: 600px) {
            body { padding: 15px; }
            .header { flex-direction: column; align-items: flex-start; }
            .btn-back { width: 100%; justify-content: center; box-sizing: border-box; }
            th, td { padding: 10px; font-size: 13px; }
            .btn-unban { padding: 6px 10px; font-size: 12px; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1><i class="fa-solid fa-ban"></i> Yasaklılar Listesi</h1>
        <a href="yonetici.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
    </div>

    <?php if(isset($_GET['mesaj'])): ?>
        <div class="alert">
            <i class="fa-solid fa-circle-check"></i> Kullanıcının yasağı başarıyla kaldırıldı.
        </div>
    <?php endif; ?>

    <?php if(count($yasaklilar) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($yasaklilar as $k): ?>
                    <tr>
                        <td>#<?php echo $k['id']; ?></td>
                        <td style="font-weight: 500;"><?php echo htmlspecialchars($k['ad'] . " " . $k['soyad']); ?></td>
                        <td style="color: #bbb;"><?php echo htmlspecialchars($k['email']); ?></td>
                        <td><span class="status-banned">YASAKLI</span></td>
                        <td>
                            <a href="ban-listesi.php?bani_ac_id=<?php echo $k['id']; ?>" class="btn-unban" onclick="return confirm('Bu kullanıcının yasağını kaldırmak istiyor musunuz?');">
                                <i class="fa-solid fa-lock-open"></i> Banı Aç
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding:60px 20px; color:#777; background:#1f1f1f; border-radius:8px; border: 1px dashed #333;">
            <i class="fa-solid fa-check-circle fa-4x" style="margin-bottom:15px; color:#4CAF50;"></i><br>
            <strong style="color: #eee; font-size: 18px;">Temiz Liste!</strong><br>
            Şu an yasaklı kullanıcı bulunmuyor.
        </div>
    <?php endif; ?>

</body>
</html>