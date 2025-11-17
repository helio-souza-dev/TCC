<?php
require_once 'config/database.php';
require_once 'includes/auth.php';


if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}


$message = '';
$error = '';
$estudante = null; 

function validarCPF(string $cpf): bool
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $usuario_id = $_POST['usuario_id'] ?? null;

    //mudar senha
    if ($action === 'change_password') {
        $newPassword = $_POST['new_password'] ?? '';
        if ($usuario_id && strlen($newPassword) >= 8) {

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            

            $sql = "UPDATE usuarios SET senha = ?, forcar_troca_senha = 1 WHERE id = ?";
            executar_consulta($conn, $sql, [$hashedPassword, $usuario_id]);
            
            $message = 'Senha alterada com sucesso! O usuário deverá trocá-la no próximo login.';
        } else {
            $error = 'A nova senha deve ter no mínimo 8 caracteres.';
        }
    }

    
    elseif ($action === 'update') {
        $aluno_id = $_POST['aluno_id'] ?? null;

        if (empty($_POST['cpf']) || !validarCPF($_POST['cpf'])) {
            $error = "O CPF informado é inválido.";

        // empacotar os dados para que todos sejam carregados juntos
        iniciar_transacao($conn);
        try {
            
            $sql_user = "UPDATE usuarios SET nome = ?, email = ?, data_nascimento = ?, telefone = ?, cpf = ?, rg = ?, cidade = ?, endereco = ?, complemento = ? WHERE id = ?";
            executar_consulta($conn, $sql_user, [
                $_POST['nome'], $_POST['email'], $_POST['data_nascimento'], 
                $_POST['telefone'], $_POST['cpf'], $_POST['rg'],
                $_POST['cidade'], $_POST['endereco'], $_POST['complemento'],  
                $usuario_id
            ]);


            $sql_aluno = "UPDATE alunos SET matricula = ?, instrumento = ?, nivel_experiencia = ?, nome_responsavel = ?, telefone_responsavel = ?, email_responsavel = ?, possui_instrumento = ?, preferencia_horario = ?, objetivos = ? WHERE id = ?";
            executar_consulta($conn, $sql_aluno, [
                $_POST['matricula'], $_POST['instrumento'], $_POST['nivel_experiencia'],
                $_POST['nome_responsavel'], $_POST['telefone_responsavel'], $_POST['email_responsavel'] ?? null,
                isset($_POST['possui_instrumento']) ? 1 : 0,
                $_POST['preferencia_horario'], $_POST['objetivos'], 
                $aluno_id
            ]);
            

            confirmar_transacao($conn);
            $message = 'Dados do aluno atualizados com sucesso!';

        } catch (Exception $e) {

            reverter_transacao($conn);
            $error = 'Erro ao atualizar dados: ' . $e->getMessage();
        }
    }
    }
}




$aluno_id_to_load = $_GET['aluno_id'] ?? $_POST['aluno_id'] ?? null;


if ($aluno_id_to_load) {

    $sql = "SELECT a.*, u.*, a.id AS aluno_id, u.id AS usuario_id
            FROM alunos a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.id = ? AND u.tipo = 'aluno'
            LIMIT 1";
            
    $stmt = executar_consulta($conn, $sql, [$aluno_id_to_load]);
    
    if ($stmt) {

        $estudante = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$estudante) {
        $error = 'Aluno não encontrado.';
    }
}

?>

