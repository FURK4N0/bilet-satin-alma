<?php
require_once '../config/init.php';

// Güvenlik Kontrolü: Sadece adminler
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// URL'den gelen düzenlenecek kullanıcının ID'sini al
$user_id_to_edit = $_GET['id'] ?? null;
if (!$user_id_to_edit) {
    die("Düzenlenecek kullanıcı ID'si belirtilmemiş.");
}

// Kullanıcının mevcut bilgilerini çek (rolünün company_admin olduğundan emin ol)
$stmt_user = $pdo->prepare("SELECT id, full_name, email, company_id FROM User WHERE id = ? AND role = 'company_admin'");
$stmt_user->execute([$user_id_to_edit]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Firma Admin kullanıcısı bulunamadı.");
}

// Firma listesini çek (select box için)
$stmt_companies = $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name");
$companies = $stmt_companies->fetchAll(PDO::FETCH_ASSOC);

$error_message = '';
$success_message = '';

// Form gönderildiyse (güncelleme işlemi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $company_id = $_POST['company_id'] ?? null; // Firma atanmamış olabilir
    $password = $_POST['password']; // Yeni parola (opsiyonel)

    if (empty($full_name) || empty($email)) {
        $error_message = "Ad Soyad ve E-posta boş bırakılamaz.";
    } else {
        // Bu e-posta başka bir kullanıcıya mı ait? (kendisi hariç)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM User WHERE email = ? AND id != ?");
        $stmt_check->execute([$email, $user_id_to_edit]);
        if ($stmt_check->fetchColumn() > 0) {
            $error_message = "Bu e-posta adresi zaten başka bir kullanıcı tarafından kullanılıyor.";
        } else {
            // Parola güncellenecek mi?
            $password_update_sql = '';
            $params = [$full_name, $email, ($company_id ?: null)]; // Boşsa NULL gönder
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $password_update_sql = ", password = ?";
                $params[] = $hashed_password;
            }
            $params[] = $user_id_to_edit; // WHERE için ID

            // Kullanıcıyı güncelle
            $sql = "UPDATE User SET full_name = ?, email = ?, company_id = ? {$password_update_sql} WHERE id = ?";
            $stmt_update = $pdo->prepare($sql);

            try {
                $stmt_update->execute($params);
                // Başarılı olunca admin paneline yönlendir
                header("Location: index.php?status=ca_updated");
                exit();
            } catch (PDOException $e) {
                $error_message = "Kullanıcı güncellenirken bir veritabanı hatası oluştu: " . $e->getMessage();
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
    <title>Firma Admin Düzenle - Admin Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul><li><strong><a href="/admin/">Admin Paneli</a></strong></li></ul>
            <ul><li><a href="/logout.php">Çıkış Yap</a></li></ul>
        </nav>

        <article>
            <hgroup>
                <h1>Firma Admin Düzenle</h1>
                <h2>Kullanıcı bilgilerini ve atanan firmayı güncelleyin.</h2>
            </hgroup>

            <?php if (!empty($error_message)): ?>
                 <p><mark><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></mark></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                 <p style="color: green;"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form method="POST">
                <label for="full_name">Ad Soyad</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>

                <label for="email">E-posta Adresi</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

                <label for="password">Yeni Parola (Değiştirmek istemiyorsanız boş bırakın)</label>
                <input type="password" id="password" name="password" placeholder="Yeni parola">

                <label for="company_id">Atanacak Firma</label>
                <select id="company_id" name="company_id">
                    <option value="">-- Firma Yok (Atanmamış) --</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo htmlspecialchars($company['id']); ?>" <?php echo ($user['company_id'] === $company['id']) ? 'selected' : ''; ?>> <?php echo htmlspecialchars($company['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Değişiklikleri Kaydet</button>
            </form>
        </article>
    </main>
</body>
</html>