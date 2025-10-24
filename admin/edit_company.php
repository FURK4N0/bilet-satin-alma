<?php
require_once '../config/init.php';

// Güvenlik Kontrolü: Sadece adminler erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// URL'den gelen düzenlenecek firmanın ID'sini al
$company_id_to_edit = $_GET['id'] ?? null;
if (!$company_id_to_edit) {
    die("Düzenlenecek firma ID'si belirtilmemiş.");
}

// Firmanın mevcut bilgilerini çek
$stmt_company = $pdo->prepare("SELECT * FROM Bus_Company WHERE id = ?");
$stmt_company->execute([$company_id_to_edit]);
$company = $stmt_company->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    die("Firma bulunamadı.");
}

$error_message = '';
$success_message = '';

// Form gönderildiyse (güncelleme işlemi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name']);

    if (empty($company_name)) {
        $error_message = "Firma adı boş bırakılamaz.";
    } else {
        // Bu isimde başka bir firma var mı diye kontrol et (kendisi hariç)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Bus_Company WHERE name = ? AND id != ?");
        $stmt_check->execute([$company_name, $company_id_to_edit]);
        if ($stmt_check->fetchColumn() > 0) {
            $error_message = "Bu isimde başka bir firma zaten mevcut.";
        } else {
            // Firmayı güncelle
            $stmt_update = $pdo->prepare("UPDATE Bus_Company SET name = ? WHERE id = ?");
            try {
                $stmt_update->execute([$company_name, $company_id_to_edit]);
                // Başarılı olunca admin paneline yönlendir
                header("Location: index.php?status=company_updated");
                exit();
            } catch (PDOException $e) {
                $error_message = "Firma güncellenirken bir veritabanı hatası oluştu: " . $e->getMessage();
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
    <title>Firma Düzenle - Admin Paneli</title>
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
                <h1>Firma Düzenle</h1>
                <h2>Firma adını güncelleyin.</h2>
            </hgroup>

            <?php if (!empty($error_message)): ?>
                <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <p style="color: green;"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form method="POST">
                <label for="company_name">Firma Adı</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company['name']); ?>" required>

                <button type="submit">Değişiklikleri Kaydet</button>
            </form>
        </article>
    </main>
</body>
</html>