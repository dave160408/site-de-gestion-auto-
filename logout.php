<?php
require_once 'config/database.php';
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
header('Location: login.php');
exit;