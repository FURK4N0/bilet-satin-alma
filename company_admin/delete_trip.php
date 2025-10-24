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

// URL'den gelen silinecek seferin ID'sini al
$trip_id_to_delete = $_GET['id'] ?? null;
if (!$trip_id_to_delete) {
    die("Silinecek sefer ID'si belirtilmemiş.");
}

try {
    // Seferi silme işlemini gerçekleştir
    // Sadece bu firmaya ait seferlerin silinebildiğinden emin oluyoruz (WHERE company_id = ?)
    $stmt_delete = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt_delete->execute([$trip_id_to_delete, $company_id]);

    // Silme işlemi başarılı olduysa (etkilenen satır sayısı > 0), ana panele yönlendir
    if ($stmt_delete->rowCount() > 0) {
        header("Location: index.php?status=trip_deleted");
        exit();
    } else {
        // Eğer sefer bulunamadıysa veya bu firmaya ait değilse
        throw new Exception("Sefer bulunamadı veya bu seferi silme yetkiniz yok.");
    }

} catch (PDOException $e) {
    // Veritabanı hatası durumunda (örneğin bileti olan sefer silinemezse - FOREIGN KEY kuralı)
     if ($e->getCode() == '23000') { // SQLite constraint violation kodu
        die("HATA: Bu sefere ait satılmış biletler olduğu için sefer silinemez. Önce ilgili biletleri iptal etmelisiniz.");
     } else {
        die("Sefer silinirken bir veritabanı hatası oluştu: " . $e->getMessage());
     }
} catch (Exception $e) {
    // Diğer hatalar (yetki vb.)
    die("HATA: " . $e->getMessage());
}
?>