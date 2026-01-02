<?php
session_start();
require '../config/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] == 'usuario') {
    header("Location: chamados.php"); exit;
}

$msg = "";

// 1. SALVAR NOVA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'salvar') {
    $senha_segura = protegerSenha($_POST['senha']);
    $stmt = $pdo->prepare("INSERT INTO passwords (titulo, login_user, senha_encrypted, serial_equipamento, iv_hash) VALUES (?, ?, ?, ?, 'aes')");
    $stmt->execute([$_POST['titulo'], $_POST['login'], $senha_segura, $_POST['serial']]);
    $msg = "Credencial salva!";
    gravarLog($pdo, $_SESSION['user_id'], "Cofre", "Criou senha: " . $_POST['titulo']);
}

// 2. EDITAR EXISTENTE (NOVO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $login = $_POST['login'];
    $serial = $_POST['serial'];
    $senha_nova = $_POST['senha'];

    if (!empty($senha_nova)) {
        // Se digitou senha nova, criptografa e atualiza tudo
        $senha_segura = protegerSenha($senha_nova);
        $stmt = $pdo->prepare("UPDATE passwords SET titulo=?, login_user=?, serial_equipamento=?, senha_encrypted=? WHERE id=?");
        $stmt->execute([$titulo, $login, $serial, $senha_segura, $id]);
    } else {
        // Se senha vazia, mantém a antiga
        $stmt = $pdo->prepare("UPDATE passwords SET titulo=?, login_user=?, serial_equipamento=? WHERE id=?");
        $stmt->execute([$titulo, $login, $serial, $id]);
    }
    $msg = "Credencial atualizada!";
    gravarLog($pdo, $_SESSION['user_id'], "Cofre", "Editou senha: " . $titulo);
}

// 3. EXCLUIR
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM passwords WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: cofre.php"); exit;
}

$senhas = $pdo->query("SELECT * FROM passwords ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cofre - Aliado TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    
    <?php if($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark"><i class="fas fa-key text-warning me-2"></i>Cofre de Senhas</h3>
            <p class="text-muted mb-0">Gestão Segura de Credenciais</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-plus-circle me-2"></i>Nova Senha
        </button>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-2">
            <div class="input-group">
                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="filtroSenha" class="form-control border-0" placeholder="Pesquisar...">
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Serviço</th>
                        <th>Nº Série</th>
                        <th>Login</th>
                        <th>Senha</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody id="listaSenhas">
                    <?php foreach($senhas as $s): $senha_real = revelarSenha($s['senha_encrypted']); ?>
                    <tr class="linha-senha">
                        <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($s['titulo']) ?></td>
                        <td>
                            <?php if($s['serial_equipamento']): ?>
                                <span class="badge bg-secondary text-white font-monospace"><?= $s['serial_equipamento'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-bold text-dark"><?= htmlspecialchars($s['login_user']) ?></span></td>
                        <td>
                            <div class="input-group input-group-sm" style="max-width: 180px;">
                                <input type="password" class="form-control" value="<?= $senha_real ?>" id="pass_<?= $s['id'] ?>" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePass(<?= $s['id'] ?>)"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyPass('<?= $senha_real ?>')"><i class="fas fa-copy"></i></button>
                            </div>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-info me-1" onclick="editarSenha('<?= $s['id'] ?>', '<?= addslashes($s['titulo']) ?>', '<?= addslashes($s['login_user']) ?>', '<?= $s['serial_equipamento'] ?>')">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir?')"><i class="fas fa-trash"></i></a>
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
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Nova Credencial</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="fw-bold">Título</label><input type="text" name="titulo" class="form-control" required></div>
                <div class="mb-3"><label class="fw-bold">Login</label><input type="text" name="login" class="form-control" required></div>
                <div class="mb-3"><label class="fw-bold">Nº Série</label><input type="text" name="serial" class="form-control"></div>
                <div class="mb-3"><label class="fw-bold">Senha</label><input type="text" name="senha" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary w-100">Salvar</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header bg-info text-white"><h5 class="modal-title">Editar Credencial</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="fw-bold">Título</label><input type="text" name="titulo" id="edit_titulo" class="form-control" required></div>
                <div class="mb-3"><label class="fw-bold">Login</label><input type="text" name="login" id="edit_login" class="form-control" required></div>
                <div class="mb-3"><label class="fw-bold">Nº Série</label><input type="text" name="serial" id="edit_serial" class="form-control"></div>
                <div class="mb-3">
                    <label class="fw-bold">Nova Senha</label>
                    <input type="text" name="senha" class="form-control" placeholder="Deixe em branco para não alterar">
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-info text-white w-100">Atualizar</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('filtroSenha').addEventListener('keyup', function() {
        let termo = this.value.toLowerCase();
        document.querySelectorAll('.linha-senha').forEach(l => {
            l.style.display = l.innerText.toLowerCase().includes(termo) ? '' : 'none';
        });
    });
    function togglePass(id) {
        let input = document.getElementById('pass_' + id);
        input.type = input.type === "password" ? "text" : "password";
    }
    function copyPass(senha) {
        navigator.clipboard.writeText(senha);
        alert("Senha copiada!");
    }
    // Preenche o modal de edição
    function editarSenha(id, titulo, login, serial) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_titulo').value = titulo;
        document.getElementById('edit_login').value = login;
        document.getElementById('edit_serial').value = serial;
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
</script>
</body>
</html>