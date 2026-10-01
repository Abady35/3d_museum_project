<?php
// config/database.php

// Veritabanı bağlantı bilgileri
$host = 'sql211.infinityfree.com';        // Sunucu adresi (genelde localhost)
$db_name = 'if0_41866826_egypt_museum';  // Oluşturduğun veritabanının adı
$username = 'if0_41866826';         // Veritabanı kullanıcı adı (XAMPP/WAMP için genelde root)
$password = 'Egypt1245';             // Veritabanı şifresi (XAMPP/WAMP için genelde boştur)
$charset = 'utf8mb4';       // Türkçe ve özel karakter (hiyeroglif emojileri vs.) desteği için en iyi standart

// Data Source Name (Veri Kaynağı Adı)
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

// PDO Ayarları
$options = [
    // Hataları fırlat (Exception) ki nerede sorun olduğunu kolayca görebilelim
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Verileri çekerken varsayılan olarak ilişkilendirilmiş dizi (Associative Array) kullan
    // Örn: $user['first_name'] şeklinde veriye erişebilmek için
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Güvenlik için emülasyonu kapat, gerçek prepared statements kullan
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // PDO nesnesini oluştur ve bağlantıyı başlat
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Geliştirme aşamasında test etmek istersen aşağıdaki satırın başındaki // işaretini kaldırabilirsin:
    // echo "Veritabanı bağlantısı başarıyla sağlandı!";
    
} catch (PDOException $e) {
    // Canlıya (Production) çıktığında güvenlik için kullanıcıya detaylı hata gösterilmez,
    // ancak geliştirme aşamasında hatayı görmek hayat kurtarır.
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>