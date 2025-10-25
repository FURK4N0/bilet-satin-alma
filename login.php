<?php
require_once 'config/init.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: /admin/index.php");
        exit();
    } elseif ($_SESSION['user_role'] === 'company_admin') {
        header("Location: /company_admin/index.php");
        exit();
    }
    
    header("Location: /index.php");
    exit();
}

$error_message = '';
$success_message = '';

if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $success_message = "Kaydınız başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz.";
}
if (isset($_GET['redirect_message'])) {
    $error_message = htmlspecialchars($_GET['redirect_message'], ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "E-posta ve parola alanları zorunludur.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM User WHERE email = ? COLLATE NOCASE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Giriş başarılı: oturum kimliğini yenileyin
            session_regenerate_id(true);

            // Session bilgilerini kaydet.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            // Rol Tabanlı Yönlendirme
            if ($user['role'] === 'admin') {
                header("Location: /admin/index.php");
                exit();
            } elseif ($user['role'] === 'company_admin') {
                header("Location: /company_admin/index.php");
                exit();
            } else {
                header("Location: /index.php");
                exit();
            }
        } else {
            $error_message = "E-posta veya parola hatalı.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Bilet Platformu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <article>
            <hgroup>
                <h1>Giriş Yap</h1>
                <h2>Devam etmek için hesabınıza giriş yapın.</h2>
            </hgroup>
            
            <?php if (!empty($success_message)): ?>
                <p style="color: #43a047;"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <p style="color: #c62828;"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="email" name="email" placeholder="E-posta Adresi" required>
                <input type="password" name="password" placeholder="Parola" required>
                <button type="submit">Giriş Yap</button>
            </form>
            <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
        </article>
    </main>
</body>
</html>