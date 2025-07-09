<?php
// config.php — ajuste estes valores conforme seu ambiente

// Define corretamente o fuso horário do PHP
date_default_timezone_set('America/Sao_Paulo');
ini_set('date.timezone', 'America/Sao_Paulo');

// (As demais constantes e conexões permanecem inalteradas)
define('DB_HOST', 'localhost');
define('DB_NAME', 'proc');
define('DB_USER', 'root');
define('DB_PASS', '');
define('ENCRYPTION_KEY', '42bdfe66ee79f1ae947bdd078afcce77e0abbbf09aaedc16203c96ffe157a82d');
