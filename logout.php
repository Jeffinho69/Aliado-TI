<?php
// logout.php
session_start(); // Inicia a sessão para poder destruí-la

// 1. Apaga todas as variáveis de sessão ($_SESSION['user_id'], etc)
$_SESSION = array();

// 2. Apaga o cookie da sessão no navegador (se existir)
// Isso é importante para invalidar o ID da sessão totalmente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destrói a sessão no servidor
session_destroy();

// 4. Redireciona o usuário para a tela de login
header("Location: index.php");
exit;
?>