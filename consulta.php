<?php include 'header.php'; ?>

<?php
// consulta.php — busca e listagem de processos com filtros avançados
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
// busca role e department
$stmt = $pdo->prepare('
    SELECT r.name, u.department_id
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$userInfo = $stmt->fetch();
$role = $userInfo['name'];
$dept = $userInfo['department_id'];

// captura filtros
$filterNup       = trim($_GET['nup'] ?? '');
$filterPassive   = trim($_GET['passive_pole'] ?? '');
$filterInCharge  = trim($_GET['in_charge'] ?? '');

// monta SQL dinâmico
$params     = [];
$conditions = [];
$sql = 'SELECT p.* FROM processes p';

if (in_array($role, ['protocolador','visualizador'], true)) {
    $sql .= ' JOIN process_assignments pa ON p.id = pa.process_id';
    $conditions[] = 'pa.user_id = ?';
    $params[]     = $_SESSION['user_id'];
} elseif ($role === 'gerente') {
    $conditions[] = 'p.department_id = ?';
    $params[]     = $dept;
}

// filtros adicionais
if ($filterNup !== '') {
    $conditions[] = 'p.nup LIKE ?';
    $params[]     = "%{$filterNup}%";
}
if ($filterPassive !== '') {
    $conditions[] = 'p.passive_pole LIKE ?';
    $params[]     = "%{$filterPassive}%";
}
if ($filterInCharge !== '') {
    $conditions[] = 'p.in_charge LIKE ?';
    $params[]     = "%{$filterInCharge}%";
}

$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$sql .= $where . ' ORDER BY p.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$processes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Consulta de Processos — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
    .badge-olive { background-color: #556B2F; }
  </style>
</head>
<body class="bg-light">

<div class="container py-5">


  <h2 class="mb-4">
    <i class="bi bi-search olive"></i> Consultar Processos
  </h2>

  <form method="get" class="card p-4 shadow-sm mb-4">
    <div class="row g-3">
      <div class="col-md-4">
        <label for="nup" class="form-label">NUP</label>
        <input type="text" id="nup" name="nup" maxlength="16" class="form-control" placeholder="0000001-001.2025" value="<?= htmlspecialchars($filterNup) ?>">
      </div>
      <div class="col-md-4">
        <label for="passive_pole" class="form-label">Polo Passivo</label>
        <input type="text" id="passive_pole" name="passive_pole" class="form-control" value="<?= htmlspecialchars($filterPassive) ?>">
      </div>
      <div class="col-md-4">
        <label for="in_charge" class="form-label">Encarregado</label>
        <input type="text" id="in_charge" name="in_charge" class="form-control" value="<?= htmlspecialchars($filterInCharge) ?>">
      </div>
    </div>

    <div class="mt-3">
      <button type="submit" class="btn btn-olive">
        <i class="bi bi-search"></i> Buscar
      </button>
      <a href="painel.php" class="btn btn-outline-secondary ms-2">
        <i class="bi bi-arrow-left"></i> Voltar ao Painel
      </a>
    </div>
  </form>

  <?php if (empty($processes)): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-circle"></i> Nenhum processo encontrado.
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="bg-olive">
          <tr>
            <th>NUP</th>
            <th>Assunto</th>
            <th>Polo Passivo</th>
            <th>Encarregado</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($processes as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['nup']) ?></td>
              <td><?= htmlspecialchars($p['subject']) ?></td>
              <td><?= htmlspecialchars($p['passive_pole']) ?></td>
              <td><?= htmlspecialchars($p['in_charge']) ?></td>
              <td>
                <?php if (in_array($role, ['admin','gerente','protocolador'], true)): ?>
                  <a href="protocol.php?process_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-upload"></i> Protocolar
                  </a>
                <?php endif; ?>
                <a href="view_process.php?process_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-file-earmark-text"></i> Anexos
                </a>
                <a href="andamento.php?process_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info">
                  <i class="bi bi-clock-history"></i> Andamento
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // máscara de NUP: 0000000-000.0000
  document.getElementById('nup').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    let p1 = v.substring(0, 7);
    let p2 = v.substring(7, 10);
    let p3 = v.substring(10, 14);
    let out = p1;
    if (p2) out += '-' + p2;
    if (p3) out += '.' + p3;
    e.target.value = out;
  });
</script>
</body>
</html>
