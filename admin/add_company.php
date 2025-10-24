<?php
require_once '../config/init.php';

// Güvenlik Kontrolü: Sadece adminler erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// Benzersiz ID oluşturma fonksiyonu
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$error_message = '';

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name']);

    if (empty($company_name)) {
        $error_message = "Firma adı boş bırakılamaz.";
    } else {
        // Bu isimde bir firma var mı diye kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Bus_Company WHERE name = ?");
        $stmt->execute([$company_name]);
        if ($stmt->fetchColumn() > 0) {
            $error_message = "Bu isimde bir firma zaten mevcut.";
        } else {
            // Yeni firmayı veritabanına ekle
            $stmt_insert = $pdo->prepare("INSERT INTO Bus_Company (id, name) VALUES (?, ?)");
            try {
                $stmt_insert->execute([generate_uuid(), $company_name]);
                // Başarılı olursa admin paneli ana sayfasına yönlendir
                header("Location: /admin/index.php?status=company_added");
                exit();
            } catch (PDOException $e) {
                $error_message = "Firma eklenirken bir veritabanı hatası oluştu: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Firma Ekle - Admin Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul>
                <li><strong><a href="/admin/">Admin Paneli</a></strong></li>
            </ul>
            <ul>
                <li><a href="/logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <article>
            <hgroup>
                <h1>Yeni Firma Ekle</h1>
                <h2>Sisteme yeni bir otobüs firması ekleyin.</h2>
            </hgroup>

            <?php if (!empty($error_message)): ?>
                <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
            <?php endif; ?>

            <form method="POST">
                <label for="company_name">Firma Adı</label>
                <input type="text" id="company_name" name="company_name" placeholder="Örn: Pamukkale Turizm" required>

                <button type="submit">Firmayı Ekle</button>
            </form>
        </article>
    </main>
</body>
</html>