<?php
session_start();
require __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {
        // Salva dados na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_nivel'] = strtolower($user['nivel']);
        
        // Atualiza horário de login (Para o recurso "Online Agora")
        $pdo->prepare("UPDATE users SET ultimo_acesso = NOW() WHERE id = ?")->execute([$user['id']]);

        // --- AQUI ESTÁ A MUDANÇA: Manda direto para CHAMADOS ---
        header("Location: pages/chamados.php");
        exit;
    } else {
        header("Location: index.php?erro=1");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}