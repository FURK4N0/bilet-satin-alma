<?php
require_once '../config/init.php';

// Güvenlik Kontrolü: Sadece adminler
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// URL'den gelen silinecek kuponun ID'sini al
$coupon_id_to_delete = $_GET['id'] ?? null;
if (!$coupon_id_to_delete) {
    die("Silinecek kupon ID'si belirtilmemiş.");
}

try {
    // Kuponu silme işlemini gerçekleştir
    // Sadece GENEL kuponların silinebildiğinden emin oluyoruz (WHERE company_id IS NULL)
    $stmt_delete = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id IS NULL");
    $stmt_delete->execute([$coupon_id_to_delete]);

    // Silme işlemi başarılı olduysa (etkilenen satır sayısı > 0), ana panele yönlendir
    if ($stmt_delete->rowCount() > 0) {
        // İlişkili User_Coupons kayıtları CASCADE kuralı sayesinde otomatik silindi.
        header("Location: index.php?status=gcoupon_deleted");
        exit();
    } else {
        // Eğer kupon bulunamadıysa veya genel kupon değilse
        throw new Exception("Genel kupon bulunamadı veya bu kuponu silme yetkiniz yok.");
    }

} catch (PDOException $e) {
    // Beklenmedik bir veritabanı hatası olursa
    die("Genel kupon silinirken bir veritabanı hatası oluştu: " . $e->getMessage());
} catch (Exception $e) {
    // Diğer hatalar
    die("HATA: " . $e->getMessage());
}
?>