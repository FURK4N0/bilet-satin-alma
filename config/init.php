<?php
// Set secure session cookie parameters before starting the session.
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => '', // default to the current host
	'secure' => $secure,
	'httponly' => true,
	'samesite' => 'Lax',
]);

// Enforce strict session mode
ini_set('session.use_strict_mode', '1');

session_start();

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/database.php';
?>