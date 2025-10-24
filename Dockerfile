# PHP'nin Apache web sunucusu ile birlikte gelen resmi imajını kullanıyoruz.
FROM php:8.2-apache

# Gerekli sistem kütüphanelerini ve PHP eklentilerini kuruyoruz.
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libicu-dev \
    && docker-php-ext-install pdo pdo_sqlite intl

# Apache'nin mod_rewrite modülünü aktif ediyoruz.
RUN a2enmod rewrite

# Proje dosyalarımızı container'ın içine kopyalıyoruz.
COPY . /var/www/html/

# EN ÖNEMLİ KISIM: Tüm web dosyalarının sahibini web sunucusu olarak ayarla.
# Bu, dosya izinleri ve 'disk I/O' sorunlarını kalıcı olarak çözer.
RUN chown -R www-data:www-data /var/www/html