<?php
require_once '../config/database.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $password   = $_POST['password'];

    // Şifreyi güvenli bir şekilde hash'le
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        $sql = "INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$first_name, $last_name, $email, $hashed_password]);
        $message = "Kayıt başarılı! Giriş yapabilirsiniz.";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Email zaten varsa
            $message = "Bu e-posta adresi zaten kullanımda.";
        } else {
            $message = "Bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mısır Müzesi - Kayıt Ol</title>
    <style>
        body { background: #0a0e14; color: #d4af37; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .form-container { background: #151b23; padding: 30px; border: 1px solid #d4af37; border-radius: 8px; box-shadow: 0 0 20px rgba(212, 175, 55, 0.2); width: 350px; }
        input { width: 100%; padding: 10px; margin: 10px 0; background: #0a0e14; border: 1px solid #333; color: white; border-radius: 4px; box-sizing: border-box; }
        input:focus { border-color: #d4af37; outline: none; }
        button { width: 100%; padding: 12px; background: #d4af37; border: none; color: #0a0e14; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background: #b08d26; }
        .msg { color: #fff; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 style="text-align: center;">Hiyerogliflerin Dünyasına Katıl</h2>
        <?php if($message) echo "<p class='msg'>$message</p>"; ?>
        <form method="POST">
            <input type="text" name="first_name" placeholder="Adınız" required>
            <input type="text" name="last_name" placeholder="Soyadınız" required>
            <input type="email" name="email" placeholder="E-posta" required>
            <input type="password" name="password" placeholder="Şifre" required>
            <button type="submit">Kayıt Ol</button>
        </form>
        <p style="text-align: center; font-size: 14px;">Zaten hesabın var mı? <a href="login.php" style="color: #fff;">Giriş Yap</a></p>
    </div>
</body>
</html>