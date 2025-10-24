<?php
// Define a URL base do seu projeto local
define('BASE_URL', '/tcc2/'); // Ajuste se o seu projeto estiver em outra pasta

// --- Configurações do Banco de Dados Local ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'tcc_local'); // O nome do banco que você criou
define('DB_USER', 'root');       // Usuário padrão do XAMPP/WAMP
define('DB_PASS', '');  // Senha padrão do XAMPP/WAMP (geralmente vazia)

// --- Conexão com o Banco de Dados usando MySQLi ---
// Cria a conexão
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Define o charset para garantir a codificação correta (essencial para acentos)
$conn->set_charset("utf8mb4");

// Verifica se a conexão falhou
if ($conn->connect_error) {
    // Em um ambiente de produção, o ideal é logar o erro e não exibi-lo na tela.
    die('Erro de conexão com o banco de dados: ' . $conn->connect_error);
}

/**
 * Função genérica para executar consultas preparadas com MySQLi.
 * Esta função substitui o antigo método $database->query().
 *
 * @param mysqli $db_connection A conexão ativa com o banco de dados.
 * @param string $sql A consulta SQL, usando '?' como placeholders.
 * @param array $params Um array com os valores para os placeholders.
 * @return mysqli_stmt|bool Retorna o objeto mysqli_stmt em sucesso ou false em falha.
 */
function executar_consulta($db_connection, $sql, $params = []) {
    // Prepara a consulta
    $stmt = $db_connection->prepare($sql);
    if ($stmt === false) {
        // Se a preparação falhar, loga o erro e retorna false.
        error_log('Erro ao preparar a consulta: ' . $db_connection->error);
        return false;
    }

    // Se existirem parâmetros, faz o bind deles
    if (!empty($params)) {
        // Cria uma string com os tipos dos parâmetros (ex: 'ssi' para string, string, integer)
        // Por simplicidade aqui, vamos assumir que todos são strings ('s').
        // Para um sistema mais robusto, seria necessário verificar o tipo de cada parâmetro.
        $types = str_repeat('s', count($params));
        
        // Faz o bind dos parâmetros de forma dinâmica
        $stmt->bind_param($types, ...$params);
    }

    // Executa a consulta
    if (!$stmt->execute()) {
        error_log('Erro ao executar a consulta: ' . $stmt->error);
        return false;
    }

    // Retorna o statement para que o resultado possa ser processado
    return $stmt;
}

/**
 * Inicia uma transação.
 * @param mysqli $db_connection
 */
function iniciar_transacao($db_connection) {
    $db_connection->begin_transaction();
}

/**
 * Confirma (commita) uma transação.
 * @param mysqli $db_connection
 */
function confirmar_transacao($db_connection) {
    $db_connection->commit();
}

/**
 * Reverte (rollback) uma transação.
 * @param mysqli $db_connection
 */
function reverter_transacao($db_connection) {
    $db_connection->rollback();
}