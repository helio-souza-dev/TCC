<?php
// Inclui os arquivos de configuração e autenticação.
// A variável $conn com a conexão já está disponível aqui.
require_once 'config/database.php';
require_once 'includes/auth.php';

// Variáveis para guardar as mensagens de sucesso ou erro.
$message = '';
$error = '';

// A função para validar o CPF não mexe com o banco de dados, então ela continua igual.
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


// --- LÓGICA PRINCIPAL: O QUE FAZER QUANDO UM FORMULÁRIO É ENVIADO ---

// Verifica se a página foi carregada através de um envio de formulário (método POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Pega a 'action' para saber se é um 'add' (adicionar) ou 'delete' (apagar).
    $action = $_POST['action'] ?? '';

    // --- AÇÃO: ADICIONAR NOVO ALUNO ---
   if ($action === 'add') {
        // 1. Validação de CPF
        if (empty($_POST['cpf']) || !validarCPF($_POST['cpf'])) {
            $error = "O CPF informado é inválido.";
        // 2. Validação de Senha
        } elseif (empty($_POST['senha']) || strlen($_POST['senha']) < 8) {
            $error = "A senha é obrigatória e deve ter no mínimo 8 caracteres.";

        // 3. Validação de Data de Nascimento (se estiver vazio)
        } elseif (empty($_POST['data_nascimento'])) { // Validação de campo obrigatório
            $error = "A Data de Nascimento é obrigatória.";
            
        } else {
            
            // --- VALIDAÇÃO DE IDADE MÍNIMA (4 ANOS) ---
            $data_nascimento = $_POST['data_nascimento'];
            $idade_minima = 4;
        
            try {
                $data_nascimento_obj = new DateTime($data_nascimento);
                $hoje = new DateTime();
                $diferenca = $hoje->diff($data_nascimento_obj);
                $idade = $diferenca->y; // Pega a idade em anos

                if ($idade < $idade_minima) {
                    $error = "O aluno deve ter no mínimo {$idade_minima} anos para ser cadastrado. Idade atual: {$idade} anos.";
                }
            } catch (Exception $e) {
                $error = "Data de Nascimento inválida.";
            }
            // --- FIM DA VALIDAÇÃO DE IDADE MÍNIMA ---
        }
        
        // Se as validações (incluindo a de idade) passaram, $error estará vazio.
        if (empty($error)) { // Executa o cadastro APENAS se não houver erro
            
            // Inicia uma transação: ou tudo funciona, ou nada é salvo.
            // Isso evita que um usuário seja criado sem os dados de aluno.
            iniciar_transacao($conn);
        
            try {
                // 1. Criptografa a senha para guardar no banco de forma segura.
                $hashedPassword = password_hash($_POST['senha'], PASSWORD_DEFAULT);

                // 2. Insere na tabela 'usuarios'.
                $sql_usuario = "INSERT INTO usuarios (nome, email, senha, tipo, cpf, rg, cidade, endereco, complemento, data_nascimento, telefone, forcar_troca_senha)
                VALUES (?, ?, ?, 'aluno', ?, ?, ?, ?, ?, ?, ?, 1)";
                $stmt_usuario = executar_consulta($conn, $sql_usuario, [
                    $_POST['nome'], $_POST['email'], $hashedPassword, $_POST['cpf'], $_POST['rg'], 
                    $_POST['cidade'], $_POST['endereco'], $_POST['complemento'], $_POST['data_nascimento'], $_POST['telefone']
                ]);

                // Pega o ID do usuário que acabamos de criar.
                $usuario_id = $conn->insert_id;

                // 3. Insere na tabela 'alunos', usando o ID do usuário.
                $sql_aluno = "INSERT INTO alunos (usuario_id, matricula, nome_responsavel, telefone_responsavel, instrumento, nivel_experiencia, tipo_aula_desejada, preferencia_horario, possui_instrumento, objetivos)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_aluno = executar_consulta($conn, $sql_aluno, [
                    $usuario_id, date('Y') . $usuario_id, // Cria uma matrícula simples
                    $_POST['nome_responsavel'], $_POST['telefone_responsavel'], $_POST['instrumento'],
                    $_POST['nivel_experiencia'], $_POST['tipo_aula_desejada'], $_POST['preferencia_horario'],
                    isset($_POST['possui_instrumento']) ? 1 : 0, $_POST['objetivos']
                ]);

                // Se chegou até aqui sem erros, confirma as alterações no banco.
                confirmar_transacao($conn);
                $message = "Aluno cadastrado com sucesso!";

            } catch (Exception $e) {
                // Se deu qualquer erro, desfaz tudo o que foi feito.
                reverter_transacao($conn);
                $error = "Erro ao cadastrar aluno: " . $e->getMessage();
            }
        }
    }

    // --- AÇÃO: APAGAR UM ALUNO ---
    elseif ($action === 'delete') {
        $usuario_id = $_POST['usuario_id'] ?? null;
        if ($usuario_id) {
            $sql = "DELETE FROM usuarios WHERE id = ?";
            $stmt = executar_consulta($conn, $sql, [$usuario_id]);
            
            // Graças à configuração do banco (ON DELETE CASCADE),
            // apagar o usuário também apaga os dados do aluno associado.
            $message = "Aluno excluído com sucesso!";
        } else {
            $error = "ID do usuário não fornecido para exclusão.";
        }
    }
}


