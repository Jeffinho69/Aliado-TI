<?php
session_start();
require '../config/db.php';

if (!isset($_GET['id'])) { header("Location: chamados.php"); exit; }
$ticket_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$nivel = $_SESSION['user_nivel'];

// --- EXCLUIR CHAMADO (Botão da Lixeira) ---
if (isset($_GET['delete']) && ($nivel == 'admin' || $nivel == 'tecnico')) {
    $pdo->prepare("DELETE FROM ticket_responses WHERE ticket_id = ?")->execute([$ticket_id]);
    $pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$ticket_id]);
    header("Location: chamados.php"); exit;
}

try {
    // 1. MUDAR TÉCNICO
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tecnico_id'])) {
        $novo_tec = $_POST['tecnico_id'];
        $nome_tec = $pdo->query("SELECT nome FROM users WHERE id = $novo_tec")->fetchColumn();
        $pdo->prepare("UPDATE tickets SET tecnico_id = ?, status = 'em_andamento' WHERE id = ?")->execute([$novo_tec, $ticket_id]);
        $pdo->prepare("INSERT INTO ticket_responses (ticket_id, user_id, mensagem) VALUES (?, ?, ?)")->execute([$ticket_id, $user_id, "🔴 <b>SISTEMA:</b> Atribuído ao técnico: <b>$nome_tec</b>"]);
        header("Location: ver_chamado.php?id=$ticket_id"); exit;
    }
    // 2. MUDAR PRIORIDADE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_prioridade'])) {
        $nova_prio = $_POST['nova_prioridade'];
        $pdo->prepare("UPDATE tickets SET prioridade = ? WHERE id = ?")->execute([$nova_prio, $ticket_id]);
        $pdo->prepare("INSERT INTO ticket_responses (ticket_id, user_id, mensagem) VALUES (?, ?, ?)")->execute([$ticket_id, $user_id, "⚠️ <b>SISTEMA:</b> Prioridade alterada para: <b>".strtoupper($nova_prio)."</b>"]);
        header("Location: ver_chamado.php?id=$ticket_id"); exit;
    }
    // 3. MENSAGEM
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
        $pdo->prepare("INSERT INTO ticket_responses (ticket_id, user_id, mensagem) VALUES (?, ?, ?)")->execute([$ticket_id, $user_id, $_POST['mensagem']]);
        header("Location: ver_chamado.php?id=$ticket_id"); exit;
    }
    // 4. RESOLVER
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'resolver') {
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'resolvido', resolucao_tipo = ?, resolucao_obs = ? WHERE id = ?");
        $stmt->execute([$_POST['resolucao_tipo'], $_POST['obs'], $ticket_id]);
        header("Location: ver_chamado.php?id=$ticket_id"); exit;
    }
} catch (PDOException $e) { die("Erro DB: " . $e->getMessage()); }

