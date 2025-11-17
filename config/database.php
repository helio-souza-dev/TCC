<?php

define('BASE_URL', '/tcc2/'); 


define('DB_HOST', 'localhost');
define('DB_NAME', 'tcc_local'); 
define('DB_USER', 'root'); 
define('DB_PASS', ''); 


$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);


$conn->set_charset("utf8mb4");


if ($conn->connect_error) {

  die('Erro de conexão com o banco de dados: ' . $conn->connect_error);
}

// prepara e executa a consulta sql de forma segura evitando invasões
function executar_consulta($db_connection, $sql, $params = []) {
  $stmt = $db_connection->prepare($sql);
  if ($stmt === false) {
    error_log('Erro ao preparar a consulta: ' . $db_connection->error);
    return false;
  }

 
  if (!empty($params)) {

    $types = str_repeat('s', count($params));
    
    
    $stmt->bind_param($types, ...$params);
  }

  
  if (!$stmt->execute()) {
    error_log('Erro ao executar a consulta: ' . $stmt->error);
    return false;
}


  return $stmt;
}

// começa uma nova transação para agrupar várias operações no banco
function iniciar_transacao($db_connection) {
  $db_connection->begin_transaction();
}

// confirma e salva permanentemente tudo o que foi feito na transação
function confirmar_transacao($db_connection) {
  $db_connection->commit();
}

// cancela tudo e volta ao estado anterior caso dê algum erro
function reverter_transacao($db_connection) {
  $db_connection->rollback();
}