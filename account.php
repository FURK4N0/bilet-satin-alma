<?php
require_once 'config/init.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Kullanıcının güncel bilgilerini (full_name, email, balance) User tablosundan çek
$stmt_user = $pdo->prepare("SELECT full_name, email, balance FROM User WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// 2. Kullanıcının tüm biletlerini, sefer ve koltuk bilgileriyle birlikte çek
$stmt_tickets = $pdo->prepare(
    "SELECT 
        T.id as ticket_id, 
        T.status, 
        T.total_price,
        TR.departure_city, 
        TR.destination_city, 
        TR.departure_time,
        BS.seat_number
     FROM Tickets T
     JOIN Trips TR ON T.trip_id = TR.id
     JOIN Booked_Seats BS ON BS.ticket_id = T.id
     WHERE T.user_id = ?
     ORDER BY TR.departure_time DESC"
);
$stmt_tickets->execute([$user_id]);
$tickets = $stmt_tickets->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabım - Bilet Platformu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul><li><strong><a href="/">Bilet Platformu</a></strong></li></ul>
            <ul>
                <li>
                    <details role="list" dir="rtl">
                        <summary aria-haspopup="listbox" role="link"><?php echo htmlspecialchars($user['full_name']); ?></summary>
                        <ul role="listbox">
                            <li><a href="/account.php">Hesabım</a></li>
                            <li><a href="/logout.php">Çıkış Yap</a></li>
                        </ul>
                    </details>
                </li>
            </ul>
        </nav>

        <h1>Hesabım</h1>
        <article>
            <strong>Ad Soyad:</strong> <?php echo htmlspecialchars($user['full_name']); ?><br>
            <strong>E-posta:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
            <strong>Bakiye:</strong> <?php echo htmlspecialchars(number_format($user['balance'])); ?> TL
        </article>

        <h2>Biletlerim</h2>
        <?php if (empty($tickets)): ?>
            <p>Henüz satın alınmış biletiniz bulunmamaktadır.</p>
        <?php else: ?>
            <div class="overflow-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Güzergah</th>
                            <th>Koltuk No</th>
                            <th>Tarih</th>
                            <th>Ödenen Tutar</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['departure_city'] . ' -> ' . $ticket['destination_city']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['seat_number']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($ticket['departure_time'])); ?></td>
                                <td><?php echo htmlspecialchars($ticket['total_price']); ?> TL</td>
                                <td>
                                    <?php if ($ticket['status'] === 'ACTIVE'): ?>
                                        <span style="color: green;">Aktif</span>
                                    <?php else: ?>
                                        <span style="color: red;">İptal Edildi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="download_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" target="_blank">PDF İndir</a>
                                    <?php
                                    $departure_timestamp = strtotime($ticket['departure_time']);
                                    $current_timestamp = time();
                                    $can_cancel = ($departure_timestamp - $current_timestamp) > 3600;

                                    if ($ticket['status'] === 'ACTIVE' && $can_cancel): ?>
                                        | <a href="cancel_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz?');">İptal Et</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>