<?php
// Inicia a sessão em todas as páginas que usarem este arquivo.
// A sessão guarda informações do usuário enquanto ele navega no site.
session_start();

// Inclui o arquivo de conexão com o banco de dados.
// Agora, a variável $conn com a conexão estará disponível aqui.
require_once __DIR__ . '/../config/database.php';

// --- Funções de verificação de Login e Nível de Acesso ---
// Estas funções não mudam, pois apenas leem dados da sessão.

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isProfessor() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'professor';
}

function isAluno() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'aluno';
}

// --- Funções de Controle de Acesso ---

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// --- Função de Login (MODIFICADA PARA SER MAIS SIMPLES) ---

function login($email, $senha) {
    // Torna a variável de conexão $conn (do arquivo database.php) acessível dentro desta função.
    global $conn;

    // 1. Escreve a consulta SQL para buscar o usuário pelo e-mail.
    // O '?' é um marcador de posição que será substituído pelo e-mail de forma segura.
    $sql = "SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ? LIMIT 1";

    // 2. Prepara a consulta SQL para execução.
    // Isso protege o banco de dados contra ataques de "SQL Injection".
    $stmt = $conn->prepare($sql);

    // 3. Vincula (bind) o e-mail do usuário ao marcador de posição '?'.
    // "s" significa que a variável $email é uma string.
    $stmt->bind_param("s", $email);

    // 4. Executa a consulta no banco de dados.
    $stmt->execute();

    // 5. Pega o resultado da consulta.
    $resultado = $stmt->get_result();

    // 6. Busca a primeira (e única) linha de resultado como um array.
    $usuario = $resultado->fetch_assoc();

    // 7. Verifica se um usuário foi encontrado E se a senha digitada corresponde à senha do banco.
    // A função password_verify é a forma segura de comparar senhas criptografadas.
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        
        // Se tudo estiver correto, salva os dados do usuário na sessão.
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['nome'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_type'] = $usuario['tipo'];

        // Retorna 'true', indicando que o login foi um sucesso.
        return true;
    }

    // Se o usuário não foi encontrado ou a senha estava errada, retorna 'false'.
    return false;
}


// --- Função de Logout (Não precisa de alteração) ---

function logout() {
    // Limpa todas as variáveis da sessão.
    $_SESSION = [];
    
    // Apaga o cookie da sessão.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destrói a sessão completamente.
    session_destroy();

    // Redireciona o usuário para a página de login.
    header('Location: login.php');
    exit();
}



// ... (Aqui terminam todas as suas funções: login(), logout(), etc.) ...


// --- VERIFICAÇÃO DE TROCA DE SENHA (SÓ RODA SE O USUÁRIO JÁ ESTIVER LOGADO) ---

// Esta é a verificação mais importante:
if (isLoggedIn()) {

    // 2. Busca o status ATUALIZADO da flag no banco de dados
    try {
        global $conn; // Garante que $conn está acessível
        $user_id = $_SESSION['user_id'];
        $sql_check = "SELECT forcar_troca_senha, tipo FROM usuarios WHERE id = ?";
        
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario_auth = $resultado->fetch_assoc();
        
        if (!$usuario_auth) {
            // Se o usuário da sessão não existe mais, desloga
            logout(); // Chama a sua função de logout para limpar tudo
            exit;
        }

        // [CORREÇÃO 1] Padroniza para 'user_type' (como no resto do seu código)
        $_SESSION['user_type'] = $usuario_auth['tipo'];

    } catch (Exception $e) {
        die("Erro ao verificar autenticação: " . $e->getMessage());
    }

    // 3. A Lógica de Redirecionamento
    $pagina_atual = basename($_SERVER['PHP_SELF']);

    // [CORREÇÃO 2] Adiciona exceção para 'logout.php'
    $paginas_permitidas = ['trocar-senha.php', 'logout.php'];

    if ($usuario_auth['forcar_troca_senha'] == 1 && !in_array($pagina_atual, $paginas_permitidas)) {
        // Se DEVE trocar E NÃO ESTÁ na página de troca (ou de logout), force-o para lá.
        header("Location: trocar-senha.php");
        exit;
    } elseif ($usuario_auth['forcar_troca_senha'] == 0 && $pagina_atual == 'trocar-senha.php') {
        // Se NÃO precisa trocar, mas está tentando acessar, mande-o para o dashboard.
        header("Location: dashboard.php");
        exit;
    }

} // <-- ESTA É A CHAVE '}' QUE FALTAVA

// --- FIM DO ARQUIVO auth.php ---