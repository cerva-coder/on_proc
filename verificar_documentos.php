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

// Verifica se o usuário tem permissão
$stmt = $pdo->prepare('
    SELECT u.id, u.department_id, r.name as role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE u.id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !in_array($user['role_name'], ['admin', 'gerente'])) {
    exit('Acesso negado.');
}

$isAdmin = $user['role_name'] === 'admin';
$departmentId = $user['department_id'];

// Marcar documentos como checados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checked_ids'])) {
    $ids = array_map('intval', $_POST['checked_ids']);
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE documents SET checked = NOW() WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    }
}

// Paginação
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Consulta de documentos pendentes de verificação
$query = "
    SELECT d.id, d.file_path, d.uploaded_at, d.uploaded_by, d.process_id, d.title,
           p.nup, p.passive_pole, p.in_charge,
           u.full_name, u.rank
    FROM documents d
    JOIN processes p ON d.process_id = p.id
    JOIN users u ON d.uploaded_by = u.id
    WHERE d.checked IS NULL
";
$params = [];
if (!$isAdmin) {
    $query .= " AND p.department_id = ?";
    $params[] = $departmentId;
}
$query .= " ORDER BY d.uploaded_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$docs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Portal de Verificação de Protocolos — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
    .table-responsive { overflow-x: auto; }
  </style>
</head>
<body class="bg-light">

<div class="container-fluid py-5 px-4">
  <div class="card shadow-sm p-4">
    <h3 class="mb-3"><i class="bi bi-check2-square olive"></i> Fluxo de Trabalho - Documentos protocolados </h3>
    <p class="text-muted mb-4">
  Esta página exibe todos os documentos protocolados no seu departamento e serve como ferramenta de controle para acompanhamento das providências adotadas. Utilize a marcação "Checado" para indicar que a ação correspondente foi verificada. Documentos não marcados permanecem como pendentes, permitindo planejamento e acompanhamento eficiente. A listagem é paginada, exibindo 20 protocolos por página.
</p>


    <form method="POST">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center w-100">
          <thead class="bg-olive">
            <tr>
              <th>Checar</th>
              <th>Movimentação</th>
              <th>NUP</th>
              <th>Polo Passivo</th>
              <th>Encarregado</th>
              <th>Quem Protocolou</th>
              <th>Abrir PDF</th>
              <th>Ver Processo</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($docs): ?>
              <?php foreach ($docs as $doc): ?>
                <tr>
                  <td><input type="checkbox" name="checked_ids[]" value="<?= $doc['id'] ?>"></td>
                  <td><?= htmlspecialchars($doc['title'] ?: '—') ?></td>
                  <td><?= htmlspecialchars($doc['nup']) ?></td>
                  <td><?= htmlspecialchars($doc['passive_pole']) ?></td>
                  <td><?= htmlspecialchars($doc['in_charge']) ?></td>
                  <td><?= htmlspecialchars($doc['rank']) ?> - <?= htmlspecialchars($doc['full_name']) ?></td>
                  <td>
                    <a href="view_document.php?process_id=<?= $doc['process_id'] ?>&file=<?= urlencode(basename($doc['file_path'])) ?>&action=view" target="_blank" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-file-earmark-pdf"></i> Abrir
                    </a>
                  </td>
                  <td>
                    <a href="andamento.php?process_id=<?= $doc['process_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-box-arrow-up-right"></i> Processo
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center">Nenhum documento pendente de verificação.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <button type="submit" class="btn btn-olive mt-3">
        <i class="bi bi-check2-circle"></i> Marcar como checados
      </button>
    </form>

    <div class="mt-4 d-flex justify-content-between">
      <a class="btn btn-outline-secondary" href="?page=<?= max(1, $page - 1) ?>">
        <i class="bi bi-chevron-left"></i> Anterior
      </a>
      <span class="align-self-center">Página <?= $page ?></span>
      <a class="btn btn-outline-secondary" href="?page=<?= $page + 1 ?>">
        Próxima <i class="bi bi-chevron-right"></i>
      </a>
    </div>

    <a href="admin.php" class="btn btn-outline-dark mt-4">
      <i class="bi bi-arrow-left-circle"></i> Voltar ao Painel Administrativo
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
