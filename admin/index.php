<?php
session_start();
require_once '../config/database.php';

// GÜVENLİK: Sadece adminler girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Artık kendi klasöründeki login.php'ye atacak
    exit();
}

$message = "";
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}

// --- CRUD İŞLEMLERİ (POST) ---

// 1. TUR EKLEME / GÜNCELLEME
if (isset($_POST['add_tour'])) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $img = $_POST['cover_image_url'];

    $stmt = $pdo->prepare("INSERT INTO tours (title, description, price, cover_image_url) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$title, $desc, $price, $img])) {
        header("Location: index.php?msg=Tur eklendi");
        exit();
    }
} elseif (isset($_POST['update_tour'])) {
    $id = $_POST['tour_id'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $img = $_POST['cover_image_url'];

    $stmt = $pdo->prepare("UPDATE tours SET title = ?, description = ?, price = ?, cover_image_url = ? WHERE id = ?");
    if ($stmt->execute([$title, $desc, $price, $img, $id])) {
        header("Location: index.php?msg=Tur güncellendi");
        exit();
    }
}

// 2. ESER EKLEME / GÜNCELLEME
if (isset($_POST['add_artifact'])) {
    $tour_id = $_POST['tour_id'];
    $name = $_POST['name'];
    $info = $_POST['historical_info'];
    $url = $_POST['model_3d_url'];
    $scale = $_POST['scale_factor'];

    $stmt = $pdo->prepare("INSERT INTO artifacts (tour_id, name, historical_info, model_3d_url, scale_factor) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$tour_id, $name, $info, $url, $scale])) {
        header("Location: index.php?msg=Eser eklendi");
        exit();
    }
} elseif (isset($_POST['update_artifact'])) {
    $id = $_POST['artifact_id'];
    $tour_id = $_POST['tour_id'];
    $name = $_POST['name'];
    $info = $_POST['historical_info'];
    $url = $_POST['model_3d_url'];
    $scale = $_POST['scale_factor'];

    $stmt = $pdo->prepare("UPDATE artifacts SET tour_id = ?, name = ?, historical_info = ?, model_3d_url = ?, scale_factor = ? WHERE id = ?");
    if ($stmt->execute([$tour_id, $name, $info, $url, $scale, $id])) {
        header("Location: index.php?msg=Eser güncellendi");
        exit();
    }
}

// --- SİLME VE DURUM GÜNCELLEME (GET) ---

if (isset($_GET['delete_tour'])) {
    // Önce tura bağlı eserleri sil ki hata vermesin (Foreign Key Error önlemi)
    $stmt = $pdo->prepare("DELETE FROM artifacts WHERE tour_id = ?");
    $stmt->execute([$_GET['delete_tour']]);
    
    $stmt = $pdo->prepare("DELETE FROM tours WHERE id = ?");
    $stmt->execute([$_GET['delete_tour']]);
    header("Location: index.php?msg=Tur silindi");
    exit();
}

if (isset($_GET['toggle_tour'])) {
    $stmt = $pdo->prepare("UPDATE tours SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$_GET['toggle_tour']]);
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_artifact'])) {
    $stmt = $pdo->prepare("DELETE FROM artifacts WHERE id = ?");
    $stmt->execute([$_GET['delete_artifact']]);
    header("Location: index.php?msg=Eser silindi");
    exit();
}

// --- VERİLERİ ÇEKME ---
$tours = $pdo->query("SELECT * FROM tours ORDER BY created_at DESC")->fetchAll();
$artifacts = $pdo->query("SELECT a.*, t.title as tour_title FROM artifacts a JOIN tours t ON a.tour_id = t.id ORDER BY a.id DESC")->fetchAll();

$stats_users = 0;
$stats_bookings = 0;
try {
    $stats_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
    $stats_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
} catch (PDOException $e) {
    // Eğer bookings veya users tablosu yoksa veya hatalıysa, sessizce geç
}

// --- DÜZENLEME İÇİN VERİ ÇEKME ---
$edit_tour = null;
if (isset($_GET['edit_tour'])) {
    $stmt = $pdo->prepare("SELECT * FROM tours WHERE id = ?");
    $stmt->execute([$_GET['edit_tour']]);
    $edit_tour = $stmt->fetch();
}

$edit_artifact = null;
if (isset($_GET['edit_artifact'])) {
    $stmt = $pdo->prepare("SELECT * FROM artifacts WHERE id = ?");
    $stmt->execute([$_GET['edit_artifact']]);
    $edit_artifact = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mısır Müzesi - Profesyonel Yönetim</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #0a0e14; color: #fff; display: flex; }
        .sidebar { width: 260px; background: #111; height: 100vh; position: fixed; padding: 20px; border-right: 1px solid #d4af37; box-sizing: border-box; }
        .sidebar h2 { color: #d4af37; text-align: center; font-size: 18px; margin-bottom: 30px; }
        .sidebar a { display: block; color: #ccc; text-decoration: none; padding: 12px; border-radius: 5px; margin-bottom: 10px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(212, 175, 55, 0.1); color: #d4af37; }
        
        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); box-sizing: border-box; }
        .card { background: #151b23; padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #333; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-box { background: #1a2233; padding: 20px; border-radius: 10px; flex: 1; border-left: 4px solid #d4af37; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #333; }
        th { color: #d4af37; }
        
        input, textarea, select { width: 100%; padding: 10px; margin-top: 5px; background: #050505; border: 1px solid #444; color: #fff; border-radius: 5px; box-sizing: border-box; }
        .btn { background: #d4af37; color: #000; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 15px; }
        .btn-danger { background: #ff4c4c; color: #fff; }
        .status-on { color: #28a745; }
        .status-off { color: #ff4c4c; }
        
        .alert { background: rgba(40, 167, 69, 0.2); color: #28a745; border: 1px solid #28a745; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; text-align: center; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>KONTROL MERKEZİ</h2>
        <a href="#stats">📊 Genel Bakış</a>
        <a href="#tours">🏛️ Turları Yönet</a>
        <a href="#artifacts">🏺 3D Eserler</a>
        <a href="../app/logout.php" style="color: #ff4c4c; border: 1px solid #ff4c4c; margin-top: 50px; text-align: center;">Güvenli Çıkış</a>
    </div>

    <div class="main">
        <?php if($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>

        <h1 id="stats">Sistem Özeti</h1>
        
        <div class="stats">
            <div class="stat-box"><h3>Kayıtlı Kullanıcı</h3><div style="font-size: 24px;"><?php echo $stats_users; ?></div></div>
            <div class="stat-box"><h3>Aktif Turlar</h3><div style="font-size: 24px;"><?php echo count($tours); ?></div></div>
            <div class="stat-box"><h3>Toplam Rezervasyon</h3><div style="font-size: 24px;"><?php echo $stats_bookings; ?></div></div>
        </div>

        <div class="card" id="tours">
            <h2>🏛️ Turları Yönet / <?php echo $edit_tour ? 'Düzenle' : 'Ekle'; ?></h2>
            <form method="POST" action="index.php">
                <?php if($edit_tour): ?>
                    <input type="hidden" name="tour_id" value="<?php echo $edit_tour['id']; ?>">
                <?php endif; ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div><label>Tur Adı</label><input type="text" name="title" value="<?php echo $edit_tour ? htmlspecialchars($edit_tour['title']) : ''; ?>" required></div>
                    <div><label>Kapak Görseli (URL)</label><input type="text" name="cover_image_url" value="<?php echo $edit_tour ? htmlspecialchars($edit_tour['cover_image_url']) : ''; ?>" placeholder="https://..."></div>
                    <div style="grid-column: span 2;"><label>Açıklama</label><textarea name="description" rows="3" required><?php echo $edit_tour ? htmlspecialchars($edit_tour['description']) : ''; ?></textarea></div>
                    <div><label>Bilet Fiyatı (Ücretsiz ise 0)</label><input type="number" name="price" value="<?php echo $edit_tour ? $edit_tour['price'] : '0'; ?>" step="0.01"></div>
                </div>
                <?php if($edit_tour): ?>
                    <button type="submit" name="update_tour" class="btn">Turu Güncelle</button>
                    <a href="index.php" class="btn" style="background:#555; color:#fff; text-decoration:none; display:inline-block; margin-left:10px;">İptal</a>
                <?php else: ?>
                    <button type="submit" name="add_tour" class="btn">+ Turu Kaydet</button>
                <?php endif; ?>
            </form>

            <table>
                <thead><tr><th>Tur Adı</th><th>Fiyat</th><th>Durum</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php foreach($tours as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['title']); ?></td>
                        <td><?php echo $t['price'] == 0 ? 'ÜCRETSİZ' : $t['price'] . ' ₺'; ?></td>
                        <td class="<?php echo $t['is_active'] ? 'status-on' : 'status-off'; ?>">
                            <?php echo $t['is_active'] ? '● Aktif' : '○ Pasif'; ?>
                        </td>
                        <td>
                            <a href="?toggle_tour=<?php echo $t['id']; ?>" style="color: #aaa; text-decoration:none;">[Aç/Kapat]</a>
                            <a href="?edit_tour=<?php echo $t['id']; ?>#tours" style="color: #4da6ff; margin-left: 10px; text-decoration:none;">Düzenle</a>
                            <a href="?delete_tour=<?php echo $t['id']; ?>" style="color: #ff4c4c; margin-left: 10px; text-decoration:none;" onclick="return confirm('Silmek istediğinize emin misiniz? (Bağlı eserler de silinir!)')">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card" id="artifacts">
            <h2>🏺 3D Eserleri <?php echo $edit_artifact ? 'Düzenle' : 'Sisteme İşle'; ?></h2>
            <form method="POST" action="index.php">
                <?php if($edit_artifact): ?>
                    <input type="hidden" name="artifact_id" value="<?php echo $edit_artifact['id']; ?>">
                <?php endif; ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div><label>Hangi Tura Ait?</label>
                        <select name="tour_id">
                            <?php foreach($tours as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo ($edit_artifact && $edit_artifact['tour_id'] == $t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Eser Adı</label><input type="text" name="name" value="<?php echo $edit_artifact ? htmlspecialchars($edit_artifact['name']) : ''; ?>" required></div>
                    <div style="grid-column: span 2;"><label>Tarihi Bilgi</label><textarea name="historical_info" rows="2" required><?php echo $edit_artifact ? htmlspecialchars($edit_artifact['historical_info']) : ''; ?></textarea></div>
                    <div><label>GLB Model Yolu</label><input type="text" name="model_3d_url" value="<?php echo $edit_artifact ? htmlspecialchars($edit_artifact['model_3d_url']) : '../public/3d-models/sphinx.glb'; ?>" required></div>
                    <div><label>Ölçek (Scale)</label><input type="number" name="scale_factor" value="<?php echo $edit_artifact ? $edit_artifact['scale_factor'] : '1.0'; ?>" step="0.1"></div>
                </div>
                <?php if($edit_artifact): ?>
                    <button type="submit" name="update_artifact" class="btn">Eseri Güncelle</button>
                    <a href="index.php" class="btn" style="background:#555; color:#fff; text-decoration:none; display:inline-block; margin-left:10px;">İptal</a>
                <?php else: ?>
                    <button type="submit" name="add_artifact" class="btn">+ Eseri Yayınla</button>
                <?php endif; ?>
            </form>

            <table style="margin-top: 30px;">
                <thead><tr><th>Eser Adı</th><th>Tur</th><th>Model Yolu</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php foreach($artifacts as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['name']); ?></td>
                        <td><?php echo htmlspecialchars($a['tour_title']); ?></td>
                        <td><?php echo htmlspecialchars($a['model_3d_url']); ?></td>
                        <td>
                            <a href="?edit_artifact=<?php echo $a['id']; ?>#artifacts" style="color: #4da6ff; text-decoration:none;">Düzenle</a>
                            <a href="?delete_artifact=<?php echo $a['id']; ?>" style="color: #ff4c4c; margin-left: 10px; text-decoration:none;" onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>