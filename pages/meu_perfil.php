<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin(); // autenticação de login

$message = '';
$error = '';
$userData = null;
$userType = $_SESSION['user_type'];
$usuario_id = $_SESSION['user_id'];

// atualizaçao de senha 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    try {
        if (strlen($newPassword) < 8) {
            throw new Exception("A nova senha deve ter no mínimo 8 caracteres.");
        }
        
        // verificar senha atual
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

        // atualizar a senha
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET senha = ?, forcar_troca_senha = 0 WHERE id = ?";
        executar_consulta($conn, $sql, [$hashedPassword, $usuario_id]);
        
        $message = 'Senha alterada com sucesso!';
        
        // atualiza a senha para forcar trocar a senha
        if (isset($_SESSION['forcar_troca_senha'])) {
            $_SESSION['forcar_troca_senha'] = 0;    
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}


// dados editaveis

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    
    iniciar_transacao($conn);

    try {
        if (isAdmin()) {
            // admin pode editar a maioria dos campos da tabela usuarios

            $sql_admin_user = "UPDATE usuarios SET 
                nome = ?, email = ?, cpf = ?, rg = ?, data_nascimento = ?, 
                telefone = ?, cidade = ?, endereco = ?, complemento = ? 
                WHERE id = ?";
            
            executar_consulta($conn, $sql_admin_user, [
                $_POST['nome'], $_POST['email'], $_POST['cpf'], $_POST['rg'], $_POST['data_nascimento'],
                $_POST['telefone'], $_POST['cidade'], $_POST['endereco'], $_POST['complemento'], $usuario_id
            ]);

        } else {
            // prof/aluno podem editar campos nao criticos
            $sql_user = "UPDATE usuarios SET telefone = ?, cidade = ?, endereco = ?, complemento = ? WHERE id = ?";
            executar_consulta($conn, $sql_user, [
                $_POST['telefone'], $_POST['cidade'], $_POST['endereco'], $_POST['complemento'], $usuario_id
            ]);

            if (isAluno()) { //atualizar table alunos 
                $aluno_id = $_POST['aluno_id'];
                $sql_aluno = "UPDATE alunos SET instrumento = ?, nivel_experiencia = ?, preferencia_horario = ?, possui_instrumento = ?, objetivos = ? WHERE id = ?";
                executar_consulta($conn, $sql_aluno, [
                    $_POST['instrumento'], $_POST['nivel_experiencia'],
                    $_POST['preferencia_horario'], isset($_POST['possui_instrumento']) ? 1 : 0, $_POST['objetivos'], $aluno_id
                ]);
            } elseif (isProfessor()) { //atualizar table professores
                $professor_id = $_POST['professor_id'];
                $sql_prof = "UPDATE professores SET formacao = ?, instrumentos_leciona = ?, biografia = ? WHERE id = ?";
                executar_consulta($conn, $sql_prof, [
                    $_POST['formacao'], $_POST['instrumentos_leciona'], $_POST['biografia'], $professor_id
                ]);
            }
        }
        
        confirmar_transacao($conn);
        // recarrega os valores do formulario imediatamente

        $message = 'Dados de perfil atualizados com sucesso!';
        
    } catch (Exception $e) {
        reverter_transacao($conn);
        $error = 'Erro ao atualizar dados: ' . $e->getMessage();
    }
}


