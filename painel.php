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

// busca role e dados do usuário
$stmt = $pdo->prepare('
    SELECT u.full_name, u.rank, u.department_id, u.username, r.name AS role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$fullName      = $user['full_name'];
$rank          = $user['rank'];
$myDept        = $user['department_id'];
$username      = $user['username'];
$role          = $user['role'];
$roleDisplay   = match($role) {
    'admin'        => 'Administrador do Sistema',
    'gerente'      => 'Administrador OM',
    'protocolador' => 'Agente Protocolador',
    'visualizador' => 'Apenas Consulta',
    default        => 'Usuário',
};

// nome do departamento
$stmt = $pdo->prepare('SELECT name FROM departments WHERE id = ?');
$stmt->execute([$myDept]);
$departmentName = $stmt->fetchColumn();

// exclusão de processo
if ($role === 'admin' && isset($_GET['delete']) && isset($_GET['process_id'])) {
    $deleteId = intval($_GET['process_id']);
    $pdo->prepare('DELETE FROM processes WHERE id = ?')->execute([$deleteId]);
    header('Location: painel.php');
    exit;
}

// filtros
$filterNup      = trim($_GET['nup'] ?? '');
$filterPassive  = trim($_GET['passive_pole'] ?? '');
$filterInCharge = trim($_GET['in_charge'] ?? '');

// paginação
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 30;
$offset = ($page - 1) * $limit;

// montagem SQL
$params     = [];
$conditions = [];
$sql  = 'SELECT SQL_CALC_FOUND_ROWS p.* FROM processes p';

if (in_array($role, ['protocolador','visualizador'], true)) {
    $sql .= ' JOIN process_assignments pa ON p.id = pa.process_id';
    $conditions[] = 'pa.user_id = ?';
    $params[]     = $_SESSION['user_id'];
} elseif ($role === 'gerente') {
    $conditions[] = 'p.department_id = ?';
    $params[]     = $myDept;
}

if ($filterNup      !== '') { $conditions[] = 'p.nup LIKE ?';          $params[] = "%{$filterNup}%"; }
if ($filterPassive  !== '') { $conditions[] = 'p.passive_pole LIKE ?';  $params[] = "%{$filterPassive}%"; }
if ($filterInCharge !== '') { $conditions[] = 'p.in_charge LIKE ?';    $params[] = "%{$filterInCharge}%"; }

$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$sql  .= $where . ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
$params[] = $limit;
$params[] = $offset;

$stmt      = $pdo->prepare($sql);
$stmt->execute($params);
$processes = $stmt->fetchAll();

$totalRows  = $pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Painel — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; }
    .badge-olive   { background-color: #556B2F; }
    .bg-finalizado { background-color: steelblue !important; color: #fff; }
    .bg-andamento  { background-color: #556B2F !important; color: #fff; }
    #filtro-box { display: none; }
    /* Container flutuante estilo “bolha” */
#floating-actions {
  position: fixed;
  top: 120px;        /* distância do topo da janela */
  right: 20px;       /* distância da lateral direita */
  background: #ffffff;
  padding: 0.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  z-index: 1050;
}
#floating-actions .btn {
  display: block;
  width: 100%;
  margin-bottom: 0.5rem;
}
/* Em telas pequenas, deixar inline no fluxo */
@media (max-width: 767.98px) {
  #floating-actions {
    position: static;
    box-shadow: none;
    margin-bottom: 1rem;
  }
}

  </style>
</head>
<body class="bg-light">

