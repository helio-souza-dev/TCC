<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin(); // Garante que o usuário está logado

$message = '';
$error = '';
$userData = null;
$userType = $_SESSION['user_type'];
$usuario_id = $_SESSION['user_id'];

// --- LÓGICA DE ATUALIZAÇÃO DE SENHA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    try {
        if (strlen($newPassword) < 8) {
            throw new Exception("A nova senha deve ter no mínimo 8 caracteres.");
        }
        
        // 1. Verificar a senha atual
        global $conn;
        $sql_check_pass = "SELECT senha FROM usuarios WHERE id = ? LIMIT 1";
        $stmt_check_pass = $conn->prepare($sql_check_pass);
        $stmt_check_pass->bind_param("i", $usuario_id);
        $stmt_check_pass->execute();
        $resultado = $stmt_check_pass->get_result();
        $user_auth_data = $resultado->fetch_assoc();
        $stmt_check_pass->close();

        if (!$user_auth_data || !password_verify($currentPassword, $user_auth_data['senha'])) {
             throw new Exception("A senha atual fornecida está incorreta.");
        }

        // 2. Atualizar a nova senha
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET senha = ?, forcar_troca_senha = 0 WHERE id = ?";
        executar_consulta($conn, $sql, [$hashedPassword, $usuario_id]);
        
        $message = 'Senha alterada com sucesso!';
        
        // Atualiza a sessão para o caso de a troca ser obrigatória
        if (isset($_SESSION['forcar_troca_senha'])) {
            $_SESSION['forcar_troca_senha'] = 0;    
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}


// --- LÓGICA DE ATUALIZAÇÃO DO PERFIL (Dados Editáveis) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    
    iniciar_transacao($conn);

    try {
        if (isAdmin()) {
            // LÓGICA ESPECÍFICA PARA ADMIN: Edita todos os campos principais na tabela usuarios.
            // Exclui apenas 'ativo', 'created_at', 'senha' e 'forcar_troca_senha'.
            $sql_admin_user = "UPDATE usuarios SET 
                nome = ?, email = ?, cpf = ?, rg = ?, data_nascimento = ?, 
                telefone = ?, cidade = ?, endereco = ?, complemento = ? 
                WHERE id = ?";
            
            executar_consulta($conn, $sql_admin_user, [
                $_POST['nome'], $_POST['email'], $_POST['cpf'], $_POST['rg'], $_POST['data_nascimento'],
                $_POST['telefone'], $_POST['cidade'], $_POST['endereco'], $_POST['complemento'], $usuario_id
            ]);

        } else {
            // LÓGICA PADRÃO PARA ALUNO/PROFESSOR: Apenas campos não críticos.
            $sql_user = "UPDATE usuarios SET telefone = ?, cidade = ?, endereco = ?, complemento = ? WHERE id = ?";
            executar_consulta($conn, $sql_user, [
                $_POST['telefone'], $_POST['cidade'], $_POST['endereco'], $_POST['complemento'], $usuario_id
            ]);

            if (isAluno()) {
                // Atualiza a tabela ALUNOS
                $aluno_id = $_POST['aluno_id'];
                $sql_aluno = "UPDATE alunos SET instrumento = ?, nivel_experiencia = ?, preferencia_horario = ?, possui_instrumento = ?, objetivos = ? WHERE id = ?";
                executar_consulta($conn, $sql_aluno, [
                    $_POST['instrumento'], $_POST['nivel_experiencia'],
                    $_POST['preferencia_horario'], isset($_POST['possui_instrumento']) ? 1 : 0, $_POST['objetivos'], $aluno_id
                ]);
            } elseif (isProfessor()) {
                // Atualiza a tabela PROFESSORES
                $professor_id = $_POST['professor_id'];
                $sql_prof = "UPDATE professores SET formacao = ?, instrumentos_leciona = ?, biografia = ? WHERE id = ?";
                executar_consulta($conn, $sql_prof, [
                    $_POST['formacao'], $_POST['instrumentos_leciona'], $_POST['biografia'], $professor_id
                ]);
            }
        }
        
        confirmar_transacao($conn);
        // Recarrega os dados para exibir os novos valores imediatamente
        // O restante do código abaixo recarrega, então podemos apenas emitir a mensagem
        $message = 'Dados de perfil atualizados com sucesso!';
        
    } catch (Exception $e) {
        reverter_transacao($conn);
        $error = 'Erro ao atualizar dados: ' . $e->getMessage();
    }
}


