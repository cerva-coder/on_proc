<?php include 'header.php'; ?>
<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Define qual documento incluir 
$faqPage = basename($_GET['page'] ?? 'overview'); // segurança contra path traversal
$faqFile = __DIR__ . "/faq/{$faqPage}.php";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>FAQ — ON_Proc</title>
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
          <h5 class="text-white mb-0"><i class="bi bi-question-circle"></i> FAQ</h5>
        </div>
        <div class="card-body sidebar-nav">
          <a href="faq.php?page=process_create" class="<?= $faqPage === 'process_create' ? 'active-link' : '' ?>"><i class="bi bi-file-earmark-plus"></i> Criar Processos</a>
          <a href="faq.php?page=protocol" class="<?= $faqPage === 'protocol' ? 'active-link' : '' ?>"><i class="bi bi-mailbox"></i> Protocolar</a>
          <a href="faq.php?page=andamento" class="<?= $faqPage === 'andamento' ? 'active-link' : '' ?>"><i class="bi bi-clock-history"></i> Andamento</a>
        </div>
      </div>
      <a href="painel.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-left"></i> Voltar ao Sistema</a>
    </div>

    <!-- Conteúdo do FAQ -->
    <div class="col-md-9">
      <div class="card shadow-sm">
        <div class="card-body">
          <?php
            if (file_exists($faqFile)) {
              include $faqFile;
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
