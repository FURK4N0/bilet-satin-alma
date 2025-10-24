<?php
require_once '../config/init.php';

// Güvenlik Kontrolü: Sadece adminler erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
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

// Firma listesini çek (select box için)
$stmt_companies = $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name");
$companies = $stmt_companies->fetchAll(PDO::FETCH_ASSOC);

$error_message = '';
$success_message = '';

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $company_id = $_POST['company_id']; // Boş olabilir

    // Boş alan kontrolü düzeltildi (company_id boş olabilir)
    if (empty($full_name) || empty($email) || empty($password)) {
        $error_message = "Ad Soyad, E-posta ve Parola alanları zorunludur.";
    } else {
        // Bu e-posta zaten var mı?
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM User WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetchColumn() > 0) {
            $error_message = "Bu e-posta adresi zaten kullanılıyor.";
        } else {
            // Parolayı hashle
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Yeni Firma Admin kullanıcısını ekle
            $stmt_insert = $pdo->prepare(
                "INSERT INTO User (id, full_name, email, password, role, company_id)
                 VALUES (?, ?, ?, ?, 'company_admin', ?)"
            );
            try {
                // company_id boşsa NULL gönder
                $company_id_to_insert = !empty($company_id) ? $company_id : null;
                $stmt_insert->execute([generate_uuid(), $full_name, $email, $hashed_password, $company_id_to_insert]);
                $success_message = "Firma Admin kullanıcısı başarıyla eklendi.";
                // Formu temizlemek için POST sonrası GET yönlendirmesi yapılabilir
                // header("Location: ".$_SERVER['PHP_SELF']."?status=success"); exit();
            } catch (PDOException $e) {
                $error_message = "Kullanıcı eklenirken bir veritabanı hatası oluştu: " . $e->getMessage();
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
    <title>Yeni Firma Admin Ekle - Admin Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul>
                <li><strong><a href="/admin/">Admin Paneli</a></strong></li>
            </ul>
            <ul>
                <li><a href="/logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <article>
            <hgroup>
                <h1>Yeni Firma Admin Ekle</h1>
                <h2>Sisteme yeni bir firma yöneticisi ekleyin ve bir firmaya atayın.</h2>
            </hgroup>

            <?php if (!empty($error_message)): ?>
                 <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                 <p style="color: green;"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form method="POST">
                <label for="full_name">Ad Soyad</label>
                <input type="text" id="full_name" name="full_name" required>

                <label for="email">E-posta Adresi</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Parola</label>
                <input type="password" id="password" name="password" required>

                <label for="company_id">Atanacak Firma (İsteğe Bağlı)</label>
                <select id="company_id" name="company_id"> <option value="">-- Firma Seçin (Boş bırakılabilir) --</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo htmlspecialchars($company['id']); ?>"> <?php echo htmlspecialchars($company['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Firma Adminini Ekle</button>
            </form>
        </article>
    </main>
</body>
</html>