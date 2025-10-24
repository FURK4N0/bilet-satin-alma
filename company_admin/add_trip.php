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
$success_message = '';

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = $_POST['price'];
    $capacity = $_POST['capacity'];

    // Basit doğrulama
    if (empty($departure_city) || empty($destination_city) || empty($departure_time) || empty($arrival_time) || empty($price) || empty($capacity)) {
        $error_message = "Tüm alanların doldurulması zorunludur.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = "Fiyat geçerli bir sayı olmalıdır.";
    } elseif (!ctype_digit($capacity) || $capacity <= 0) {
        $error_message = "Kapasite geçerli bir tam sayı olmalıdır.";
    } elseif (strtotime($arrival_time) <= strtotime($departure_time)) {
        $error_message = "Varış zamanı, kalkış zamanından sonra olmalıdır.";
    } else {
        // Yeni seferi veritabanına ekle
        $stmt_insert = $pdo->prepare(
            "INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        try {
            // Tarih ve saatleri veritabanı formatına (YYYY-MM-DD HH:MM:SS) çevir
            $formatted_departure_time = date('Y-m-d H:i:s', strtotime($departure_time));
            $formatted_arrival_time = date('Y-m-d H:i:s', strtotime($arrival_time));

            $stmt_insert->execute([
                generate_uuid(),
                $company_id, // Firma admininin kendi şirket ID'si
                $departure_city,
                $destination_city,
                $formatted_departure_time,
                $formatted_arrival_time,
                $price,
                $capacity
            ]);
            // Başarılı olunca firma paneline yönlendir
            header("Location: index.php?status=trip_added");
            exit();
        } catch (PDOException $e) {
            $error_message = "Sefer eklenirken bir veritabanı hatası oluştu: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Sefer Ekle - Firma Paneli</title>
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
                <h1>Yeni Sefer Ekle</h1>
                <h2>Şirketinize ait yeni bir sefer oluşturun.</h2>
            </hgroup>

            <?php if (!empty($error_message)): ?>
                <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                 <p style="color: green;"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form method="POST">
                <div class="grid">
                    <label for="departure_city">Kalkış Şehri</label>
                    <input type="text" id="departure_city" name="departure_city" required>

                    <label for="destination_city">Varış Şehri</label>
                    <input type="text" id="destination_city" name="destination_city" required>
                </div>

                <div class="grid">
                    <label for="departure_time">Kalkış Zamanı</label>
                    <input type="datetime-local" id="departure_time" name="departure_time" required>

                    <label for="arrival_time">Varış Zamanı</label>
                    <input type="datetime-local" id="arrival_time" name="arrival_time" required>
                </div>

                 <div class="grid">
                    <label for="price">Fiyat (TL)</label>
                    <input type="number" id="price" name="price" step="1" min="0" required>

                    <label for="capacity">Koltuk Kapasitesi</label>
                    <input type="number" id="capacity" name="capacity" min="1" required>
                 </div>
                
                <button type="submit">Seferi Ekle</button>
            </form>
        </article>
    </main>
</body>
</html>