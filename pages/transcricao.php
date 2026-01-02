<?php
session_start();
require '../config/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }
ini_set('max_execution_time', 300); 

$resultado_html = ""; 
$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pasta_uploads = __DIR__ . '/../uploads/';
    if (!is_dir($pasta_uploads)) { @mkdir($pasta_uploads, 0777, true); }

    if (isset($_FILES['arquivos_doc'])) {
        $arquivos_para_ia = [];
        $total_files = count($_FILES['arquivos_doc']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['arquivos_doc']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['arquivos_doc']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'])) {
                    $novo_nome = uniqid("doc_") . "." . $ext;
                    $destino = $pasta_uploads . $novo_nome;
                    if (move_uploaded_file($_FILES['arquivos_doc']['tmp_name'][$i], $destino)) {
                        $mime = ($ext == 'pdf') ? 'application/pdf' : 'image/jpeg';
                        $arquivos_para_ia[] = ['caminho' => $destino, 'mime' => $mime];
                    }
                }
            }
        }

        if (count($arquivos_para_ia) > 0) {
            $resultado_html = processarGemini($arquivos_para_ia);
            gravarLog($pdo, $_SESSION['user_id'], "IA Docs", "Digitalizou $total_files arquivos.");
            foreach ($arquivos_para_ia as $arq) { @unlink($arq['caminho']); }
        } else {
            $erro = "Selecione pelo menos um arquivo (PDF ou Imagem).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Digitalização - Câmara</title>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <style>
        /* CORREÇÃO DO VISUAL DO PAPEL */
        .paper-preview {
            background-color: white;
            padding: 2.5cm 2.5cm; /* Margens A4 */
            width: 100%;
            min-height: 800px;
            height: auto; /* IMPORTANTE: Cresce com o texto */
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border: 1px solid #d1d1d1;
            font-family: 'Calibri', 'Segoe UI', sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: #000;
            text-align: justify;
            margin-bottom: 50px; /* Espaço no final */
        }
        
        .upload-area { 
            border: 2px dashed #0d6efd; 
            background: #f8f9fa; 
            border-radius: 15px; 
            padding: 50px 20px; 
            text-align: center; 
            cursor: pointer; 
            transition: 0.3s; 
        }
        .upload-area:hover { background: #e9ecef; transform: scale(1.02); }
        
        .loading-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); z-index: 9999; align-items: center; justify-content: center; flex-direction: column; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status"></div>
    <h3 class="mt-4 fw-bold text-primary">Digitalizando...</h3>
    <p class="text-muted">Lendo documentos e formatando.</p>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark"><i class="fas fa-print text-primary me-2"></i>Digitalização Jurídica</h3>
            <p class="text-muted mb-0">Transforme PDFs e Imagens em Word formatado.</p>
        </div>
    </div>

    <?php if($erro): ?><div class="alert alert-danger shadow-sm border-0"><i class="fas fa-exclamation-triangle"></i> <?= $erro ?></div><?php endif; ?>

    <div class="row h-100">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="fas fa-file-upload me-2"></i> Novo Documento
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="alert alert-light border small text-muted mb-3">
                            <strong><i class="fas fa-magic"></i> IA Jurídica:</strong> 
                            Reconhece Ementas, Artigos e formata automaticamente para Word (Calibri 12).
                        </div>

                        <div class="upload-area" onclick="document.getElementById('filesDoc').click()">
                            <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                            <h5 class="fw-bold">Selecionar Arquivos</h5>
                            <p class="small text-muted mb-0">PDF (Várias pág.) ou Imagens [.jpg e .png]</p>
                            <input type="file" name="arquivos_doc[]" id="filesDoc" class="d-none" accept=".pdf, .jpg, .jpeg, .png" multiple onchange="updateCount()">
                        </div>
                        
                        <div id="docCount" class="mt-3 text-center fw-bold text-primary"></div>

                        <button class="btn btn-primary w-100 mt-3 py-3 fw-bold shadow-sm" onclick="startLoading()">
                            <i class="fas fa-bolt me-2"></i> Processar Arquivos
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100 bg-secondary bg-opacity-10">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-eye text-muted me-2"></i>Visualização</h6>
                    
                    <?php if($resultado_html && strpos($resultado_html, 'Erro') === false): ?>
                        <button class="btn btn-sm btn-primary fw-bold px-4 shadow-sm" onclick="downloadWord()">
                            <i class="fas fa-file-word me-2"></i> Baixar Word
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="card-body d-flex justify-content-center" style="overflow-y: auto; max-height: 85vh;">
                    
                    <?php if($resultado_html): ?>
                        <div id="previewHtml" class="paper-preview">
                            <?= $resultado_html ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted align-self-center opacity-50">
                            <i class="fas fa-file-contract fa-5x mb-3"></i>
                            <h5>Nenhum documento gerado</h5>
                            <p>Envie os arquivos ao lado para visualizar.</p>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function updateCount() {
        let count = document.getElementById('filesDoc').files.length;
        document.getElementById('docCount').innerHTML = count > 0 ? `<i class="fas fa-check-circle"></i> ${count} arquivo(s) selecionado(s)` : '';
    }
    function startLoading() { document.getElementById('loadingOverlay').style.display = 'flex'; }

    function downloadWord() {
        var content = document.getElementById("previewHtml").innerHTML;
        var header = `
            <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
            <head>
                <meta charset='utf-8'>
                <style>
                    body { font-family: 'Calibri', sans-serif; font-size: 12pt; }
                    p { margin: 0pt; margin-bottom: 10pt; line-height: 1.5; text-align: justify; }
                    .ementa, [style*="margin-left"] { margin-left: 7cm !important; text-align: justify; font-style: italic; }
                </style>
            </head>
            <body>`;
        var footer = "</body></html>";
        var source = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(header + content + footer);
        var link = document.createElement("a");
        link.href = source;
        link.download = 'Documento_Camara.doc';
        link.click();
    }
</script>
</body>
</html>