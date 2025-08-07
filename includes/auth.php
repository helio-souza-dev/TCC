<?php
session_start();
require_once 'config/database.php';

function login($email, $senha) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ? AND ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(password_verify($senha, $row['senha'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['nome'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_type'] = $row['tipo'];
            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function requireLogin() {
    if(!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if(!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