<div class="container-fluid py-5">

  <!-- cabeçalho -->
  <h2 class="mb-2">
    <i class="bi bi-person-circle olive"></i>
    Bem-vindo, <?= htmlspecialchars($fullName) ?>
    <span class="badge badge-olive"><?= htmlspecialchars($roleDisplay) ?></span>
  </h2>
  <p class="text-muted ms-4">
    Usuário: <strong><?= htmlspecialchars($username) ?></strong><br>
    OM: <strong><?= htmlspecialchars($departmentName) ?></strong><br>
    Função: <strong><?= htmlspecialchars($rank) ?></strong>
  </p>

  <!-- menu -->
  <nav class="mb-4">
    <?php if (in_array($role,['admin','gerente'])): ?>
      <a href="admin.php" class="btn btn-outline-secondary me-2"><i class="bi bi-gear"></i> Administração</a>
      <a href="process_create.php" class="btn btn-outline-success me-2"><i class="bi bi-file-earmark-plus"></i> Criar Processo</a>
    <?php endif; ?>
    <button class="btn btn-outline-info me-2" onclick="document.getElementById('filtro-box').style.display='block'">
      <i class="bi bi-search"></i> Consultar Processo
    </button>
    <a href="faq.php" class="btn btn-outline-warning me-2"><i class="bi bi-question-circle"></i> FAQ</a>
    <?php if (in_array($role,['admin','gerente'])): ?>
      <a href="doc.php" class="btn btn-outline-primary me-2"><i class="bi bi-journal-bookmark"></i> Documentação</a>
    <?php endif; ?>
    <a href="logout.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Sair</a>
  </nav>

  <!-- filtros -->
  <form id="filtro-box" method="get" class="card p-3 shadow-sm mb-4">
    <div class="row g-3">
      <div class="col-md-4">
        <label for="nup" class="form-label">NUP</label>
        <input type="text" id="nup" name="nup" class="form-control" placeholder="0000001-001.2025"
               value="<?= htmlspecialchars($filterNup) ?>">
      </div>
      <div class="col-md-4">
        <label for="passive_pole" class="form-label">Polo Passivo</label>
        <input type="text" id="passive_pole" name="passive_pole" class="form-control"
               value="<?= htmlspecialchars($filterPassive) ?>">
      </div>
      <div class="col-md-4">
        <label for="in_charge" class="form-label">Encarregado</label>
        <input type="text" id="in_charge" name="in_charge" class="form-control"
               value="<?= htmlspecialchars($filterInCharge) ?>">
      </div>
    </div>
    <div class="mt-3">
      <button type="submit" class="btn btn-olive me-2"><i class="bi bi-search"></i> Buscar</button>
      <a href="painel.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Limpar</a>
    </div>
  </form>

  <!-- form que engloba botões e tabela -->
  <form id="actionForm" method="get">
    <div class="mb-4">
    <div id="floating-actions">
  <?php if (in_array($role, ['admin','gerente','protocolador'], true)): ?>
    <button type="submit" formaction="protocol.php" class="btn btn-outline-success">
      <i class="bi bi-upload"></i> Protocolar
    </button>
  <?php endif; ?>

  <button type="submit" formaction="andamento.php" class="btn btn-outline-info">
    <i class="bi bi-clock-history"></i> Andamento
  </button>

  <?php if (in_array($role,['admin','gerente'], true)): ?>
    <button type="submit" formaction="view_process.php" class="btn btn-outline-primary">
      <i class="bi bi-file-earmark-text"></i> Anexos
    </button>
    <button type="submit" formaction="process_edit.php" class="btn btn-outline-warning">
      <i class="bi bi-pencil-square"></i> Editar
    </button>
  <?php endif; ?>

  <?php if ($role === 'admin'): ?>
    <button type="submit" formaction="painel.php" name="delete" value="1" class="btn btn-outline-danger">
      <i class="bi bi-trash"></i> Excluir
    </button>
  <?php endif; ?>
</div>

    </div>

    <?php if (!$processes): ?>
      <div class="alert alert-warning">Nenhum processo encontrado.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle w-100">
          <thead class="bg-olive text-white">
            <tr>
              <th class="text-center">Selecionar</th>
              <th>NUP</th>
              <th>Tipo de Processo</th>
              <th>Assunto</th>
              <th>Polo Passivo</th>
              <th>Encarregado</th>
              <th>OM</th>
              <th>Anexos</th>
              <th>Último Andamento</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($processes as $p):
              $deptStmt = $pdo->prepare('SELECT name FROM departments WHERE id = ?');
              $deptStmt->execute([$p['department_id']]);
              $deptName = $deptStmt->fetchColumn();

              $cntStmt = $pdo->prepare('SELECT COUNT(*) FROM documents WHERE process_id = ?');
              $cntStmt->execute([$p['id']]);
              $docCount = $cntStmt->fetchColumn();

              $lastStmt = $pdo->prepare('SELECT title FROM documents WHERE process_id = ? ORDER BY uploaded_at DESC LIMIT 1');
              $lastStmt->execute([$p['id']]);
              $lastTitle = $lastStmt->fetchColumn() ?: '-';

              $badgeClass = match($p['status']) {
                'Finalizado' => 'bg-finalizado',
                'Arquivado'  => 'bg-secondary',
                default      => 'bg-andamento'
              };
            ?>
            <tr>
              <td class="text-center">
                <input type="radio" name="process_id" value="<?= $p['id'] ?>"
                       data-nup="<?= htmlspecialchars($p['nup']) ?>">
              </td>
              <td>
                <span class="badge bg-secondary"><?= htmlspecialchars($p['nup']) ?></span>
                <span class="badge <?= $badgeClass ?> ms-1"><?= htmlspecialchars($p['status']) ?></span>
              </td>
              <td><?= htmlspecialchars($p['process_type']) ?></td>
              <td><?= htmlspecialchars($p['subject']) ?></td>
              <td><?= htmlspecialchars($p['passive_pole']) ?></td>
              <td><?= htmlspecialchars($p['in_charge']) ?></td>
              <td><?= htmlspecialchars($deptName) ?></td>
              <td><span class="badge bg-info text-dark"><?= $docCount ?> Anexo(s)</span></td>
              <td><?= htmlspecialchars($lastTitle) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav>
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // alerta se nenhum processo selecionado
  document.querySelectorAll('#actionForm button[type=submit]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const sel = document.querySelector('#actionForm input[name="process_id"]:checked');
      if (!sel) {
        e.preventDefault();
        alert('Por favor, selecione um processo.');
      }
    });
  });

  // máscara de NUP
  document.getElementById('nup').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    let p1 = v.substring(0, 7), p2 = v.substring(7, 10), p3 = v.substring(10, 14);
    let out = p1;
    if (p2) out += '-' + p2;
    if (p3) out += '.' + p3;
    e.target.value = out;
  });
});
</script>
</body>
</html>
