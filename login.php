<?php
// Inclui o nosso arquivo de autenticação simplificado.
// É ele quem vai fazer todo o trabalho de verificar o login no banco de dados.
require_once 'includes/auth.php'; 

// Se o usuário já estiver logado (ou seja, já tem uma sessão ativa),
// redireciona ele direto para o painel principal (dashboard).
if(isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Variáveis para guardar as mensagens que serão mostradas ao usuário.
$erro = '';
$sucesso = '';

// Verifica se o formulário foi enviado (se o método da requisição é POST).
if($_POST) {
    // Pega o e-mail e a senha que o usuário digitou no formulário.
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Verifica se os campos não estão vazios.
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos!';
    } else {
        // Tenta fazer o login usando a nossa função simplificada do arquivo auth.php
        if (login($email, $senha)) {
            // Se a função login() retornar 'true', o login foi um sucesso.
            $sucesso = 'Login realizado com sucesso! Redirecionando...';
            // Espera 2 segundos e redireciona para o painel.
            header('refresh:2;url=dashboard.php');
        } else {
            // Se a função login() retornar 'false', os dados estavam errados.
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
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST">
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
            
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </div>
</body>
</html>