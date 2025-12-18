<?php
session_start();
include 'baglanti.php';

header('Content-Type: application/json'); // Cevabın JSON olduğunu belirt

// Giriş kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['kullanici_id'];

// İŞLEM 1: KÜTÜPHANE EKLE/ÇIKAR
if (isset($_POST['action']) && $_POST['action'] == 'toggle_library') {
    $film_id = $_POST['film_id'];
    
    // Var mı kontrol et
    $check = $db->prepare("SELECT id FROM kutuphanem WHERE kullanici_id = ? AND film_id = ?");
    $check->execute([$user_id, $film_id]);
    
    if ($check->rowCount() > 0) {
        // Varsa sil
        $db->prepare("DELETE FROM kutuphanem WHERE kullanici_id = ? AND film_id = ?")->execute([$user_id, $film_id]);
        echo json_encode(['status' => 'removed']);
    } else {
        // Yoksa ekle
        $db->prepare("INSERT INTO kutuphanem (kullanici_id, film_id) VALUES (?, ?)")->execute([$user_id, $film_id]);
        echo json_encode(['status' => 'added']);
    }
    exit;
}

// İŞLEM 2: YORUM VE PUAN EKLE
if (isset($_POST['action']) && $_POST['action'] == 'add_comment') {
    $film_id = $_POST['film_id'];
    $yorum = htmlspecialchars($_POST['yorum']);
    $puan = intval($_POST['puan']);
    
    if (!empty($yorum) && $puan > 0) {
        $sql = "INSERT INTO yorumlar (film_id, kullanici_id, yorum, puan) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$film_id, $user_id, $yorum, $puan]);
        
        if ($result) {
            // Yeni yorumu ekrana basmak için hazırlayalım
            $ad = $_SESSION['ad_soyad']; // Basitçe oturumdan alıyoruz
            // Soyad gizleme işlemi
            $parcalar = explode(" ", $ad);
            $soyad = array_pop($parcalar);
            $isim = implode(" ", $parcalar);
            $gorunen_isim = $isim . " " . mb_substr($soyad, 0, 1, 'UTF-8') . ".";
            
            echo json_encode([
                'status' => 'success',
                'user' => $gorunen_isim,
                'puan' => $puan,
                'yorum' => nl2br($yorum),
                'tarih' => date("d.m.Y H:i")
            ]);
        } else {
            echo json_encode(['status' => 'error']);
        }
    } else {
        echo json_encode(['status' => 'empty']);
    }
    exit;
}
?>