<?php
session_start();
require_once '../config/database.php';

// Yönetici zaten giriş yapmışsa direkt panele al
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            
            // KESİN GÜVENLİK KONTROLÜ: Giren kişi 'admin' değilse kapıdan çevir!
            if ($user['role'] === 'admin') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['role'] = $user['role'];

                // Başarılı giriş, kontrol merkezine yönlendir
                header("Location: index.php");
                exit();
            } else {
                $error = "Erişim Reddedildi: Bu alan sadece sistem yöneticilerine özeldir.";
            }
        } else {
            $error = "E-posta adresi veya şifre hatalı.";
        }
    } catch (PDOException $e) {
        $error = "Sistem Hatası: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mısır Müzesi - Yönetici Girişi</title>
    <style>
        /* Admin paneline uygun, daha ciddi, gizli ve koyu bir tasarım */
        body { margin: 0; background: #050505; color: #fff; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .admin-login-box { background: #111; padding: 40px; border-radius: 8px; width: 100%; max-width: 350px; border-top: 4px solid #ff4c4c; box-shadow: 0 10px 30px rgba(255, 76, 76, 0.1); }
        .header-text { text-align: center; color: #ff4c4c; font-size: 20px; font-weight: bold; letter-spacing: 2px; margin-bottom: 5px; text-transform: uppercase; }
        .sub-text { text-align: center; color: #888; font-size: 12px; margin-bottom: 30px; letter-spacing: 1px; text-transform: uppercase; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 11px; color: #aaa; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        input { width: 100%; padding: 12px; background: #0a0a0a; border: 1px solid #333; color: white; border-radius: 4px; box-sizing: border-box; transition: 0.3s; font-size: 14px; }
        input:focus { border-color: #ff4c4c; outline: none; box-shadow: 0 0 5px rgba(255, 76, 76, 0.3); }
        
        button { width: 100%; padding: 14px; background: #ff4c4c; color: #fff; border: none; font-weight: bold; font-size: 14px; cursor: pointer; border-radius: 4px; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; }
        button:hover { background: #d93838; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 76, 76, 0.2); }
        
        .error-msg { background: rgba(255, 76, 76, 0.1); color: #ff4c4c; padding: 12px; border-radius: 4px; border-left: 3px solid #ff4c4c; font-size: 13px; margin-bottom: 20px; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 25px; color: #555; text-decoration: none; font-size: 12px; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; }
        .back-link:hover { color: #888; }
    </style>
</head>
<body>

    <div class="admin-login-box">
        <div class="header-text">Kontrol Merkezi</div>
        <div class="sub-text">Sadece Yetkili Personel</div>
        
        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Yönetici E-Posta</label>
                <input type="email" name="email" placeholder="abady@admin.com" required>
            </div>
            <div class="form-group">
                <label>Sistem Şifresi</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit">Güvenli Giriş Yap</button>
        </form>
        
        <a href="../public/index.php" class="back-link">⬅ Müzeye Dön</a>
    </div>

</body>
</html>