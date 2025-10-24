<?php
require_once 'config/init.php';

// Güvenlik: Kullanıcı giriş yapmamışsa işlemi direkt sonlandır.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

// Form POST metodu ile mi gönderilmiş kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_id = $_POST['trip_id'] ?? null;
    $seat_number = $_POST['seat_number'] ?? null;
    $coupon_code = trim($_POST['coupon_code'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if (!$trip_id || !$seat_number) {
        die("Eksik bilgi. Lütfen tekrar deneyin.");
    }

    try {
        // --- VERİTABANI İŞLEMİ BAŞLAT ---
        $pdo->beginTransaction();

        // 1. Gerekli bilgileri topla (Kullanıcı bakiyesi, sefer fiyatı)
        $stmt_user = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user_balance = $stmt_user->fetchColumn();

        $stmt_trip = $pdo->prepare("SELECT price, company_id FROM Trips WHERE id = ?");
        $stmt_trip->execute([$trip_id]);
        $trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);
        $trip_price = $trip['price'];
        $final_price = $trip_price;

        // 2. Koltuğun hala boş olup olmadığını kontrol et
        $stmt_seat = $pdo->prepare(
            "SELECT COUNT(*) FROM Booked_Seats BS
             JOIN Tickets T ON BS.ticket_id = T.id
             WHERE T.trip_id = ? AND BS.seat_number = ? AND T.status = 'ACTIVE'"
        );
        $stmt_seat->execute([$trip_id, $seat_number]);
        if ($stmt_seat->fetchColumn() > 0) {
            throw new Exception("Üzgünüz, seçtiğiniz koltuk siz işlemi tamamlarken başkası tarafından satın alındı.");
        }
        
        // 3. Kupon Kodu Kontrolü 
        if (!empty($coupon_code)) {
            $stmt_coupon = $pdo->prepare("SELECT * FROM Coupons WHERE code = ? AND expire_date > date('now') AND usage_limit > 0");
            $stmt_coupon->execute([$coupon_code]);
            $coupon = $stmt_coupon->fetch(PDO::FETCH_ASSOC);

            if ($coupon) {
                // Kuponun firmasını kontrol et (eğer firmaya özelse)
                if ($coupon['company_id'] === null || $coupon['company_id'] === $trip['company_id']) {
                    $final_price = $trip_price - ($trip_price * $coupon['discount']);
                    // Kupon kullanım limitini düşür
                    $stmt_update_coupon = $pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ?");
                    $stmt_update_coupon->execute([$coupon['id']]);
                    // Kuponun kim tarafından kullanıldığını kaydet
                    $stmt_log_coupon = $pdo->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id) VALUES (?, ?, ?)");
                    $stmt_log_coupon->execute([generate_uuid(), $coupon['id'], $user_id]);
                }
            }
        }


        // 4. Kullanıcının bakiyesi yeterli mi?
        if ($user_balance < $final_price) {
            throw new Exception("Yetersiz bakiye.");
        }

        // 5. Bakiyeyi Düş
        $new_balance = $user_balance - $final_price;
        $stmt_update_balance = $pdo->prepare("UPDATE User SET balance = ? WHERE id = ?");
        $stmt_update_balance->execute([$new_balance, $user_id]);

        // 6. Önce Bilet (Tickets) kaydını oluştur
        $ticket_id = generate_uuid();
        $stmt_insert_ticket = $pdo->prepare(
            "INSERT INTO Tickets (id, user_id, trip_id, total_price) VALUES (?, ?, ?, ?)"
        );
        $stmt_insert_ticket->execute([$ticket_id, $user_id, $trip_id, $final_price]);

        // 7. Sonra Koltuk (Booked_Seats) kaydını oluştur ve bilete bağla
        $stmt_insert_seat = $pdo->prepare(
            "INSERT INTO Booked_Seats (id, ticket_id, seat_number) VALUES (?, ?, ?)"
        );
        $stmt_insert_seat->execute([generate_uuid(), $ticket_id, $seat_number]);

        // Her şey yolunda gittiyse, işlemi onayla
        $pdo->commit();

        header("Location: account.php?purchase_status=success");
        exit();

    } catch (Exception $e) {
        // Herhangi bir adımda hata oluşursa, tüm değişiklikleri geri al
        $pdo->rollBack();
        die("Bilet alımı sırasında bir hata oluştu: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit();
}
?>