<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

// Se já estiver autenticado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: painel.php');
    exit;
}

// Segurança: headers para evitar ataques comuns
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net;");

$erro = '';
$MAX_ATTEMPTS = 5;
$LOCK_TIME = 300;

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['last_attempt_time'])) $_SESSION['last_attempt_time'] = 0;

// Bloqueio por múltiplas tentativas
if ($_SESSION['login_attempts'] >= $MAX_ATTEMPTS && time() - $_SESSION['last_attempt_time'] < $LOCK_TIME) {
    $erro = 'Muitas tentativas. Tente novamente em alguns minutos.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['last_attempt_time'] = time();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $erro = 'Preencha usuário e senha.';
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role_id, department_id, is_active FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); // proteção contra fixation
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role_id']       = $user['role_id'];
            $_SESSION['department_id'] = $user['department_id'];
            $_SESSION['login_attempts'] = 0;
            header('Location: painel.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            $erro = 'Usuário ou senha inválidos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login — ON_Proc</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="language" content="pt-BR">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .olive { color: #556B2F; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
    .footer { font-size: 0.9rem; color: #888; text-align: center; margin-top: 30px; }
  </style>
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="text-center mb-4">
    <h1 class="display-4 fw-bold olive">
      <i class="bi bi-shield-lock"></i> ON_<span class="text-dark">Proc</span>
    </h1>
    <p class="text-muted">Sistema de Processo Administrativo Eletrônico</p>
  </div>

  <div class="card shadow mx-auto" style="max-width: 400px;">
    <div class="card-body">
      <h4 class="card-title mb-4 text-center olive">Login</h4>

      <?php if ($erro): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($erro) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="mb-3">
          <label for="username" class="form-label">Usuário</label>
          <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Senha</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-olive w-100">
          <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>
      </form>
    </div>
  </div>

  <div class="footer">Desenvolvido por herocode.tech</div>

   <div class="container mt-5">
    <div class="row g-4">

      <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm h-100">
          <div class="card-body text-center">
            <i class="bi bi-check2-circle fs-1 text-success"></i>
            <h5 class="card-title mt-3">Facilidade de Uso</h5>
            <p class="card-text text-muted">Interface simples, intuitiva e adaptada à rotina administrativa do Departamento. Crie, acompanhe e conclua processos em poucos cliques.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm h-100">
          <div class="card-body text-center">
            <i class="bi bi-lock-fill fs-1 text-danger"></i>
            <h5 class="card-title mt-3">Segurança Garantida</h5>
            <p class="card-text text-muted">Documentos criptografados com padrão AES-256. Acesso protegido por senha e controle de permissões por perfil.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm h-100">
          <div class="card-body text-center">
            <i class="bi bi-clock-history fs-1 text-primary"></i>
            <h5 class="card-title mt-3">Rastreabilidade</h5>
            <p class="card-text text-muted">Cada movimentação é registrada com horário, autor e descrição. Transparência total no andamento dos processos.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm h-100">
          <div class="card-body text-center">
            <i class="bi bi-diagram-3 fs-1 text-warning"></i>
            <h5 class="card-title mt-3">Departamento</h5>
            <p class="card-text text-muted">Gerencie processos por setor. Apenas usuários autorizados acessam os documentos de seu departamento.</p>
          </div>
        </div>
      </div>

    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