// --- LÓGICA PARA CARREGAR DADOS DO PERFIL (Recarregar após updates) ---
try {
    $sql = '';
    
    if (isAluno()) {
        $sql = "SELECT a.*, u.*, a.id AS aluno_id, u.id AS usuario_id
                FROM alunos a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE u.id = ? AND u.tipo = 'aluno' LIMIT 1";
    } elseif (isProfessor()) {
        $sql = "SELECT p.*, u.*, p.id AS professor_id, u.id AS usuario_id
                FROM professores p
                JOIN usuarios u ON p.usuario_id = u.id
                WHERE u.id = ? AND u.tipo = 'professor' LIMIT 1";
    } elseif (isAdmin()) { // <<< ADICIONADO: Lógica para o Administrador
                            // O administrador só busca dados da tabela 'usuarios'.
        $sql = "SELECT u.*, u.id AS usuario_id
                FROM usuarios u
                WHERE u.id = ? AND u.tipo = 'admin' LIMIT 1";
    }

    if (!empty($sql)) {
        $stmt = executar_consulta($conn, $sql, [$usuario_id]);
        $userData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$userData) {
        $error = 'Seu perfil não foi encontrado.';
    }
} catch (Exception $e) {
    $error = 'Erro ao carregar dados do perfil: ' . $e->getMessage();
}
?>

<div class="card">
    <div class="flex justify-between align-center mb-20">
        <h3>Meu Perfil (<?php echo ucfirst($userType); ?>)</h3>
        <a href="dashboard.php" class="btn btn-outline">Voltar</a>
    </div>

    <?php if($message): ?><div class="alert alert-success"> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <?php if ($userData): ?>
    
    <form method="POST">
        <input type="hidden" name="action" value="update_profile">
        
        <?php if(isAluno()): ?>
            <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars($userData['aluno_id']); ?>">
        <?php elseif(isProfessor()): ?>
            <input type="hidden" name="professor_id" value="<?php echo htmlspecialchars($userData['professor_id']); ?>">
        <?php endif; ?>

        <div class="form-section">
            
            <?php $readonlyAttr = isAdmin() ? '' : 'readonly'; // Define o atributo readonly condicionalmente?>

            <?php if (isAdmin()): ?>
                <h4> Dados Pessoais (Admin Editável)</h4>
                <p class="alert alert-info">Como administrador, você pode editar todos os seus dados pessoais (exceto ID e datas de sistema).</p>
            <?php else: ?>
                <h4> Dados Pessoais (Não Editáveis por aqui)</h4>
                <p class="alert alert-info">Estes campos (Nome, CPF, RG, Data de Nasc.) são críticos e só podem ser alterados mediante solicitação e aprovação de um administrador.</p>
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group"><label>Nome Completo:</label><input type="text" name="nome" required <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['nome'] ?? ''); ?>"></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" required <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>CPF:</label><input type="text" readonly name="cpf" <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['cpf'] ?? ''); ?>"></div>
                <div class="form-group"><label>RG:</label><input type="text" readonly name="rg" <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['rg'] ?? ''); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Data de Nascimento:</label><input type="date" readonly name="data_nascimento" <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['data_nascimento'] ?? ''); ?>"></div>
                <div class="form-group"><label>Telefone:</label><input type="text" required name="telefone" value="<?php echo htmlspecialchars($userData['telefone'] ?? ''); ?>"></div>
            </div>
             <div class="form-row">
                <div class="form-group"><label>Cidade:</label><input type="text" name="cidade" required value="<?php echo htmlspecialchars($userData['cidade'] ?? ''); ?>"></div>
                <div class="form-group"><label>Endereço:</label><input type="text" name="endereco" required value="<?php echo htmlspecialchars($userData['endereco'] ?? ''); ?>"></div>
                <div class="form-group"><label>Complemento:</label><input type="text" name="complemento" value="<?php echo htmlspecialchars($userData['complemento'] ?? ''); ?>"></div>
            </div>
        </div>
        
        <?php if(isAluno()): ?>
        <div class="form-section">
            <h4> Dados de Aluno Editáveis</h4>
            <div class="form-row">
                <div class="form-group"><label>Matrícula:</label><input type="text" readonly value="<?php echo htmlspecialchars($userData['matricula'] ?? ''); ?>"></div>
                <div class="form-group"><label>Instrumento Principal:</label><input type="text" name="instrumento" value="<?php echo htmlspecialchars($userData['instrumento'] ?? ''); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Nível de Experiência:</label>
                    <select name="nivel_experiencia">
                        <option value="Iniciante" <?php echo ($userData['nivel_experiencia'] == 'Iniciante') ? 'selected' : ''; ?>>Iniciante</option>
                        <option value="Básico" <?php echo ($userData['nivel_experiencia'] == 'Básico') ? 'selected' : ''; ?>>Básico</option>
                        <option value="Intermediário" <?php echo ($userData['nivel_experiencia'] == 'Intermediário') ? 'selected' : ''; ?>>Intermediário</option>
                        <option value="Avançado" <?php echo ($userData['nivel_experiencia'] == 'Avançado') ? 'selected' : ''; ?>>Avançado</option>
                    </select>
                </div>
                <div class="form-group"><label>Tipo de Aula Desejada:</label><input type="text" name="tipo_aula_desejada" value="<?php echo htmlspecialchars($userData['tipo_aula_desejada'] ?? ''); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Preferência de Horário:</label><input type="text" name="preferencia_horario" value="<?php echo htmlspecialchars($userData['preferencia_horario'] ?? ''); ?>"></div>
                <div class="form-group">
                    <label>Possui Instrumento?</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="possui_instrumento" value="1" <?php echo ($userData['possui_instrumento'] == 1) ? 'checked' : ''; ?>>
                            <span class="custom-radio"></span> Sim
                        </label>
                        <label>
                            <input type="radio" name="possui_instrumento" value="0" <?php echo ($userData['possui_instrumento'] != 1) ? 'checked' : ''; ?>>
                            <span class="custom-radio"></span> Não
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group"><label>Objetivos com a Música:</label><textarea name="objetivos" rows="3"><?php echo htmlspecialchars($userData['objetivos'] ?? ''); ?></textarea></div>
            
            <?php if(!empty($userData['nome_responsavel'])): ?>
            <div class="form-section" style="padding: 15px; border-left: 4px solid #f0ad4e;">
                 <p><strong>Responsável:</strong> <?php echo htmlspecialchars($userData['nome_responsavel']); ?></p>
                 <p><strong>Telefone do Responsável:</strong> <?php echo htmlspecialchars($userData['telefone_responsavel']); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if(isProfessor()): ?>
        <div class="form-section">
            <h4> Dados de Professor Editáveis</h4>
            <div class="form-row">
                <div class="form-group"><label>Formação:</label><input type="text" required name="formacao" value="<?php echo htmlspecialchars($userData['formacao'] ?? ''); ?>"></div>
                <div class="form-group"><label>Data Contratação:</label><input type="date" readonly value="<?php echo htmlspecialchars($userData['data_contratacao'] ?? ''); ?>"></div>
            </div>
             <div class="form-row">
                <div class="form-group"><label>Instrumentos:</label><input type="text" required name="instrumentos_leciona" value="<?php echo htmlspecialchars($userData['instrumentos_leciona'] ?? ''); ?>"></div>
            </div>
            <div class="form-group"><label>Biografia:</label><textarea name="biografia" rows="3"><?php echo htmlspecialchars($userData['biografia'] ?? ''); ?></textarea></div>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary mt-20">Salvar Alterações do Perfil</button>
    </form>


        <div class="form-section" style="margin-top: 30px;">
            <h4> Alterar Senha</h4>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Senha Atual:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">Nova Senha (mín. 8 caracteres):</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_new_password">Confirmar Nova Senha:</label>
                    <input type="password" id="confirm_new_password" oninput="checkPasswordMatch(this);" required>
                    <small id="password_match_status"></small>
                </div>
                
                <button type="submit" id="btnChangePassword" class="btn btn-warning" disabled>Alterar Senha</button>
                </form>
        </div>

        <?php 
            if (isProfessor() || isAluno()):  ?> 
            echo "<div class="form-section" style="margin-top: 30px;">
                    <h4> Precisa Alterar Dados Pessoais Críticos?</h4>
                    <p>Seus dados pessoais CRÍTICOS (Nome, CPF, RG, Data de Nascimento) só podem ser alterados mediante solicitação e aprovação de um administrador.</p>
                        <div class="flex gap-10 mt-10">
                            <a href="dashboard.php?page=solicitar-dados" class="btn btn-secondary">
                                Criar Nova Solicitação
                            </a>
                            <a href="dashboard.php?page=minhas_solicitacoes" class="btn btn-outline">
                            Ver Minhas Solicitações
                            </a>
                        </div>
                    </div>";
    
        <?php endif; ?>
         
        
        
        

    <?php endif; ?>
