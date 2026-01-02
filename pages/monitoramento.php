<?php
session_start();
require '../config/db.php';

// ADICIONAR NOVO HOST (Post)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'add') {
    $nome = $_POST['nome'];
    $host = $_POST['host'];
    $porta = $_POST['porta'];
    
    $stmt = $pdo->prepare("INSERT INTO monitoring (nome, host, porta) VALUES (?, ?, ?)");
    $stmt->execute([$nome, $host, $porta]);
    header("Location: monitoramento.php"); exit;
}

// EXCLUIR HOST
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    $pdo->prepare("DELETE FROM monitoring WHERE id = ?")->execute([$id]);
    header("Location: monitoramento.php"); exit;
}

// BUSCAR LISTA
$hosts = $pdo->query("SELECT * FROM monitoring")->fetchAll(PDO::FETCH_ASSOC);

function verificarStatus($host, $porta) {
    $conexao = @fsockopen($host, $porta, $errno, $errstr, 1);
    if ($conexao) {
        fclose($conexao);
        return 'online';
    }
    return 'offline';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="60"> <title>Monitoramento - Aliado TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <style>
        .monitor-card { transition: 0.3s; border-left: 5px solid #ddd; }
        .monitor-card.online { border-left-color: #28a745; }
        .monitor-card.offline { border-left-color: #dc3545; }
        .status-badge { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0"><i class="fas fa-network-wired text-info me-2"></i>NOC - Monitoramento</h3>
            <p class="text-muted">Status de Servidores e Serviços em Tempo Real</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalHost">
            <i class="fas fa-plus me-2"></i>Adicionar Monitor
        </button>
    </div>

    <div class="row">
        <?php if(count($hosts) == 0): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-server fa-4x text-muted mb-3 opacity-25"></i>
                <h4>Nenhum servidor monitorado</h4>
                <p class="text-muted">Clique em "Adicionar Monitor" para começar.</p>
            </div>
        <?php endif; ?>

        <?php foreach($hosts as $h): 
            $status = verificarStatus($h['host'], $h['porta']);
            $cor = ($status == 'online') ? 'bg-success' : 'bg-danger';
            $texto_cor = ($status == 'online') ? 'text-success' : 'text-danger';
        ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 monitor-card <?= $status ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title fw-bold mb-1"><?= $h['nome'] ?></h5>
                        <a href="?del=<?= $h['id'] ?>" class="text-danger small" onclick="return confirm('Remover este monitor?')"><i class="fas fa-trash"></i></a>
                    </div>
                    
                    <p class="text-muted small mb-3">
                        <i class="fas fa-globe me-1"></i> <?= $h['host'] ?> : <?= $h['porta'] ?>
                    </p>
                    
                    <div class="d-flex align-items-center bg-light p-2 rounded">
                        <div class="status-badge <?= $cor ?>"></div>
                        <span class="fw-bold <?= $texto_cor ?> text-uppercase me-auto">
                            <?= $status ?>
                        </span>
                        <small class="text-muted" style="font-size: 0.7em">Ping Check: 1s</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="modalHost" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="acao" value="add">
            <div class="modal-header">
                <h5 class="modal-title">Novo Monitoramento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome Amigável</label>
                    <input type="text" name="nome" class="form-control" placeholder="Ex: Servidor de Arquivos" required>
                </div>
                <div class="row">
                    <div class="col-8 mb-3">
                        <label class="form-label">Host (IP ou URL)</label>
                        <input type="text" name="host" class="form-control" placeholder="Ex: 192.168.1.5 ou google.com" required>
                    </div>
                    <div class="col-4 mb-3">
                        <label class="form-label">Porta</label>
                        <input type="number" name="porta" class="form-control" value="80" required>
                        <small class="text-muted d-block mt-1" style="font-size: 10px">80=Web, 3389=RDP</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100">Iniciar Monitoramento</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>