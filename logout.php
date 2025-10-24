<?php
require_once 'config/init.php';

// Tüm oturum değişkenlerini temizle
$_SESSION = array();

// Oturumu sonlandır
session_destroy();

// Kullanıcıyı ana sayfaya yönlendir
header("Location: index.php");
exit;
?>