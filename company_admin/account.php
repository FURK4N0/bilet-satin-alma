<?php
require_once '../config/init.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company_admin') {
    header("Location: /index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Firma Admin'in kendi bilgilerini çek
$stmt_user = $pdo->prepare("SELECT full_name, email FROM User WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// $user bulunamazsa diye kontrol ekleyelim (güvenlik için)
if (!$user) {
    session_destroy(); // Oturumu sonlandır
    die("HATA: Kullanıcı bilgileri alınamadı. Lütfen tekrar giriş yapın.");
}


$error_message = '';
$success_message = '';

// Şifre değiştirme formu gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Önce mevcut şifreyi doğrula
    $stmt_check_pw = $pdo->prepare("SELECT password FROM User WHERE id = ?");
    $stmt_check_pw->execute([$user_id]);
    $hashed_password = $stmt_check_pw->fetchColumn();

    if (!$hashed_password || !password_verify($current_password, $hashed_password)) {
        $error_message = "Mevcut şifreniz hatalı.";
    } elseif (empty($new_password) || $new_password !== $confirm_password) {
        $error_message = "Yeni şifreler eşleşmiyor veya boş bırakıldı.";
    } else {
        // Yeni şifreyi hash'le ve güncelle
        $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt_update_pw = $pdo->prepare("UPDATE User SET password = ? WHERE id = ?");
        try {
            $stmt_update_pw->execute([$new_hashed_password, $user_id]);
            $success_message = "Şifreniz başarıyla güncellendi.";
        } catch (PDOException $e) {
            $error_message = "Şifre güncellenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabım - Firma Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul>
                <li><strong><a href="/company_admin/">Firma Paneli</a></strong></li>
            </ul>
            <ul>
                <li><a href="/company_admin/account.php">Hesabım</a></li>
                <li><a href="/logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <h1>Hesabım</h1>
        <div class="grid">
            <article>
                <h3>Profil Bilgileri</h3>
                <strong>Ad Soyad:</strong> <?php echo htmlspecialchars($user['full_name']); ?><br>
                <strong>E-posta:</strong> <?php echo htmlspecialchars($user['email']); ?>
            </article>

            <article>
                <h3>Şifre Değiştir</h3>
                <?php if (!empty($error_message)): ?>
                    <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                     <p style="color: green;"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <form method="POST">
                    <label for="current_password">Mevcut Şifre</label>
                    <input type="password" id="current_password" name="current_password" required>

                    <label for="new_password">Yeni Şifre</label>
                    <input type="password" id="new_password" name="new_password" required>

                    <label for="confirm_password">Yeni Şifre (Tekrar)</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>

                    <button type="submit">Şifreyi Güncelle</button>
                </form>
            </article>
        </div>
    </main>
</body>
</html>