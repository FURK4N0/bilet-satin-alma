<?php
require_once 'config/init.php';

// Benzersiz ID (UUID) oluşturmak için bir fonksiyon.
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$error_message = '';

// Form gönderimi (POST) ile çağrıldıysa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // YENİ SÜTUN ADI: full_name
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($full_name) || empty($email) || empty($password)) {
        $error_message = "Tüm alanların doldurulması zorunludur.";
    } elseif ($password !== $password_confirm) {
        $error_message = "Parolalar eşleşmiyor.";
    } else {
        // YENİ TABLO ADI: User
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM User WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error_message = "Bu e-posta adresi zaten kullanımda.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // YENİ SQL SORGUSU: Yeni tablo ve sütun adlarına göre güncellendi.
            // Bakiye artık veritabanı tarafından varsayılan olarak 800 atanıyor.
            $stmt = $pdo->prepare(
                "INSERT INTO User (id, full_name, email, password) VALUES (?, ?, ?, ?)"
            );
            
            try {
                $stmt->execute([generate_uuid(), $full_name, $email, $hashed_password]);
                
                header("Location: login.php?status=success");
                exit();
            } catch (PDOException $e) {
                $error_message = "Kayıt sırasında bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Bilet Platformu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <article>
            <hgroup>
                <h1>Kayıt Ol</h1>
                <h2>Platforma katılmak için bilgilerinizi girin.</h2>
            </hgroup>
            
            <?php if (!empty($error_message)): ?>
                <p style="color: #c62828;"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <input type="text" name="full_name" placeholder="Ad Soyad" required>
                <input type="email" name="email" placeholder="E-posta Adresi" required>
                <input type="password" name="password" placeholder="Parola" required>
                <input type="password" name="password_confirm" placeholder="Parola Tekrar" required>
                <button type="submit">Kayıt Ol</button>
            </form>
            <p>Zaten bir hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
        </article>
    </main>
</body>
</html>