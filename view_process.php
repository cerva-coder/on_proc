<?php include 'header.php'; ?>

<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/fpdf/fpdf.php';
require_once __DIR__ . '/config.php'; // para ENCRYPTION_KEY

if (!isset($_SESSION['user_id'], $_GET['process_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$pid = intval($_GET['process_id']);

// busca usuário e role
$stmt = $pdo->prepare('SELECT u.id AS user_id, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) exit('Acesso negado.');
$role = $user['role_name'];
$uid  = $user['user_id'];

// valida processo
$stmt = $pdo->prepare('SELECT * FROM processes WHERE id = ?');
$stmt->execute([$pid]);
$proc = $stmt->fetch();
if (!$proc) exit('Processo não encontrado.');

// exclusão segura de documento pelo admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc_id'], $_POST['delete_reason']) && $role === 'admin') {
    $docId = intval($_POST['delete_doc_id']);
    $reason = trim($_POST['delete_reason']);
    $dataHora = date('d/m/Y H:i');

    $stmt = $pdo->prepare('SELECT file_path FROM documents WHERE id = ? AND process_id = ?');
    $stmt->execute([$docId, $pid]);
    $doc = $stmt->fetch();

    if ($doc) {
        $oldPath = $doc['file_path'];
        $fullPath = realpath(__DIR__ . '/' . $oldPath);

        // Exclui o arquivo real
        if ($fullPath && is_file($fullPath) && str_starts_with($fullPath, realpath(__DIR__ . '/uploads'))) {
            unlink($fullPath);
        }

        // Pasta do processo
        $processFolder = 'uploads/' . $pid;
        if (!is_dir(__DIR__ . '/' . $processFolder)) {
            mkdir(__DIR__ . '/' . $processFolder, 0777, true);
        }

        // Gera PDF com o motivo e data/hora da exclusão
        $tempPdfPath = tempnam(sys_get_temp_dir(), 'pdf');
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, utf8_decode('Documento excluído pelo Administrador do Sistema'), 0, 1, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(0, 10, utf8_decode("Motivo da exclusão:\n" . $reason . "\n\nDocumento excluído em: " . $dataHora));
        $pdf->Output('F', $tempPdfPath);

        // Criptografa PDF
        $original_data = file_get_contents($tempPdfPath);
        $key = ENCRYPTION_KEY;
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted_data = openssl_encrypt($original_data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $final_data = $iv . $encrypted_data;

        $newFileName = 'excluido_' . $docId . '_' . time() . '.pdf';
        $newPdfPath = $processFolder . '/' . $newFileName;
        file_put_contents(__DIR__ . '/' . $newPdfPath, $final_data);
        unlink($tempPdfPath);

        // Atualiza banco
        $stmt = $pdo->prepare('UPDATE documents SET title = ?, description = ?, file_path = ? WHERE id = ?');
        $stmt->execute([
            'Excluído pelo Administrador do Sistema',
            'Motivo da exclusão do documento: ' . $reason . ' — Documento excluído em ' . $dataHora,
            $newPdfPath,
            $docId
        ]);

        echo "<script>alert('Documento excluído com sucesso. Um novo PDF com os motivos foi gerado no processo.'); window.location = 'view_process.php?process_id={$pid}';</script>";
        exit;
    }
}

// busca documentos
$stmt = $pdo->prepare('SELECT d.*, u.username FROM documents d JOIN users u ON d.uploaded_by = u.id WHERE d.process_id = ? ORDER BY d.uploaded_at DESC');
$stmt->execute([$pid]);
$docs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Processo <?= htmlspecialchars($proc['nup']) ?> — ON_Proc</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
</style>
</head>
<body class="bg-light">

<div class="container py-5">

    <h2 class="mb-4">
        <span class="badge bg-secondary"><?= htmlspecialchars($proc['nup']) ?></span>
    </h2>

    <ul class="list-group mb-4">
        <li class="list-group-item"><strong>Assunto:</strong> <?= htmlspecialchars($proc['subject']) ?></li>
        <li class="list-group-item"><strong>Polo Passivo:</strong> <?= htmlspecialchars($proc['passive_pole']) ?></li>
        <li class="list-group-item"><strong>Encarregado:</strong> <?= htmlspecialchars($proc['in_charge']) ?></li>
        <li class="list-group-item"><strong>Criado em:</strong> <?= htmlspecialchars($proc['created_at']) ?></li>
    </ul>

    <h3 class="mb-3"><i class="bi bi-file-earmark-pdf"></i> Documentos Protocolados</h3>

    <?php if (empty($docs)): ?>
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Nenhum PDF ainda.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="bg-olive">
                    <tr>
                        <th>Nome</th>
                        <th>Usuário</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($docs as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['file_name']) ?></td>
                        <td><?= htmlspecialchars($d['username']) ?></td>
                        <td><?= htmlspecialchars($d['uploaded_at']) ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right"></i> Abrir
                            </a>
                            <?php if ($role === 'admin' && $d['file_name'] !== 'Excluído pelo Administrador do Sistema'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Confirmar exclusão?');">
                                    <input type="hidden" name="delete_doc_id" value="<?= $d['id'] ?>">
                                    <input type="text" name="delete_reason" placeholder="Motivo" required class="form-control d-inline w-auto mt-1">
                                    <button type="submit" class="btn btn-sm btn-outline-danger mt-1">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <a href="painel.php" class="btn btn-outline-secondary mt-4">
        <i class="bi bi-arrow-left-circle"></i> Voltar ao Painel
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
