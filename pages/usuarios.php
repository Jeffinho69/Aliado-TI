<?php
session_start();
require '../config/db.php';

// Segurança
if (!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: dashboard.php"); exit;
}

// Lógica de EXCLUIR
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Impede excluir a si mesmo
    if ($id != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }
    header("Location: usuarios.php"); exit;
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Usuários - Aliado TI</title>
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
            <h3 class="fw-bold text-dark"><i class="fas fa-users-cog text-primary me-2"></i>Gerenciar Equipe</h3>
            <p class="text-muted mb-0">Controle de acesso e permissões.</p>
        </div>
        <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="limparForm()">
            <i class="fas fa-user-plus me-2"></i>Novo Usuário
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Usuário</th>
                            <th>Email</th>
                            <th>Nível de Acesso</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usuarios as $user): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="fw-bold"><?= $user['nome'] ?></span>
                                </div>
                            </td>
                            <td class="text-muted"><?= $user['email'] ?></td>
                            <td>
                                <?php 
                                    $badge = match($user['nivel']) {
                                        'admin' => 'bg-danger',
                                        'tecnico' => 'bg-primary',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <span class="badge <?= $badge ?> rounded-pill px-3"><?= strtoupper($user['nivel']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editarUsuario(<?= $user['id'] ?>, '<?= $user['nome'] ?>', '<?= $user['email'] ?>', '<?= $user['nivel'] ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="usuario_acao.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Dados do Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="user_id">
                    <div class="mb-3">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" id="user_nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>E-mail de Login</label>
                        <input type="email" name="email" id="user_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Nível de Permissão</label>
                        <select name="nivel" id="user_nivel" class="form-select">
                            <option value="usuario">Usuário (Abre chamados)</option>
                            <option value="tecnico">Técnico (Resolve chamados)</option>
                            <option value="admin">Admin (Acesso total)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Senha <small class="text-muted">(Deixe em branco para manter a atual)</small></label>
                        <input type="password" name="senha" class="form-control" placeholder="******">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editarUsuario(id, nome, email, nivel) {
        document.getElementById('user_id').value = id;
        document.getElementById('user_nome').value = nome;
        document.getElementById('user_email').value = email;
        document.getElementById('user_nivel').value = nivel;
        new bootstrap.Modal(document.getElementById('modalUsuario')).show();
    }
    function limparForm() {
        document.getElementById('user_id').value = '';
        document.getElementById('user_nome').value = '';
        document.getElementById('user_email').value = '';
        document.getElementById('user_nivel').value = 'usuario';
    }
</script>
</body>
</html>