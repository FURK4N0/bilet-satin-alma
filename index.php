<?php require_once 'config/init.php'; ?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Alma Platformu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>
    <main class="container">
        <nav>
            <ul>
                <li><strong><a href="/">Bilet Platformu</a></strong></li>
            </ul>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>
                        <details role="list" dir="rtl">
                            <summary aria-haspopup="listbox" role="link"><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></summary>
                            <ul role="listbox">
                                <li><a href="/account.php">Hesabım</a></li>
                                <li><a href="/logout.php">Çıkış Yap</a></li>
                            </ul>
                        </details>
                    </li>
                <?php else: ?>
                    <li><a href="/login.php">Giriş Yap</a></li>
                    <li><a href="/register.php" role="button">Kayıt Ol</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <hgroup>
            <h1>Sefer Arama</h1>
            <h2>İstediğiniz güzergahtaki seferleri kolayca bulun.</h2>
        </hgroup>

        <form action="/search.php" method="GET">
            <div class="grid">
                <label for="departure">
                    Nereden
                    <input type="text" id="departure" name="departure" placeholder="Örn: İstanbul" required list="city-list">
                </label>
                <label for="arrival">
                    Nereye
                    <input type="text" id="arrival" name="arrival" placeholder="Örn: Ankara" required list="city-list">
                </label>
            </div>
            <button type="submit">Sefer Bul</button>
        </form>
        
        <datalist id="city-list"></datalist>
        
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/api/get_cities.php')
                .then(response => response.json())
                .then(cities => {
                    const dataList = document.getElementById('city-list');
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city;
                        dataList.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Şehir listesi yüklenirken bir hata oluştu:', error);
                });
        });
    </script>
</body>
</html>