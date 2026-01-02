<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }

$nivel = $_SESSION['user_nivel'];
$user_id = $_SESSION['user_id'];

// --- LÓGICA DE EXCLUIR ---
if (isset($_GET['delete']) && ($nivel == 'admin' || $nivel == 'tecnico')) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM ticket_responses WHERE ticket_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$id]);
    header("Location: chamados.php"); exit;
}

// --- FILTROS ---
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_status = $status_filter ? " AND t.status = '$status_filter'" : "";

// --- BUSCA CHAMADOS ---
if ($nivel != 'usuario') {
    $sql = "SELECT t.*, u.nome as autor, tec.nome as tecnico_nome 
            FROM tickets t 
            JOIN users u ON t.user_id = u.id 
            LEFT JOIN users tec ON t.tecnico_id = tec.id
            WHERE 1=1 $where_status 
            ORDER BY t.criado_em DESC";
    $stmt = $pdo->query($sql);
} else {
    $sql = "SELECT t.*, u.nome as autor, tec.nome as tecnico_nome 
            FROM tickets t 
            JOIN users u ON t.user_id = u.id 
            LEFT JOIN users tec ON t.tecnico_id = tec.id
            WHERE t.user_id = :id $where_status 
            ORDER BY t.criado_em DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $user_id]);
}
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- CONTAGENS PARA OS CARDS ---
if ($nivel != 'usuario') {
    $c_aberto = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='aberto'")->fetchColumn();
    $c_andamento = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='em_andamento'")->fetchColumn();
    $c_resolvido = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='resolvido'")->fetchColumn();
} else {
    $c_aberto = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='aberto' AND user_id=$user_id")->fetchColumn();
    $c_andamento = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='em_andamento' AND user_id=$user_id")->fetchColumn();
    $c_resolvido = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='resolvido' AND user_id=$user_id")->fetchColumn();
}

