<?php
session_start();
require_once '../config/database.php';

// Eğer kullanıcı zaten giriş yapmışsa direkt panele yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Kullanıcı var mı ve şifre (hash) eşleşiyor mu?
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Oturum bilgilerini oluştur
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];
            $_SESSION['role'] = $user['role']; // 'admin' veya 'user'

            // Yetkiye göre doğru panele yönlendir
            if ($user['role'] === 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "E-posta adresi veya şifre hatalı.";
        }
    } catch (PDOException $e) {
        $error = "Sistemsel bir hata oluştu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mısır Müzesi - Giriş Yap</title>
    <style>
        body { background: #0a0e14; color: #d4af37; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background: #151b23; padding: 40px 30px; border: 1px solid #d4af37; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); width: 100%; max-width: 350px; }
        .logo-text { text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; }
        input { width: 100%; padding: 12px; margin: 10px 0 20px 0; background: #050505; border: 1px solid #333; color: white; border-radius: 4px; box-sizing: border-box; }
        input:focus { border-color: #d4af37; outline: none; }
        button { width: 100%; padding: 12px; background: #d4af37; border: none; color: #0a0e14; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; border-radius: 4px; }
        button:hover { background: #b08d26; transform: translateY(-2px); }
        .error { color: #ff4c4c; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(255, 76, 76, 0.1); padding: 10px; border-radius: 4px; }
        .link { text-align: center; margin-top: 20px; font-size: 14px; color: #aaa; }
        .link a { color: #d4af37; text-decoration: none; font-weight: bold; }
        .link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo-text">Mısır 3D</div>
        <h3 style="text-align: center; margin-top: 0; color: #fff;">Sisteme Giriş</h3>
        
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST">
            <label style="font-size: 12px; color: #aaa; text-transform: uppercase;">E-posta Adresiniz</label>
            <input type="email" name="email" placeholder="ornek@mail.com" required>
            
            <label style="font-size: 12px; color: #aaa; text-transform: uppercase;">Şifreniz</label>
            <input type="password" name="password" placeholder="••••••••" required>
            
            <button type="submit">Giriş Yap</button>
        </form>
        
        <div class="link">
            Hesabın yok mu? <a href="register.php">Hemen Kayıt Ol</a><br><br>
            <a href="../public/index.php" style="color: #aaa; font-weight: normal;">⬅ Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>