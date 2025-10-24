<?php
require_once '../config/init.php';

// --- GÜVENLİK KONTROLÜ ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company_admin') {
    header("Location: /index.php");
    exit();
}

// Firma admininin şirket ID'sini al
$stmt_user = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$company_id = $stmt_user->fetchColumn();

if (!$company_id) {
    session_destroy();
    die("HATA: Bu kullanıcı bir şirkete atanmamış veya şirket ID bulunamadı. Lütfen tekrar giriş yapın.");
}

// Bu şirkete ait seferleri listele
$stmt_trips = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC");
$stmt_trips->execute([$company_id]);
$trips = $stmt_trips->fetchAll(PDO::FETCH_ASSOC);

// Bu şirkete ait kuponları listele
$stmt_coupons = $pdo->prepare("SELECT * FROM Coupons WHERE company_id = ? ORDER BY expire_date DESC");
$stmt_coupons->execute([$company_id]);
$coupons = $stmt_coupons->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Admin Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul>
                <li><strong>Firma Paneli</strong></li>
            </ul>
            <ul>
                <li><a href="/company_admin/account.php">Hesabım</a></li>
                <li><a href="/logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Sefer Yönetimi</h2>
            <a href="add_trip.php" role="button">Yeni Sefer Ekle</a>
        </div>

        <div class="overflow-auto">
            <table>
                <thead>
                    <tr>
                        <th>Güzergah</th>
                        <th>Kalkış Zamanı</th>
                        <th>Varış Zamanı</th>
                        <th>Fiyat</th>
                        <th>Kapasite</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($trips)): ?>
                        <tr>
                            <td colspan="6">Bu firmaya ait henüz hiç sefer oluşturulmamış.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($trips as $trip): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trip['departure_city'] . ' -> ' . $trip['destination_city']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($trip['departure_time'])); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($trip['arrival_time'])); ?></td>
                                <td><?php echo htmlspecialchars($trip['price']); ?> TL</td>
                                <td><?php echo htmlspecialchars($trip['capacity']); ?></td>
                                <td>
                                    <a href="edit_trip.php?id=<?php echo $trip['id']; ?>">Düzenle</a> |
                                    <a href="delete_trip.php?id=<?php echo $trip['id']; ?>" onclick="return confirm('Bu seferi silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <hr> <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Kupon Yönetimi</h2>
            <a href="add_coupon.php" role="button">Yeni Kupon Ekle</a>
        </div>

        <div class="overflow-auto">
            <table>
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>İndirim Oranı (%)</th>
                        <th>Kullanım Limiti</th>
                        <th>Son Kullanma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="5">Bu firmaya ait henüz kupon oluşturulmamış.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($coupon['code']); ?></td>
                                <td><?php echo htmlspecialchars($coupon['discount'] * 100); ?>%</td>
                                <td><?php echo htmlspecialchars($coupon['usage_limit']); ?></td>
                                <td><?php echo date('d M Y', strtotime($coupon['expire_date'])); ?></td>
                                <td>
                                    <a href="edit_coupon.php?id=<?php echo $coupon['id']; ?>">Düzenle</a> |
                                    <a href="delete_coupon.php?id=<?php echo $coupon['id']; ?>" onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </main> </body>
</html>