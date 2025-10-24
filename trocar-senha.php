<?php
// Inclui o guardião. 
// Ele vai garantir que só usuários logados E que precisam trocar a senha cheguem aqui.
require_once 'config/database.php';
require_once 'includes/auth.php'; 

$error = '';
$message = '';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$nova_senha = $_POST['nova_senha'] ?? '';
 $confirma_senha = $_POST['confirma_senha'] ?? '';


if (empty($nova_senha) || strlen($nova_senha) < 8) {
 $error = "A nova senha deve ter no mínimo 8 caracteres.";
 } elseif ($nova_senha !== $confirma_senha) {
 $error = "As senhas não conferem. Tente novamente.";
 } else {
 // Sucesso! Atualizar o banco
 try {
 $hashedPassword = password_hash($nova_senha, PASSWORD_DEFAULT);
 $user_id = $_SESSION['user_id'];


 $sql_update = "UPDATE usuarios SET senha = ?, forcar_troca_senha = 0 WHERE id = ?";
 executar_consulta($conn, $sql_update, [$hashedPassword, $user_id]);

 $message = "Senha alterada com sucesso! Você será redirecionado para o painel em 3 segundos.";
 
// Redireciona para o dashboard após um pequeno delay
 header("Refresh: 3; url=dashboard.php");

 } catch (Exception $e) {
 $error = "Erro ao atualizar a senha: " . $e->getMessage();
 }
 }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
 <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Troca Obrigatória de Senha</title>
<link rel="stylesheet" href="assets/css/style.css"> 
<style>
 body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f4f4; }
 .login-container { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
 .form-group { margin-bottom: 1rem; }
 .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
 .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
 </style>
</head>
<body>
 <div class="login-container">
 <h2>Alteração de Senha Obrigatória</h2>
 <p>Este é o seu primeiro acesso ou sua senha foi redefinida. Por favor, crie uma nova senha.</p>
 
 <?php if($message): ?>
 <div class="alert alert-success"><?php echo $message; ?></div>
 <?php endif; ?>
 <?php if($error): ?>
<div class="alert alert-error"><?php echo $error; ?></div>
 <?php endif; ?>

 <?php if(empty($message)): // Só mostra o formulário se a senha ainda não foi alterada ?>
 <form method="POST">
 <div class="form-group">
  <label for="nova_senha">Nova Senha (mín. 8 caracteres):</label>
  <input type="password" id="nova_senha" name="nova_senha" required>
 </div>
 <div class="form-group">
  <label for="confirma_senha">Confirme a Nova Senha:</label>
  <input type="password" id="confirma_senha" name="confirma_senha" required>
 </div>
 <button type="submit" class="btn btn-primary" style="width: 100%;">Salvar Nova Senha</button>
 </form>
 <?php endif; ?>
 </div>
</body>
</html>