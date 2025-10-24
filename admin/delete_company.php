<?php
require_once '../config/init.php';

// Güvenlik Kontrolü: Sadece adminler erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// URL'den gelen silinecek firmanın ID'sini al
$company_id_to_delete = $_GET['id'] ?? null;
if (!$company_id_to_delete) {
    die("Silinecek firma ID'si belirtilmemiş.");
}

try {
    // Firmayı silme işlemini gerçekleştir
    $stmt_delete = $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
    $stmt_delete->execute([$company_id_to_delete]);

    // Silme işlemi başarılı olduysa (etkilenen satır sayısı > 0), ana panele yönlendir
    if ($stmt_delete->rowCount() > 0) {
        // İlişkili veriler (seferler, kuponlar) CASCADE kuralları sayesinde otomatik silindi/güncellendi.
        header("Location: index.php?status=company_deleted");
        exit();
    } else {
        // Eğer firma bulunamadıysa
        throw new Exception("Firma bulunamadı.");
    }

} catch (PDOException $e) {
    // Beklenmedik bir veritabanı hatası olursa
    die("Firma silinirken bir veritabanı hatası oluştu: " . $e->getMessage());
} catch (Exception $e) {
    // Diğer hatalar
    die("HATA: " . $e->getMessage());
}
?>