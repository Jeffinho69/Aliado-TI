<?php
session_start();
require '../config/db.php';
if ($_SESSION['user_nivel'] == 'usuario') { header("Location: dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Manutenção Preventiva - Aliado TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <style>
        /* Estilos específicos para impressão */
        @media print {
            .sidebar, .btn-print, .tutorial-area, .page-header { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .card { border: 1px solid #000 !important; box-shadow: none !important; }
            .form-check-input { border: 1px solid #000; }
            body { background: white; -webkit-print-color-adjust: exact; }
            .signature-area { display: block !important; margin-top: 50px; }
        }
        .signature-area { display: none; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    
    <div class="d-flex justify-content-between mb-4 page-header">
        <div>
            <h3><i class="fas fa-clipboard-check text-success me-2"></i>Manutenção Preventiva</h3>
            <p class="text-muted">Realize os procedimentos e gere o relatório técnico.</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-print"><i class="fas fa-print me-2"></i>Imprimir Relatório</button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm p-4">
                <div class="text-center mb-4 border-bottom pb-3">
                    <h4 class="fw-bold">RELATÓRIO TÉCNICO - ALIADO TI</h4>
                    <p class="mb-0">Técnico Responsável: <strong><?= $_SESSION['user_nome'] ?></strong></p>
                    <p>Data da Execução: <strong><?= date('d/m/Y') ?></strong></p>
                </div>

                <form id="checkForm">
                    <h6 class="bg-light p-2 border fw-bold mb-3">1. Hardware e Limpeza</h6>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" id="check1">
                        <label class="form-check-label" for="check1">Limpeza física externa (Gabinete/Monitor)</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" id="check2">
                        <label class="form-check-label" for="check2">Verificação de ruídos nos Coolers</label>
                    </div>
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="check3">
                        <label class="form-check-label" for="check3">Organização de cabos</label>
                    </div>

                    <h6 class="bg-light p-2 border fw-bold mb-3">2. Sistema e Segurança</h6>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" id="check4">
                        <label class="form-check-label" for="check4">Atualizações do Windows (Windows Update)</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" id="check5">
                        <label class="form-check-label" for="check5">Verificação de Antivírus (Scan Rápido)</label>
                    </div>
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="check6">
                        <label class="form-check-label" for="check6">Limpeza de Arquivos Temporários</label>
                    </div>

                    <h6 class="bg-light p-2 border fw-bold mb-3">3. Observações Técnicas</h6>
                    <textarea class="form-control" rows="4" placeholder="Descreva problemas encontrados ou peças que precisam de troca..."></textarea>
                </form>

                <div class="signature-area text-center">
                    <div class="row mt-5">
                        <div class="col-6">
                            ___________________________________<br>
                            Assinatura do Técnico
                        </div>
                        <div class="col-6">
                            ___________________________________<br>
                            Visto do Cliente/Gestor
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5 tutorial-area">
            <div class="card bg-dark text-white mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-graduation-cap text-warning"></i> Guia de Execução</h5>
                    <p class="small">Dúvidas em como realizar a manutenção? Consulte os guias abaixo.</p>
                </div>
            </div>

            <div class="accordion" id="accordionTuto">
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tuto1">
                            🛠️ Como fazer Limpeza de Disco?
                        </button>
                    </h2>
                    <div id="tuto1" class="accordion-collapse collapse" data-bs-parent="#accordionTuto">
                        <div class="accordion-body">
                            <p>1. Pressione <strong>Win + R</strong>.</p>
                            <p>2. Digite <code>cleanmgr</code> e dê Enter.</p>
                            <p>3. Selecione o Disco C: e marque "Arquivos Temporários".</p>
                            <div class="ratio ratio-16x9 mt-2">
                                <iframe src="https://www.youtube.com/embed/BnYqD8h8fXM" title="Limpeza de Disco" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tuto2">
                            🔄 Verificar Windows Update
                        </button>
                    </h2>
                    <div id="tuto2" class="accordion-collapse collapse" data-bs-parent="#accordionTuto">
                        <div class="accordion-body">
                            <p>Vá em <strong>Configurações > Atualização e Segurança</strong> e clique em "Verificar se há atualizações".</p>
                            <p class="text-danger small">Atenção: Reinicie o PC se necessário.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tuto3">
                            🧹 Limpeza Física
                        </button>
                    </h2>
                    <div id="tuto3" class="accordion-collapse collapse" data-bs-parent="#accordionTuto">
                        <div class="accordion-body">
                            <p>Use pincel anti-estático para a placa mãe e ar comprimido para a fonte. Não use pano úmido nos componentes internos.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>