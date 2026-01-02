<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user_nivel'] == 'admin') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $nivel = $_POST['nivel'];
    $senha = $_POST['senha'];

    if (!empty($id)) {
        if (!empty($senha)) {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nome=?, email=?, nivel=?, senha=? WHERE id=?");
            $stmt->execute([$nome, $email, $nivel, $hash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nome=?, email=?, nivel=? WHERE id=?");
            $stmt->execute([$nome, $email, $nivel, $id]);
        }
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nome, email, nivel, senha) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $nivel, $hash]);
    }

    header("Location: usuarios.php");
    exit;
}