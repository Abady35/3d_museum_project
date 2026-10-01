<?php
// Oturum başlatıyoruz ki kullanıcı giriş yapmışsa menüde "Panele Dön", yapmamışsa "Giriş Yap" gösterelim
session_start();
require_once '../config/database.php';

// Veritabanından sadece aktif olan turları çekiyoruz
try {
    $stmt = $pdo->query("SELECT * FROM tours WHERE is_active = 1 ORDER BY created_at DESC");
    $tours = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Turlar yüklenirken hata oluştu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mısır Müzesi - Turları Keşfet</title>
    <style>
        /* Genel Temel Ayarlar */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #050505;
            background-image: radial-gradient(circle at 50% -20%, #1a2233, #050505);
            color: #fff;
            min-height: 100vh;
        }

        /* Üst Menü */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 50px;
            background: rgba(5, 5, 5, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        header .logo { font-size: 24px; font-weight: bold; color: #d4af37; text-decoration: none; text-transform: uppercase; letter-spacing: 2px;}
        .nav-links a { color: #fff; text-decoration: none; margin-left: 30px; font-size: 14px; transition: 0.3s; }
        .nav-links a:hover { color: #d4af37; }

        /* Başlık Alanı */
        .page-header {
            text-align: center;
            padding: 60px 20px 40px;
        }
        .page-header h1 { font-size: 42px; color: #d4af37; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 2px; }
        .page-header p { color: #aaa; max-width: 600px; margin: 0 auto; line-height: 1.6; }

        /* Turlar Izgarası (Grid) */
        .tours-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 40px;
            padding: 0 50px 60px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* 3D Kart Konteyneri */
        .card-wrapper {
            perspective: 1000px; /* 3D derinlik hissi için perspektif alanı */
        }

        /* Tur Kartı Tasarımı */
        .tour-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: transform 0.5s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.5s;
            transform-style: preserve-3d; /* Çocuk elementlerin de 3D düzlemde kalması için */
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* 3D Hover Efekti */
        .card-wrapper:hover .tour-card {
            transform: rotateY(5deg) rotateX(5deg) scale(1.02);
            box-shadow: -15px 15px 30px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(212, 175, 55, 0.1);
            border-color: rgba(212, 175, 55, 0.6);
        }

        /* Kart Görseli Alanı */
        .tour-image {
            height: 200px;
            background-color: #111;
            background-size: cover;
            background-position: center;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            position: relative;
        }

        /* Görselin üstündeki fiyat etiketi (3D olarak öne çıkar) */
        .price-tag {
            position: absolute;
            bottom: -15px;
            right: 20px;
            background: #d4af37;
            color: #050505;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 18px;
            transform: translateZ(30px); /* 3D düzlemde havaya kalkar */
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }

        /* Kart İçeriği */
        .tour-content {
            padding: 30px 20px 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            transform: translateZ(20px); /* Yazıları da 3D düzlemde hafif öne alıyoruz */
        }

        .tour-title { font-size: 22px; color: #fff; margin: 0 0 10px 0; }
        .tour-desc { color: #ccc; font-size: 14px; line-height: 1.5; margin-bottom: 20px; flex-grow: 1; }

        /* Satın Al Butonu */
        .btn-buy {
            display: block;
            text-align: center;
            background: transparent;
            border: 2px solid #d4af37;
            color: #d4af37;
            padding: 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-buy:hover {
            background: #d4af37;
            color: #050505;
        }

    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo">Mısır 3D</a>
        <div class="nav-links">
            <a href="index.php">Ana Sayfa</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <!-- Kullanıcı giriş yaptıysa kendi paneline dönsün -->
                <a href="../app/dashboard.php" style="color: #d4af37; border: 1px solid #d4af37; padding: 5px 15px; border-radius: 4px;">Panele Dön</a>
            <?php else: ?>
                <!-- Giriş yapmamışsa standart yönlendirmeler -->
                <a href="../app/login.php">Giriş Yap</a>
                <a href="../app/register.php">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="page-header">
        <h1>Antik Keşifler</h1>
        <p>Aşağıdaki turlardan dilediğinizi seçerek, 3D modelleme teknolojisi ile hiyerogliflerin ve firavunların dünyasına adım atabilirsiniz. Biletinizi alın ve sanal müzemizin kapılarını aralayın.</p>
    </div>

    <div class="tours-container">
        <?php if(count($tours) > 0): ?>
            <?php foreach($tours as $tour): ?>
               <div class="card-wrapper">
    <div class="tour-card">
        <div class="tour-image" style="background-image: url('<?php echo htmlspecialchars($tour['cover_image_url'] ?? ''); ?>');">
            <div class="price-tag" style="background: #28a745; color: white;">ÜCRETSİZ</div>
        </div>
        
        <div class="tour-content">
            <h3 class="tour-title"><?php echo htmlspecialchars($tour['title']); ?></h3>
            <div class="tour-desc">
                <?php echo htmlspecialchars($tour['description']); ?>
            </div>
            
            <a href="../app/free_booking.php?tour_id=<?php echo $tour['id']; ?>" class="btn-buy" style="border-color: #28a745; color: #28a745;">
                Tura Ücretsiz Katıl
            </a>
        </div>
    </div>
</div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Veritabanında henüz tur yoksa gösterilecek boş durum tasarımı -->
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: rgba(255,255,255,0.05); border-radius: 10px; border: 1px dashed #d4af37;">
                <h3 style="color: #d4af37;">Şu an planlanmış bir tur bulunmuyor.</h3>
                <p style="color: #aaa;">Yöneticilerimiz çok yakında yeni antik eserleri sergiye açacaktır. Lütfen daha sonra tekrar kontrol edin.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>