</div>

<script>
    function checkPasswordMatch(input) {
        const newPass = document.getElementById('new_password').value;
        const confirmPass = input.value;
        const status = document.getElementById('password_match_status');
        const button = document.getElementById('btnChangePassword');

        // Verifica se ambas as senhas têm pelo menos 8 caracteres e se são iguais
        if (newPass.length >= 8 && newPass === confirmPass) {
            status.textContent = 'As senhas são iguais.';
            status.style.color = '#28a745';
            button.disabled = false;
        } else if (newPass.length >= 8 && confirmPass.length > 0 && newPass !== confirmPass) {
            status.textContent = 'As senhas não conferem.';
            status.style.color = '#dc3545';
            button.disabled = true;
        } else if (newPass.length > 0 && newPass.length < 8) {
            status.textContent = 'A nova senha deve ter no mínimo 8 caracteres.';
            status.style.color = '#dc3545';
            button.disabled = true;
        } else {
             status.textContent = '';
             button.disabled = true;
        }
    }

    document.getElementById('new_password').addEventListener('input', function() {
        const confirmPassInput = document.getElementById('confirm_new_password');
        checkPasswordMatch(confirmPassInput);
    });

    // Adiciona o listener de input para a confirmação de senha também, caso o usuário comece por ela
    document.getElementById('confirm_new_password').addEventListener('input', function() {
        checkPasswordMatch(this);
    });
</script>