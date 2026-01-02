<?php
// fix_admin.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/config/db.php';

echo "<h2>Diagnóstico de Login</h2>";

// 1. Tenta conectar
if ($pdo) {
    echo "<p style='color:green'>✅ Conexão com Banco de Dados: OK!</p>";
} else {
    echo "<p style='color:red'>❌ Falha na conexão.</p>";
    exit;
}

// 2. Gera uma senha nova real
$senha_plana = "admin123";
$nova_hash = password_hash($senha_plana, PASSWORD_DEFAULT);

// 3. Atualiza ou Cria o Admin
$email = "admin@aliadoti.com";

// Verifica se já existe
$busca = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$busca->bindValue(':email', $email);
$busca->execute();

if ($busca->rowCount() > 0) {
    // Atualiza a senha do existente
    $sql = "UPDATE users SET senha = :senha, nivel = 'admin' WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':senha', $nova_hash);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    echo "<p style='color:blue'>🔄 Usuário Admin encontrado. Senha ATUALIZADA para: <b>admin123</b></p>";
} else {
    // Cria do zero se não existir
    $sql = "INSERT INTO users (nome, email, senha, nivel) VALUES ('Admin', :email, :senha, 'admin')";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':senha', $nova_hash);
    $stmt->execute();
    echo "<p style='color:green'>✅ Novo Usuário Admin CRIADO. Senha: <b>admin123</b></p>";
}

echo "<br><hr><br>";
echo "<h3>Teste Final:</h3>";
echo "Agora tente logar com:<br>";
echo "Email: <b>jefferson1990miguel@gmail.com</b><br>";
echo "Senha: <b>admin123</b><br>";
echo "<br><a href='index.php'>Clique aqui para ir ao Login</a>";
?>