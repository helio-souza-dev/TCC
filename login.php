<?php
// ARQUIVO: login.php (adicione no topo para testar)
$host = 'shqdmrqhddaxnvutsomv.supabase.co';
if (gethostbyname($host) == $host) {
    echo "ALERTA: Não foi possível resolver o host do Supabase: $host. Verifique sua conexão com a internet ou as configurações de DNS do WAMP.";
} else {
    echo "SUCESSO: O host do Supabase foi resolvido com sucesso.";
}
echo "<hr>";

require_once 'includes/auth.php'; 

if(isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if($_POST) {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // CORREÇÃO: Chama a função de login centralizada.
    if (login($email, $senha)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Email ou senha incorretos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Chamadas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Sistema de Chamadas</h2>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="btn">Entrar</button>
        </form>
    </div>
</body>
</html>