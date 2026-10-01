<?php
// app/logout.php

session_start();

// Tüm session değişkenlerini boşalt
$_SESSION = array();

// Session çerezini (cookie) tarayıcıdan sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu tamamen yok et
session_destroy();

// Kullanıcıyı herkesin gördüğü genel vitrin sayfasına yönlendir
header("Location: ../public/index.php");
exit();
?>