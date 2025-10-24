<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Garante que apenas administradores podem acessar esta página.
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

// Variáveis para mensagens e para guardar os dados do professor.
$message = '';
$error = '';
$professor = null;

// --- LÓGICA 1: PROCESSAR FORMULÁRIOS ENVIADOS (POST) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $usuario_id = $_POST['usuario_id'] ?? null;

    // AÇÃO: MUDAR A SENHA
    if ($action === 'change_password') {
        $newPassword = $_POST['new_password'] ?? '';
        if ($usuario_id && strlen($newPassword) >= 8) {
            // Criptografa a nova senha.
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            

            $sql = "UPDATE usuarios SET senha = ?, forcar_troca_senha = 1 WHERE id = ?";
            executar_consulta($conn, $sql, [$hashedPassword, $usuario_id]);
            
            $message = 'Senha alterada com sucesso! O usuário deverá trocá-la no próximo login.';
        } else {
            $error = 'A nova senha deve ter no mínimo 8 caracteres.';
        }
    }

    // AÇÃO: ATUALIZAR DADOS DO PROFESSOR
    elseif ($action === 'update') {
        $professor_id = $_POST['professor_id'] ?? null;
        
        // Inicia a transação para garantir a consistência dos dados.
        iniciar_transacao($conn);
        try {
            // 1. Atualiza a tabela 'usuarios'.
            $sql_user = "UPDATE usuarios SET nome=?, email=?, cpf=?, rg=?, cidade=?, endereco=?, complemento=?, ativo=? WHERE id=?";
            executar_consulta($conn, $sql_user, [
                $_POST['nome'], $_POST['email'], $_POST['cpf'], $_POST['rg'],
                $_POST['cidade'], $_POST['endereco'], $_POST['complemento'],
                isset($_POST['ativo']) ? 1 : 0,
                $usuario_id
            ]);

            // 2. Atualiza a tabela 'professores'.
            $sql_prof = "UPDATE professores SET formacao=?, data_contratacao=?, instrumentos_leciona=?, valor_hora_aula=?, biografia=? WHERE id=?";
            executar_consulta($conn, $sql_prof, [
                $_POST['formacao'], $_POST['data_contratacao'], $_POST['instrumentos_leciona'],
                $_POST['valor_hora_aula'], $_POST['biografia'],
                $professor_id
            ]);

            // Se tudo correu bem, confirma as alterações.
            confirmar_transacao($conn);
            $message = 'Dados do professor atualizados com sucesso!';

        } catch (Exception $e) {
            // Se deu erro, desfaz tudo.
            reverter_transacao($conn);
            $error = 'Erro ao atualizar dados: ' . $e->getMessage();
        }
    }
}


// --- LÓGICA 2: CARREGAR DADOS DO PROFESSOR PARA MOSTRAR NO FORMULÁRIO ---

// Pega o ID do professor da URL.
$professor_id_to_load = $_GET['professor_id'] ?? null;

if ($professor_id_to_load) {
    // Busca os dados completos do professor usando um JOIN.
    $sql = "SELECT p.*, u.*, p.id AS professor_id, u.id AS usuario_id
            FROM professores p
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.id = ? AND u.tipo = 'professor' LIMIT 1";
            
    $stmt = executar_consulta($conn, $sql, [$professor_id_to_load]);
    
    if ($stmt) {
        // Guarda os dados no array $professor para usar no HTML.
        $professor = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$professor) { 
        $error = 'Professor não encontrado.'; 
    }
}
?>

<div class="card">
    <h3> Editar Professor</h3>
    
    <?php if($message): ?><div class="alert alert-success"> <?php echo $message; ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"> <?php echo $error; ?></div><?php endif; ?>
    
    <?php if ($professor): ?>
    
        <div class="form-section" style="margin-top: 30px;">
            <h4> Alterar Senha</h4>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($professor['usuario_id']); ?>">
                <div class="form-group">
                    <label for="new_password">Nova Senha (mínimo 8 caracteres):</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                <button type="submit" class="btn btn-warning" onclick="return confirm('Tem certeza que deseja alterar a senha deste usuário?')">Salvar Nova Senha</button>
            </form>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="professor_id" value="<?php echo htmlspecialchars($professor['professor_id']); ?>">
            <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($professor['usuario_id']); ?>">

            <div class="form-section">
                <h4> Dados Pessoais</h4>
                <div class="form-row">
                    <div class="form-group"><label for="nome">Nome Completo:</label><input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($professor['nome']); ?>"></div>
                    <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($professor['email']); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label for="cpf">CPF:</label><input type="text" id="cpf" name="cpf" required value="<?php echo htmlspecialchars($professor['cpf']); ?>"></div>
                    <div class="form-group"><label for="rg">RG:</label><input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($professor['rg'] ?? ''); ?>"></div>
                </div>
            </div>

            <div class="form-section">
                <h4> Dados Profissionais e Musicais</h4>
                <div class="form-row">
                    <div class="form-group"><label for="formacao">Formação:</label><input type="text" id="formacao" name="formacao" value="<?php echo htmlspecialchars($professor['formacao'] ?? ''); ?>"></div>
                    <div class="form-group"><label for="data_contratacao">Data de Contratação:</label><input type="date" id="data_contratacao" name="data_contratacao" value="<?php echo htmlspecialchars($professor['data_contratacao'] ?? ''); ?>"></div>
                </div>
                 <div class="form-row">
                    <div class="form-group"><label for="instrumentos_leciona">Instrumentos que Leciona:</label><input type="text" id="instrumentos_leciona" name="instrumentos_leciona" value="<?php echo htmlspecialchars($professor['instrumentos_leciona'] ?? ''); ?>"></div>
                    <div class="form-group"><label for="valor_hora_aula">Valor da Hora/Aula (R$):</label><input type="number" step="0.01" id="valor_hora_aula" name="valor_hora_aula" value="<?php echo htmlspecialchars($professor['valor_hora_aula'] ?? ''); ?>"></div>
                </div>
                <div class="form-group"><label for="biografia">Biografia:</label><textarea id="biografia" name="biografia" rows="3"><?php echo htmlspecialchars($professor['biografia'] ?? ''); ?></textarea></div>
            </div>
            
            <div class="form-section">
                <h4> Endereço</h4>
                <div class="form-row">
                    <div class="form-group"><label for="cidade">Cidade:</label><input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($professor['cidade'] ?? ''); ?>"></div>
                    <div class="form-group"><label for="endereco">Endereço:</label><input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($professor['endereco'] ?? ''); ?>"></div>
                </div>
                <div class="form-group"><label for="complemento">Complemento:</label><input type="text" id="complemento" name="complemento" value="<?php echo htmlspecialchars($professor['complemento'] ?? ''); ?>"></div>
            </div>
            
            <div class="form-section">
                <h4> Status</h4>
                <div class="form-group">
                    <label><input type="checkbox" name="ativo" value="1" <?php echo ($professor['ativo'] ?? 1) ? 'checked' : ''; ?>> Professor Ativo</label>
                </div>
            </div>

            <div class="flex gap-10 mt-20">
                <button type="submit" class="btn btn-primary"> Salvar Alterações</button>
                <a href="dashboard.php?page=professores" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>