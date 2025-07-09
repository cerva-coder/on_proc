<?php
// logout.php — encerra sessão
session_start();
date_default_timezone_set('America/Sao_Paulo');

$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;
