<?php
session_start();
require '../config/db.php';

// --- 1. EXCLUIR ITEM ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM assets WHERE id = ?")->execute([$id]);
    header("Location: inventario.php"); exit;
}

// --- 2. SALVAR NOVO ITEM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'salvar') {
    $stmt = $pdo->prepare("INSERT INTO assets (nome, serial, localizacao, propriedade, categoria, status) VALUES (?, ?, ?, ?, ?, 'estoque')");
    $stmt->execute([$_POST['nome'], $_POST['serial'], $_POST['local'], $_POST['propriedade'], $_POST['categoria']]);
    header("Location: inventario.php"); exit;
}

// --- 3. EDITAR ITEM (NOVO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE assets SET nome=?, serial=?, localizacao=?, propriedade=?, categoria=? WHERE id=?");
    $stmt->execute([$_POST['nome'], $_POST['serial'], $_POST['local'], $_POST['propriedade'], $_POST['categoria'], $id]);
    header("Location: inventario.php"); exit;
}

// --- 4. DEVOLVER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'devolver') {
    $stmt = $pdo->prepare("UPDATE assets SET status = 'estoque', usuario_emprestado_id = NULL, usuario_atual = NULL WHERE id = ?");
    $stmt->execute([$_POST['asset_id']]);
    header("Location: inventario.php"); exit;
}

// --- 5. EMPRESTAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'emprestar') {
    $u_nome = $pdo->query("SELECT nome FROM users WHERE id = " . $_POST['user_id'])->fetchColumn();
    $stmt = $pdo->prepare("UPDATE assets SET status = 'em_uso', usuario_emprestado_id = ?, usuario_atual = ? WHERE id = ?");
    $stmt->execute([$_POST['user_id'], $u_nome, $_POST['asset_id']]);
    header("Location: inventario.php"); exit;
}

$ativos = $pdo->query("SELECT * FROM assets ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$usuarios = $pdo->query("SELECT id, nome FROM users ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Inventário - Aliado TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark"><i class="fas fa-boxes text-primary me-2"></i>Inventário de TI</h3>
            <p class="text-muted mb-0">Controle de Hardware, Serial e Empréstimos</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-plus me-2"></i>Novo Item
        </button>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-2">
            <div class="input-group">
                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="filtro" class="form-control border-0" placeholder="Pesquisar por Nome, Nº Série, Setor ou Usuário...">
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Equipamento / N. Série</th>
                        <th>Setor/Local</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody id="tabelaItens">
                    <?php foreach($ativos as $a): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($a['nome']) ?></div>
                            <small class="text-muted">
                                <i class="fas fa-barcode me-1 text-secondary"></i> <?= $a['serial'] ?: 'S/N' ?>
                            </small>
                        </td>
                        <td><?= htmlspecialchars($a['localizacao']) ?: '-' ?></td>
                        <td>
                            <?php if($a['propriedade'] == 'corporativo'): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Corporativo</span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning">Pessoal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($a['status'] == 'estoque'): ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Estoque</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-user me-1"></i> <?= htmlspecialchars($a['usuario_atual']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <a href="?delete=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger me-1" onclick="return confirm('Excluir item?')">
                                <i class="fas fa-trash"></i>
                            </a>

                            <button class="btn btn-sm btn-outline-info me-1" 
                                onclick="editarItem('<?= $a['id'] ?>', '<?= addslashes($a['nome']) ?>', '<?= $a['serial'] ?>', '<?= $a['localizacao'] ?>', '<?= $a['propriedade'] ?>', '<?= $a['categoria'] ?>')">
                                <i class="fas fa-pencil-alt"></i>
                            </button>

                            <?php if($a['status'] == 'estoque'): ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="abrirEmprestimo(<?= $a['id'] ?>, '<?= addslashes($a['nome']) ?>')">
                                    <i class="fas fa-hand-holding"></i>
                                </button>
                            <?php else: ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Devolver ao estoque?')">
                                    <input type="hidden" name="acao" value="devolver">
                                    <input type="hidden" name="asset_id" value="<?= $a['id'] ?>">
                                    <button class="btn btn-sm btn-warning text-dark">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="acao" value="salvar">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Novo Ativo</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label>Nome</label><input type="text" name="nome" class="form-control" required></div>
                <div class="row mb-2">
                    <div class="col-6"><label>Nº Série</label><input type="text" name="serial" class="form-control"></div>
                    <div class="col-6"><label>Setor</label><input type="text" name="local" class="form-control"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><label>Propriedade</label><select name="propriedade" class="form-select"><option value="corporativo">Corporativo</option><option value="pessoal">Pessoal</option></select></div>
                    <div class="col-6"><label>Tipo</label><select name="categoria" class="form-select"><option>Notebook</option><option>Monitor</option><option>Periférico</option></select></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Salvar</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header bg-info text-white"><h5 class="modal-title">Editar Ativo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label>Nome</label><input type="text" name="nome" id="edit_nome" class="form-control" required></div>
                <div class="row mb-2">
                    <div class="col-6"><label>Nº Série</label><input type="text" name="serial" id="edit_serial" class="form-control"></div>
                    <div class="col-6"><label>Setor</label><input type="text" name="local" id="edit_local" class="form-control"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><label>Propriedade</label><select name="propriedade" id="edit_propriedade" class="form-select"><option value="corporativo">Corporativo</option><option value="pessoal">Pessoal</option></select></div>
                    <div class="col-6"><label>Tipo</label><select name="categoria" id="edit_categoria" class="form-select"><option>Notebook</option><option>Monitor</option><option>Periférico</option></select></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-info text-white fw-bold">Atualizar</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEmprestimo" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="acao" value="emprestar">
            <input type="hidden" name="asset_id" id="emp_asset_id">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Empréstimo</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>Item: <b id="emp_nome_item"></b></p>
                <select name="user_id" class="form-select" required>
                    <option value="">Selecione o Usuário...</option>
                    <?php foreach($usuarios as $u): ?><option value="<?= $u['id'] ?>"><?= $u['nome'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer"><button class="btn btn-success">Confirmar</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Filtro
    document.getElementById('filtro').addEventListener('keyup', function() {
        let termo = this.value.toLowerCase();
        document.querySelectorAll('#tabelaItens tr').forEach(l => {
            l.style.display = l.innerText.toLowerCase().includes(termo) ? '' : 'none';
        });
    });

    // Função para preencher o modal de edição
    function editarItem(id, nome, serial, local, propriedade, categoria) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nome').value = nome;
        document.getElementById('edit_serial').value = serial;
        document.getElementById('edit_local').value = local;
        document.getElementById('edit_propriedade').value = propriedade;
        document.getElementById('edit_categoria').value = categoria;
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    function abrirEmprestimo(id, nome) {
        document.getElementById('emp_asset_id').value = id;
        document.getElementById('emp_nome_item').innerText = nome;
        new bootstrap.Modal(document.getElementById('modalEmprestimo')).show();
    }
</script>
</body>
</html>