<?php
// app/free_booking.php
require_once 'auth_check.php'; // Giriş yapmamışsa login.php'ye atacaktır
require_once '../config/database.php';

if (!isset($_GET['tour_id'])) {
    die("Hata: Herhangi bir tur seçilmedi.");
}

$tour_id = $_GET['tour_id'];
$user_id = $_SESSION['user_id'];

// 1. Kontrol: Kullanıcı bu tura zaten daha önce katılmış mı?
$stmt_check = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND tour_id = ?");
$stmt_check->execute([$user_id, $tour_id]);

if ($stmt_check->rowCount() > 0) {
    // Zaten bileti varsa paneline yönlendir ve uyarı parametresi gönder
    header("Location: dashboard.php?status=already_joined");
    exit();
}

// 2. Ücretsiz Bileti Oluştur
$unique_hash = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
$ticket_code = 'FREE-' . $unique_hash; // TKT yerine FREE prefixi kullandık

try {
    // Rezervasyonu direkt 'confirmed' (onaylandı) olarak veritabanına ekle
    $stmt_booking = $pdo->prepare("INSERT INTO bookings (user_id, tour_id, ticket_code, status) VALUES (?, ?, ?, 'confirmed')");
    $stmt_booking->execute([$user_id, $tour_id, $ticket_code]);
    
    // İşlem başarılı, kullanıcıyı 3D turlarını göreceği panele gönder
    header("Location: dashboard.php?status=success");
    exit();
    
} catch (PDOException $e) {
    die("Sistemsel bir hata oluştu: " . $e->getMessage());
}
?>