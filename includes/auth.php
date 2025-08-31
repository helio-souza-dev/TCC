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

function login($email, $senha) {
    $database = new Database();
    $db = $database->client;

    try {
        // Passo 1: Autenticar no Supabase
        $authResponse = $db->auth->signInWithPassword([
            'email' => $email,
            'password' => $senha,
        ]);

        if (isset($authResponse['error'])) {
            return false;
        }

        if (empty($authResponse['data']) || empty($authResponse['data']->user)) {
            return false;
        }

        $user = $authResponse['data']->user;

        // Passo 2: Buscar o perfil na tabela 'usuarios' (ESSENCIAL para o sistema)
        $profileResponse = $db->from('usuarios')
            ->select('id, nome, email, tipo')
            ->eq('id', $user->id) 
            ->single()
            ->execute();

        if ($profileResponse->error || empty($profileResponse->data)) {
            // Usuário existe na autenticação mas não tem perfil no sistema.
            return false;
        }

        $userData = $profileResponse->data;

        // Passo 3: Configurar a sessão COMPLETA do PHP
        $_SESSION['user_id'] = $userData->id;
        $_SESSION['user_name'] = $userData->nome;
        $_SESSION['user_email'] = $userData->email;
        $_SESSION['user_type'] = $userData->tipo;
        $_SESSION['access_token'] = $authResponse['data']->session->access_token;

        return true; // Sucesso!

    } catch (Exception $e) {
        return false;
    }
}

// ... (restante do arquivo) ...
?>
function logout() {
    // CORREÇÃO: Limpa a sessão
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit();
}
?>