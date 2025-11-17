<?php

require_once 'config/database.php';
require_once 'includes/auth.php'; 

$error = '';
$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';


    if (empty($nova_senha) || strlen($nova_senha) < 8) {
        $error = "A nova senha deve ter no mínimo 8 caracteres.";
    } elseif ($nova_senha !== $confirma_senha) {
        $error = "As senhas não conferem. Tente novamente.";
    } else {
        
        try {
            $hashedPassword = password_hash($nova_senha, PASSWORD_DEFAULT);
            $user_id = $_SESSION['user_id'];

            $sql_update = "UPDATE usuarios SET senha = ?, forcar_troca_senha = 0 WHERE id = ?";
            executar_consulta($conn, $sql_update, [$hashedPassword, $user_id]);

            $message = "Senha alterada com sucesso! Você será redirecionado para o painel em 3 segundos.";
            
            
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
    

    <link rel="stylesheet" href="assets/style.css"> 
    

    <style>
        body {
          margin: 0;
          font-family: "Segoe UI", Tahoma, sans-serif;
          height: 100vh;
          display: flex;
          justify-content: center;
          align-items: center;

          background: linear-gradient(135deg, rgba(139, 124, 200, 0.8), rgba(74, 111, 165, 0.9)),
          url("img/fundo_login.png") ;
          background-size: cover;
        }


        .login-form {
          background: linear-gradient(135deg, rgba(42, 42, 42, 0.95) 0%, rgba(26, 26, 46, 0.95) 100%);
          padding: 40px;
          border-radius: 15px;
          box-shadow: 0 15px 35px rgba(139, 124, 200, 0.4);
          width: 100%;
          max-width: 400px;
          text-align: center; 
          backdrop-filter: blur(15px); 
          border: 1px solid rgba(139, 124, 200, 0.3);
        }

        .login-form h2 {
          margin-bottom: 15px;
          font-size: 26px;
          color: #fff;
          text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }


        .login-form .logo {
          margin-bottom: 20px;
        }
        .login-form .logo img {
          max-width: 100px;
          border-radius: 50%;
          border: 3px solid rgba(139, 124, 200, 0.5);
          padding: 5px;
          background: rgba(255, 255, 255, 0.1);
        }

        .login-form p.subtitle { 
          margin-bottom: 25px;
          font-size: 14px;
          color: #e0e0e0;
        }

        .form-group {
          margin-bottom: 20px;
          text-align: left;
        }


        .form-group label {
          font-weight: 600;
          color: #a594d1; 
          display: block;
          margin-bottom: 8px;
          text-transform: none; 
        }

        .form-group input {
          width: 100%;
          padding: 12px;
          border: 2px solid rgba(139, 124, 200, 0.5); 
          border-radius: 8px;
          background: rgba(42, 42, 42, 0.8); 
          color: #fff;
          font-size: 16px;
          transition: all 0.3s ease;
        }
        
        .form-group input:focus {
          border-color: #a594d1; 
          box-shadow: 0 0 0 3px rgba(139, 124, 200, 0.2);
          outline: none;
        }

    </style>
</head>
<body>

    <div class="login-container">
        

        <form class="login-form" method="POST">
            
            <div class="logo">
                <img src="img/logo.png" alt="Logo Escola de Música">
            </div>

            <h2>Alteração de Senha Obrigatória</h2>
            <p class="subtitle">Este é o seu primeiro acesso ou sua senha foi redefinida. Por favor, crie uma nova senha.</p>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if(empty($message)):  ?>
                <div class="form-group">
                    <label for="nova_senha">Nova Senha (mín. 8 caracteres):</label>
                    <input type="password" id="nova_senha" name="nova_senha" required>
                </div>
                <div class="form-group">
                    <label for="confirma_senha">Confirme a Nova Senha:</label>
                    <input type="password" id="confirma_senha" name="confirma_senha" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Salvar Nova Senha</button>
            <?php endif; ?>
            
        </form>
    </div>
</body>
</html>