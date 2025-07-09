<?php include 'header.php'; ?>

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

// valida acesso e busca dados do processo
$stmt = $pdo->prepare(
    'SELECT p.nup, p.subject, p.passive_pole, p.in_charge, p.process_type, p.status
     FROM users u
     JOIN roles r ON u.role_id = r.id
     JOIN processes p ON p.id = ?
     WHERE u.id = ?'
);
$stmt->execute([$pid, $_SESSION['user_id']]);
$process = $stmt->fetch();
if (!$process) exit('Sem permissão.');

$nup = $process['nup'];
$subject = $process['subject'];
$passive = $process['passive_pole'];
$inCharge = $process['in_charge'];
$processType = $process['process_type'];
$status = $process['status'];

// badge de status
$badgeClass = match ($status) {
    'Finalizado' => 'bg-finalizado',
    'Arquivado' => 'bg-secondary',
    default => 'bg-olive'
};

// busca documentos com dados completos
$stmt = $pdo->prepare(
    'SELECT d.*, u.username, u.full_name, u.rank
     FROM documents d
     JOIN users u ON d.uploaded_by = u.id
     WHERE d.process_id = ?
     ORDER BY d.uploaded_at DESC'
);
$stmt->execute([$pid]);
$docs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Andamento do Processo <?= htmlspecialchars($nup) ?> — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
    .badge-olive { background-color: #556B2F; }
    .bg-finalizado { background-color: steelblue !important; color: #fff; }
    .file-name-small { font-size: 0.85rem; color: #666; margin-top: 6px; }
  </style>
</head>
<body class="bg-light">

<div class="container py-5">

  <h2 class="mb-4">
    <i class="bi bi-journal-text olive"></i> Andamento do Processo
    <span class="badge bg-olive"><?= htmlspecialchars($nup) ?></span>
    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
  </h2>

  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-info-circle"></i> Informações do Processo</h5>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div><strong>NUP:</strong> <span id="nup-text"><?= htmlspecialchars($nup) ?></span></div>
          <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('nup-text')" title="Copiar NUP">
            <i class="bi bi-clipboard"></i>
          </button>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div><strong>Polo Passivo:</strong> <span id="passive-text"><?= htmlspecialchars($passive) ?></span></div>
          <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('passive-text')" title="Copiar Polo Passivo">
            <i class="bi bi-clipboard"></i>
          </button>
        </li>
        <li class="list-group-item"><strong>Assunto:</strong> <?= htmlspecialchars($subject) ?></li>
        <li class="list-group-item"><strong>Encarregado:</strong> <?= htmlspecialchars($inCharge) ?></li>
        <li class="list-group-item"><strong>Tipo de Processo:</strong> <?= htmlspecialchars($processType) ?></li>
<li class="list-group-item d-flex align-items-center">
  <strong class="me-2">Status:</strong>

  <span class="d-inline-flex align-items-center px-3 py-1 rounded-pill text-white <?= $badgeClass ?>" style="font-size: 0.85rem; line-height: 1.4;">
    <?= htmlspecialchars($status) ?>
  </span>

  <?php if ($status === 'Em andamento'): ?>
    <a href="protocol.php?process_id=<?= $pid ?>" class="d-inline-flex align-items-center ms-2 px-3 py-1 rounded-pill text-white bg-primary text-decoration-none" style="font-size: 0.85rem; line-height: 1.4;">
      <i class="bi bi-mailbox me-1"></i> Protocolar
    </a>
  <?php endif; ?>
</li>


      </ul>
    </div>
  </div>



  <div class="mb-4">
    <a href="painel.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left-circle"></i> Voltar ao Painel
    </a>
  </div>

  <?php if (empty($docs)): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle"></i> Sem andamentos registrados.
    </div>
  <?php else: ?>
    <div class="mb-4">
      <a href="download_zip.php?process_id=<?= $pid ?>" class="btn btn-olive">
        <i class="bi bi-download"></i> Baixar todo processo (ZIP)
      </a>
    </div>

    <div class="timeline">
      <?php foreach ($docs as $d): ?>
        <div class="card mb-3 shadow">
          <div class="card-header bg-olive text-white d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center" style="min-width: 0;">
              <span class="badge bg-light text-dark me-2">ID: <?= str_pad($d['id_doc_process'], 4, '0', STR_PAD_LEFT) ?></span>
              <span class="text-truncate" style="max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="bi bi-file-earmark-text"></i> <strong><?= htmlspecialchars($d['title']) ?></strong>
              </span>
            </div>
            <span class="badge bg-light text-dark"><?= date('d/m/Y H:i', strtotime($d['uploaded_at'])) ?></span>
          </div>

          <div class="card-body">
            <h6 class="card-subtitle mb-2 text-muted">
              <i class="bi bi-person-badge"></i> <?= htmlspecialchars($d['full_name']) ?>
              <span class="badge bg-secondary"><?= htmlspecialchars($d['rank']) ?></span>
            </h6>
            <p class="card-text fst-italic">
              <i class="bi bi-chat-left-text"></i> <?= htmlspecialchars($d['description']) ?>
            </p>
            <a href="view_document.php?process_id=<?= $pid ?>&file=<?= urlencode(basename($d['file_path'])) ?>&action=view" class="btn btn-outline-primary btn-sm" target="_blank">
              <i class="bi bi-box-arrow-up-right"></i> Abrir PDF
            </a>
            <a href="view_document.php?process_id=<?= $pid ?>&file=<?= urlencode(basename($d['file_path'])) ?>&action=download" class="btn btn-outline-success btn-sm">
              <i class="bi bi-download"></i> Baixar PDF
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyToClipboard(elementId) {
  const text = document.getElementById(elementId).innerText;
  navigator.clipboard.writeText(text).then(() => {
    const btn = event.currentTarget;
    btn.innerHTML = '<i class="bi bi-clipboard-check"></i>';
    setTimeout(() => {
      btn.innerHTML = '<i class="bi bi-clipboard"></i>';
    }, 1500);
  });
}
</script>

</body>
</html>
