<?php include 'header.php'; ?>
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$pdo = getPDO();

$stmt = $pdo->prepare('SELECT u.department_id, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$deptFilter = '';
$params = [];

if ($user['role'] === 'gerente') {
    $deptFilter = 'WHERE p.department_id = ?';
    $params[] = $user['department_id'];
} elseif ($user['role'] !== 'admin') {
    exit('Acesso negado.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Estatísticas — ON_Proc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .olive { color: #556B2F; }
    .bg-olive { background-color: #556B2F !important; color: #fff !important; }
    .btn-olive { background-color: #556B2F; color: #fff; border: none; }
    .btn-olive:hover { background-color: #445522; color: #fff; }
    .badge-olive { background-color: #556B2F; }
    .bg-finalizado { background-color: steelblue !important; color: #fff; }
    .bg-andamento { background-color: #556B2F !important; color: #fff; }
    .table th, .table td { vertical-align: middle; }
    body { background-color: #f8f9fa; }
  </style>

<style>
    h3, h4, h6, .card-body, .list-group-item {
        font-family: "Segoe UI", sans-serif;
    }

    h3 {
        font-size: 1.8rem;
        font-weight: 600;
    }

    h6 {
        font-size: 1rem;
        font-weight: 500;
    }

    .card-body {
        font-size: 0.95rem;
        padding: 1rem;
    }

    .chart-container {
        height: 300px;
        overflow: hidden;
    }

    canvas {
        display: block;
        max-height: 300px;
    }

    .list-group-item {
        font-size: 0.95rem;
    }

    .badge {
        font-size: 0.85rem;
    }
</style>


<body class="bg-light">
<div class="container py-5">
<h2 class="mb-2">
    <div class="text-end">
  <a href="admin.php" class="btn btn-outline-secondary mt-4">
    <i class="bi bi-arrow-left"></i> Voltar ao Portal Admin
  </a>
</div>

  <i class="bi bi-bar-chart-line olive"></i> Estatísticas Gerais
  <span class="badge badge-olive"><?= ucfirst($user['role']) ?></span>
</h2>
<p class="text-muted ms-1">
  Departamento: <strong>
    <?php
    $stmt = $pdo->prepare('SELECT name FROM departments WHERE id = ?');
    $stmt->execute([$user['department_id']]);
    echo htmlspecialchars($stmt->fetchColumn());
    ?>
  </strong>
</p>

    <!-- Cards Resumo -->
    <div class="row g-3">
        <?php
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as total FROM processes p $deptFilter GROUP BY status");
        $stmt->execute($params);
        $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents d JOIN processes p ON d.process_id = p.id $deptFilter");
        $stmt->execute($params);
        $docTotal = $stmt->fetchColumn();
        ?>
        <?php
        $cards = [
            ['label' => 'Em andamento', 'value' => $statusCounts['Em andamento'] ?? 0, 'color' => 'primary'],
            ['label' => 'Finalizados', 'value' => $statusCounts['Finalizado'] ?? 0, 'color' => 'success'],
            ['label' => 'Arquivados', 'value' => $statusCounts['Arquivado'] ?? 0, 'color' => 'secondary'],
            ['label' => 'Documentos enviados', 'value' => $docTotal, 'color' => 'info']
        ];
        foreach ($cards as $card): ?>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-start border-4 border-<?= $card['color'] ?>">
                <div class="card-body">
                    <h6 class="text-muted"><?= $card['label'] ?></h6>
                    <h4 class="text-<?= $card['color'] ?>"><?= $card['value'] ?></h4>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Gráficos -->
    <?php
    $stmt = $pdo->prepare("SELECT process_type, COUNT(*) as total FROM processes p $deptFilter GROUP BY process_type");
    $stmt->execute($params);
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total
        FROM processes p $deptFilter
        GROUP BY mes ORDER BY mes DESC LIMIT 12
    ");
    $stmt->execute($params);
    $meses = array_reverse($stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    ?>
    <div class="row g-4 mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6><i class="bi bi-diagram-3 card-title-icon"></i>Distribuição por Tipo</h6>
                    <div class="chart-container">
                        <canvas id="chartTipos"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6><i class="bi bi-calendar-range card-title-icon"></i>Protocolados por Mês</h6>
                    <div class="chart-container">
                        <canvas id="chartMensal"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listagens -->
    <div class="row g-4 mt-4">
        <?php
        $stmt = $pdo->prepare("
            SELECT u.full_name, COUNT(*) as total
            FROM process_assignments pa
            JOIN users u ON pa.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            JOIN processes p ON p.id = pa.process_id
            WHERE r.name NOT IN ('admin','gerente') " . ($deptFilter ? "AND p.department_id = ?" : "") . "
            GROUP BY u.full_name ORDER BY total DESC LIMIT 5
        ");
        $stmt->execute($params);
        $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT pt.category, COUNT(p.id) as total
            FROM processes p
            JOIN process_types pt ON pt.name = p.process_type
            $deptFilter
            GROUP BY pt.category
        ");
        $stmt->execute($params);
        $categorias = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        ?>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6><i class="bi bi-people-fill card-title-icon"></i>Top Usuários Atribuídos</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topUsers as $u): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($u['full_name']) ?>
                            <span class="badge bg-dark"><?= $u['total'] ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6><i class="bi bi-tags card-title-icon"></i>Processos por Categoria</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categorias as $cat => $qtd): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= $cat ?: 'Sem categoria' ?>
                            <span class="badge bg-secondary"><?= $qtd ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartTipos = document.getElementById('chartTipos').getContext('2d');
const chartMensal = document.getElementById('chartMensal').getContext('2d');

new Chart(chartTipos, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($tipos, 'process_type')) ?>,
        datasets: [{
            label: 'Total por Tipo',
            data: <?= json_encode(array_column($tipos, 'total')) ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.7)',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

new Chart(chartMensal, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($meses)) ?>,
        datasets: [{
            label: 'Processos por Mês',
            data: <?= json_encode(array_values($meses)) ?>,
            borderColor: 'rgba(40, 167, 69, 0.8)',
            backgroundColor: 'rgba(40, 167, 69, 0.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: 'rgba(40, 167, 69, 1)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>