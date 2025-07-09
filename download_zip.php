<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php'; // ENCRYPTION_KEY

if (!isset($_SESSION['user_id'], $_GET['process_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$pid = intval($_GET['process_id']);

// Valida se o processo pertence ao usuário ou é admin
$stmt = $pdo->prepare('
    SELECT p.nup, p.passive_pole, p.department_id, r.name as role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    JOIN processes p ON p.id = ?
    WHERE u.id = ?
');
$stmt->execute([$pid, $_SESSION['user_id']]);
$info = $stmt->fetch();

if (!$info) {
    exit('Sem permissão.');
}

$role = $info['role'];
if ($role !== 'admin' && $_SESSION['department_id'] != $info['department_id']) {
    exit('Acesso negado.');
}

function sanitizeFileName($string) {
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = preg_replace('/[^A-Za-z0-9_\-]/', '_', $string);
    $string = preg_replace('/_+/', '_', $string);
    return trim($string, '_');
}

// Gera nome do arquivo ZIP
$nup = preg_replace('/[^0-9]/', '', $info['nup']);
$polo = sanitizeFileName($info['passive_pole']);
$polo = substr($polo, 0, 40);
$zipFileName = "processo_{$nup}_{$polo}.zip";
$tmpZipPath = sys_get_temp_dir() . '/' . uniqid('zip_') . '.zip';

// Garante que o arquivo temporário será excluído mesmo se houver falha
register_shutdown_function(function() use ($tmpZipPath) {
    if (file_exists($tmpZipPath)) {
        unlink($tmpZipPath);
    }
});

// Busca documentos
$stmt = $pdo->prepare('
    SELECT id_doc_process, file_name, file_path, title
    FROM documents
    WHERE process_id = ?
    ORDER BY id_doc_process DESC
');
$stmt->execute([$pid]);
$docs = $stmt->fetchAll();

if (empty($docs)) {
    exit('Nenhum arquivo para baixar.');
}

$zip = new ZipArchive();
if ($zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    exit('Erro ao criar o arquivo ZIP.');
}

foreach ($docs as $d) {
    $filepath = realpath(__DIR__ . '/' . $d['file_path']);
    if ($filepath && file_exists($filepath) && str_starts_with($filepath, __DIR__)) {
        $final_data = file_get_contents($filepath);
        $key = ENCRYPTION_KEY;
        $iv = substr($final_data, 0, 16);
        $encrypted_data = substr($final_data, 16);
        $decrypted_data = openssl_decrypt($encrypted_data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted_data !== false) {
            $title = sanitizeFileName($d['title'] ?: 'documento');
            $fileInZip = "{$d['id_doc_process']}_{$title}.pdf";
            $zip->addFromString($fileInZip, $decrypted_data);
        }
    }
}
$zip->close();

// Força download do ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
header('Content-Length: ' . filesize($tmpZipPath));
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($tmpZipPath);
exit;
