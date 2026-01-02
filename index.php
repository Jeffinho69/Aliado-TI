<?php
session_start();
if (isset($_SESSION['user_id'])) { 
    header("Location: pages/chamados.php"); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Câmara Municipal</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%; margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            /* Fundo institucional (Pode trocar por uma foto da fachada da câmara se quiser) */
            background: linear-gradient(rgba(10, 25, 47, 0.85), rgba(10, 25, 47, 0.9)), url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            display: flex; align-items: center; justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            width: 90%; max-width: 400px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            color: white; text-align: center;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white; border-radius: 8px; height: 45px; padding-left: 40px;
        }
        .form-control:focus { background: rgba(255, 255, 255, 0.2); color: white; border-color: #00d2ff; box-shadow: none; }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.6); }
        .input-icon { position: absolute; left: 15px; top: 14px; color: rgba(255, 255, 255, 0.6); }
        .btn-login {
            background: linear-gradient(45deg, #00d2ff, #3a7bd5);
            border: none; height: 45px; border-radius: 8px; font-weight: bold; width: 100%; margin-top: 10px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 210, 255, 0.4); }
    </style>
</head>
<body>
    <div class="login-card">
        
        <div class="mb-4">
            <img src="assets/img/camara.png" alt="Logo Câmara" style="height: 80px; width: auto;" class="mb-3">
            <h5 class="fw-bold text-uppercase mb-0">Câmara Municipal</h5>
            <small class="text-white-50">Portal do Servidor</small>
        </div>

        <div class="mb-4 pb-3 border-bottom border-light border-opacity-25">
            <span class="badge bg-info bg-opacity-25 text-white border border-info border-opacity-50">
                <i class="fas fa-shield-alt"></i> Sistema Aliado TI
            </span>
        </div>

        <?php if(isset($_GET['erro'])): ?>
            <div class="alert alert-danger py-2 text-center small border-0 bg-danger bg-opacity-25 text-white">
                Credenciais inválidas.
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST" class="text-start">
            <div class="mb-3 position-relative">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-control" placeholder="E-mail Institucional" required>
            </div>
            <div class="mb-3 position-relative">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="senha" class="form-control" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn btn-login text-white">ACESSAR PAINEL</button>
        </form>
        
        <div class="mt-4 text-white-50 small">
            Esqueceu a senha? <p>Procure algum disponivel na sala de TI.
        </div>
    </div>
</body>
</html>