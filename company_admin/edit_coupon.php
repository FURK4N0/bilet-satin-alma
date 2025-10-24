<?php
require_once '../config/init.php';

// kontrol
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company_admin') {
    header("Location: /index.php");
    exit();
}

// Firma admininin şirket ID'si
$stmt_user = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$company_id = $stmt_user->fetchColumn();

if (!$company_id) {
    die("HATA: Bu kullanıcı bir şirkete atanmamış.");
}

// URL'den kuponun ID'si
$coupon_id_to_edit = $_GET['id'] ?? null;
if (!$coupon_id_to_edit) {
    die("Düzenlenecek kupon ID'si belirtilmemiş.");
}

// Kuponun mevcut bilgilerini çek (sadece bu firmaya ait olduğundan emin ol)
$stmt_coupon = $pdo->prepare("SELECT * FROM Coupons WHERE id = ? AND company_id = ?");
$stmt_coupon->execute([$coupon_id_to_edit, $company_id]);
$coupon = $stmt_coupon->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    die("Kupon bulunamadı veya bu kuponu düzenleme yetkiniz yok.");
}

$error_message = '';
$success_message = ''; // Bu değişken kodda kullanılmıyor ama kalsın

// Form gönderildiyse (güncelleme işlemi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $discount_percentage = $_POST['discount_percentage_input']; // Formdaki input adı
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
            // İndirim oranını veritabanı formatına çevir (örn: 10 -> 0.10)
            $discount_db_value = $discount_percentage / 100.0;

            // Kuponu güncelle
            $stmt_update = $pdo->prepare(
                "UPDATE Coupons SET
                    code = ?,
                    discount = ?,
                    usage_limit = ?,
                    expire_date = ?
                 WHERE id = ? AND company_id = ?"
            );
            try {
                $formatted_expire_date = date('Y-m-d', strtotime($expire_date));

                $stmt_update->execute([
                    $code,
                    $discount_db_value,
                    $usage_limit,
                    $formatted_expire_date,
                    $coupon_id_to_edit,
                    $company_id
                ]);
                 header("Location: index.php?status=coupon_updated");
                 exit();
            } catch (PDOException $e) {
                $error_message = "Kupon güncellenirken bir veritabanı hatası oluştu: " . $e->getMessage();
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
    <title>Kupon Düzenle - Firma Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul>
                <li><strong><a href="/company_admin/">Firma Paneli</a></strong></li>
            </ul>
            <ul>
                <li><a href="/logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <article>
            <hgroup>
                <h1>Kupon Düzenle</h1>
                <h2>Kupon bilgilerini güncelleyin.</h2>
            </hgroup>

            <?php if (!empty($error_message)): ?>
                <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
            <?php endif; ?>
            <?php /* Başarı mesajı bu kodda gösterilmiyor ama gösterilseydi o da htmlspecialchars içine alınmalıydı */ ?>

            <form method="POST">
                 <label for="code">Kupon Kodu</label>
                 <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($coupon['code']); ?>" required>

                 <label for="discount_percentage_input">İndirim Oranı (%)</label>
                 <input type="number" id="discount_percentage_input" name="discount_percentage_input" min="1" max="100" value="<?php echo htmlspecialchars($coupon['discount'] * 100); ?>" required>

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