//carregar dados do perfil
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
    } elseif (isAdmin()) { 
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
            
            <?php $readonlyAttr = isAdmin() ? '' : 'readonly'; // condicao de readonly, se o usuario for admin não será um readonly ?>

            <?php if (isAdmin()): ?>
                <h4> Dados Pessoais (Admin Editável)</h4>
                <p class="alert alert-info">Como administrador, você pode editar todos os seus dados pessoais (exceto ID).</p>
            <?php else: ?>
                <h4> Dados Pessoais (Não Editáveis por aqui)</h4>
                <p class="alert alert-info">Estes campos (Nome, CPF, RG, Data de Nasc.) são críticos e só podem ser alterados mediante solicitação e aprovação de um administrador.</p>
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group"><label>Nome Completo:</label><input type="text" name="nome" required <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['nome'] ?? ''); ?>"></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" required <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>CPF:</label><input type="text"  id="cpf" name="cpf" <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['cpf'] ?? ''); ?>"></div>
                <div class="form-group"><label>RG:</label><input type="text"  id="rg" name="rg" <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['rg'] ?? ''); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Data de Nascimento:</label><input type="date"  id="data_nascimento" name="data_nascimento" <?php echo $readonlyAttr; ?> value="<?php echo htmlspecialchars($userData['data_nascimento'] ?? ''); ?>"></div>
                <div class="form-group"><label>Telefone:</label><input type="text" required id="telefone" name="telefone" value="<?php echo htmlspecialchars($userData['telefone'] ?? ''); ?>"></div>
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
           <div class="form-section" style="margin-top: 30px;">
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
                    </div>
    
        <?php endif; ?>
         
        
        
        

    <?php endif; ?>
</div>

<script>
    function checkPasswordMatch(input) {
        const newPass = document.getElementById('new_password').value;
        const confirmPass = input.value;
        const status = document.getElementById('password_match_status');
        const button = document.getElementById('btnChangePassword');

        // verifica se ambas as senhas tem pelo menos 8 caracteres e se sao iguais
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

    // adiciona o listener de input para a confirmação de senha, caso o usuário comece por ela
    document.getElementById('confirm_new_password').addEventListener('input', function() {
        checkPasswordMatch(this);
    });
</script>


<script>
    // formatacao

    // formatar data
    function formatarDataInput(event) {
        let input = event.target;
        // remove tudo que nao for digito
        let valor = input.value.replace(/\D/g, '');
        let tamanho = valor.length;


        if (tamanho > 2) {
            valor = valor.substring(0, 2) + '/' + valor.substring(2);
        }
  
        if (tamanho > 4) {

            valor = valor.substring(0, 5) + '/' + valor.substring(5, 9); 
        }
        
        // atualiza o valor no campo
        input.value = valor;
    }

    // formatar cpf
    function formatarCPF(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = value;
    }

    // formatar rg
    function formatarRG(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)$/, '.$1-$2');
        input.value = value.substring(0, 12); // é o um maxlenght da vida
    }

    // formatar telefone
    function formatarTelefone(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        input.setAttribute('maxlength', '15'); 
        input.value = value;
    }

    
    document.addEventListener('DOMContentLoaded', function() {
        
        //flatpickr
        flatpickr("#data_nascimento", {
            locale: "pt", 
            dateFormat: "Y-m-d", // formato date para o db
            altInput: true, 
            altFormat: "d/m/Y", 
            allowInput: true,
            
            // coloca o maxlenght no input do date
            onReady: function(selectedDates, dateStr, instance) {
                instance.altInput.setAttribute('maxlength', '10');
                instance.altInput.addEventListener('input', formatarDataInput);
                
                //mostrar o input da data
                const dataInputVisivel = instance.altInput;
                

                instance.set('onChange', function() {
                    document.getElementById('data_nascimento').dispatchEvent(new Event('change'));
                });
                
                dataInputVisivel.addEventListener('blur', function() {

                    document.getElementById('data_nascimento').dispatchEvent(new Event('change'));
                });
            }
        });

    
        const cpfInput = document.getElementById('cpf');
        if (cpfInput) {
            cpfInput.setAttribute('maxlength', '14'); 
            cpfInput.addEventListener('input', formatarCPF);
        }

        
        const rgInput = document.getElementById('rg');
        if (rgInput) {
            rgInput.setAttribute('maxlength', '12'); 
            rgInput.addEventListener('input', formatarRG);
        }
        
       
        const telInput = document.getElementById('telefone');
        if (telInput) {
            telInput.addEventListener('input', formatarTelefone);
        }
        
        
        const telResponsavelInput = document.getElementById('telefone_responsavel');
        if (telResponsavelInput) {
            telResponsavelInput.addEventListener('input', formatarTelefone);
        }
    });
</script>