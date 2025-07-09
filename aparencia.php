<?php include 'header.php'; ?>

<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Verifica se é admin
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    exit('Acesso negado.');
}

// Caminho do arquivo a ser editado
$filePath = __DIR__ . '/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newContent = $_POST['header_content'] ?? '';

    // Proteção básica contra edição vazia
    if (trim($newContent) === '') {
        $error = 'O conteúdo não pode estar vazio.';
    } else {
        // Backup antes da alteração
        $backupPath = __DIR__ . '/header_backup_' . date('Ymd_His') . '.php';
        if (copy($filePath, $backupPath)) {
            // Tenta salvar novo conteúdo
            if (file_put_contents($filePath, $newContent) !== false) {
                $success = 'Arquivo atualizado com sucesso. Backup criado.';
            } else {
                $error = 'Erro ao salvar o arquivo.';
            }
        } else {
            $error = 'Não foi possível criar o backup.';
        }
    }
}

// Carrega o conteúdo atual
$currentContent = htmlspecialchars(file_get_contents($filePath));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Editor de Header — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    textarea { font-family: monospace; min-height: 500px; }
    .btn-olive { background-color: #556B2F; color: #fff; }
    .btn-olive:hover { background-color: #445522; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3"><i class="bi bi-code-square me-1"></i> Editor do Cabeçalho (header.php)</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <textarea name="header_content" class="form-control mb-3" spellcheck="false"><?= $currentContent ?></textarea>
    <button type="submit" class="btn btn-olive"><i class="bi bi-save me-1"></i> Salvar Alterações</button>
    <a href="admin.php" class="btn btn-outline-secondary ms-2"><i class="bi bi-arrow-left"></i> Voltar</a>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
