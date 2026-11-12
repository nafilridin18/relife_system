<?php
require 'config.php';

$lang = $_GET['lang'] ?? 'bn';
$lang = in_array($lang, ['bn', 'en'], true) ? $lang : 'bn';
$_SESSION['lang'] = $lang;
setcookie('reliefx_lang', $lang, time() + 86400 * 30, '/');

$back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $back);
exit;
