<?php
// Sayfanın en üstünde oturum kontrolünü yapıyoruz
require_once 'auth_check.php';
require_once '../config/database.php';

// Aktif kullanıcının ID'sini ve adını oturumdan alıyoruz
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Kullanıcının satın aldığı turları veritabanından çekiyoruz (JOIN işlemi ile)
try {
    $stmt = $pdo->prepare("
        SELECT b.ticket_code, b.status, b.booking_date, t.title, t.price 
        FROM bookings b 
        INNER JOIN tours t ON b.tour_id = t.id 
        WHERE b.user_id = ? 
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Biletler yüklenirken hata oluştu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mısır Müzesi - Kontrol Paneli</title>
    <style>
        /* Genel Tasarım ve Tema Ayarları */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0e14 0%, #1a2233 100%);
            color: #f0f0f0;
            min-height: 100vh;
        }

        /* Üst Menü (Navbar) */
        header {
            background-color: rgba(21, 27, 35, 0.9);
            backdrop-filter: blur(10px); /* Cam efekti */
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #d4af37;
        }

        header h1 {
            margin: 0;
            color: #d4af37;
            font-size: 24px;
            letter-spacing: 2px;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #d4af37;
        }

        .logout-btn {
            background: transparent;
            border: 1px solid #ff4c4c;
            color: #ff4c4c;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background: #ff4c4c;
            color: #fff;
        }

        /* Ana İçerik Alanı */
        .container {
            padding: 40px 50px;
        }

        .welcome-text {
            font-size: 28px;
            margin-bottom: 30px;
        }

        .welcome-text span {
            color: #d4af37;
        }

        /* Bilet Kartları Izgarası */
        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        /* Tekil Bilet Kartı (Glassmorphism) */
        .ticket-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 12px;
            padding: 25px;
            backdrop-filter: blur(5px);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.15);
            border-color: rgba(212, 175, 55, 0.6);
        }

        /* Kart İçi Tipografi */
        .tour-title {
            font-size: 20px;
            color: #d4af37;
            margin: 0 0 10px 0;
        }

        .ticket-info {
            font-size: 14px;
            color: #aaa;
            margin-bottom: 5px;
        }

        /* 3D Tura Katıl Butonu */
        .btn-3d {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: linear-gradient(45deg, #d4af37, #b08d26);
            color: #0a0e14;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            text-align: center;
            width: calc(100% - 40px);
            transition: 0.3s;
        }

        .btn-3d:hover {
            filter: brightness(1.1);
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: -25px;
            background: #28a745;
            color: white;
            padding: 5px 30px;
            font-size: 12px;
            font-weight: bold;
            transform: rotate(45deg);
        }
    </style>
</head>
<body>

    <header>
        <h1>Mısır Müzesi 3D</h1>
        <div class="nav-links">
            <a href="dashboard.php">Biletlerim</a>
            <a href="../public/tours.php">Yeni Tur Keşfet</a>
            <!-- Çıkış yapma işlemi için logout.php'ye yönlendiriyoruz -->
            <a href="logout.php" class="logout-btn">Çıkış Yap</a> 
        </div>
    </header>

    <div class="container">
        <div class="welcome-text">
            Merhaba, <span><?php echo htmlspecialchars($user_name); ?></span>! Antik dünyaya hoş geldin.
        </div>

        <h2 style="border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 25px;">Biletlerim ve Turlarım</h2>

        <div class="ticket-grid">
            <?php if (count($tickets) > 0): ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card">
                        <div class="status-badge">Aktif</div>
                        <h3 class="tour-title"><?php echo htmlspecialchars($ticket['title']); ?></h3>
                        <div class="ticket-info"><strong>Bilet Kodu:</strong> <?php echo htmlspecialchars($ticket['ticket_code']); ?></div>
                        <div class="ticket-info"><strong>Alım Tarihi:</strong> <?php echo date('d.m.Y', strtotime($ticket['booking_date'])); ?></div>
                        
                        <!-- Bu buton bizi projenin en havalı kısmı olan 3D görüntüleyiciye götürecek -->
                        <a href="virtual-tour.php?code=<?php echo urlencode($ticket['ticket_code']); ?>" class="btn-3d">
                            👁️ 3D Tura Başla
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #aaa;">Henüz hiçbir tura bilet almadınız. Hemen "Yeni Tur Keşfet" sekmesinden antik dünyayı keşfetmeye başlayın!</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>