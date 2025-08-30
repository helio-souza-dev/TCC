<?php
session_start();
// CORREÇÃO: Inclui o novo arquivo de banco de dados que inicializa o cliente.
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// CORREÇÃO: A função de login agora usa o cliente Supabase Auth.
function login($email, $senha) {
    // NOVO: Ativa a exibição de todos os erros do PHP na tela para depuração.
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    echo '--- INICIANDO TENTATIVA DE LOGIN ---<br>';
    echo 'Email recebido: ' . htmlspecialchars($email) . '<br>';
    // NOTA: Nunca imprima a senha em um ambiente de produção. Apenas para este teste.
    echo 'Senha recebida: ' . htmlspecialchars($senha) . '<br><br>';

    $database = new Database();
    $db = $database->client;

    try {
        echo '<strong>PASSO 1: Tentando autenticar no Supabase...</strong><br>';
        
        $authResponse = $db->auth->signInWithPassword([
            'email' => $email,
            'password' => $senha,
        ]);

        echo 'Resposta completa do Supabase (Autenticação):<br>';
        echo '<pre>';
        print_r($authResponse);
        echo '</pre>';

        // NOVO: Verificação explícita da resposta de autenticação
        if (!empty($authResponse['error'])) {
            $errorMessage = is_object($authResponse['error']) ? $authResponse['error']->getMessage() : json_encode($authResponse['error']);
            die('<strong>ERRO NA AUTENTICAÇÃO:</strong> ' . $errorMessage);
        }
        
        if (empty($authResponse['data']) || empty($authResponse['data']->user)) {
             die('<strong>ERRO:</strong> Resposta de autenticação do Supabase veio vazia ou sem dados do usuário.');
        }

        echo '<strong style="color:green;">SUCESSO: Autenticação no Supabase funcionou!</strong><br><br>';
        
        $user = $authResponse['data']->user;

        // ---------------------------------------------------------------------

        echo '<strong>PASSO 2: Buscando perfil do usuário na tabela "usuarios"...</strong><br>';
        echo 'Buscando pelo email: ' . htmlspecialchars($user->email) . '<br>';

        $response = $db->from('usuarios')
            ->select('id, nome, email, tipo')
            ->eq('email', $user->email)
            ->single()
            ->execute();
        
        echo 'Resposta completa do Supabase (Busca de Perfil):<br>';
        echo '<pre>';
        print_r($response);
        echo '</pre>';

        if (!empty($response->error)) {
            die('<strong>ERRO AO BUSCAR PERFIL:</strong> ' . $response->error->message);
        }

        if (empty($response->data)) {
            die('<strong>ERRO:</strong> O usuário foi autenticado, mas nenhum perfil foi encontrado na tabela "usuarios" com este email.');
        }

        echo '<strong style="color:green;">SUCESSO: Perfil encontrado na tabela "usuarios"!</strong><br><br>';

        $userData = $response->data;

        // ---------------------------------------------------------------------

        echo '<strong>PASSO 3: Configurando a sessão do PHP...</strong><br>';
        $_SESSION['user_id'] = $userData->id;
        $_SESSION['user_name'] = $userData->nome;
        $_SESSION['user_email'] = $userData->email;
        $_SESSION['user_type'] = $userData->tipo;
        $_SESSION['access_token'] = $authResponse['data']->session->access_token;
        
        echo '<strong style="color:green;">SUCESSO: Sessão configurada! O login deveria funcionar.</strong>';
        echo '<br><br>--- FIM DO DEBUG ---';
        
        // NOVO: Interrompe o script para que possamos ver as mensagens antes do redirecionamento.
        die(); 
        
        // O código abaixo não será executado por causa do die()
        // header('Location: dashboard.php');
        // exit();
        // return true;

    } catch (Exception $e) {
        die('<strong>EXCEÇÃO FATAL CAPTURADA:</strong> ' . $e->getMessage());
    }

    // return false; // Inalcançável por causa do die()
}
function logout() {
    // CORREÇÃO: Limpa a sessão
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit();
}
?>