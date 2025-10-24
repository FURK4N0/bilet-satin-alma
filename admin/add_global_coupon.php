<?php
require_once '../config/init.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// Benzersiz ID
function generate_uuid() { /* ... (fonksiyon aynı) ... */
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff) );
}


$error_message = '';
$success_message = ''; // Bu değişken şu anda kullanılmıyor (direkt yönlendirme var)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $discount_percentage = $_POST['discount'];
    $usage_limit = $_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];

    // Doğrulamalar (Firma Admin ile aynı)
    if (empty($code) || !isset($discount_percentage) || empty($usage_limit) || empty($expire_date)) {
        $error_message = "Tüm alanların doldurulması zorunludur.";
    } elseif (!is_numeric($discount_percentage) || $discount_percentage <= 0 || $discount_percentage > 100) {
        $error_message = "İndirim oranı 1 ile 100 arasında bir sayı olmalıdır.";
    } elseif (!ctype_digit($usage_limit) || $usage_limit <= 0) {
        $error_message = "Kullanım limiti pozitif bir tam sayı olmalıdır.";
    } elseif (strtotime($expire_date) < strtotime(date('Y-m-d'))) {
         $error_message = "Son kullanma tarihi geçmiş bir tarih olamaz.";
    } else {
        // Kupon kodu var mı kontrolü
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Coupons WHERE code = ?");
        $stmt_check->execute([$code]);
        if ($stmt_check->fetchColumn() > 0) {
            $error_message = "Bu kupon kodu zaten kullanılıyor.";
        } else {
            $discount_db_value = $discount_percentage / 100.0;

            // Yeni kuponu ekle, company_id'yi NULL bırak
            $stmt_insert = $pdo->prepare(
                "INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date)
                 VALUES (?, ?, ?, NULL, ?, ?)" // company_id için NULL
            );
            try {
                $formatted_expire_date = date('Y-m-d', strtotime($expire_date));

                $stmt_insert->execute([
                    generate_uuid(),
                    $code,
                    $discount_db_value,
                    // company_id yok
                    $usage_limit,
                    $formatted_expire_date
                ]);
                header("Location: index.php?status=gcoupon_added");
                exit();
            } catch (PDOException $e) {
                $error_message = "Genel kupon eklenirken bir veritabanı hatası oluştu: " . $e->getMessage();
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
    <title>Yeni Genel Kupon Ekle - Admin Paneli</title>
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
                <h1>Yeni Genel Kupon Ekle</h1>
                <h2>Tüm firmalarda geçerli olacak bir indirim kuponu oluşturun.</h2>
            </hgroup>

            <?php if (!empty($error_message)): ?>
                <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
            <?php endif; ?>
            <?php /* Başarı mesajı bu kodda gösterilmiyor (yönlendirme var) */ ?>

            <form method="POST">
                 <label for="code">Kupon Kodu</label>
                 <input type="text" id="code" name="code" placeholder="Örn: SUPERINDIRIM" required>

                 <label for="discount">İndirim Oranı (%)</label>
                 <input type="number" id="discount" name="discount" min="1" max="100" placeholder="Örn: 20" required>

                 <label for="usage_limit">Kullanım Limiti</label>
                 <input type="number" id="usage_limit" name="usage_limit" min="1" placeholder="Kaç kez kullanılabilir?" required>

                 <label for="expire_date">Son Kullanma Tarihi</label>
                 <input type="date" id="expire_date" name="expire_date" required min="<?php echo date('Y-m-d'); ?>">

                <button type="submit">Genel Kuponu Ekle</button>
            </form>
        </article>
    </main>
</body>
</html>