<?php
require_once 'config/init.php';

$departure = $_GET['departure'] ?? '';
$arrival = $_GET['arrival'] ?? '';

$trips = [];
if (!empty($departure) && !empty($arrival)) {
    // Sorgunun en sade hali. Veritabanı artık büyük/küçük harf kontrolünü kendisi yapıyor.
    $stmt = $pdo->prepare(
        "SELECT T.*, C.name as company_name 
         FROM Trips T 
         JOIN Bus_Company C ON T.company_id = C.id
         WHERE T.departure_city LIKE ? AND T.destination_city LIKE ?"
    );
    
    $stmt->execute(["%$departure%", "%$arrival%"]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Sonuçları - Bilet Platformu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul>
                <li><strong><a href="/">Bilet Platformu</a></strong></li>
            </ul>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>
                        <details role="list" dir="rtl">
                            <summary aria-haspopup="listbox" role="link"><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></summary>
                            <ul role="listbox">
                                <li><a href="/account.php">Hesabım</a></li>
                                <li><a href="/logout.php">Çıkış Yap</a></li>
                            </ul>
                        </details>
                    </li>
                <?php else: ?>
                    <li><a href="/login.php">Giriş Yap</a></li>
                    <li><a href="/register.php" role="button">Kayıt Ol</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <hgroup>
            <h1>Arama Sonuçları</h1>
            <h2>'<?php echo htmlspecialchars($departure); ?>' - '<?php echo htmlspecialchars($arrival); ?>' arası seferler</h2>
        </hgroup>
        
        <?php if (empty($trips)): ?>
            <article>
                <p>Aradığınız kriterlere uygun sefer bulunamadı.</p>
                <a href="/" role="button">Yeni Arama Yap</a>
            </article>
        <?php else: ?>
            <?php foreach ($trips as $trip): ?>
                <article>
                    <div class="grid">
                        <div>
                            <strong><?php echo htmlspecialchars($trip['company_name']); ?></strong><br>
                            <small>Kalkış: <?php echo date('d M Y H:i', strtotime($trip['departure_time'])); ?></small><br>
                            <small>Varış: <?php echo date('d M Y H:i', strtotime($trip['arrival_time'])); ?></small>
                        </div>
                        <div>
                            <h3><?php echo htmlspecialchars($trip['price']); ?> TL</h3>
                            <a href="/buy_ticket.php?trip_id=<?php echo $trip['id']; ?>" role="button">Bilet Al</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>