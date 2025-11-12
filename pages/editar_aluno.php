<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Garante que apenas administradores podem acessar esta página.
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

// Variáveis para mensagens e para guardar os dados do aluno.
$message = '';
$error = '';
$student = null; // Começa como nulo.

// --- LÓGICA 1: PROCESSAR FORMULÁRIOS ENVIADOS (POST) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $usuario_id = $_POST['usuario_id'] ?? null;

    // AÇÃO: MUDAR A SENHA
    if ($action === 'change_password') {
        $newPassword = $_POST['new_password'] ?? '';
        if ($usuario_id && strlen($newPassword) >= 8) {
            // Criptografa a nova senha de forma segura.
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Prepara e executa o UPDATE na tabela de usuários.
            $sql = "UPDATE usuarios SET senha = ?, forcar_troca_senha = 1 WHERE id = ?";
            executar_consulta($conn, $sql, [$hashedPassword, $usuario_id]);
            
            $message = 'Senha alterada com sucesso! O usuário deverá trocá-la no próximo login.';
        } else {
            $error = 'A nova senha deve ter no mínimo 8 caracteres.';
        }
    }

    // AÇÃO: ATUALIZAR DADOS DO ALUNO
    elseif ($action === 'update') {
        $aluno_id = $_POST['aluno_id'] ?? null;
        
        // Inicia a transação para garantir que as duas tabelas sejam atualizadas juntas.
        iniciar_transacao($conn);
        try {
            // 1. Atualiza a tabela 'usuarios' com os dados pessoais.
            $sql_user = "UPDATE usuarios SET nome = ?, email = ?, data_nascimento = ?, telefone = ?, cpf = ?, rg = ? WHERE id = ?";
            executar_consulta($conn, $sql_user, [
                $_POST['nome'], $_POST['email'], $_POST['data_nascimento'], 
                $_POST['telefone'], $_POST['cpf'], $_POST['rg'], $usuario_id
            ]);

            // 2. Atualiza a tabela 'alunos' com os dados musicais.
            $sql_aluno = "UPDATE alunos SET matricula = ?, instrumento = ?, nivel_experiencia = ?, nome_responsavel = ?, telefone_responsavel = ?, email_responsavel = ?, possui_instrumento = ? WHERE id = ?";
            executar_consulta($conn, $sql_aluno, [
                $_POST['matricula'], $_POST['instrumento'], $_POST['nivel_experiencia'],
                $_POST['nome_responsavel'], $_POST['telefone_responsavel'], $_POST['email_responsavel'] ?? null,
                isset($_POST['possui_instrumento']) ? 1 : 0,
                $aluno_id
            ]);
            
            // Se tudo deu certo, confirma as alterações no banco.
            confirmar_transacao($conn);
            $message = 'Dados do aluno atualizados com sucesso!';

        } catch (Exception $e) {
            // Se algo deu errado, desfaz tudo.
            reverter_transacao($conn);
            $error = 'Erro ao atualizar dados: ' . $e->getMessage();
        }
    }
}

// --- LÓGICA 2: CARREGAR DADOS DO ALUNO PARA MOSTRAR NO FORMULÁRIO ---

// Pega o ID do aluno da URL (GET) ou do formulário (POST)
$aluno_id_to_load = $_GET['aluno_id'] ?? $_POST['aluno_id'] ?? null;

// Se um ID foi encontrado...
if ($aluno_id_to_load) {
    // ...busca os dados no banco.
    $sql = "SELECT a.*, u.*, a.id AS aluno_id, u.id AS usuario_id
            FROM alunos a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.id = ? AND u.tipo = 'aluno'
            LIMIT 1";
            
    $stmt = executar_consulta($conn, $sql, [$aluno_id_to_load]);
    
    if ($stmt) {
        // Guarda os dados do aluno no array $student para usar no HTML.
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$student) {
        $error = 'Aluno não encontrado.';
    }
}

?>

