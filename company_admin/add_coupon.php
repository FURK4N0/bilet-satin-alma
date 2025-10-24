<?php
require_once '../config/init.php';

// kontrol
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company_admin') {
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

// Firma admininin şirket ID'si
$stmt_user = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$company_id = $stmt_user->fetchColumn();

if (!$company_id) {
    die("HATA: Bu kullanıcı bir şirkete atanmamış.");
}

$error_message = '';
$success_message = ''; // Başarı mesajı şu an yönlendirme nedeniyle gösterilmiyor ama düzeltelim

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $discount_percentage = $_POST['discount']; // Formdan yüzde olarak alınacak (örn: 10)
    $usage_limit = $_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];

    // Doğrulamalar
    if (empty($code) || empty($discount_percentage) || empty($usage_limit) || empty($expire_date)) {
        $error_message = "Tüm alanların doldurulması zorunludur.";
    } elseif (!is_numeric($discount_percentage) || $discount_percentage <= 0 || $discount_percentage > 100) {
        $error_message = "İndirim oranı 1 ile 100 arasında bir sayı olmalıdır.";
    } elseif (!ctype_digit($usage_limit) || $usage_limit <= 0) {
        $error_message = "Kullanım limiti pozitif bir tam sayı olmalıdır.";
    } elseif (strtotime($expire_date) < strtotime(date('Y-m-d'))) { // Sadece tarihi kontrol et
         $error_message = "Son kullanma tarihi geçmiş bir tarih olamaz.";
    } else {
        // Bu kodda bir kupon var mı?
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Coupons WHERE code = ?");
        $stmt_check->execute([$code]);
        if ($stmt_check->fetchColumn() > 0) {
            $error_message = "Bu kupon kodu zaten kullanılıyor.";
        } else {
            // İndirim oranını veritabanı formatına çevir (örn: 10 -> 0.10)
            $discount_rate = $discount_percentage / 100.0;

            // Yeni kuponu veritabanına ekle
            $stmt_insert = $pdo->prepare(
                "INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            try {
                $formatted_expire_date = date('Y-m-d', strtotime($expire_date));

                $stmt_insert->execute([
                    generate_uuid(),
                    $code,
                    $discount_rate,
                    $company_id, // Firma admininin kendi şirket ID'si
                    $usage_limit,
                    $formatted_expire_date
                ]);
                // Başarılı olunca firma paneline yönlendir
                header("Location: index.php?status=coupon_added");
                exit();
            } catch (PDOException $e) {
                $error_message = "Kupon eklenirken bir veritabanı hatası oluştu: " . $e->getMessage();
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
    <title>Yeni Kupon Ekle - Firma Paneli</title>
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
                <h1>Yeni Kupon Ekle</h1>
                <h2>Firmanıza özel yeni bir indirim kuponu oluşturun.</h2>
            </hgroup>

            <?php if (!empty($error_message)): ?>
                <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                 <p style="color: green;"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form method="POST">
                 <label for="code">Kupon Kodu</label>
                 <input type="text" id="code" name="code" placeholder="Örn: HOSGELDIN10" required>

                 <label for="discount">İndirim Oranı (%)</label>
                 <input type="number" id="discount" name="discount" min="1" max="100" placeholder="Örn: 10" required>

                 <label for="usage_limit">Kullanım Limiti</label>
                 <input type="number" id="usage_limit" name="usage_limit" min="1" placeholder="Kaç kez kullanılabilir?" required>

                 <label for="expire_date">Son Kullanma Tarihi</label>
                 <input type="date" id="expire_date" name="expire_date" required min="<?php echo date('Y-m-d'); ?>">

                <button type="submit">Kuponu Ekle</button>
            </form>
        </article>
    </main>
</body>
</html>