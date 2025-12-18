<?php
session_start();
include 'baglanti.php';

// GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || ($_SESSION['rol'] != 'admin' && $_SESSION['rol'] != 'yonetici')) {
    die("Erişim yetkiniz yok.");
}

// YORUM SİLME
if (isset($_GET['sil_id'])) {
    $db->prepare("DELETE FROM yorumlar WHERE id = ?")->execute([$_GET['sil_id']]);
    header("Location: yorumlar.php?mesaj=silindi");
    exit;
}

// YORUMLARI ÇEK (Film adı ve Kullanıcı adı ile birleştirerek)
$sql = "SELECT y.*, f.baslik, k.ad, k.soyad 
        FROM yorumlar y 
        JOIN filmler f ON y.film_id = f.id 
        JOIN kullanicilar k ON y.kullanici_id = k.id 
        ORDER BY y.tarih DESC";
$yorumlar = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yorum Yönetimi - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* GENEL AYARLAR */
        * { box-sizing: border-box; }
        body { background-color: #141414; color: white; font-family: 'Segoe UI', sans-serif; padding: 20px; margin: 0; }
        
        /* HEADER RESPONSIVE */
        .header { 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid #333; padding-bottom: 20px; margin-bottom: 20px;
            flex-wrap: wrap; gap: 15px;
        }
        .header-title { display: flex; align-items: center; gap: 15px; }
        .header h1 { margin: 0; color: #f5c518; font-size: 24px; }
        .header-links { display: flex; gap: 15px; }
        .header a { color: #ddd; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.3s; }
        .header a:hover { color: #f5c518; }

        /* TABLO KONTEYNERİ (Mobilde Kaydırma İçin) */
        .table-container { 
            width: 100%; 
            overflow-x: auto; 
            background: #1f1f1f; 
            border-radius: 8px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }

        table { width: 100%; border-collapse: collapse; min-width: 900px; } /* Mobilde çok daralmasın diye min-width */
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #333; vertical-align: middle; }
        th { color: #f5c518; background: #111; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        tr:hover { background: #252525; }
        
        /* ÖZEL STİLLER */
        .puan-badge { 
            background: #f5c518; color: #000; padding: 3px 8px; 
            border-radius: 4px; font-weight: bold; font-size: 12px; 
        }
        .film-name { color: #f5c518; font-weight: bold; font-size: 14px; }
        .user-name { font-weight: 500; color: #fff; }
        .comment-text { color: #ccc; font-style: italic; font-size: 14px; line-height: 1.4; max-width: 400px; }
        
        .btn-del { 
            background: #f44336; color: white; padding: 8px 12px; 
            border-radius: 4px; text-decoration: none; font-size: 12px; 
            font-weight: bold; display: inline-flex; align-items: center; gap: 5px;
            transition: 0.3s;
        }
        .btn-del:hover { background: #b71c1c; transform: scale(1.05); }

        .alert { 
            background: #1a1a1a; color: #4CAF50; padding: 12px; 
            border-left: 4px solid #4CAF50; margin-bottom: 20px; 
            border-radius: 4px; font-size: 14px; 
        }

        /* MOBİL AYARLAR */
        @media (max-width: 768px) {
            body { padding: 15px; }
            .header { flex-direction: column; align-items: flex-start; }
            .header-links { width: 100%; justify-content: space-between; }
            th, td { padding: 10px; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-title">
            <h1><i class="fa-solid fa-comments"></i> Yorum Yönetimi</h1>
        </div>
        <div class="header-links">
            <a href="admin.php"><i class="fa-solid fa-arrow-left"></i> Admin Panel</a>
            <a href="index.php"><i class="fa-solid fa-house"></i> Siteye Git</a>
        </div>
    </div>

    <?php if(isset($_GET['mesaj'])): ?>
        <div class="alert">
            <i class="fa-solid fa-check-circle"></i> Yorum başarıyla silindi.
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th width="150">Kullanıcı</th>
                    <th width="200">İçerik (Film/Dizi)</th>
                    <th width="80">Puan</th>
                    <th>Yorum Özeti</th>
                    <th width="140">Tarih</th>
                    <th width="100">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($yorumlar) > 0): ?>
                    <?php foreach($yorumlar as $y): ?>
                    <tr>
                        <td>
                            <span class="user-name"><?php echo htmlspecialchars($y['ad'] . " " . $y['soyad']); ?></span>
                        </td>
                        <td>
                            <span class="film-name"><?php echo htmlspecialchars($y['baslik']); ?></span>
                        </td>
                        <td>
                            <span class="puan-badge"><i class="fa-solid fa-star"></i> <?php echo $y['puan']; ?></span>
                        </td>
                        <td>
                            <div class="comment-text">"<?php echo nl2br(htmlspecialchars($y['yorum'])); ?>"</div>
                        </td>
                        <td style="font-size:12px; color:#777;">
                            <?php echo date("d.m.Y", strtotime($y['tarih'])); ?><br>
                            <?php echo date("H:i", strtotime($y['tarih'])); ?>
                        </td>
                        <td>
                            <a href="yorumlar.php?sil_id=<?php echo $y['id']; ?>" class="btn-del" onclick="return confirm('Bu yorumu silmek istediğinize emin misiniz?');">
                                <i class="fa-solid fa-trash-can"></i> SİL
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:50px; color:#555;">Henüz yorum yapılmamış.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>