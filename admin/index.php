<?php
require_once '../config/init.php';

// --- GÜVENLİK KONTROLÜ ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// Firma listesini çek
$stmt_companies = $pdo->query("SELECT * FROM Bus_Company ORDER BY name");
$companies = $stmt_companies->fetchAll(PDO::FETCH_ASSOC);

// Firma Admin kullanıcılarını çek
$stmt_company_admins = $pdo->query(
    "SELECT U.id, U.full_name, U.email, BC.name as company_name
     FROM User U
     LEFT JOIN Bus_Company BC ON U.company_id = BC.id
     WHERE U.role = 'company_admin'
     ORDER BY U.full_name"
);
$company_admins = $stmt_company_admins->fetchAll(PDO::FETCH_ASSOC);

// Genel (tüm firmalarda geçerli) kuponları listele
$stmt_global_coupons = $pdo->query("SELECT * FROM Coupons WHERE company_id IS NULL ORDER BY expire_date DESC");
$global_coupons = $stmt_global_coupons->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul>
                <li><strong>Admin Paneli</strong></li>
            </ul>
            <ul>
                <li><a href="/logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Firma Yönetimi</h2>
            <a href="add_company.php" role="button">Yeni Firma Ekle</a>
        </div>

        <div class="overflow-auto">
            <table>
                <thead>
                    <tr>
                        <th>Firma Adı</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($companies)): ?>
                        <tr>
                            <td colspan="3">Sistemde kayıtlı otobüs firması bulunmamaktadır.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($companies as $company): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($company['name']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($company['created_at'])); ?></td>
                                <td>
                                    <a href="edit_company.php?id=<?php echo $company['id']; ?>">Düzenle</a> |
                                    <a href="delete_company.php?id=<?php echo $company['id']; ?>" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <hr>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Firma Admin Yönetimi</h2>
            <a href="add_company_admin.php" role="button">Yeni Firma Admin Ekle</a>
        </div>

        <div class="overflow-auto">
            <table>
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Atanan Firma</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($company_admins)): ?>
                        <tr>
                            <td colspan="4">Sistemde kayıtlı Firma Admin kullanıcısı bulunmamaktadır.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($company_admins as $ca): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ca['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($ca['email']); ?></td>
                                <td><?php echo htmlspecialchars($ca['company_name'] ?? 'Atanmamış'); ?></td>
                                <td>
                                    <a href="edit_company_admin.php?id=<?php echo $ca['id']; ?>">Düzenle</a> |
                                    <a href="delete_company_admin.php?id=<?php echo $ca['id']; ?>" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <hr> <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Genel Kupon Yönetimi (Tüm Firmalar)</h2>
            <a href="add_global_coupon.php" role="button">Yeni Genel Kupon Ekle</a>
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
                    <?php if (empty($global_coupons)): ?>
                        <tr>
                            <td colspan="5">Sistemde genel kupon bulunmamaktadır.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($global_coupons as $coupon): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($coupon['code']); ?></td>
                                <td><?php echo htmlspecialchars($coupon['discount'] * 100); ?>%</td> <td><?php echo htmlspecialchars($coupon['usage_limit']); ?></td>
                                <td><?php echo date('d M Y', strtotime($coupon['expire_date'])); ?></td>
                                <td>
                                    <a href="edit_global_coupon.php?id=<?php echo $coupon['id']; ?>">Düzenle</a> |
                                    <a href="delete_global_coupon.php?id=<?php echo $coupon['id']; ?>" onclick="return confirm('Bu genel kuponu silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </main>
</body>
</html>