<div class="card">
    <h3> Editar Aluno</h3>
    
    <?php if($message): ?><div class="alert alert-success"> <?php echo $message; ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"> <?php echo $error; ?></div><?php endif; ?>
    
    <?php if ($student): ?>
    
        <div class="form-section" style="margin-top: 30px;">
            <h4> Alterar Senha</h4>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($student['usuario_id']); ?>">
                   <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars($student['aluno_id']); ?>">
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
            <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars($student['aluno_id']); ?>">
            <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($student['usuario_id']); ?>">

            <div class="form-section">
                <h4> Dados Pessoais</h4>
                <div class="form-row">
                    <div class="form-group"><label>Nome Completo:</label><input type="text" name="nome" required value="<?php echo htmlspecialchars($student['nome'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Email:</label><input type="email" name="email" required value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>CPF:</label><input type="text" name="cpf" value="<?php echo htmlspecialchars($student['cpf'] ?? ''); ?>"></div>
                    <div class="form-group"><label>RG:</label><input type="text" name="rg" value="<?php echo htmlspecialchars($student['rg'] ?? ''); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Data de Nascimento:</label><input type="text" id="data_nascimento" name="data_nascimento" required value="<?php echo htmlspecialchars($student['data_nascimento'] ?? ''); ?>" placeholder="Selecione ou digite a data"></div>
                    <div class="form-group"><label>Telefone:</label><input type="text" name="telefone" value="<?php echo htmlspecialchars($student['telefone'] ?? ''); ?>"></div>
                </div>
            </div>
            
            <div class="form-section">
                <h4> Dados Musicais e de Matrícula</h4>
                 <div class="form-row">
                    <div class="form-group"><label>Matrícula:</label><input type="text" name="matricula" required value="<?php echo htmlspecialchars($student['matricula'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Instrumento:</label><input type="text" name="instrumento" required value="<?php echo htmlspecialchars($student['instrumento'] ?? ''); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Nível:</label><select name="nivel_experiencia">
                        <option value="Iniciante" <?php echo ($student['nivel_experiencia'] == 'Iniciante') ? 'selected' : ''; ?>>Iniciante</option>
                        <option value="Básico" <?php echo ($student['nivel_experiencia'] == 'Básico') ? 'selected' : ''; ?>>Básico</option>
                        <option value="Intermediário" <?php echo ($student['nivel_experiencia'] == 'Intermediário') ? 'selected' : ''; ?>>Intermediário</option>
                        <option value="Avançado" <?php echo ($student['nivel_experiencia'] == 'Avançado') ? 'selected' : ''; ?>>Avançado</option>
                    </select></div>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label>Possui Instrumento Próprio?</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="possui_instrumento" value="1" <?php echo ($student['possui_instrumento'] == 1) ? 'checked' : ''; ?>>
                                <span class="custom-radio"></span> Sim
                            </label>
                            <label>
                                <input type="radio" name="possui_instrumento" value="0" <?php echo ($student['possui_instrumento'] != 1) ? 'checked' : ''; ?>>
                                <span class="custom-radio"></span> Não
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="responsavel-fields">
                <h4> Dados do Responsável</h4>
                <div class="form-row">
                    <div class="form-group"><label>Nome do Responsável:</label><input type="text" name="nome_responsavel" value="<?php echo htmlspecialchars($student['nome_responsavel'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Telefone do Responsável:</label><input type="text" name="telefone_responsavel" value="<?php echo htmlspecialchars($student['telefone_responsavel'] ?? ''); ?>"></div>
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
// JavaScript não foi alterado.
document.addEventListener('DOMContentLoaded', function() {
    const dataNascInput = document.getElementById('data_nascimento');
    if (!dataNascInput) return; // Garante que o script não quebre em outras páginas

    const responsavelFields = document.getElementById('responsavel-fields');
    
    function checkAge() {
        if (!dataNascInput.value) return;
        const dataNasc = new Date(dataNascInput.value);
        const hoje = new Date();
        let idade = hoje.getFullYear() - dataNasc.getFullYear();
        const m = hoje.getMonth() - dataNasc.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < dataNasc.getDate())) {
            idade--;
        }
        
        responsavelFields.style.display = (idade < 18) ? 'block' : 'none';
    }

    dataNascInput.addEventListener('change', checkAge);
    checkAge(); // Verifica a idade ao carregar a página
});
</script>

<script>
    // 1. Pega os elementos que acabamos de criar no HTML
   const botaoGerar = document.getElementById('btnGerarSenhaEdicao'); // <-- ID do novo botão
   const inputSenha = document.getElementById('new_password');

    // 2. A sua função de gerar senha, "traduzida" para JavaScript
    function gerarSenhaJS(tamanho = 8) {
        const caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const tamanhoStr = caracteres.length;
        let strAleatorio = '';

        // Em JS, usamos crypto.getRandomValues para segurança
       // Linha Correta:
const randomValues = new Uint32Array(tamanho);
        window.crypto.getRandomValues(randomValues);

        for (let i = 0; i < tamanho; i++) {
            // Isso é o equivalente seguro de 'random_int'
            const index = randomValues[i] % tamanhoStr;
            strAleatorio += caracteres[index];
        }
        return strAleatorio;
    }

    // 3. Adiciona o "ouvinte" de clique no botão
    botaoGerar.addEventListener('click', function() {
        // Quando o botão for clicado:
        // 1. Gera uma nova senha
        const novaSenha = gerarSenhaJS(8); // Gera uma senha de 8 caracteres
        
        // 2. Coloca a senha gerada no campo de input
        inputSenha.value = novaSenha;
        
        // 3. Muda o tipo para 'text' para o usuário ver a senha
        inputSenha.type = 'text';

        // 4. (NOVO) Desabilita o botão para que só possa ser gerada uma vez
        botaoGerar.disabled = true;
        botaoGerar.textContent = 'Gerada!'; // Muda o texto do botão
    });

    // (O listener de 'input' foi removido, pois o campo é readonly)
</script>

<script>
    // Função pura de JS para formatar data (DD/MM/AAAA)
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

    // Função para formatar CPF (000.000.000-00)
    function formatarCPF(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = value;
    }

    // Função para formatar RG (00.000.000-0)
    function formatarRG(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)$/, '.$1-$2');
        input.value = value;
    }

    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Configura o seletor de DATA (em Português)
        flatpickr("#data_nascimento", {
            locale: "pt",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            allowInput: true, // Permite digitação
            
            // Conecta a máscara e o maxlength
            onReady: function(selectedDates, dateStr, instance) {
                instance.altInput.setAttribute('maxlength', '10');
                instance.altInput.addEventListener('input', formatarDataInput);
            }
        });

        // 2. Adiciona a máscara de CPF (nesta página o input não tem ID, usamos o name)
        const cpfInput = document.querySelector('input[name="cpf"]');
        if (cpfInput) {
            cpfInput.setAttribute('maxlength', '14'); // 000.000.000-00
            cpfInput.addEventListener('input', formatarCPF);
        }

        // 3. Adiciona a máscara de RG (nesta página o input não tem ID, usamos o name)
        const rgInput = document.querySelector('input[name="rg"]');
        if (rgInput) {
            rgInput.setAttribute('maxlength', '12'); // 00.000.000-0
            rgInput.addEventListener('input', formatarRG);
        }
        
    });
</script>