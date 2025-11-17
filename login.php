<?php

require_once 'includes/auth.php'; 


if(isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}


$erro = '';
$sucesso = '';


if($_POST) {

    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    

    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos!';
    } else {
 
        if (login($email, $senha)) {

            $sucesso = 'Login realizado com sucesso! Redirecionando...';

            header('refresh:2;url=dashboard.php');
        } else {

            $erro = 'Email ou senha incorretos! Verifique suas credenciais.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Agendamento</title>
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
          backdrop-filter: blur(15px); /
          border: 1px solid rgba(139, 124, 200, 0.3);
        }

        .login-form h2 {
          margin-bottom: 15px;
          font-size: 26px;
          color: #fff;
          text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

/
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


        .btn {
          width: 100%;
          padding: 12px;
          border: none;
          border-radius: 8px;
          background: linear-gradient(135deg, #8b7cc8 0%, #4a6fa5 100%);
          color: #fff;
          font-weight: bold;
          cursor: pointer;
          font-size: 16px;
          transition: all 0.3s ease;
        }

        .btn:hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 20px rgba(139, 124, 200, 0.4);
        }
        
 
        .social-links {
          margin-top: 25px;
          display: flex;
          justify-content: center;
          gap: 30px; 
        }

        .social-links img {
          width: 45px;   
          height: 45px;  
          object-fit: contain;
          transition: all 0.3s ease;
          border-radius: 50%;
          padding: 8px;
          background: rgba(139, 124, 200, 0.2);
        }

        .social-links img:hover {
          transform: scale(1.1);
          background: rgba(139, 124, 200, 0.4);
        }


    </style>
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST">
            
            <div class="logo">
                <img src="img/logo.png" alt="Logo Escola de Música">
            </div>

            <h2>Sistema de Agendamento</h2>
            <p class="subtitle">Faça login para acessar o sistema</p>
            
            <?php if($erro): ?>
                <div class="alert alert-error">
                    <strong>Erro:</strong> <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>
            
            <?php if($sucesso): ?>
                 <div class="alert alert-success">
                    <strong>Sucesso:</strong> <?php echo htmlspecialchars($sucesso); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       placeholder="seu@email.com" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" 
                       placeholder="Digite sua senha" required>
            </div>
            
            <button type="submit" class="btn">Entrar</button>

            <div class="social-links">
                <a href="https://api.whatsapp.com" target="_blank">
                    <img src="img/whatsapp.png" alt="WhatsApp">
                </a>
                <a href="https://www.instagram.com/forjados_musicstudio" target="_blank">
                    <img src="img/instagram.png" alt="Instagram">
                </a>
            </div>

        </form>
    </div>
</body>
</html>