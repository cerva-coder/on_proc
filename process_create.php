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

// busca role e departamento do usuário
$stmt = $pdo->prepare('SELECT r.name, u.department_id FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$role = $user['name'];
$myDept = $user['department_id'];

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

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject      = trim($_POST['subject'] ?? '');
    $passivePole  = trim($_POST['passive_pole'] ?? '');
    $inCharge     = trim($_POST['in_charge'] ?? '');
    $deptId       = ($role === 'admin') ? intval($_POST['department_id']) : $myDept;
    $processTypeId = $_POST['process_type'] ?? '';
    $outrosTexto  = trim($_POST['outros_texto'] ?? '');

    if ($processTypeId === 'outros') {
        $processTypeName = $outrosTexto;
    } else {
        $stmt = $pdo->prepare('SELECT name FROM process_types WHERE id = ?');
        $stmt->execute([$processTypeId]);
        $processTypeName = $stmt->fetchColumn();
    }

    if ($subject === '' || $passivePole === '' || $inCharge === '' || $processTypeName === '') {
        $erro = 'Preencha todos os campos.';
    } else {
        $year = date('Y');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM processes WHERE YEAR(created_at)=? AND department_id=?');
        $stmt->execute([$year, $deptId]);
        $count = $stmt->fetchColumn();
        $order = $count + 1;

        $stmt = $pdo->prepare('SELECT code FROM departments WHERE id = ?');
        $stmt->execute([$deptId]);
        $code = $stmt->fetchColumn();

        $nup = sprintf('%07d-%03s.%d', $order, $code, $year);

        $stmt = $pdo->prepare('
            INSERT INTO processes (nup, process_type, subject, passive_pole, in_charge, department_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$nup, $processTypeName, $subject, $passivePole, $inCharge, $deptId, $_SESSION['user_id']]);
        header('Location: painel.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Criar Processo — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
    .badge-olive { background-color: #556B2F; }
    .select2-container .select2-selection--single {
      height: 38px;
      padding: 5px 10px;
    }
  </style>
</head>
<body class="bg-light">

<div class="container py-5">
  <h2 class="mb-4">
    <i class="bi bi-file-earmark-plus olive"></i> Criar Processo
  </h2>

  <?php if ($erro): ?>
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($erro) ?>
    </div>
  <?php endif; ?>

  <form method="post" class="card p-4 shadow-sm">
    <?php if ($role === 'admin'): ?>
      <div class="mb-3">
        <label for="department_id" class="form-label">Departamento</label>
        <select id="department_id" name="department_id" class="form-select" required>
          <option value="">-- Selecione --</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['code'] . ' – ' . $d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="mb-3">
      <label for="subject" class="form-label">Assunto</label>
      <input type="text" id="subject" name="subject" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="passive_pole" class="form-label">Polo Passivo</label>
      <input type="text" id="passive_pole" name="passive_pole" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="in_charge" class="form-label">Encarregado</label>
      <input type="text" id="in_charge" name="in_charge" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="process_type" class="form-label">Tipo de Processo</label>
      <select name="process_type" id="process_type" class="form-select select2" required>
        <option value="">-- Selecione --</option>
        <?php foreach ($groupedTypes as $category => $items): ?>
          <?php if ($category): ?><optgroup label="<?= htmlspecialchars($category) ?>"><?php endif; ?>
          <?php foreach ($items as $item): ?>
            <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
          <?php endforeach; ?>
          <?php if ($category): ?></optgroup><?php endif; ?>
        <?php endforeach; ?>
        <option value="outros">Outros</option>
      </select>
    </div>

    <div class="mb-3" id="outros-container" style="display: none;">
      <label for="outros_texto" class="form-label">Especifique:</label>
      <input type="text" id="outros_texto" name="outros_texto" class="form-control">
    </div>

    <button type="submit" class="btn btn-olive">
      <i class="bi bi-save"></i> Criar Processo
    </button>
    <a href="painel.php" class="btn btn-outline-secondary ms-2">
      <i class="bi bi-arrow-left"></i> Voltar ao Painel
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
        $('#outros_texto').prop('required', false);
      }
    });
  });
</script>
</body>
</html>
