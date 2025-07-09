<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'], $_GET['process_id'], $_GET['file'])) {
    http_response_code(401);
    exit('Acesso negado.');
}

$pdo = getPDO();
$pid = intval($_GET['process_id']);
$file = basename($_GET['file']); // evita path traversal
$action = $_GET['action'] ?? 'view'; // 'view' ou 'download'

// valida permissão
$stmt = $pdo->prepare(
    'SELECT 1 FROM processes p
     JOIN users u ON u.id = ?
     WHERE p.id = ?'
);
$stmt->execute([$_SESSION['user_id'], $pid]);
if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Sem permissão.');
}

$path = __DIR__ . "/uploads/{$pid}/{$file}";
if (!file_exists($path)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

// descriptografar
$final_data = file_get_contents($path);
$key = ENCRYPTION_KEY;
$iv = substr($final_data, 0, 16);
$encrypted_data = substr($final_data, 16);
$decrypted_data = openssl_decrypt($encrypted_data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

if ($decrypted_data === false) {
    http_response_code(500);
    exit('Erro ao descriptografar.');
}

header('Content-Type: application/pdf');

if ($action === 'download') {
    header('Content-Disposition: attachment; filename="' . $file . '"');
} else {
    header('Content-Disposition: inline; filename="' . $file . '"');
}

header('Content-Length: ' . strlen($decrypted_data));
echo $decrypted_data;
exit;
?>