<div class="card">
    <h3> Editar Aluno</h3>
    
    <?php if($message): ?><div class="alert alert-success"> <?php echo $message; ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"> <?php echo $error; ?></div><?php endif; ?>
    
    <?php if ($estudante): ?>
    
        <div class="form-section" style="margin-top: 30px;">
            <h4> Alterar Senha</h4>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($estudante['usuario_id']); ?>">
                   <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars($estudante['aluno_id']); ?>">
                <div class="form-group">
                    <label for="new_password">Nova Senha (mínimo 8 caracteres):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="password" id="new_password" name="new_password" required minlength="8" style="flex-grow: 1;" readonly placeholder="Clique em 'Gerar' para criar a senha">
                        <button type="button" id="btnGerarSenhaEdicao" class="btn btn-secondary">Gerar</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning" onclick="return confirm('Tem certeza que deseja alterar a senha deste usuário?')">Salvar Nova Senha</button>
            </form>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars($estudante['aluno_id']); ?>">
            <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($estudante['usuario_id']); ?>">

            <div class="form-section">
                <h4> Dados Pessoais e Endereço</h4>
                <div class="form-row">
                    <div class="form-group"><label>Nome Completo:</label><input type="text" name="nome" required value="<?php echo htmlspecialchars($estudante['nome'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Email:</label><input type="email" name="email" required value="<?php echo htmlspecialchars($estudante['email'] ?? ''); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>CPF:</label><input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($estudante['cpf'] ?? ''); ?>"></div>
                    <div class="form-group"><label>RG:</label><input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($estudante['rg'] ?? ''); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Data de Nascimento:</label><input type="text" id="data_nascimento" name="data_nascimento" required value="<?php echo htmlspecialchars($estudante['data_nascimento'] ?? ''); ?>" placeholder="Selecione ou digite a data"></div>
                    <div class="form-group"><label>Telefone:</label><input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($estudante['telefone'] ?? ''); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label for="cidade">Cidade:</label><input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($estudante['cidade'] ?? ''); ?>"></div>
                    <div class="form-group"><label for="endereco">Endereço:</label><input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($estudante['endereco'] ?? ''); ?>"></div>
                </div>
                <div class="form-group"><label for="complemento">Complemento:</label><input type="text" id="complemento" name="complemento" value="<?php echo htmlspecialchars($estudante['complemento'] ?? ''); ?>"></div>
            </div>
            
            <div class="form-section">
                <h4> Dados Musicais e de Matrícula</h4>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="matricula">Matrícula:</label>
                        <input readonly type="text" id="matricula" name="matricula" required value="<?php echo htmlspecialchars($estudante['matricula'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                       <label for="instrumento">Instrumento Principal:</label>
                       <select id="instrumento" name="instrumento" required>
                           <option value="" disabled>-- Selecione --</option>
                           <option value="Violão" <?php echo ($estudante['instrumento'] ?? '') == 'Violão' ? 'selected' : ''; ?>>Violão</option>
                           <option value="Guitarra" <?php echo ($estudante['instrumento'] ?? '') == 'Guitarra' ? 'selected' : ''; ?>>Guitarra</option>
                           <option value="Baixo" <?php echo ($estudante['instrumento'] ?? '') == 'Baixo' ? 'selected' : ''; ?>>Baixo</option>
                           <option value="Bateria" <?php echo ($estudante['instrumento'] ?? '') == 'Bateria' ? 'selected' : ''; ?>>Bateria</option>
                           <option value="Teclado" <?php echo ($estudante['instrumento'] ?? '') == 'Teclado' ? 'selected' : ''; ?>>Teclado</option>
                           <option value="Piano" <?php echo ($estudante['instrumento'] ?? '') == 'Piano' ? 'selected' : ''; ?>>Piano</option>
                           <option value="Canto" <?php echo ($estudante['instrumento'] ?? '') == 'Canto' ? 'selected' : ''; ?>>Canto</option>
                           <option value="Ukulele" <?php echo ($estudante['instrumento'] ?? '') == 'Ukulele' ? 'selected' : ''; ?>>Ukulele</option>
                           <option value="Outro" <?php echo ($estudante['instrumento'] ?? '') == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Nível:</label><select name="nivel_experiencia">
                        <option value="Iniciante" <?php echo ($estudante['nivel_experiencia'] == 'Iniciante') ? 'selected' : ''; ?>>Iniciante</option>
                        <option value="Básico" <?php echo ($estudante['nivel_experiencia'] == 'Básico') ? 'selected' : ''; ?>>Básico</option>
                        <option value="Intermediário" <?php echo ($estudante['nivel_experiencia'] == 'Intermediário') ? 'selected' : ''; ?>>Intermediário</option>
                        <option value="Avançado" <?php echo ($estudante['nivel_experiencia'] == 'Avançado') ? 'selected' : ''; ?>>Avançado</option>
                    </select></div>
                    <div class="form-group">
                        <label for="preferencia_horario">Preferência de Horário:</label>
                        <select id="preferencia_horario" name="preferencia_horario">
                            <option value="" disabled>-- Selecione --</option>
                            <option value="manha" <?php echo ($estudante['preferencia_horario'] ?? '') == 'manha' ? 'selected' : ''; ?>>Manhã</option>
                            <option value="tarde" <?php echo ($estudante['preferencia_horario'] ?? '') == 'tarde' ? 'selected' : ''; ?>>Tarde</option>
                            <option value="noite" <?php echo ($estudante['preferencia_horario'] ?? '') == 'noite' ? 'selected' : ''; ?>>Noite</option>
                        </select>
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label>Possui Instrumento Próprio?</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="possui_instrumento" value="1" <?php echo ($estudante['possui_instrumento'] == 1) ? 'checked' : ''; ?>>
                                <span class="custom-radio"></span> Sim
                            </label>
                            <label>
                                <input type="radio" name="possui_instrumento" value="0" <?php echo ($estudante['possui_instrumento'] != 1) ? 'checked' : ''; ?>>
                                <span class="custom-radio"></span> Não
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="objetivos">Objetivos com a Música:</label>
                        <textarea id="objetivos" name="objetivos" rows="3"><?php echo htmlspecialchars($estudante['objetivos'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div id="responsavel-fields">
                <h4> Dados do Responsável</h4>
                <div class="form-row">
                    <div class="form-group"><label>Nome do Responsável:</label><input type="text" name="nome_responsavel" value="<?php echo htmlspecialchars($estudante['nome_responsavel'] ?? ''); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Telefone do Responsável:</label><input type="text" id="telefone_responsavel" name="telefone_responsavel" value="<?php echo htmlspecialchars($estudante['telefone_responsavel'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Email do Responsável:</label><input type="email" id="email_responsavel" name="email_responsavel" value="<?php echo htmlspecialchars($estudante['email_responsavel'] ?? ''); ?>"></div>
                </div>
            </div>
            
            <div class="flex gap-10 mt-20">
                <button type="submit" class="btn btn-primary"> Salvar Alterações</button>
                <a href="dashboard.php?page=alunos" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {
    const dataNascInput = document.getElementById('data_nascimento');
    if (!dataNascInput) return; 

    const responsavelFields = document.getElementById('responsavel-fields');
    
    function checaridade() {
        if (!dataNascInput.value) return;
        const dataNasc = new Date(dataNascInput.value);
        const hoje = new Date();
        let idade = hoje.getFullYear() - dataNasc.getFullYear();
        const m = hoje.getMonth() - dataNasc.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < dataNasc.getDate())) {
            idade--;
        }

        const nomeResponsavel = "<?php echo htmlspecialchars($estudante['nome_responsavel'] ?? ''); ?>";
        if (idade < 18 || nomeResponsavel.length > 0) {
             responsavelFields.style.display = 'block';
        } else {
             responsavelFields.style.display = 'none';
        }
    }

    dataNascInput.addEventListener('change', checarIdade);
    checaridade(); 
});
</script>

<script>

   const botaoGerar = document.getElementById('btnGerarSenhaEdicao'); 
   const inputSenha = document.getElementById('new_password');


    function gerarSenhaJS(tamanho = 8) {
        const caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const tamanhoStr = caracteres.length;
        let strAleatorio = '';


const randomValues = new Uint32Array(tamanho);
        window.crypto.getRandomValues(randomValues);

        for (let i = 0; i < tamanho; i++) {
            const index = randomValues[i] % tamanhoStr;
            strAleatorio += caracteres[index];
        }
        return strAleatorio;
    }


    if (botaoGerar) { 
        botaoGerar.addEventListener('click', function() {

            const novaSenha = gerarSenhaJS(8); 
            
            inputSenha.value = novaSenha;

            inputSenha.type = 'text';

            botaoGerar.disabled = true;
            botaoGerar.textContent = 'Gerada!'; 
        });
    }
</script>

<script>
    function formatarDataInput(event) {
        let input = event.target;
        let valor = input.value.replace(/\D/g, '');
        let tamanho = valor.length;

        if (tamanho > 2) {
            valor = valor.substring(0, 2) + '/' + valor.substring(2);
        }
        if (tamanho > 4) {
            valor = valor.substring(0, 5) + '/' + valor.substring(5, 9); 
        }
        input.value = valor;
    }

    
    function formatarCPF(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = value;
    }

    
    function formatarRG(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)$/, '.$1-$2');
        input.value = value;
    }


    function formatarTelefone(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        input.setAttribute('maxlength', '15'); 
        input.value = value;
    }

    document.addEventListener('DOMContentLoaded', function() {
        
        
        flatpickr("#data_nascimento", {
            locale: "pt",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            allowInput: true, 
            
            
            onReady: function(selectedDates, dateStr, instance) {
                instance.altInput.setAttribute('maxlength', '10');
                instance.altInput.addEventListener('input', formatarDataInput);
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