// --- QUEM ESTÁ ONLINE (Lógica trazida do Dashboard) ---
$online_ti = $pdo->query("SELECT nome FROM users WHERE ultimo_acesso > (NOW() - INTERVAL 5 MINUTE) AND (nivel = 'admin' OR nivel = 'tecnico')")->fetchAll(PDO::FETCH_ASSOC);
$online_users = $pdo->query("SELECT nome FROM users WHERE ultimo_acesso > (NOW() - INTERVAL 5 MINUTE) AND nivel = 'usuario'")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Service Desk - Aliado TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <style>
        .filter-btn.active { border-bottom: 3px solid #0d6efd; font-weight: bold; color: #0d6efd; }
        .filter-btn { color: #6c757d; text-decoration: none; padding: 10px 15px; display: inline-block; }
        .filter-btn:hover { color: #0d6efd; background: #f8f9fa; }
        .avatar-small { width: 25px; height: 25px; background: #e9ecef; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; color: #495057; font-weight: bold; margin-right: 5px; }
        .online-dot { width: 8px; height: 8px; background-color: #28a745; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .card-counter { transition: transform 0.2s; }
        .card-counter:hover { transform: translateY(-3px); }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fas fa-headset text-primary me-2"></i>Service Desk</h3>
            <p class="text-muted small">Central de Chamados e Suporte</p>
        </div>
        <a href="abrir_chamado.php" class="btn btn-primary shadow-sm px-4">
            <i class="fas fa-plus me-2"></i>Novo Ticket
        </a>
    </div>

    <div class="row">
        <div class="col-md-9">
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <a href="chamados.php?status=aberto" class="text-decoration-none">
                        <div class="card card-counter border-0 shadow-sm border-start border-4 border-primary p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="text-muted text-uppercase small fw-bold mb-1">Abertos</h6><h3 class="mb-0 text-primary fw-bold"><?= $c_aberto ?></h3></div>
                                <i class="fas fa-inbox fa-2x text-primary opacity-25"></i>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="chamados.php?status=em_andamento" class="text-decoration-none">
                        <div class="card card-counter border-0 shadow-sm border-start border-4 border-warning p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="text-muted text-uppercase small fw-bold mb-1">Em Andamento</h6><h3 class="mb-0 text-warning fw-bold"><?= $c_andamento ?></h3></div>
                                <i class="fas fa-tools fa-2x text-warning opacity-25"></i>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="chamados.php?status=resolvido" class="text-decoration-none">
                        <div class="card card-counter border-0 shadow-sm border-start border-4 border-success p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="text-muted text-uppercase small fw-bold mb-1">Resolvidos</h6><h3 class="mb-0 text-success fw-bold"><?= $c_resolvido ?></h3></div>
                                <i class="fas fa-check-circle fa-2x text-success opacity-25"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                    <div class="nav nav-tabs border-0">
                        <a href="chamados.php" class="filter-btn <?= $status_filter==''?'active':'' ?>">Todos</a>
                        <a href="chamados.php?status=aberto" class="filter-btn <?= $status_filter=='aberto'?'active':'' ?>">Abertos</a>
                        <a href="chamados.php?status=em_andamento" class="filter-btn <?= $status_filter=='em_andamento'?'active':'' ?>">Andamento</a>
                        <a href="chamados.php?status=resolvido" class="filter-btn <?= $status_filter=='resolvido'?'active':'' ?>">Resolvidos</a>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Assunto</th>
                                <th>Solicitante / Técnico</th>
                                <th>Status</th>
                                <th>Prioridade</th>
                                <th>Data</th>
                                <th class="text-end pe-4">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tickets as $t): 
                                $status_badge = match($t['status']) {
                                    'aberto' => 'bg-primary',
                                    'em_andamento' => 'bg-warning text-dark',
                                    'resolvido' => 'bg-success',
                                    'fechado' => 'bg-secondary',
                                    default => 'bg-light text-dark'
                                };
                                $prio_text = match($t['prioridade']) {
                                    'critica' => 'text-danger fw-bold',
                                    'alta' => 'text-danger',
                                    'media' => 'text-warning fw-bold',
                                    'baixa' => 'text-success',
                                    default => 'text-muted'
                                };
                            ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold">#<?= $t['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($t['titulo']) ?></div>
                                    <?php if($t['anexo']): ?>
                                        <small class="text-muted"><i class="fas fa-paperclip"></i> Anexo</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="small"><i class="fas fa-user text-muted me-1"></i> <?= $t['autor'] ?></span>
                                        <?php if($t['tecnico_nome']): ?>
                                            <span class="small text-primary"><i class="fas fa-user-cog me-1"></i> <?= $t['tecnico_nome'] ?></span>
                                        <?php else: ?>
                                            <span class="small text-muted fst-italic">-- Sem técnico --</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><span class="badge rounded-pill <?= $status_badge ?>"><?= strtoupper(str_replace('_', ' ', $t['status'])) ?></span></td>
                                <td class="<?= $prio_text ?> text-uppercase small"><?= $t['prioridade'] ?></td>
                                <td class="fw-bold text-dark"><?= date('d/m/Y H:i', strtotime($t['criado_em'])) ?></td>
                                <td class="text-end pe-4">
                                    <a href="ver_chamado.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    <?php if($nivel == 'admin' || $nivel == 'tecnico'): ?>
                                        <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir?')"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if(count($tickets) == 0): ?>
                        <div class="p-5 text-center text-muted">Nenhum chamado encontrado com este filtro.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-bold"><i class="fas fa-users text-success me-2"></i> Online [Atualiza a cada 5minutos.]</div>
                <div class="card-body">
                    <h6 class="text-muted small text-uppercase fw-bold mb-3">Equipe TI</h6>
                    <?php foreach($online_ti as $u): ?>
                        <div class="mb-2 d-flex align-items-center">
                            <span class="online-dot"></span> <?= $u['nome'] ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($online_ti)) echo "<small class='text-muted'>Ninguém da TI online.</small>"; ?>

                    <hr>
                    <h6 class="text-muted small text-uppercase fw-bold mb-3">Usuários</h6>
                    <?php foreach($online_users as $u): ?>
                        <div class="mb-2 d-flex align-items-center">
                            <span class="online-dot"></span> <?= $u['nome'] ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($online_users)) echo "<small class='text-muted'>Nenhum usuário online.</small>"; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>