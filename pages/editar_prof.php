<?php
require_once 'config/database.php';
require_once 'includes/auth.php';


if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}


$message = '';
$error = '';
$professor = null;

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

    // muda senha
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

    //atualizar
    elseif ($action === 'update') {
        $professor_id = $_POST['professor_id'] ?? null;
        
        //validar cpf
        if (empty($_POST['cpf']) || !validarCPF($_POST['cpf'])) {
            $error = "O CPF informado é inválido.";
        } else {

            iniciar_transacao($conn);
            try {
                // tabela usuarios
                $sql_user = "UPDATE usuarios SET nome=?, email=?, cpf=?, rg=?, data_nascimento=?, telefone=?, cidade=?, endereco=?, complemento=?, ativo=? WHERE id=?";
                executar_consulta($conn, $sql_user, [
                    $_POST['nome'], $_POST['email'], $_POST['cpf'], $_POST['rg'],
                    $_POST['data_nascimento'], $_POST['telefone'],
                    $_POST['cidade'], $_POST['endereco'], $_POST['complemento'],
                    isset($_POST['ativo']) ? 1 : 0,
                    $usuario_id
                ]);

                // tabela professores
                $sql_prof = "UPDATE professores SET formacao=?, data_contratacao=?, instrumentos_leciona=?, biografia=? WHERE id=?";
                executar_consulta($conn, $sql_prof, [
                    $_POST['formacao'], $_POST['data_contratacao'], $_POST['instrumentos_leciona'],
                    $_POST['biografia'],
                    $professor_id
                ]);

                confirmar_transacao($conn);
                $message = 'Dados do professor atualizados com sucesso!';

            } catch (Exception $e) {
                reverter_transacao($conn);
                $error = 'Erro ao atualizar dados: ' . $e->getMessage();
            }
        }
    }
}


$professor_id_to_load = $_GET['professor_id'] ?? null;

if ($professor_id_to_load) {
    $sql = "SELECT p.*, u.*, p.id AS professor_id, u.id AS usuario_id
            FROM professores p
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.id = ? AND u.tipo = 'professor' LIMIT 1";
            
    $stmt = executar_consulta($conn, $sql, [$professor_id_to_load]);
    
    if ($stmt) {
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
                <input type="hidden" name="professor_id" value="<?php echo htmlspecialchars($professor['professor_id']); ?>">
                
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
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento: <span style="color: red;">*</span></label>
                        <input type="text" id="data_nascimento" name="data_nascimento" required 
                               placeholder="Selecione uma data"
                               value="<?php echo htmlspecialchars($professor['data_nascimento'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="telefone">Telefone:</label>
                        <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" 
                               value="<?php echo htmlspecialchars($professor['telefone'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4> Dados Profissionais e Musicais</h4>
                <div class="form-row">
                    <div class="form-group"><label for="formacao">Formação:</label><input type="text" id="formacao" name="formacao" value="<?php echo htmlspecialchars($professor['formacao'] ?? ''); ?>"></div>
                    <div class="form-group"><label for="data_contratacao">Data de Contratação:</label><input type="text" id="data_contratacao" name="data_contratacao" value="<?php echo htmlspecialchars($professor['data_contratacao'] ?? ''); ?>" placeholder="Selecione ou digite a data"></div>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="instrumentos_leciona">Instrumentos que Leciona:</label>
                        <select id="instrumentos_leciona" name="instrumentos_leciona" required>
                            <option value="" disabled>-- Selecione --</option>
                            <option value="Violão" <?php echo ($professor['instrumentos_leciona'] ?? '') == 'Violão' ? 'selected' : ''; ?>>Violão</option>
                            <option value="Guitarra" <?php echo ($professor['instrumentos_leciona'] ?? '') == 'Guitarra' ? 'selected' : ''; ?>>Guitarra</option>
                            <option value="Baixo" <?php echo ($professor['instrumentos_leciona'] ?? '') == 'Baixo' ? 'selected' : ''; ?>>Baixo</option>
                            <option value="Bateria" <?php echo ($professor['instrumentos_leciona'] ?? '') == 'Bateria' ? 'selected' : ''; ?>>Bateria</option>
                            <option value="Teclado" <?php echo ($professor['instrumentos_leciona'] ?? '') == 'Teclado' ? 'selected' : ''; ?>>Teclado</option>
                            <option value="Piano" <?php echo ($professor['instrumentos_leciona'] ?? '') == 'Piano' ? 'selected' : ''; ?>>Piano</option>
                            <option value="Canto" <?php echo ($professor['instrumentos_leciona'] ?? '') == 'Canto' ? 'selected' : ''; ?>>Canto</option>
                            <option value="Ukulele" <?php echo ($professor['instrumentos_leciona'] ?? '') == 'Ukulele' ? 'selected' : ''; ?>>Ukulele</option>
                            <option value="Outro" <?php echo ($professor['instrumentos_leciona'] ?? '') == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
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
                    <label>
                        <input type="checkbox" name="ativo" value="1" <?php echo ($professor['ativo'] ?? 1) ? 'checked' : ''; ?>>
                        <span class="custom-checkbox"></span> Professor Ativo
                    </label>
                </div>
            </div>

            <div class="flex gap-10 mt-20">
                <button type="submit" class="btn btn-primary"> Salvar Alterações</button>
                <a href="dashboard.php?page=professores" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>

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
            if(inputSenha) { 
                inputSenha.value = novaSenha;
                inputSenha.type = 'text';
                botaoGerar.disabled = true;
                botaoGerar.textContent = 'Gerada!'; 
            }
        });
    }
</script>

<script>

    function formatarDataInput(event) {
        let input = event.target;
        let valor = input.value.replace(/\D/g, '');
        let tamanho = valor.length;
        if (tamanho > 2) { valor = valor.substring(0, 2) + '/' + valor.substring(2); }
        if (tamanho > 4) { valor = valor.substring(0, 5) + '/' + valor.substring(5, 9); }
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
        input.value = value.substring(0, 12); 
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
        
        //calendario contratacao
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#data_contratacao", {
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
            //calendario nascimento
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
        } else {
            console.warn('Flatpickr não foi carregado. Verifique o dashboard.php.');
        }


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
    });
</script>