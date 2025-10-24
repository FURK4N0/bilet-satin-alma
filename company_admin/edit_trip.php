<?php
require_once '../config/init.php';

// kontrol
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company_admin') {
    header("Location: /index.php");
    exit();
}

// şirket ID
$stmt_user = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$company_id = $stmt_user->fetchColumn();

if (!$company_id) {
    die("HATA: Bu kullanıcı bir şirkete atanmamış.");
}

// URL'den seferin ID'si alınır
$trip_id_to_edit = $_GET['id'] ?? null;
if (!$trip_id_to_edit) {
    die("Düzenlenecek sefer ID'si belirtilmemiş.");
}

// Seferin mevcut bilgilerini çek ( sadece bu firmaya ait olduğundan emin ol)
$stmt_trip = $pdo->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
$stmt_trip->execute([$trip_id_to_edit, $company_id]);
$trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Sefer bulunamadı veya bu seferi düzenleme yetkiniz yok.");
}

$error_message = '';
$success_message = '';

// Form gönderildiyse (güncelleme işlemi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = $_POST['price'];
    $capacity = $_POST['capacity'];

    // Doğrulamalar (add_trip.php ile aynı)
    if (empty($departure_city) || empty($destination_city) || empty($departure_time) || empty($arrival_time) || empty($price) || empty($capacity)) {
        $error_message = "Tüm alanların doldurulması zorunludur.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = "Fiyat geçerli bir sayı olmalıdır.";
    } elseif (!ctype_digit($capacity) || $capacity <= 0) {
        $error_message = "Kapasite geçerli bir tam sayı olmalıdır.";
    } elseif (strtotime($arrival_time) <= strtotime($departure_time)) {
        $error_message = "Varış zamanı, kalkış zamanından sonra olmalıdır.";
    } else {
        // Seferi güncelle
        $stmt_update = $pdo->prepare(
            "UPDATE Trips SET
                departure_city = ?,
                destination_city = ?,
                departure_time = ?,
                arrival_time = ?,
                price = ?,
                capacity = ?
             WHERE id = ? AND company_id = ?"
        );
        try {
            $formatted_departure_time = date('Y-m-d H:i:s', strtotime($departure_time));
            $formatted_arrival_time = date('Y-m-d H:i:s', strtotime($arrival_time));

            $stmt_update->execute([
                $departure_city,
                $destination_city,
                $formatted_departure_time,
                $formatted_arrival_time,
                $price,
                $capacity,
                $trip_id_to_edit,
                $company_id
            ]);
            // Başarılı olunca firma paneline yönlendir
            header("Location: index.php?status=trip_updated");
            exit();
        } catch (PDOException $e) {
            $error_message = "Sefer güncellenirken bir veritabanı hatası oluştu: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Düzenle - Firma Paneli</title>
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
                <h1>Sefer Düzenle</h1>
                <h2><?php echo htmlspecialchars($trip['departure_city'] . ' -> ' . $trip['destination_city']); ?></h2>
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
                    <input type="text" id="departure_city" name="departure_city" value="<?php echo htmlspecialchars($trip['departure_city']); ?>" required>

                    <label for="destination_city">Varış Şehri</label>
                    <input type="text" id="destination_city" name="destination_city" value="<?php echo htmlspecialchars($trip['destination_city']); ?>" required>
                </div>

                <div class="grid">
                    <label for="departure_time">Kalkış Zamanı</label>
                    <input type="datetime-local" id="departure_time" name="departure_time" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($trip['departure_time']))); ?>" required>

                    <label for="arrival_time">Varış Zamanı</label>
                    <input type="datetime-local" id="arrival_time" name="arrival_time" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($trip['arrival_time']))); ?>" required>
                </div>

                 <div class="grid">
                    <label for="price">Fiyat (TL)</label>
                    <input type="number" id="price" name="price" step="1" min="0" value="<?php echo htmlspecialchars($trip['price']); ?>" required>

                    <label for="capacity">Koltuk Kapasitesi</label>
                    <input type="number" id="capacity" name="capacity" min="1" value="<?php echo htmlspecialchars($trip['capacity']); ?>" required>
                 </div>

                <button type="submit">Değişiklikleri Kaydet</button>
            </form>
        </article>
    </main>
</body>
</html>