<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }

$msg_erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $prioridade = $_POST['prioridade'];
    $user_id = $_SESSION['user_id'];
    $arquivo_nome = null;

    // Upload de Anexo
    if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['anexo']['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        
        if (in_array($ext, $permitidos)) {
            // Cria pasta se não existir
            if (!is_dir('../uploads')) { mkdir('../uploads', 0777, true); }

            $novo_nome = uniqid() . "." . $ext;
            $destino = "../uploads/" . $novo_nome;
            
            if (move_uploaded_file($_FILES['anexo']['tmp_name'], $destino)) {
                $arquivo_nome = $novo_nome;
            } else {
                $msg_erro = "Erro ao salvar arquivo no servidor.";
            }
        } else {
            $msg_erro = "Formato inválido. Use Imagens ou PDF.";
        }
    }

    if (empty($msg_erro)) {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, titulo, descricao, prioridade, anexo) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $titulo, $descricao, $prioridade, $arquivo_nome])) {
            header("Location: chamados.php");
            exit;
        } else {
            $msg_erro = "Erro ao registrar no banco de dados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Chamado - Aliado TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                
                <div class="d-flex align-items-center mb-4">
                    <a href="chamados.php" class="btn btn-outline-secondary me-3"><i class="fas fa-arrow-left"></i> Voltar</a>
                    <div>
                        <h3 class="fw-bold text-dark mb-0">Abrir Novo Chamado</h3>
                        <p class="text-muted small mb-0">Descreva o problema para que possamos ajudar.</p>
                    </div>
                </div>

                <?php if($msg_erro): ?>
                    <div class="alert alert-danger shadow-sm border-0 mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= $msg_erro ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-pen-square me-2"></i> Formulário de Solicitação</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted">Assunto / Título</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-heading text-secondary"></i></span>
                                    <input type="text" name="titulo" class="form-control form-control-lg" required placeholder="Ex: Impressora do RH não conecta" autofocus>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted">Prioridade</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-signal text-secondary"></i></span>
                                        <select name="prioridade" class="form-select">
                                            <option value="baixa">🟢 Baixa (Dúvida/Configuração)</option>
                                            <option value="media" selected>🟡 Média (Falha Parcial)</option>
                                            <option value="alta">🟠 Alta (Falha total no Setor)</option>
                                            <option value="critica">🔴 Crítica (Setor Parado)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted">Anexo (Opcional)</label>
                                    <input type="file" name="anexo" class="form-control">
                                    <small class="text-muted" style="font-size: 0.75rem">Prints, Fotos ou PDF (Max: 5MB)</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted">Descrição Detalhada</label>
                                <textarea name="descricao" class="form-control" rows="6" required placeholder="Explique o que aconteceu, mensagens de erro que apareceram, etc..."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm">
                                    <i class="fas fa-paper-plane me-2"></i> Enviar Solicitação
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>