<?php include 'header.php'; ?>
<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();

// Verifica role
$stmt = $pdo->prepare('SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if (!in_array($role, ['admin', 'gerente'])) {
    exit('Acesso negado.');
}

// Define qual documento incluir 
$docPage = basename($_GET['page'] ?? 'overview'); // segurança contra path traversal
$docFile = __DIR__ . "/doc/{$docPage}.php";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Documentação — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
    .sidebar-nav a { display: block; padding: 8px 12px; color: #333; text-decoration: none; }
    .sidebar-nav a:hover { background: #f1f1f1; }
    .active-link { background: #e2e6da; font-weight: bold; }
  </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
  <div class="row">
    <!-- Menu lateral -->
    <div class="col-md-3">
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-olive">
          <h5 class="text-white mb-0"><i class="bi bi-journal-code"></i> Documentação</h5>
        </div>
        <div class="card-body sidebar-nav">
          <a href="doc.php?page=login" class="<?= $docPage === 'login' ? 'active-link' : '' ?>"><i class="bi bi-box-arrow-in-right"></i> Página de Login</a>
          <a href="doc.php?page=painel" class="<?= $docPage === 'painel' ? 'active-link' : '' ?>"><i class="bi bi-kanban"></i> Painel Principal</a>
          <a href="doc.php?page=process_create" class="<?= $docPage === 'process_create' ? 'active-link' : '' ?>"><i class="bi bi-file-earmark-plus"></i> Criar Processo</a>
          <a href="doc.php?page=admin" class="<?= $docPage === 'admin' ? 'active-link' : '' ?>"><i class="bi bi-gear"></i> Administração</a>
          <a href="doc.php?page=andamento" class="<?= $docPage === 'andamento' ? 'active-link' : '' ?>"><i class="bi bi-clock-history"></i> Andamento</a>
          <a href="doc.php?page=view_process" class="<?= $docPage === 'view_process' ? 'active-link' : '' ?>"><i class="bi bi-file-earmark-text"></i> Anexos</a>
        </div>
      </div>
      <a href="painel.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-left"></i> Voltar ao Sistema</a>
    </div>

    <!-- Conteúdo da documentação -->
    <div class="col-md-9">
      <div class="card shadow-sm">
        <div class="card-body">
          <?php
            if (file_exists($docFile)) {
              include $docFile;
            } else {
              echo "<div class='alert alert-warning'>Documento não encontrado.</div>";
            }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Rodapé -->
<div class="text-center mt-4 text-muted" style="font-size: 0.9rem;">
  Sistema desenvolvido por herocode.tech
</div>

</body>
</html>
