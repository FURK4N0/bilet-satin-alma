<?php
require_once 'config/init.php';

// Güvenlik: Kullanıcı giriş yapmamışsa login sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect_message=Bilet almak için lütfen giriş yapın.");
    exit();
}

// URL'den gelen trip_id'yi alıyoruz.
$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) {
    die("Geçersiz sefer ID'si.");
}

// 1. Seferin bilgilerini yeni sütun adlarıyla çek.
$stmt = $pdo->prepare(
    "SELECT T.*, C.name as company_name 
     FROM Trips T 
     JOIN Bus_Company C ON T.company_id = C.id 
     WHERE T.id = ?"
);
$stmt->execute([$trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Sefer bulunamadı.");
}

// 2. Bu sefere ait satılmış koltuk numaralarını YENİ YAPIYA GÖRE çek.
// Booked_Seats ve Tickets tablolarını birleştiriyoruz.
$stmt_tickets = $pdo->prepare(
    "SELECT BS.seat_number 
     FROM Booked_Seats BS
     JOIN Tickets T ON BS.ticket_id = T.id
     WHERE T.trip_id = ? AND T.status = 'ACTIVE'"
);
$stmt_tickets->execute([$trip_id]);
$sold_seats = $stmt_tickets->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Al - Bilet Platformu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .seat-map { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; max-width: 300px; margin-top: 1rem;}
        .seat { text-align: center; }
        .seat-label { display: block; padding: 10px; border: 1px solid #ccc; border-radius: 5px; cursor: pointer; }
        .seat input[type="radio"] { display: none; }
        .seat input[type="radio"]:checked + .seat-label { background-color: #43a047; color: white; border-color: #43a047; }
        .seat input[type="radio"]:disabled + .seat-label { background-color: #e0e0e0; color: #9e9e9e; cursor: not-allowed; border-color: #e0e0e0;}
        .corridor { grid-column: 3; }
    </style>
</head>
<body>
    <main class="container">
        <nav>
            <ul><li><strong><a href="/">Bilet Platformu</a></strong></li></ul>
            <ul>
                <li>
                    <details role="list" dir="rtl">
                        <summary aria-haspopup="listbox" role="link"><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></summary>
                        <ul role="listbox">
                            <li><a href="/account.php">Hesabım</a></li>
                            <li><a href="/logout.php">Çıkış Yap</a></li>
                        </ul>
                    </details>
                </li>
            </ul>
        </nav>

        <hgroup>
            <h1>Bilet Satın Al</h1>
            <h2><?php echo htmlspecialchars($trip['departure_city']); ?> - <?php echo htmlspecialchars($trip['destination_city']); ?></h2>
            <p><?php echo date('d M Y H:i', strtotime($trip['departure_time'])); ?> | <?php echo htmlspecialchars($trip['company_name']); ?></p>
        </hgroup>

        <article>
            <h3>Koltuk Seçimi</h3>
            <p>Lütfen yolculuk yapmak istediğiniz koltuğu seçin.</p>
            <form action="process_purchase.php" method="POST">
                <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($trip['id']); ?>">
                
                <div class="seat-map">
                    <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
                        <?php $is_sold = in_array($i, $sold_seats); ?>
                        <div class="seat">
                            <input type="radio" id="seat-<?php echo $i; ?>" name="seat_number" value="<?php echo $i; ?>" <?php if ($is_sold) echo 'disabled'; ?> required>
                            <label class="seat-label" for="seat-<?php echo $i; ?>"><?php echo $i; ?></label>
                        </div>
                        <?php if ($i % 2 == 0 && $i % 4 != 0): ?>
                            <div class="corridor"></div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <hr>
                
                <label for="coupon_code">İndirim Kuponu (varsa)</label>
                <input type="text" name="coupon_code" id="coupon_code" placeholder="Kupon Kodunuz">
                
                <footer>
                    <strong>Toplam Tutar: <?php echo htmlspecialchars($trip['price']); ?> TL</strong>
                    <button type="submit">Satın Al</button>
                </footer>
            </form>
        </article>
    </main>
</body>
</html>