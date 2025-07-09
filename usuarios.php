<?php include 'header.php'; ?>
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();

$stmt = $pdo->prepare('SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

$canEditAll = $currentUser['role_name'] === 'admin';
$isGerente  = $currentUser['role_name'] === 'gerente';

if (!$canEditAll && !$isGerente) {
    exit('Acesso negado.');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $rank = trim($_POST['rank'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $roleId = intval($_POST['role_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $editId = intval($_POST['user_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

    $stmt = $pdo->prepare('SELECT name FROM roles WHERE id = ?');
    $stmt->execute([$roleId]);
    $targetRoleName = $stmt->fetchColumn();

    if ($isGerente && !in_array($targetRoleName, ['protocolador', 'visualizador'])) {
        $errors[] = 'Gerente só pode criar/editar usuários com função Protocolador ou Visualizador.';
    }

    if ($action === 'create_user') {
        if ($fullName && $rank && $email && $username && $password && $roleId) {
            if (
                strlen($password) < 8 ||
                !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[\W]/', $password)
            ) {
                $errors[] = 'A senha deve ter pelo menos 8 caracteres, incluindo uma letra maiúscula, um número e um caractere especial.';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'Já existe um usuário com esse username.';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $deptId = $canEditAll ? intval($_POST['department_id']) : $currentUser['department_id'];
                    try {
                        $stmt = $pdo->prepare('INSERT INTO users (full_name, rank, email, username, password_hash, role_id, department_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$fullName, $rank, $email, $username, $passwordHash, $roleId, $deptId, 1]);
                        $success = 'Usuário criado com sucesso.';
                    } catch (PDOException $e) {
                        $errors[] = 'Erro ao criar: ' . $e->getMessage();
                    }
                }
            }
        } else {
            $errors[] = 'Todos os campos são obrigatórios.';
        }
    }

    if ($action === 'edit_user') {
        if ($fullName && $rank && $email && $username && $roleId && $editId) {
            try {
                $fields = [$fullName, $rank, $email, $username, $roleId];
                $sql = 'UPDATE users SET full_name = ?, rank = ?, email = ?, username = ?, role_id = ?';

                if (!empty($password)) {
                    if (
                        strlen($password) < 8 ||
                        !preg_match('/[A-Z]/', $password) ||
                        !preg_match('/[0-9]/', $password) ||
                        !preg_match('/[\W]/', $password)
                    ) {
                        $errors[] = 'A nova senha deve ter pelo menos 8 caracteres, incluindo uma letra maiúscula, um número e um caractere especial.';
                    } else {
                        $sql .= ', password_hash = ?';
                        $fields[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                }

                $deptId = $canEditAll ? intval($_POST['department_id']) : $currentUser['department_id'];
                $sql .= ', department_id = ?, is_active = ? WHERE id = ?';
                $fields[] = $deptId;
                $fields[] = $isActive;
                $fields[] = $editId;

                if (!$errors) {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($fields);
                    $success = 'Usuário atualizado com sucesso.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Erro ao editar: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Preencha todos os campos obrigatórios.';
        }
    }

    if ($action === 'delete_user' && intval($_POST['user_id'])) {
        $userId = intval($_POST['user_id']);
        try {
            if ($isGerente) {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND department_id = ?');
                $stmt->execute([$userId, $currentUser['department_id']]);
            } else {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$userId]);
            }
            $success = 'Usuário removido com sucesso.';
        } catch (PDOException $e) {
            $errors[] = 'Erro ao excluir: ' . $e->getMessage();
        }
    }
}

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '1';
$params = [];

if ($search !== '') {
    $whereClause .= ' AND (u.username LIKE ? OR u.full_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($isGerente) {
    $whereClause .= ' AND u.department_id = ?';
    $params[] = $currentUser['department_id'];
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereClause");
$totalStmt->execute($params);
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$userStmt = $pdo->prepare("
SELECT u.*, r.name as role_name, d.name as dept_name
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE $whereClause 
    ORDER BY u.username 
    LIMIT $limit OFFSET $offset
");
$userStmt->execute($params);
$users = $userStmt->fetchAll();

$roles = $pdo->query("SELECT id, name FROM roles")->fetchAll();
$departments = $pdo->query("SELECT id, code, name FROM departments")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Usuários — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .btn-olive { background-color: #556B2F; color: #fff; }
    .btn-olive:hover { background-color: #445522; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <h3 class="mb-4">Gerenciar Usuários</h3>

    <?php if ($errors): ?>
      <div class="alert alert-danger"><?php foreach ($errors as $e) echo "<p>{$e}</p>"; ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="get" class="mb-3 row g-2">
      <div class="col-md-6">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Buscar usuário...">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-secondary w-100">Buscar</button>
      </div>
    </form>

    <table class="table table-sm table-bordered">
      <thead class="table-light">
        <tr>
          <th>Username</th>
          <th>Nome</th>
          <th>Permissão</th>
          <th>OM</th>
          <th style="width: 100px">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['full_name']) ?></td>
          <td><?= htmlspecialchars($u['role_name']) ?></td>
<td><?= htmlspecialchars($u['dept_name']) ?></td>
          <td>
            <form method="post" class="d-inline" onsubmit="return confirm('Excluir usuário?');">
              <input type="hidden" name="action" value="delete_user">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">🗑</button>
            </form>
            <button class="btn btn-sm btn-outline-warning" onclick="fillForm(<?= htmlspecialchars(json_encode($u)) ?>)">✏️</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <div class="col-md-2">
 
    </table>

    <nav>
      <ul class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>

    <h5 class="mt-4">Criar/Editar Usuário</h5>
<form method="post" class="row g-2">
  <input type="hidden" name="action" value="create_user" id="form_action">
  <input type="hidden" name="user_id" id="user_id">

  <div class="col-md-4">
    <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Nome Completo" required>
  </div>
  <div class="col-md-2">
    <input type="text" name="rank" id="rank" class="form-control" placeholder="Função" required>
  </div>
  <div class="col-md-3">
    <input type="email" name="email" id="email" class="form-control" placeholder="E-mail" required>
  </div>
  <div class="col-md-3">
    <input type="text" name="username" id="username" class="form-control" placeholder="Usuário" required>
  </div>

  <div class="col-md-3">
    <input type="password" name="password" id="password" class="form-control" placeholder="Senha (ou nova senha)">
  </div>

  <div class="col-md-3">
    <select name="role_id" id="role_id" class="form-select" required>
      <option value="">-- Permissões --</option>
      <?php foreach ($roles as $r): ?>
        <?php if ($canEditAll || in_array($r['name'], ['protocolador', 'visualizador'])): ?>
          <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
        <?php endif; ?>
      <?php endforeach; ?>
    </select>
  </div>

<?php if ($canEditAll || $isGerente): ?>
  <?php if ($canEditAll): ?>
    <div class="col-md-4">
      <select name="department_id" id="department_id" class="form-select">
        <option value="">-- Departamento --</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['code'] . ' – ' . $d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>

  <div class="col-md-2">
    <select name="is_active" id="is_active" class="form-select">
      <option value="1">Ativo</option>
      <option value="0">Inativo</option>
    </select>
  </div>
<?php endif; ?>


  <div class="col-md-2">
    <button type="submit" class="btn btn-olive w-100">Salvar</button>
  </div>
</form>

<a href="admin.php" class="btn btn-outline-secondary mt-4">← Voltar ao Portal Admin</a>


  <script>
function fillForm(user) {
  document.getElementById('form_action').value = 'edit_user';
  document.getElementById('user_id').value = user.id;
  document.getElementById('full_name').value = user.full_name;
  document.getElementById('rank').value = user.rank;
  document.getElementById('email').value = user.email;
  document.getElementById('username').value = user.username;
  document.getElementById('role_id').value = user.role_id;
  if (document.getElementById('department_id')) {
    document.getElementById('department_id').value = user.department_id || '';
  }
  document.getElementById('is_active').value = user.is_active;
}
</script>
</body>
</html>
