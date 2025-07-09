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

if ($roleName !== 'admin') {
    exit('Acesso negado.');
}

$errors = [];
$success = '';

// Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'create_department') {
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($code === '' || $name === '') {
            $errors[] = 'Código e nome são obrigatórios.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO departments (code, name) VALUES (?, ?)');
                $stmt->execute([$code, $name]);
                $success = 'Departamento criado com sucesso.';
            } catch (PDOException $e) {
                $errors[] = 'Erro ao criar: ' . $e->getMessage();
            }
        }
    }

    if ($_POST['action'] === 'delete_department') {
        $id = intval($_POST['department_id']);
        try {
            $stmt = $pdo->prepare('DELETE FROM departments WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'Departamento removido com sucesso.';
        } catch (PDOException $e) {
            $errors[] = 'Erro ao remover: ' . $e->getMessage();
        }
    }
}

// Carregar lista de departamentos
$departments = $pdo->query('SELECT id, code, name FROM departments ORDER BY code')->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Departamentos — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    .btn-olive { background-color: #556B2F; color: #fff; }
    .btn-olive:hover { background-color: #445522; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <h3 class="mb-4">Gerenciar Departamentos</h3>

    <?php if ($errors): ?>
      <div class="alert alert-danger"><?php foreach ($errors as $e) echo "<p>{$e}</p>"; ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Tabela de departamentos -->
    <table class="table table-bordered table-striped table-sm">
      <thead class="table-secondary">
        <tr>
          <th>Código</th>
          <th>Nome</th>
          <th style="width: 100px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($departments as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['code']) ?></td>
          <td><?= htmlspecialchars($d['name']) ?></td>
          <td>
            <form method="post" class="d-inline" onsubmit="return confirm('Confirmar remoção?');">
              <input type="hidden" name="action" value="delete_department">
              <input type="hidden" name="department_id" value="<?= $d['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Formulário de criação -->
    <h5 class="mt-4">Novo Departamento</h5>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="create_department">
      <div class="col-md-3">
        <input type="text" name="code" class="form-control" placeholder="Código (ex: 001)" required>
      </div>
      <div class="col-md-6">
        <input type="text" name="name" class="form-control" placeholder="Nome do Departamento" required>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-olive w-100">Criar</button>
      </div>
    </form>

    <a href="admin.php" class="btn btn-outline-secondary mt-4"><i class="bi bi-arrow-left"></i> Voltar ao Portal Admin</a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
