<?php
// Exibir erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require '../config/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }
if (!isset($_SESSION['chat_history'])) { $_SESSION['chat_history'] = []; }

if (isset($_POST['limpar'])) {
    $_SESSION['chat_history'] = [];
    header("Location: chat_ia.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
    $msg = trim($_POST['mensagem']);
    $caminho_imagem = null;
    $anexo_para_ticket = null;

    // Upload de Imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $novo_nome = uniqid("chat_") . "." . $ext;
            $destino = "../uploads/" . $novo_nome;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                $caminho_imagem = $destino;
                $anexo_para_ticket = $novo_nome; 
            }
        }
    }

    if (!empty($msg) || $caminho_imagem) {
        $nome_atual = $_SESSION['user_nome'] ?? 'Colega';
        
        // Histórico
        $texto_user = $msg;
        if ($caminho_imagem) { $texto_user .= " <br><small><i>[📎 Imagem enviada]</i></small>"; }
        $_SESSION['chat_history'][] = ['tipo' => 'user', 'texto' => $texto_user];

        // CHAMA A IA
        $resposta_raw = chatGemini($msg, $_SESSION['chat_history'], $nome_atual, $caminho_imagem);
        
        // DETECTOR DE JSON (ABRIR CHAMADO)
        if (strpos($resposta_raw, 'abrir_chamado') !== false && strpos($resposta_raw, '{') !== false) {
            
            // Extração segura do JSON
            preg_match('/\{.*\}/s', $resposta_raw, $matches);
            $json_str = $matches[0] ?? '';
            
            $dados = json_decode($json_str, true);

            if ($dados && isset($dados['titulo'])) {
                try {
                    $prio = strtolower($dados['prioridade']);
                    if (!in_array($prio, ['baixa', 'media', 'alta', 'critica'])) { $prio = 'media'; }

                    $anexo_sql = $anexo_para_ticket ? $anexo_para_ticket : null;

                    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, titulo, descricao, prioridade, anexo) VALUES (?, ?, ?, ?, ?)");
                    
                    if($stmt->execute([
                        $_SESSION['user_id'], 
                        $dados['titulo'], 
                        $dados['descricao'], 
                        $prio,
                        $anexo_sql
                    ])) {
                        $novo_id = $pdo->lastInsertId();
                        
                        // Resposta bonita confirmando a triagem
                        $resposta = "✅ **Chamado #$novo_id Aberto!**\n\n" .
                                    "**Assunto:** " . $dados['titulo'] . "\n" .
                                    "**Prioridade:** " . ucfirst($prio) . "\n" .
                                    "**Resumo Técnico:**\n" . $dados['descricao'] . "\n\n" .
                                    "A equipe já foi notificada.";
                        
                        $caminho_imagem = null; // Não apaga se usou
                    } else {
                        $resposta = "❌ Erro ao salvar ticket no banco.";
                    }
                } catch (Exception $e) {
                    $resposta = "❌ Erro técnico: " . $e->getMessage();
                }
            } else {
                $resposta = $resposta_raw; // Falha no JSON, mostra texto normal
            }
        } else {
            $resposta = $resposta_raw; 
        }

        if ($caminho_imagem) { @unlink($caminho_imagem); }
        
        $_SESSION['chat_history'][] = ['tipo' => 'bot', 'texto' => $resposta];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Aliada TI - Chat</title>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .chat-area { height: 60vh; overflow-y: auto; padding: 20px; background: #fff; border-radius: 10px; border: 1px solid #e9ecef; }
        .msg { margin-bottom: 20px; display: flex; flex-direction: column; }
        .msg-user { align-items: flex-end; }
        .msg-bot { align-items: flex-start; }
        .bubble { max-width: 80%; padding: 15px 20px; border-radius: 18px; font-size: 15px; line-height: 1.6; position: relative; }
        .bubble-user { background-color: #0d6efd; color: white; border-bottom-right-radius: 4px; }
        .bubble-bot { background-color: #f1f3f5; color: #212529; border-bottom-left-radius: 4px; }
        .suggestion-btn { font-size: 0.85rem; border: 1px solid #dee2e6; background: white; color: #495057; padding: 8px 15px; border-radius: 20px; margin-bottom: 8px; display: flex; align-items: center; width: 100%; cursor: pointer; transition:0.2s; }
        .suggestion-btn:hover { background: #e9ecef; border-color: #adb5bd; }
        .suggestion-btn i { margin-right: 10px; color: #0d6efd; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold text-dark"><i class="fas fa-robot text-primary me-2"></i>Aliada TI</h3>
            <p class="text-muted mb-0">Sua assistente virtual na Câmara.</p>
        </div>
        <form method="POST">
            <button name="limpar" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Nova Conversa</button>
        </form>
    </div>

    <div class="row">
        <div class="col-md-9">
            <div class="chat-area shadow-sm" id="chatBox">
                <?php if (empty($_SESSION['chat_history'])): ?>
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted opacity-50">
                        <i class="fas fa-robot fa-4x mb-3 text-info"></i>
                        <h5>Olá, <?= htmlspecialchars($_SESSION['user_nome']) ?>!</h5>
                        <p>Posso tirar dúvidas ou <strong>abrir chamados</strong> para você.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($_SESSION['chat_history'] as $msg): ?>
                    <div class="msg <?= $msg['tipo'] == 'user' ? 'msg-user' : 'msg-bot' ?>">
                        <div class="small text-muted mb-1 ms-1 me-1"><?= $msg['tipo'] == 'user' ? 'Você' : 'Aliada TI' ?></div>
                        <div class="bubble <?= $msg['tipo'] == 'user' ? 'bubble-user' : 'bubble-bot' ?>">
                            <?php if($msg['tipo'] == 'bot'): ?>
                                <div class="markdown-content"><?= $msg['texto'] ?></div>
                            <?php else: ?>
                                <?= $msg['texto'] ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div id="loader" style="display:none;" class="msg msg-bot">
                    <div class="bubble bubble-bot"><i class="fas fa-circle-notch fa-spin"></i> Processando...</div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="mt-3" onsubmit="document.getElementById('loader').style.display='flex'">
                <div id="filePreview" class="small text-success fw-bold mb-2 ms-2" style="display:none;">
                    <i class="fas fa-image"></i> <span id="fileName">imagem.jpg</span>
                </div>
                <div class="input-group input-group-lg shadow-sm">
                    <label for="imgUpload" class="btn btn-light border" title="Enviar Print"><i class="fas fa-paperclip text-muted"></i></label>
                    <input type="file" name="imagem" id="imgUpload" class="d-none" accept="image/*" onchange="showFile(this)">
                    <input type="text" name="mensagem" id="inputMsg" class="form-control border-0" placeholder="Ex: A impressora do RH parou..." autocomplete="off">
                    <button class="btn btn-primary px-4"><i class="fas fa-paper-plane"></i></button>
                </div>
            </form>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3">🤖 Comandos Rápidos</div>
                <div class="card-body bg-light">
                    <button class="suggestion-btn" type="button" onclick="usarPrompt('Abra um chamado: Minha internet caiu.')">
                        <i class="fas fa-ticket-alt"></i> Abrir Chamado
                    </button>
                    <button class="suggestion-btn" type="button" onclick="usarPrompt('Corrija este texto formalmente: ')">
                        <i class="fas fa-pen-nib"></i> Corrigir Texto
                    </button>
                    <button class="suggestion-btn" type="button" onclick="usarPrompt('Analise este erro na imagem e abra um chamado.')">
                        <i class="fas fa-image"></i> Analisar Erro
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var chatBox = document.getElementById("chatBox");
    chatBox.scrollTop = chatBox.scrollHeight;

    function usarPrompt(texto) {
        document.getElementById("inputMsg").value = texto;
        document.getElementById("inputMsg").focus();
    }

    function showFile(input) {
        if(input.files && input.files[0]) {
            document.getElementById('filePreview').style.display = 'block';
            document.getElementById('fileName').innerText = input.files[0].name;
        }
    }

    document.querySelectorAll('.markdown-content').forEach(el => {
        el.innerHTML = marked.parse(el.innerText);
    });
</script>

</body>
</html>