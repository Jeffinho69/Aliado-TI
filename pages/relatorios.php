<?php
session_start();
require '../config/db.php';
require '../includes/functions.php'; // Para usar gravarLog se precisar

// Apenas Admin e Técnico acessam relatórios
if ($_SESSION['user_nivel'] == 'usuario') { header("Location: dashboard.php"); exit; }

// --- LÓGICA DE LIMPAR LOGS (SÓ ADMIN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['limpar_logs'])) {
    if ($_SESSION['user_nivel'] == 'admin') {
        $pdo->exec("TRUNCATE TABLE system_logs");
        gravarLog($pdo, $_SESSION['user_id'], "Sistema", "Limpou todo o histórico de logs.");
        header("Location: relatorios.php"); 
        exit;
    }
}


$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-t');

// 1. Estatísticas
$sqlStats = "SELECT prioridade, COUNT(*) as qtd FROM tickets WHERE criado_em BETWEEN ? AND ? GROUP BY prioridade";
$stmt = $pdo->prepare($sqlStats);
$stmt->execute([$inicio . ' 00:00:00', $fim . ' 23:59:59']);
$dadosGrafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalChamados = 0;
foreach($dadosGrafico as $d) $totalChamados += $d['qtd'];

// 2. Logs
$sqlLogs = "SELECT l.*, u.nome FROM system_logs l LEFT JOIN users u ON l.user_id = u.id WHERE l.data_hora BETWEEN ? AND ? ORDER BY l.data_hora DESC";
$stmt2 = $pdo->prepare($sqlLogs);
$stmt2->execute([$inicio . ' 00:00:00', $fim . ' 23:59:59']);
$logs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios - Aliado TI</title>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #000; box-shadow: none; }
            body { background: white; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <?php include '../includes/sidebar.php'; ?>
</div>

<div class="main-content">
    
    <div class="card p-3 mb-4 no-print shadow-sm border-0">
        <form class="row align-items-end">
            <div class="col-md-4">
                <label class="fw-bold small text-muted">Data Início</label>
                <input type="date" name="inicio" class="form-control" value="<?= $inicio ?>">
            </div>
            <div class="col-md-4">
                <label class="fw-bold small text-muted">Data Fim</label>
                <input type="date" name="fim" class="form-control" value="<?= $fim ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filtrar</button>
                <button type="button" onclick="window.print()" class="btn btn-secondary w-100"><i class="fas fa-print"></i> Imprimir</button>
            </div>
        </form>
    </div>

    <div class="text-center mb-4">
        <h3 class="fw-bold text-dark">Relatório de Gestão TI</h3>
        <p class="text-muted">Período: <?= date('d/m/Y', strtotime($inicio)) ?> até <?= date('d/m/Y', strtotime($fim)) ?></p>
    </div>

    <?php if($totalChamados == 0): ?>
        <div class="alert alert-warning text-center p-4 border-0 shadow-sm">
            <i class="fas fa-chart-bar fa-2x mb-3 text-warning"></i>
            <h5>Sem dados de chamados neste período.</h5>
        </div>
    <?php else: ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100 p-3 shadow-sm border-0">
                    <h5 class="card-title text-center text-primary fw-bold">Volume de Chamados</h5>
                    <h2 class="display-4 fw-bold text-center my-4"><?= $totalChamados ?></h2>
                    <canvas id="chartPrioridade" style="max-height: 200px;"></canvas>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100 p-3 shadow-sm border-0">
                    <h5 class="card-title fw-bold text-dark">Detalhamento</h5>
                    <ul class="list-group list-group-flush mt-3">
                        <?php foreach($dadosGrafico as $d): 
                            $porc = ($totalChamados > 0) ? round(($d['qtd'] / $totalChamados) * 100, 1) : 0;
                            $cor = match($d['prioridade']) { 'critica'=>'danger', 'alta'=>'warning', 'media'=>'primary', default=>'success' };
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-uppercase fw-bold text-<?= $cor ?>"><?= $d['prioridade'] ?></span>
                            <span>
                                <span class="fw-bold"><?= $d['qtd'] ?></span> 
                                <span class="badge bg-light text-dark ms-2"><?= $porc ?>%</span>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fas fa-history me-2"></i>Histórico de Atividades (Logs)</span>
            
            <?php if($_SESSION['user_nivel'] == 'admin'): ?>
                <form method="POST" onsubmit="return confirm('ATENÇÃO: Isso apagará TODO o histórico de atividades do sistema. Tem certeza?');">
                    <button type="submit" name="limpar_logs" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash-alt me-1"></i> Limpar Histórico
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): ?>
                    <tr>
                        <td style="white-space: nowrap;"><?= date('d/m H:i', strtotime($log['data_hora'])) ?></td>
                        <td class="fw-bold text-primary"><?= htmlspecialchars($log['nome'] ?: 'Sistema') ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($log['acao']) ?></span></td>
                        <td class="text-muted small"><?= htmlspecialchars($log['detalhes']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(count($logs) == 0): ?>
                <div class="text-center p-5 text-muted opacity-50">
                    <i class="fas fa-wind fa-3x mb-3"></i>
                    <p>Nenhuma atividade registrada.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    <?php if($totalChamados > 0): ?>
    const ctx = document.getElementById('chartPrioridade');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($dadosGrafico as $d) echo "'" . ucfirst($d['prioridade']) . "',"; ?>],
            datasets: [{
                data: [<?php foreach($dadosGrafico as $d) echo $d['qtd'] . ","; ?>],
                backgroundColor: ['#28a745', '#ffc107', '#fd7e14', '#dc3545']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
    <?php endif; ?>
</script>

</body>
</html>