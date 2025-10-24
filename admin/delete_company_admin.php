<?php
require_once '../config/init.php';

// Güvenlik Kontrolü: Sadece adminler
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// URL'den gelen silinecek kullanıcının ID'sini al
$user_id_to_delete = $_GET['id'] ?? null;
if (!$user_id_to_delete) {
    die("Silinecek kullanıcı ID'si belirtilmemiş.");
}

// Güvenlik: Admin kendini silemesin!
if ($user_id_to_delete === $_SESSION['user_id']) {
    die("HATA: Kendi hesabınızı silemezsiniz.");
}

try {
    // Kullanıcıyı silme işlemini gerçekleştir
    // Sadece rolü 'company_admin' olanların silinebildiğinden emin oluyoruz (ekstra güvenlik)
    $stmt_delete = $pdo->prepare("DELETE FROM User WHERE id = ? AND role = 'company_admin'");
    $stmt_delete->execute([$user_id_to_delete]);

    // Silme işlemi başarılı olduysa (etkilenen satır sayısı > 0), ana panele yönlendir
    if ($stmt_delete->rowCount() > 0) {
        // İlişkili biletler (varsa) CASCADE kuralı sayesinde otomatik silindi.
        header("Location: index.php?status=ca_deleted");
        exit();
    } else {
        // Eğer kullanıcı bulunamadıysa veya rolü company_admin değilse
        throw new Exception("Firma Admin kullanıcısı bulunamadı veya silme yetkiniz yok.");
    }

} catch (PDOException $e) {
    // Beklenmedik bir veritabanı hatası olursa
    die("Kullanıcı silinirken bir veritabanı hatası oluştu: " . $e->getMessage());
} catch (Exception $e) {
    // Diğer hatalar
    die("HATA: " . $e->getMessage());
}
?>