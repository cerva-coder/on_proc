<?php include 'header.php'; ?>
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();

// Verifica a role do usuário logado
$stmt = $pdo->prepare('SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

$canEditAll = $currentUser['role_name'] === 'admin';
$isGerente   = $currentUser['role_name'] === 'gerente';

if (!$canEditAll && !$isGerente) {
    exit('Acesso negado.');
}

$errors = [];
$success = '';

// Ações de atribuição e remoção
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid = intval($_POST['process_id'] ?? 0);
    $uid = intval($_POST['user_id'] ?? 0);

    if ($action === 'assign' && $pid && $uid) {
        // Validação extra para gerente: só pode atribuir processos do seu departamento
        if ($isGerente) {
            $stmt = $pdo->prepare('SELECT department_id FROM processes WHERE id = ?');
            $stmt->execute([$pid]);
            $processDept = $stmt->fetchColumn();

            if ($processDept != $currentUser['department_id']) {
                $errors[] = 'Você só pode atribuir processos do seu departamento.';
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO process_assignments (process_id, user_id) VALUES (?, ?)');
                $stmt->execute([$pid, $uid]);
                $success = 'Atribuição realizada com sucesso.';
            } catch (PDOException $e) {
                $errors[] = 'Erro ao atribuir: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'delete' && $pid && $uid) {
        $stmt = $pdo->prepare('DELETE FROM process_assignments WHERE process_id = ? AND user_id = ?');
        $stmt->execute([$pid, $uid]);
        $success = 'Atribuição removida com sucesso.';
    }
}

// Filtros de busca
$searchNUP = trim($_GET['nup'] ?? '');
$searchUser = trim($_GET['user'] ?? '');

// Processos (filtrados, com limitação para gerente)
if ($isGerente) {
    $procStmt = $pdo->prepare('SELECT id, nup FROM processes WHERE department_id = ? AND nup LIKE ? ORDER BY created_at DESC LIMIT 20');
    $procStmt->execute([$currentUser['department_id'], "%$searchNUP%"]);
} else {
    $procStmt = $pdo->prepare('SELECT id, nup FROM processes WHERE nup LIKE ? ORDER BY created_at DESC LIMIT 20');
    $procStmt->execute(["%$searchNUP%"]);
}
$processes = $procStmt->fetchAll();

// Usuários filtrados
$userStmt = $pdo->prepare("
    SELECT u.id, u.username, r.name as role 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE (u.username LIKE ? OR u.full_name LIKE ?) 
      AND r.name IN ('protocolador','visualizador') 
    ORDER BY u.username LIMIT 20
");
$userStmt->execute(["%$searchUser%", "%$searchUser%"]);
$users = $userStmt->fetchAll();

// Atribuições atuais
$assignments = $pdo->query("
    SELECT pa.process_id, p.nup, pa.user_id, u.username 
    FROM process_assignments pa 
    JOIN processes p ON p.id = pa.process_id 
    JOIN users u ON u.id = pa.user_id 
    ORDER BY p.nup, u.username
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Atribuir Processos — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .btn-olive { background-color: #556B2F; color: #fff; }
    .btn-olive:hover { background-color: #445522; }
    .table-sm td, .table-sm th { vertical-align: middle; }
  </style>
</head>
<body class="bg-light">
<div class="container py-5">
  <h3 class="mb-4">Atribuir Processos a Usuários</h3>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e) echo "<p>{$e}</p>"; ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <!-- Filtros -->
  <form method="get" class="row g-2 mb-4">
    <div class="col-md-5">
      <input type="text" name="nup" value="<?= htmlspecialchars($searchNUP) ?>" class="form-control" placeholder="Buscar processo (NUP)">
    </div>
    <div class="col-md-5">
      <input type="text" name="user" value="<?= htmlspecialchars($searchUser) ?>" class="form-control" placeholder="Buscar usuário (username ou nome)">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
    </div>
        <a href="admin.php" class="btn btn-outline-secondary mt-4">← Voltar ao Portal Admin</a><br>

  </form>


  <div class="row">
    <!-- Lista de processos -->
    <div class="col-md-6">
      <h5>Processos encontrados</h5>
      <table class="table table-bordered table-sm">
        <thead class="table-light"><tr><th>NUP</th><th>Atribuir a</th></tr></thead>
        <tbody>
        <?php foreach ($processes as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['nup']) ?></td>
            <td>
              <form method="post" class="d-flex">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="process_id" value="<?= $p['id'] ?>">
                <select name="user_id" class="form-select form-select-sm me-2" required>
                  <option value="">Usuário...</option>
                  <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username'] . ' (' . $u['role'] . ')') ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-olive">Atribuir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Lista de usuários -->
    <div class="col-md-6">
      <h5>Usuários encontrados</h5>
      <table class="table table-bordered table-sm">
        <thead class="table-light"><tr><th>Usuário</th><th>Atribuir NUP</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['username'] . ' (' . $u['role'] . ')') ?></td>
            <td>
              <form method="post" class="d-flex">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <select name="process_id" class="form-select form-select-sm me-2" required>
                  <option value="">NUP...</option>
                  <?php foreach ($processes as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nup']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-olive">Atribuir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Atribuições existentes -->
  <h5 class="mt-4">Atribuições Atuais</h5>
  <table class="table table-bordered table-sm">
    <thead class="table-light">
      <tr><th>Processo (NUP)</th><th>Usuário</th><th>Ação</th></tr>
    </thead>
    <tbody>
    <?php foreach ($assignments as $a): ?>
      <tr>
        <td><?= htmlspecialchars($a['nup']) ?></td>
        <td><?= htmlspecialchars($a['username']) ?></td>
        <td>
          <form method="post" class="d-inline" onsubmit="return confirm('Remover esta atribuição?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="process_id" value="<?= $a['process_id'] ?>">
            <input type="hidden" name="user_id" value="<?= $a['user_id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Remover</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

</div>
</body>
</html>
