<?php
//db.php
$host = ''; 
$dbname = ''; 
$username = ''; 
$password = '';    
     
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- NOVO: Rastrear Online ---
    // Se o usuário estiver logado, atualiza o horário dele agora
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE users SET ultimo_acesso = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }

} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>