<?php

require_once '../config/database.php';


$senha_protecao = 'senha123'; 
$senha_fornecida = $_GET['senha'] ?? ''; 

// 1. Verifica se a senha bate
if ($senha_fornecida !== $senha_protecao) {
    die("<div style='color: red; font-family: sans-serif; text-align: center; margin-top: 50px;'>
            <h1>Acesso Negado </h1>
            <p>Este script é protegido. Você precisa fornecer a senha correta</p>
            
         </div>");
}


$nome = "Admin de Recuperação";
$email = "admin_novo@sistema.com"; 
$senha_usuario = "senha123";         



try {

    $checkSql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        die("<div style='color: orange; font-family: sans-serif; padding: 20px; text-align: center;'>
                 O usuário <strong>$email</strong> já existe no banco de dados.
             </div>");
    }

 
    $senha_hash = password_hash($senha_usuario, PASSWORD_DEFAULT);


    $sql = "INSERT INTO usuarios (nome, email, senha, tipo, ativo, forcar_troca_senha, created_at) 
            VALUES (?, ?, ?, 'admin', 1, 0, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $email, $senha_hash);

    if ($stmt->execute()) {
        echo "<div style='color: green; font-family: sans-serif; padding: 20px; border: 1px solid green; border-radius: 5px; background: #e8f5e9; max-width: 600px; margin: 20px auto;'>
                <h2 style='margin-top:0;'> Sucesso!</h2>
                <p>O script foi autenticado e o usuário Administrador foi criado.</p>
                <hr>
                <ul>
                    <li><strong>Login:</strong> $email</li>
                    <li><strong>Senha:</strong> $senha_usuario</li>
                </ul>
                <p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir para Login</a></p>
                <p style='color: red; font-size: 0.9em; margin-top: 20px;'><strong>IMPORTANTE:</strong> Apague este arquivo do servidor agora.</p>
              </div>";
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    echo "<div style='color: red; font-family: sans-serif;'>
            Erro ao criar usuário: " . $e->getMessage() . 
         "</div>";
}
?>