$ticket = $pdo->query("SELECT t.*, u.nome as autor, tec.nome as tecnico_nome FROM tickets t JOIN users u ON t.user_id = u.id LEFT JOIN users tec ON t.tecnico_id = tec.id WHERE t.id = $ticket_id")->fetch(PDO::FETCH_ASSOC);
$msgs = $pdo->query("SELECT r.*, u.nome, u.nivel FROM ticket_responses r JOIN users u ON r.user_id = u.id WHERE ticket_id = $ticket_id ORDER BY r.criado_em ASC")->fetchAll(PDO::FETCH_ASSOC);
$tecnicos = $pdo->query("SELECT id, nome FROM users WHERE nivel = 'admin' OR nivel = 'tecnico'")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $ticket['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <style>
        .msg-bubble { max-width: 80%; padding: 12px 15px; border-radius: 15px; position: relative; margin-bottom: 10px; }
        .msg-me { background-color: #0d6efd; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
        .msg-other { background-color: #f1f3f5; color: #212529; align-self: flex-start; border-bottom-left-radius: 2px; }
        .msg-system { background-color: #fff3cd; color: #856404; font-size: 0.85em; text-align: center; border-radius: 20px; padding: 5px 15px; margin: 10px auto; width: fit-content; border: 1px solid #ffeeba; }
        .chat-container { display: flex; flex-direction: column; }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="chamados.php" class="text-decoration-none text-muted fw-bold"><i class="fas fa-arrow-left"></i> Voltar</a>
            <h3 class="fw-bold mt-2">Chamado #<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['titulo']) ?></h3>
        </div>
        <div>
            <?php if($nivel != 'usuario'): ?>
                <a href="?id=<?= $ticket['id'] ?>&delete=1" class="btn btn-outline-danger" onclick="return confirm('Tem certeza que deseja EXCLUIR este chamado?')">
                    <i class="fas fa-trash-alt"></i> Excluir
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small fw-bold">Detalhes</h6>
                    
                    <div class="mb-3">
                        <label class="small text-muted">Solicitante</label>
                        <div class="fw-bold"><i class="fas fa-user-circle"></i> <?= $ticket['autor'] ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="small text-muted">Status</label><br>
                            <span class="badge bg-secondary"><?= strtoupper($ticket['status']) ?></span>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted">Prioridade</label><br>
                            <span class="badge bg-warning text-dark"><?= strtoupper($ticket['prioridade']) ?></span>
                        </div>
                    </div>

                    <?php if($ticket['anexo']): ?>
                        <div class="alert alert-info border-0 d-flex align-items-center">
                            <i class="fas fa-file-download fa-2x me-3"></i>
                            <div>
                                <small>Anexo disponível</small><br>
                                <a href="../uploads/<?= $ticket['anexo'] ?>" target="_blank" class="fw-bold text-decoration-none">Baixar Arquivo</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <label class="small text-muted">Descrição Inicial</label>
                    <div class="p-3 bg-light rounded text-muted small">
                        <?= nl2br($ticket['descricao']) ?>
                    </div>
                </div>
            </div>

            <?php if($nivel != 'usuario' && $ticket['status'] != 'resolvido'): ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold"><i class="fas fa-cogs"></i> Painel Técnico</div>
                <div class="card-body">
                    <form method="POST" class="mb-3">
                        <label class="small fw-bold">Atribuir Técnico</label>
                        <div class="input-group input-group-sm">
                            <select name="tecnico_id" class="form-select">
                                <option value="">Selecione...</option>
                                <?php foreach($tecnicos as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $ticket['tecnico_id']==$t['id']?'selected':'' ?>><?= $t['nome'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-primary">Salvar</button>
                        </div>
                    </form>

                    <form method="POST" class="mb-3">
                        <label class="small fw-bold">Mudar Gravidade</label>
                        <div class="input-group input-group-sm">
                            <select name="nova_prioridade" class="form-select">
                                <option value="baixa" <?= $ticket['prioridade']=='baixa'?'selected':'' ?>>Baixa</option>
                                <option value="media" <?= $ticket['prioridade']=='media'?'selected':'' ?>>Média</option>
                                <option value="alta" <?= $ticket['prioridade']=='alta'?'selected':'' ?>>Alta</option>
                                <option value="critica" <?= $ticket['prioridade']=='critica'?'selected':'' ?>>Crítica</option>
                            </select>
                            <button class="btn btn-outline-warning">Alterar</button>
                        </div>
                    </form>

                    <button class="btn btn-success w-100 fw-bold mt-2" data-bs-toggle="modal" data-bs-target="#modalResolver">
                        <i class="fas fa-check"></i> Concluir Atendimento
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="height: 600px; display: flex; flex-direction: column;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-comments text-primary"></i> Histórico de Interação</h6>
                </div>
                
                <div class="card-body chat-container" style="overflow-y: auto; flex: 1; background: #f8f9fa;">
                    <?php foreach($msgs as $m): 
                        $is_system = strpos($m['mensagem'], 'SISTEMA') !== false;
                        $is_me = ($m['user_id'] == $user_id);
                    ?>
                        <?php if($is_system): ?>
                            <div class="msg-system"><?= $m['mensagem'] ?></div>
                        <?php else: ?>
                            <div class="msg-bubble <?= $is_me ? 'msg-me' : 'msg-other' ?>">
                                <div class="d-flex justify-content-between small mb-1 opacity-75">
                                    <strong><?= $m['nome'] ?></strong>
                                    <span><?= date('H:i', strtotime($m['criado_em'])) ?></span>
                                </div>
                                <?= nl2br($m['mensagem']) ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <?php if($ticket['status'] != 'resolvido'): ?>
                <div class="card-footer bg-white p-3">
                    <form method="POST" class="d-flex gap-2">
                        <input type="text" name="mensagem" class="form-control" placeholder="Digite sua mensagem..." required autocomplete="off">
                        <button class="btn btn-primary px-4"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
                <?php else: ?>
                    <div class="card-footer bg-success text-white text-center fw-bold">
                        Chamado Encerrado.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalResolver" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="acao" value="resolver">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Finalizar Chamado</h5></div>
            <div class="modal-body">
                <label class="fw-bold">Como foi resolvido?</label>
                <select name="resolucao_tipo" class="form-select mb-3">
                    <option value="remoto">Resolvido Remotamente (TeamViewer/AnyDesk)</option>
                    <option value="presencial">Atendimento no Local</option>
                    <option value="orientacao">Apenas Orientação</option>
                </select>
                <label class="fw-bold">Observações Técnicas</label>
                <textarea name="obs" class="form-control" rows="3" placeholder="O que foi feito..." required></textarea>
            </div>
            <div class="modal-footer"><button class="btn btn-success w-100">Confirmar e Fechar</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>