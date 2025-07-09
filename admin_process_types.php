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

$errors = [];
$success = '';

// Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Criar novo tipo de processo
    if ($_POST['action'] === 'create_process_type') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        if ($name === '') {
            $errors[] = 'Nome do tipo de processo é obrigatório.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO process_types (name, category) VALUES (?, ?)');
                $stmt->execute([$name, $category ?: null]);
                $success = 'Tipo de processo criado com sucesso.';
            } catch (PDOException $e) {
                $errors[] = 'Erro ao criar: ' . $e->getMessage();
            }
        }
    }

    // Excluir tipo (somente admin)
    if ($_POST['action'] === 'delete_process_type' && $roleName === 'admin') {
        $id = intval($_POST['process_type_id']);
        try {
            $stmt = $pdo->prepare('DELETE FROM process_types WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'Tipo de processo removido com sucesso.';
        } catch (PDOException $e) {
            $errors[] = 'Erro ao remover: ' . $e->getMessage();
        }
    }
}

// Carregar lista de tipos de processo
$types = $pdo->query('SELECT id, name, category FROM process_types ORDER BY category, name')->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Tipos de Processo — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    .btn-olive { background-color: #556B2F; color: #fff; }
    .btn-olive:hover { background-color: #445522; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <h3 class="mb-4">Gerenciar Tipos de Processo</h3>

    <?php if ($errors): ?>
      <div class="alert alert-danger"><?php foreach ($errors as $e) echo "<p>{$e}</p>"; ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Tabela de tipos -->
    <table class="table table-bordered table-striped table-sm">
      <thead class="table-secondary">
        <tr>
          <th>Categoria</th>
          <th>Nome</th>
          <?php if ($roleName === 'admin'): ?>
          <th style="width: 100px;">Ações</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($types as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['category'] ?: '-') ?></td>
          <td><?= htmlspecialchars($t['name']) ?></td>
          <?php if ($roleName === 'admin'): ?>
          <td>
            <form method="post" class="d-inline" onsubmit="return confirm('Confirmar remoção?');">
              <input type="hidden" name="action" value="delete_process_type">
              <input type="hidden" name="process_type_id" value="<?= $t['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Formulário de criação -->
    <h5 class="mt-4">Novo Tipo de Processo</h5>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="create_process_type">
      <div class="col-md-5">
        <input type="text" name="name" class="form-control" placeholder="Nome do tipo de processo" required>
      </div>
      <div class="col-md-4">
        <input type="text" name="category" class="form-control" placeholder="Categoria (opcional)">
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
