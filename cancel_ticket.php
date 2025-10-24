<?php
require_once 'config/init.php';

// Kullanıcı giriş yapmamışsa işlemi sonlandır
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$ticket_id = $_GET['ticket_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$ticket_id) {
    die("Geçersiz bilet ID'si.");
}

try {
    // Veritabanı transaction başlat
    $pdo->beginTransaction();

    // 1. Bileti ve sefer bilgilerini çek. Bileti sadece kendi sahibi iptal edebilir.
    $stmt = $pdo->prepare(
        "SELECT 
            T.id as ticket_id, T.status, T.total_price,
            TR.departure_time
         FROM Tickets T
         JOIN Trips TR ON T.trip_id = TR.id
         WHERE T.id = ? AND T.user_id = ?"
    );
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        throw new Exception("Bilet bulunamadı veya bu bileti iptal etme yetkiniz yok.");
    }
    if ($ticket['status'] !== 'ACTIVE') {
        throw new Exception("Bu bilet zaten iptal edilmiş veya aktif değil.");
    }

    // 2. Zaman kontrolü: Sefere 1 saatten az mı kaldı?
    $departure_timestamp = strtotime($ticket['departure_time']);
    if (($departure_timestamp - time()) <= 3600) { // 3600 saniye = 1 saat
        throw new Exception("Seferin kalkışına 1 saatten az kaldığı için bilet iptal edilemez.");
    }

    // 3. Biletin durumunu 'CANCELLED' olarak güncelle
    $stmt_cancel = $pdo->prepare("UPDATE Tickets SET status = 'CANCELLED' WHERE id = ?");
    $stmt_cancel->execute([$ticket_id]);

    // 4. Bilet ücretini (total_price) kullanıcının hesabına iade et
    $stmt_refund = $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt_refund->execute([$ticket['total_price'], $user_id]);

    // İşlemi onayla
    $pdo->commit();

    header("Location: account.php?status=cancel_success");
    exit();

} catch (Exception $e) {
    // Hata olursa işlemi geri al
    $pdo->rollBack();
    die("Bilet iptali sırasında bir hata oluştu: " . $e->getMessage());
}
?>