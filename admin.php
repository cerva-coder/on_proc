<?php include 'header.php'; ?>
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$_SESSION['user_id']]);
$roleName = $stmt->fetchColumn();

if (!in_array($roleName, ['admin', 'gerente'])) {
    exit('Acesso negado.');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Administração — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="card shadow-sm p-4">
      <h3 class="mb-4"><i class="bi bi-gear-fill olive"></i> Painel de Administração</h3>

      <div class="d-grid gap-3">
        <?php if ($roleName === 'admin'): ?>
          <a href="departamentos.php" class="btn btn-olive btn-lg">
            <i class="bi bi-building"></i> Gerenciar Departamentos
          </a>
          <a href="aparencia.php" class="btn btn-olive btn-lg">
            <i class="bi bi-brush"></i> Editar Aparência
          </a>
        <?php endif; ?>
        <a href="usuarios.php" class="btn btn-olive btn-lg">
          <i class="bi bi-people"></i> Gerenciar Usuários
        </a>
        <a href="atribuir_processos.php" class="btn btn-olive btn-lg">
          <i class="bi bi-diagram-3"></i> Atribuir Processos a Usuários
        </a>
        <a href="verificar_documentos.php" class="btn btn-olive btn-lg">
          <i class="bi bi-check2-square"></i>  Fluxo de Trabalho - Documentos protocolados
        </a>
          <a href="admin_process_types.php" class="btn btn-olive btn-lg">
            <i class="bi bi-file-earmark-plus"></i> Criar tipos de processos
          </a>
           </a>
          <a href="estatisticas.php" class="btn btn-olive btn-lg">
            <i class="bi bi-bar-chart-line"></i> Estatísticas
          </a>
      </div>

      <a href="painel.php" class="btn btn-outline-secondary mt-5">
        <i class="bi bi-arrow-left-circle"></i> Voltar ao Painel Principal
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
