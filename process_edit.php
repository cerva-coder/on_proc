<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'], $_GET['process_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$pid = intval($_GET['process_id']);

// busca role e departamento do usuário
$stmt = $pdo->prepare('SELECT r.name, u.department_id FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$role = $user['name'];
$myDept = $user['department_id'];

// busca dados do processo
$stmt = $pdo->prepare('SELECT * FROM processes WHERE id = ?');
$stmt->execute([$pid]);
$process = $stmt->fetch();
if (!$process) exit('Processo não encontrado.');

// valida acesso se gerente
if ($role === 'gerente' && $process['department_id'] != $myDept) {
    exit('Sem permissão.');
}

// departamentos disponíveis se admin
$departments = [];
if ($role === 'admin') {
    $departments = $pdo->query('SELECT id, code, name FROM departments')->fetchAll();
}

// busca tipos de processo
$stmt = $pdo->query('SELECT id, name, category FROM process_types ORDER BY category, name');
$types = $stmt->fetchAll();
$groupedTypes = [];
foreach ($types as $t) {
    $groupedTypes[$t['category']][] = $t;
}
$isOutro = true;
foreach ($types as $t) {
    if ($t['name'] === $process['process_type']) {
        $isOutro = false;
        break;
    }
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject      = trim($_POST['subject'] ?? '');
    $passivePole  = trim($_POST['passive_pole'] ?? '');
    $inCharge     = trim($_POST['in_charge'] ?? '');
    $status       = $_POST['status'] ?? 'Em andamento';
    $deptId       = ($role === 'admin') ? intval($_POST['department_id']) : $myDept;
    $processType  = $_POST['process_type'] ?? '';
    $outrosTexto  = trim($_POST['outros_texto'] ?? '');

    if ($processType === 'outros') {
        $processType = $outrosTexto;
    } else {
        $stmt = $pdo->prepare('SELECT name FROM process_types WHERE id = ?');
        $stmt->execute([$processType]);
        $processType = $stmt->fetchColumn();
    }

    if ($subject === '' || $passivePole === '' || $inCharge === '' || $processType === '') {
        $erro = 'Preencha todos os campos.';
    } else {
        $stmt = $pdo->prepare('
            UPDATE processes
            SET subject = ?, passive_pole = ?, in_charge = ?, department_id = ?, process_type = ?, status = ?
            WHERE id = ?
        ');
        $stmt->execute([$subject, $passivePole, $inCharge, $deptId, $processType, $status, $pid]);
        header('Location: painel.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Editar Processo — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .olive { color: #556B2F; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; }
  </style>
</head>
<body class="bg-light">
<div class="container py-5">
  <h2 class="mb-4"><i class="bi bi-pencil-square olive"></i> Editar Processo</h2>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="post" class="card p-4 shadow-sm">
    <?php if ($role === 'admin'): ?>
      <div class="mb-3">
        <label class="form-label">Departamento</label>
        <select name="department_id" class="form-select" required>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $d['id'] == $process['department_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['code'] . ' – ' . $d['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="mb-3">
      <label class="form-label">Assunto</label>
      <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($process['subject']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Polo Passivo</label>
      <input type="text" name="passive_pole" class="form-control" value="<?= htmlspecialchars($process['passive_pole']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Encarregado</label>
      <input type="text" name="in_charge" class="form-control" value="<?= htmlspecialchars($process['in_charge']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Tipo de Processo</label>
      <select name="process_type" id="process_type" class="form-select select2" required>
        <option value="">-- Selecione --</option>
        <?php foreach ($groupedTypes as $category => $items): ?>
          <?php if ($category): ?><optgroup label="<?= htmlspecialchars($category) ?>"><?php endif; ?>
          <?php foreach ($items as $item): ?>
            <option value="<?= $item['id'] ?>" <?= $item['name'] === $process['process_type'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($item['name']) ?>
            </option>
          <?php endforeach; ?>
          <?php if ($category): ?></optgroup><?php endif; ?>
        <?php endforeach; ?>
        <option value="outros" <?= $isOutro ? 'selected' : '' ?>>Outros</option>
      </select>
    </div>

    <div class="mb-3" id="outros-container" style="<?= $isOutro ? '' : 'display:none;' ?>">
      <label class="form-label">Especifique:</label>
      <input type="text" name="outros_texto" id="outros_texto" class="form-control"
             value="<?= $isOutro ? htmlspecialchars($process['process_type']) : '' ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Status do Processo</label>
      <select name="status" class="form-select" required>
        <option value="Em andamento" <?= $process['status'] === 'Em andamento' ? 'selected' : '' ?>>Em andamento</option>
        <option value="Finalizado" <?= $process['status'] === 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
        <option value="Arquivado" <?= $process['status'] === 'Arquivado' ? 'selected' : '' ?>>Arquivado</option>
      </select>
    </div>

    <button type="submit" class="btn btn-olive">
      <i class="bi bi-check-circle"></i> Salvar Alterações
    </button>
    <a href="painel.php" class="btn btn-outline-secondary ms-2">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(document).ready(function() {
    $('.select2').select2();
    $('#process_type').on('change', function() {
      if ($(this).val() === 'outros') {
        $('#outros-container').show();
        $('#outros_texto').prop('required', true);
      } else {
        $('#outros-container').hide();
        $('#outros_texto').prop('required', false).val('');
      }
    });
  });
</script>
</body>
</html>
