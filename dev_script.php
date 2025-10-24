<?php
// v_bdlocal/dev_script.php
require_once 'config/database.php';
require_once 'includes/auth.php'; 

// Variáveis globais de conexão (do database.php) e mensagens.
global $conn;
$message = '';
$error = '';

// --- FUNÇÃO AUXILIAR ---
function generateRandomPassword($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    $characterCount = strlen($characters);
    // Usando rand() para simplicidade em scripts de desenvolvimento
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, $characterCount - 1)];
    }
    return $password;
}

// --- LÓGICA DE PROCESSAMENTO DE FORMULÁRIOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        // --- AÇÃO DE GERAR EM MASSA PARA TESTE DE CAPACIDADE ---
        if ($action === 'generate_bulk') {
            $num_users = (int) ($_POST['num_users'] ?? 0);
            $tipo = $_POST['bulk_test_tipo'] ?? 'aluno';
            
            if ($num_users <= 0 || $num_users > 1000) {
                throw new Exception("O número de usuários deve ser entre 1 e 1000 para este teste.");
            }
            if (!in_array($tipo, ['aluno', 'professor', 'admin'])) {
                throw new Exception("Tipo de usuário inválido.");
            }

            $success_count = 0;
            
            iniciar_transacao($conn);

            try {
                for ($i = 1; $i <= $num_users; $i++) {
                    // Dados randômicos para garantir unicidade
                    $timestamp = time();
                    $nome = "UsuarioTeste N" . $i . " (" . ucfirst($tipo) . ")";
                    $email = "teste_" . strtolower($tipo) . "_" . $timestamp . "_" . $i . "@bulk.com";
                    $password = generateRandomPassword(10);
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    // Geração simplificada de CPF/RG para teste (não é um CPF/RG válido)
                    $cpf = str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT) . str_pad(mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
                    $rg = str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);


                    // 1. Insere na tabela 'usuarios'.
                    $sql_usuario = "INSERT INTO usuarios (nome, email, senha, tipo, cpf, rg, forcar_troca_senha)
                                    VALUES (?, ?, ?, ?, ?, ?, 0)"; 
                    executar_consulta($conn, $sql_usuario, [$nome, $email, $hashedPassword, $tipo, $cpf, $rg]);
                    $userId = $conn->insert_id;

                    // 2. Insere na tabela específica (alunos ou professores)
                    if ($tipo === 'aluno') {
                        $matricula = date('Y') . $userId; 
                        $sql_aluno = "INSERT INTO alunos (usuario_id, matricula, instrumento) VALUES (?, ?, ?)";
                        executar_consulta($conn, $sql_aluno, [$userId, $matricula, 'Piano']);
                    } elseif ($tipo === 'professor') {
                         $sql_prof = "INSERT INTO professores (usuario_id, instrumentos_leciona, valor_hora_aula) VALUES (?, ?, ?)";
                         executar_consulta($conn, $sql_prof, [$userId, 'Violão, Guitarra', 60.00]);
                    }
                    
                    $success_count++;
                }

                confirmar_transacao($conn);
                $message = "$success_count usuários do tipo '$tipo' criados para teste de capacidade!";
            } catch (Exception $e) {
                reverter_transacao($conn);
                $error = 'Erro ao gerar usuários em massa: ' . $e->getMessage();
            }
        }
        // Se a action não for 'generate_bulk', ignora (removendo as outras lógicas)

    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
             reverter_transacao($conn);
        }
        $error = 'Erro: ' . $e->getMessage();
    }
}

// O restante do script para listar usuários (que você pediu para remover) foi excluído.
// Mantendo apenas o HTML mínimo para a ferramenta de teste.
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dev Tool: Gerar Usuários de Teste</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; background-color: #f4f4f4; color: #333; }
        .container { max-width: 800px; margin: 20px auto; background: white; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2, h3 { text-align: center; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="number"], select { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; transition: background-color 0.2s; }
        button:hover { background-color: #218838; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🛠️ Ferramenta de Geração de Usuários de Teste</h2>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <h3>⚡ Gerar Usuários para Teste de Capacidade</h3>
        <form action="dev_script.php" method="POST">
            <input type="hidden" name="action" value="generate_bulk">
            <div class="form-group">
                <label for="num_users">Número de Usuários para Criar (Max 1000):</label>
                <input type="number" id="num_users" name="num_users" min="1" max="1000" required value="10">
            </div>
            <div class="form-group">
                <label for="bulk_test_tipo">Tipo de Usuário a ser criado:</label>
                <select id="bulk_test_tipo" name="bulk_test_tipo" required>
                    <option value="aluno" selected>Aluno</option>
                    <option value="professor">Professor</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <button type="submit">Gerar Usuários de Teste</button>
        </form>
    </div>
</body>
</html>