// --- LÓGICA PARA LISTAR OS ALUNOS NA TABELA ---

// Escreve o comando SQL para buscar todos os alunos e juntar com seus dados da tabela de usuários.
$sql_listar = "SELECT a.*, u.nome, u.email, u.created_at FROM alunos a JOIN usuarios u ON a.usuario_id = u.id ORDER BY u.nome ASC";

// Executa a consulta diretamente, pois não há parâmetros do usuário aqui.
$resultado = $conn->query($sql_listar);

// Pega todos os resultados e guarda no array $alunos.
$alunos = $resultado->fetch_all(MYSQLI_ASSOC);

?>

<div class="card">
    <h3> Cadastrar Novo Aluno</h3>
    <?php if($message): ?>
        <div class="alert alert-success"> <?php echo $message; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-error"> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" style="margin-bottom: 30px;">
        <input type="hidden" name="action" value="add">

        <div class="form-section">
            <h4>Dados Pessoais</h4>
    <!--Email-->
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                </div>
    <!--Email-->
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
    <!--Senha e Gerar senha-->
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label for="senha">Senha (mín. 8 caracteres):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="password" id="senha" name="senha" required minlength="8" style="flex-grow: 1;" readonly placeholder="Clique em 'Gerar' para criar a senha">
                        <button type="button" id="btnGerarSenha" class="btn btn-secondary">Gerar</button>
                    </div>
                </div>
            </div>
    <!--Cpf-->
            <div class="form-row">
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" required value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
                </div>
    <!--Rg-->
                <div class="form-group">
                    <label for="rg">RG:</label>
                    <input type="text" id="rg" name="rg" maxlength="12" required value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>">
                </div>
            </div>
    <!--Data de Nascimento-->
            <div class="form-group">
                <label for="data_nascimento">Data de Nascimento: <span style="color: red;">*</span></label>
                <input type="text" id="data_nascimento" name="data_nascimento" required 
                       placeholder="Selecione uma data"
                       value="<?php echo htmlspecialchars($_POST['data_contratacao'] ?? date('Y-m-d')); ?>">
            </div>
    <!--Telefone-->
            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
                </div>
            </div>

        </div> 
        
        <div class="form-section"> <h4>Endereço</h4>

        <div class="form-row">
                <div class="form-group">
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 2;"> <label for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco" placeholder="Ex: Rua, Número" value="<?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="complemento">Complemento:</label>
                    <input type="text" id="complemento" name="complemento" placeholder="Ex: Apto, Bloco" value="<?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?>">
                </div>
            </div>

            
        </div>

        <div class="form-section">
            <h4>Dados Musicais</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="instrumento">Instrumento Principal:</label>
                    <input type="text" id="instrumento" name="instrumento" placeholder="Ex: Violão" value="<?php echo htmlspecialchars($_POST['instrumento'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="nivel_experiencia">Nível de Experiência:</label>
                    <select id="nivel_experiencia" name="nivel_experiencia">
                        <option value="Iniciante" <?php echo ($_POST['nivel_experiencia'] ?? '') == 'Iniciante' ? 'selected' : ''; ?>>Iniciante</option>
                        <option value="Básico" <?php echo ($_POST['nivel_experiencia'] ?? '') == 'Básico' ? 'selected' : ''; ?>>Básico</option>
                        <option value="Intermediário" <?php echo ($_POST['nivel_experiencia'] ?? '') == 'Intermediário' ? 'selected' : ''; ?>>Intermediário</option>
                        <option value="Avançado" <?php echo ($_POST['nivel_experiencia'] ?? '') == 'Avançado' ? 'selected' : ''; ?>>Avançado</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_aula_desejada">Tipo de Aula Desejada:</label>
                    <input type="text" id="tipo_aula_desejada" name="tipo_aula_desejada" placeholder="Ex: Aula em grupo, individual" value="<?php echo htmlspecialchars($_POST['tipo_aula_desejada'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="preferencia_horario">Preferência de Horário:</label>
                    <input type="text" id="preferencia_horario" name="preferencia_horario" placeholder="Ex: Manhã, Sábados" value="<?php echo htmlspecialchars($_POST['preferencia_horario'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Possui Instrumento Próprio?</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="possui_instrumento" value="1" <?php echo ($_POST['possui_instrumento'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            <span class="custom-radio"></span>
                            Sim
                        </label>
                        <label>
                            <input type="radio" name="possui_instrumento" value="0" <?php echo ($_POST['possui_instrumento'] ?? '0') == '0' ? 'checked' : ''; ?>>
                            <span class="custom-radio"></span>
                            Não
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width">
                    <label for="objetivos">Objetivos com a Música:</label>
                    <textarea id="objetivos" name="objetivos" rows="3" placeholder="Ex: Tocar como hobby, me profissionalizar..."><?php echo htmlspecialchars($_POST['objetivos'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <div id="responsavel-fields" style="display: none;">
            <div class="form-section">
                <h4> Dados do Responsável (Obrigatório para menores de 18 anos)</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome_responsavel">Nome Completo do Responsável:</label>
                        <input type="text" id="nome_responsavel" name="nome_responsavel" value="<?php echo htmlspecialchars($_POST['nome_responsavel'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="telefone_responsavel">Telefone do Responsável:</label>
                        <input type="text" id="telefone_responsavel" name="telefone_responsavel" value="<?php echo htmlspecialchars($_POST['telefone_responsavel'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary"> Cadastrar Aluno</button>
    </form>
</div>

<div class="card">
    <h3> Lista de Alunos (<?php echo count($alunos); ?>)</h3>
    
    <?php if(empty($alunos)): ?>
        <div class="empty-state"><p>Nenhum aluno cadastrado ainda.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" id="tabelaAlunos">
                <thead>
                    <tr>
                        <th> Nome</th>
                        <th> Email</th>
                        <th> Matrícula</th>
                        <th> Instrumento</th>
                        <th> Nível</th>
                        <th> Responsável</th>
                        <th> Cadastrado</th>
                        <?php if (isAdmin()): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($alunos as $aluno): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($aluno['nome'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo htmlspecialchars($aluno['email'] ?? 'N/A'); ?></td>
                            <td><strong><?php echo htmlspecialchars($aluno['matricula']); ?></strong></td>
                            <td><?php echo htmlspecialchars($aluno['instrumento'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($aluno['nivel_experiencia'] ?? '-'); ?></td>
                            <td>
                                <?php if (!empty($aluno['nome_responsavel'])): ?>
                                    <strong><?php echo htmlspecialchars($aluno['nome_responsavel']); ?></strong>
                                    <?php if (!empty($aluno['telefone_responsavel'])): ?>
                                        <br><small> <?php echo htmlspecialchars($aluno['telefone_responsavel']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($aluno['created_at'])); ?></td>
                            <?php if (isAdmin()): ?>
                                <td>
                                    <div class="flex gap-10">
                                        <a href="dashboard.php?page=editar-aluno&aluno_id=<?php echo htmlspecialchars($aluno['id']); ?>" class="btn btn-sm btn-info">Editar</a>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars($aluno['id']); ?>">
                                            <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($aluno['usuario_id'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger delete-btn">Apagar</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- idade -->

<script> // verifica a idade do aluno
document.addEventListener('DOMContentLoaded', function() {
    const dataNascimentoInput = document.getElementById('data_nascimento');
    const responsavelFields = document.getElementById('responsavel-fields');
    const nomeResponsavelInput = document.getElementById('nome_responsavel');

    function checkAge() {
        if (!dataNascimentoInput.value) {
            responsavelFields.style.display = 'none';
            nomeResponsavelInput.removeAttribute('required');
            return;
        }

        const dataNasc = new Date(dataNascimentoInput.value);
        const hoje = new Date();
        let idade = hoje.getFullYear() - dataNasc.getFullYear();
        const m = hoje.getMonth() - dataNasc.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < dataNasc.getDate())) {
            idade--;
        }

        if (idade < 18) {
            responsavelFields.style.display = 'block';
            nomeResponsavelInput.setAttribute('required', 'required');
        } else {
            responsavelFields.style.display = 'none';
            nomeResponsavelInput.removeAttribute('required');
        }
    }

    dataNascimentoInput.addEventListener('change', checkAge);
    checkAge(); // Executa ao carregar a página para o caso de o formulário ser recarregado com dados
    
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            if (confirm('Tem certeza que deseja apagar este aluno? Esta ação é irreversível.')) {
                form.submit();
            }
        });
    });
});
</script>

<script>
    // 1. Pega os elementos que acabamos de criar no HTML
    const botaoGerar = document.getElementById('btnGerarSenha');
    const inputSenha = document.getElementById('senha');

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
        const novaSenha = gerarSenhaJS(8); // Gera uma senha de 12 caracteres
        
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
document.addEventListener('DOMContentLoaded', function() {
    
    // Configura o seletor de DATA (em Português)
    // O ID correto neste formulário é "data_contratacao"
    flatpickr("#data_nascimento", {
        locale: "pt", // Usa a tradução que carregamos
        dateFormat: "Y-m-d", // Formato que o banco de dados entende
        altInput: true, // Mostra um formato amigável para o usuário
        altFormat: "d/m/Y", // Formato amigável
        // Removemos a opção "minDate: 'today'" porque a data de contratação pode ser no passado.
    });
    
});
</script>



<script>
// NOVO SCRIPT PARA DATATABLES
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            $('#tabelaAlunos').DataTable({
                "language": {
                    // Arquivo de tradução oficial do DataTables para PT-BR
                    "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json" 
                }
            });
        });
    }
});
</script>


<!--adicionar caracteres especiais do RG-->

<script>
document.getElementById('rg').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
  value = value.replace(/^(\d{2})(\d)/, '$1.$2');       // Adiciona o primeiro ponto
  value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3'); // Adiciona o segundo ponto
  value = value.replace(/\.(\d{3})(\d)$/, '.$1-$2');    // Adiciona o hífen
    e.target.value = value;
});
</script>