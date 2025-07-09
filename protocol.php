<?php include 'header.php'; ?>
<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'], $_GET['process_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$pdo->exec("SET time_zone = '-03:00'");
$pid = intval($_GET['process_id']);

// busca processo, valida permissão e status
$stmt = $pdo->prepare(
    'SELECT p.nup, p.department_id, p.status, r.name
     FROM processes p
     JOIN users u ON u.id = ?
     JOIN roles r ON u.role_id = r.id
     WHERE p.id = ?'
);
$stmt->execute([$_SESSION['user_id'], $pid]);
$info = $stmt->fetch();

if (!$info) exit('Sem permissão.');

$nup = $info['nup'];
$dept = $info['department_id'];
$role = $info['name'];
$status = $info['status'];
if ($role === 'visualizador') {
    exit('Sem permissão.');
}

// valida permissão
$can = false;
if ($role === 'admin' || ($role === 'gerente' && $_SESSION['department_id'] === $dept)) {
    $can = true;
} else {
    $stmt = $pdo->prepare('SELECT 1 FROM process_assignments WHERE process_id=? AND user_id=?');
    $stmt->execute([$pid, $_SESSION['user_id']]);
    if ($stmt->fetch()) $can = true;
}

if (!$can) exit('Sem permissão.');

// bloqueia se status não for "Em Andamento"
if (strtolower($status) !== 'em andamento') {
    exit('O processo precisa estar com status "Em Andamento" para receber protocolos.');
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');

    if (!$password || !password_verify($password, $hash)) {
        $erro = 'Senha incorreta.';
    } elseif (empty($title)) {
        $erro = 'Preencha o título da movimentação.';
    } elseif (empty($desc)) {
        $erro = 'Preencha a descrição do documento.';
    } elseif (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Erro ao receber o arquivo.';
    } else {
        $f = $_FILES['pdf'];

        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($f['tmp_name']);
        $sizeMB = $f['size'] / (1024 * 1024);

        if ($ext !== 'pdf' || $mime !== 'application/pdf') {
            $erro = 'O arquivo deve ser um PDF válido.';
        } elseif ($sizeMB > 50) {
            $erro = 'O arquivo excede o limite de 50MB.';
        } elseif (preg_match('/[^\w\-.]/', $f['name'])) {
            $erro = 'Nome de arquivo inválido.';
        } else {
            $dir = __DIR__ . "/uploads/{$pid}";
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $uniqName = uniqid('doc_', true) . '.pdf';
            $path = "$dir/$uniqName";

            // criptografia
            $original_data = file_get_contents($f['tmp_name']);
            $key = ENCRYPTION_KEY;
            $iv = openssl_random_pseudo_bytes(16);
            $encrypted_data = openssl_encrypt($original_data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            $final_data = $iv . $encrypted_data;

            if (file_put_contents($path, $final_data)) {
                $stmt = $pdo->prepare('SELECT COALESCE(MAX(id_doc_process), 0) + 1 FROM documents WHERE process_id = ?');
                $stmt->execute([$pid]);
                $nextId = $stmt->fetchColumn();

                $stmt = $pdo->prepare(
                    'INSERT INTO documents (process_id, title, file_name, description, file_path, uploaded_by, id_doc_process)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $pid,
                    $title,
                    $f['name'],
                    $desc,
                    "uploads/{$pid}/{$uniqName}",
                    $_SESSION['user_id'],
                    $nextId
                ]);
                header("Location: andamento.php?process_id={$pid}");
                exit;
            } else {
                $erro = 'Falha ao salvar o arquivo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Protocolar Documento — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
    .badge-olive { background-color: #556B2F; }
  </style>
  <script>
    function confirmarEnvio() {
      return confirm('ATENÇÃO: O protocolo é IRREVERSÍVEL e só pode ser excluído pelo ADMINISTRADOR GERAL. Deseja continuar?');
    }
  </script>
</head>
<body class="bg-light">

<div class="container py-5">
  <h2 class="mb-4">
    <i class="bi bi-upload olive"></i> Protocolar Documento
    <small class="text-muted">(NUP: <?= htmlspecialchars($nup) ?>)</small>
  </h2>

  <?php if ($erro): ?>
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($erro) ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm" onsubmit="return confirmarEnvio();">
    <div class="mb-3">
      <label for="title" class="form-label">Título da Movimentação:</label>
      <input type="text" id="title" name="title" class="form-control" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label for="description" class="form-label">Descrição do Documento:</label>
      <textarea id="description" name="description" class="form-control" rows="3" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
      <label for="pdf" class="form-label">Selecione o PDF (máx. 50MB):</label>
      <input type="file" id="pdf" name="pdf" class="form-control" accept="application/pdf" required>
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">Confirme sua senha:</label>
      <input type="password" id="password" name="password" class="form-control" required minlength="8">
    </div>

    <button type="submit" class="btn btn-olive">
      <i class="bi bi-upload"></i> Enviar
    </button>
    <a href="painel.php" class="btn btn-outline-secondary ms-2">
      <i class="bi bi-arrow-left"></i> Voltar ao Painel
    </a>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
