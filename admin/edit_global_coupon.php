<?php
require_once '../config/init.php';

// Güvenlik Kontrolü: Sadece adminler
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// URL'den gelen düzenlenecek kuponun ID'sini al
$coupon_id_to_edit = $_GET['id'] ?? null;
if (!$coupon_id_to_edit) {
    die("Düzenlenecek kupon ID'si belirtilmemiş.");
}

// Kuponun mevcut bilgilerini çek (ve sadece GENEL kupon olduğundan emin ol - company_id IS NULL)
$stmt_coupon = $pdo->prepare("SELECT * FROM Coupons WHERE id = ? AND company_id IS NULL");
$stmt_coupon->execute([$coupon_id_to_edit]);
$coupon = $stmt_coupon->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    die("Genel kupon bulunamadı veya bu kuponu düzenleme yetkiniz yok.");
}

$error_message = '';
$success_message = '';

// Form gönderildiyse (güncelleme işlemi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $discount_percentage = $_POST['discount'];
    $usage_limit = $_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];

    // Doğrulamalar
    if (empty($code) || !isset($discount_percentage) || empty($usage_limit) || empty($expire_date)) {
        $error_message = "Tüm alanların doldurulması zorunludur.";
    } elseif (!is_numeric($discount_percentage) || $discount_percentage <= 0 || $discount_percentage > 100) {
        $error_message = "İndirim oranı 1 ile 100 arasında bir sayı olmalıdır.";
    } elseif (!ctype_digit($usage_limit) || $usage_limit <= 0) {
        $error_message = "Kullanım limiti pozitif bir tam sayı olmalıdır.";
    } elseif (strtotime($expire_date) < strtotime(date('Y-m-d'))) {
         $error_message = "Son kullanma tarihi geçmiş bir tarih olamaz.";
    } else {
        // Kod unique mi kontrolü (kendisi hariç)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Coupons WHERE code = ? AND id != ?");
        $stmt_check->execute([$code, $coupon_id_to_edit]);
        if ($stmt_check->fetchColumn() > 0) {
            $error_message = "Bu kupon kodu zaten başka bir kupon tarafından kullanılıyor.";
        } else {
            $discount_db_value = $discount_percentage / 100.0;

            // Kuponu güncelle (WHERE company_id IS NULL kontrolü önemli)
            $stmt_update = $pdo->prepare(
                "UPDATE Coupons SET
                    code = ?,
                    discount = ?,
                    usage_limit = ?,
                    expire_date = ?
                 WHERE id = ? AND company_id IS NULL"
            );
            try {
                $formatted_expire_date = date('Y-m-d', strtotime($expire_date));

                $stmt_update->execute([
                    $code,
                    $discount_db_value,
                    $usage_limit,
                    $formatted_expire_date,
                    $coupon_id_to_edit
                ]);
                header("Location: index.php?status=gcoupon_updated");
                exit();
            } catch (PDOException $e) {
                $error_message = "Genel kupon güncellenirken bir veritabanı hatası oluştu: " . $e->getMessage();
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
    <title>Genel Kupon Düzenle - Admin Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul><li><strong><a href="/admin/">Admin Paneli</a></strong></li></ul>
            <ul><li><a href="/logout.php">Çıkış Yap</a></li></ul>
        </nav>

        <article>
            <hgroup>
                <h1>Genel Kupon Düzenle</h1>
                <h2>Genel kupon bilgilerini güncelleyin.</h2>
            </hgroup>

            <?php if (!empty($error_message)): ?>
                <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                 <p style="color: green;"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form method="POST">
                 <label for="code">Kupon Kodu</label>
                 <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($coupon['code']); ?>" required>

                 <label for="discount">İndirim Oranı (%)</label>
                 <input type="number" id="discount" name="discount" min="1" max="100" value="<?php echo htmlspecialchars($coupon['discount'] * 100); ?>" required>

                 <label for="usage_limit">Kullanım Limiti</label>
                 <input type="number" id="usage_limit" name="usage_limit" min="1" value="<?php echo htmlspecialchars($coupon['usage_limit']); ?>" required>

                 <label for="expire_date">Son Kullanma Tarihi</label>
                 <input type="date" id="expire_date" name="expire_date" value="<?php echo htmlspecialchars($coupon['expire_date']); ?>" required min="<?php echo date('Y-m-d'); ?>">

                <button type="submit">Değişiklikleri Kaydet</button>
            </form>
        </article>
    </main>
</body>
</html>