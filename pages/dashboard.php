<?php
session_start();
require '../config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }

$nivel = $_SESSION['user_nivel'];

// --- Lógica dos Cards e Modals ---
// Pegar os últimos 5 de cada categoria para mostrar no Modal
if ($nivel != 'usuario') {
    $novos = $pdo->query("SELECT id, titulo, criado_em FROM tickets WHERE status = 'aberto' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $online_users = $pdo->query("SELECT nome, nivel FROM users WHERE ultimo_acesso > (NOW() - INTERVAL 5 MINUTE) AND nivel = 'usuario'")->fetchAll(PDO::FETCH_ASSOC);
    $online_ti = $pdo->query("SELECT nome FROM users WHERE ultimo_acesso > (NOW() - INTERVAL 5 MINUTE) AND (nivel = 'admin' OR nivel = 'tecnico')")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $online_ti = $pdo->query("SELECT nome FROM users WHERE ultimo_acesso > (NOW() - INTERVAL 5 MINUTE) AND (nivel = 'admin' OR nivel = 'tecnico')")->fetchAll(PDO::FETCH_ASSOC);
}

// Contadores
$count_abertos = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'aberto'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Interativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <style>
        .card-click { cursor: pointer; transition: 0.2s; }
        .card-click:hover { transform: scale(1.02); }
        .user-online { position: relative; padding-left: 20px; }
        .user-online::before { content: ''; width: 10px; height: 10px; background: #28a745; border-radius: 50%; position: absolute; left: 0; top: 8px; }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <h2 class="mb-4">Visão Geral</h2>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-3 bg-primary text-white card-click" data-bs-toggle="modal" data-bs-target="#modalNovos">
                <h3><?= $count_abertos ?></h3>
                <p>Novos Chamados (Clique para ver)</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 bg-success text-white card-click" data-bs-toggle="modal" data-bs-target="#modalOnline">
                <h3><i class="fas fa-users"></i></h3>
                <p>Quem está Online?</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovos" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Últimos Chamados Abertos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group">
                    <?php if(!empty($novos)): foreach($novos as $n): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>#<?= $n['id'] ?> - <?= $n['titulo'] ?></span>
                            <a href="ver_chamado.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
                        </li>
                    <?php endforeach; else: echo "<p>Nenhum chamado novo.</p>"; endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalOnline" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pessoas Online (Últimos 5 min)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Equipe de TI:</h6>
                <?php foreach($online_ti as $ti): ?>
                    <div class="user-online fw-bold text-primary"><?= $ti['nome'] ?></div>
                <?php endforeach; ?>
                
                <?php if($nivel != 'usuario'): ?>
                    <hr>
                    <h6>Usuários:</h6>
                    <?php foreach($online_users as $u): ?>
                        <div class="user-online"><?= $u['nome'] ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>