<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Utilitários - Aliado TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between mb-4">
        <h3><i class="fas fa-toolbox text-secondary me-2"></i>Caixa de Ferramentas</h3>
        <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
    </div>

    <div class="alert alert-info shadow-sm border-0">
        <i class="fas fa-info-circle me-2"></i> <strong>Atenção:</strong> Os scripts (.bat/.ps1) serão baixados para sua máquina. Execute-os como Administrador.
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-dark text-white"><i class="fas fa-terminal me-2"></i>Scripts de Automação</div>
                <div class="list-group list-group-flush">
                    <a href="../assets/scripts/limpa_cache.bat" download class="list-group-item list-group-item-action py-3">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold text-primary">Limpeza de Cache Windows</h6>
                                <p class="mb-0 small text-muted">Limpa temp, prefetch e cache de updates.</p>
                            </div>
                            <span class="badge bg-secondary">.BAT</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white"><i class="fas fa-link me-2"></i>Downloads Externos</div>
                <div class="list-group list-group-flush">
                    <a href="https://anydesk.com/pt/downloads" target="_blank" class="list-group-item list-group-item-action py-3">
                        <div class="d-flex align-items-center">
                            <img src="https://anydesk.com/_static/img/favicon/favicon-32x32.png" width="24" class="me-3">
                            <div>
                                <h6 class="mb-0 fw-bold">AnyDesk</h6>
                                <small class="text-muted">Software de Acesso Remoto</small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>