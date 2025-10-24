<?php
require_once '../config/init.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company_admin') {
    header("Location: /index.php");
    exit();
}

// Firma admininin şirket ID'sini al
$stmt_user = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$company_id = $stmt_user->fetchColumn();

if (!$company_id) {
    die("HATA: Bu kullanıcı bir şirkete atanmamış.");
}

// URL'den gelen silinecek kuponun ID'sini al
$coupon_id_to_delete = $_GET['id'] ?? null;
if (!$coupon_id_to_delete) {
    die("Silinecek kupon ID'si belirtilmemiş.");
}

try {
    // Kuponu silme işlemini gerçekleştir
    // Sadece bu firmaya ait kuponların silinebildiğinden emin oluyoruz
    $stmt_delete = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
    $stmt_delete->execute([$coupon_id_to_delete, $company_id]);

    // Silme işlemi başarılı olduysa (etkilenen satır sayısı > 0), ana panele yönlendir
    if ($stmt_delete->rowCount() > 0) {
        header("Location: index.php?status=coupon_deleted");
        exit();
    } else {
        // Eğer kupon bulunamadıysa veya bu firmaya ait değilse
        throw new Exception("Kupon bulunamadı veya bu kuponu silme yetkiniz yok.");
    }

} catch (PDOException $e) {
    // Veritabanı hatası durumunda (örneğin kupon kullanıldıysa User_Coupons'da kayıt olabilir - CASCADE kuralı bunu çözer)
    die("Kupon silinirken bir veritabanı hatası oluştu: " . $e->getMessage());
} catch (Exception $e) {
    // Diğer hatalar (yetki vb.)
    die("HATA: " . $e->getMessage());
}
?>