<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Variáveis para as mensagens.
$message = '';
$error = '';

// A função de validar CPF não muda.
function validarCPF(string $cpf): bool
{
    // remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    // verifica o tamanho
    if (strlen($cpf) != 11) {
        return false;
    }

    // verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;

    // verifica o primeiro dígito
    if ($cpf[9] != $digito1) {
        return false;
    }

    // calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;

    // verifica o segundo dígito
    if ($cpf[10] != $digito2) {
        return false;
    }

    return true;
}


// --- LÓGICA PRINCIPAL: O QUE FAZER QUANDO UM FORMULÁRIO É ENVIADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- AÇÃO: ADICIONAR NOVO PROFESSOR ---
    // --- AÇÃO: ADICIONAR NOVO PROFESSOR ---
    if ($action === 'add') {
        
        // --- INÍCIO DAS VALIDAÇÕES ---
        $data_nascimento = $_POST['data_nascimento'] ?? '';
        $data_contratacao = $_POST['data_contratacao'] ?? '';

        if (empty($_POST['cpf']) || !validarCPF($_POST['cpf'])) {
            $error = "O CPF informado é inválido.";
        } elseif (empty($_POST['senha']) || strlen($_POST['senha']) < 8) {
            $error = "A senha é obrigatória e deve ter no mínimo 8 caracteres.";
        } elseif (empty($data_nascimento)) { 
            $error = "A Data de Nascimento é obrigatória.";
        } elseif (empty($data_contratacao)) { 
            $error = "A Data de Contratação é obrigatória.";
        } else {
            // --- VALIDAÇÃO DAS DATAS ---
            try {
                $hoje = new DateTime();
                $hoje_sem_hora = new DateTime($hoje->format('Y-m-d')); // Pega só a data
                
                // 1. Valida Data de Nascimento
                $data_nascimento_obj = new DateTime($data_nascimento);
                $idade_minima = 18;
                
                if ($data_nascimento_obj > $hoje) {
                    $error = "A data de nascimento não pode ser uma data no futuro.";
                } else {
                    $diferenca = $hoje->diff($data_nascimento_obj);
                    $idade = $diferenca->y;
                    if ($idade < $idade_minima) {
                        $error = "O professor deve ter no mínimo {$idade_minima} anos. Idade informada: {$idade} anos.";
                    }
                }

                // 2. Valida Data de Contratação (SÓ SE A DATA DE NASCIMENTO ESTIVER OK)
                if (empty($error)) {
                    $data_contratacao_obj = new DateTime($data_contratacao);
                    if ($data_contratacao_obj > $hoje_sem_hora) {
                         $error = "A data de contratação não pode ser no futuro.";
                    }
                }

            } catch (Exception $e) {
                $error = "Formato de Data de Nascimento ou Contratação inválido.";
            }
            // --- FIM DA VALIDAÇÃO DE DATAS ---
        }
        
        // Se as validações (incluindo a de idade) passaram, $error estará vazio.
        if (empty($error)) { 
            
            iniciar_transacao($conn);
        
            try {
                // 1. Criptografa a senha
                $hashedPassword = password_hash($_POST['senha'], PASSWORD_DEFAULT);

                // 2. Insere na tabela 'usuarios' (Versão corrigida com telefone)
                $sql_usuario = "INSERT INTO usuarios (nome, email, senha, tipo, cpf, rg, cidade, endereco, complemento, data_nascimento, telefone, forcar_troca_senha)
                VALUES (?, ?, ?, 'professor', ?, ?, ?, ?, ?, ?, ?, 1)";
                
                executar_consulta($conn, $sql_usuario, [
                    $_POST['nome'], $_POST['email'], $hashedPassword, $_POST['cpf'], $_POST['rg'], 
                    $_POST['cidade'], $_POST['endereco'], $_POST['complemento'], 
                    $_POST['data_nascimento'], $_POST['telefone']
                ]);

                // 3. Pega o ID
                $usuario_id = $conn->insert_id;

                // 4. Insere na tabela 'professores'
                $sql_professor = "INSERT INTO professores (usuario_id, data_contratacao, formacao, instrumentos_leciona, biografia)
                                    VALUES (?, ?, ?, ?, ?)";
                executar_consulta($conn, $sql_professor, [
                    $usuario_id, $data_contratacao, $_POST['formacao'],
                    $_POST['instrumentos_leciona'], $_POST['biografia']
                ]);

                confirmar_transacao($conn);
                $message = "Professor cadastrado com sucesso!";

            } catch (Exception $e) {
                reverter_transacao($conn);
                $error = "Erro ao cadastrar professor: " . $e->getMessage();
            }
        }
    
    } 
    // --- AÇÃO: APAGAR UM PROFESSOR ---
    elseif ($action === 'delete') {
        $usuario_id = $_POST['usuario_id'] ?? null;
        if ($usuario_id) {
            $sql = "DELETE FROM usuarios WHERE id = ?";
            executar_consulta($conn, $sql, [$usuario_id]);
            $message = "Professor excluído com sucesso!";
        } else {
            $error = "ID do usuário não fornecido para exclusão.";
        }
    }
}
    
// --- LÓGICA PARA LISTAR OS PROFESSORES NA TABELA ---
$sql_listar = "SELECT p.*, u.nome, u.email, u.created_at, u.ativo, u.cpf, u.rg, p.id as professor_id, u.id as usuario_id
               FROM professores p 
               JOIN usuarios u ON p.usuario_id = u.id 
               ORDER BY u.nome ASC";

$resultado = $conn->query($sql_listar);
$professores = $resultado->fetch_all(MYSQLI_ASSOC);

?>


<div class="card">
    <h3> Cadastrar Novo Professor</h3>
    <?php if($message): ?><div class="alert alert-success"> <?php echo $message; ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"> <?php echo $error; ?></div><?php endif; ?>
    
    <form method="POST" style="margin-bottom: 30px;">
        <input type="hidden" name="action" value="add">
        
        <div class="form-section">
            <h4> Dados Pessoais e de Acesso</h4>
            <div class="form-row">
                <div class="form-group"><label for="nome">Nome Completo:</label><input   type="text" id="nome" name="nome"  value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"required></div>
                <div class="form-group"><label for="email">Email:</label><input  type="email" id="email" name="email"  value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label for="senha">Senha (mín. 8 caracteres):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="password" id="senha" name="senha" minlength="8" style="flex-grow: 1;" readonly placeholder="Clique em 'Gerar' para criar a senha" required>
                        <button type="button" id="btnGerarSenha" class="btn btn-secondary">Gerar</button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="data_nascimento">Data de Nascimento: <span style="color: red;">*</span></label>
                <input type="text" id="data_nascimento" name="data_nascimento" required 
                       placeholder="Selecione uma data"
                       value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
            </div>

             <div class="form-row">
                <div class="form-group"><label for="cpf">CPF:</label><input required type="text" id="cpf" name="cpf" required maxlength="14" value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>" required></div>
                <div class="form-group"><label for="rg">RG:</label><input required type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>" required></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input  minlenght="11" type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" required>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4> Endereço</h4>
            <div class="form-row">
                <div class="form-group"><label for="endereco">Endereço:</label><input type="text" id="endereco" name="endereco" placeholder="Rua, número" value="<?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?>" required></div>
                <div class="form-group"><label for="cidade">Cidade:</label><input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>"></div>
            </div>
            <div class="form-group"><label for="complemento">Complemento:</label><input type="text" id="complemento" name="complemento" placeholder="Apto, bloco, etc." value="<?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?>" required></div>
        </div>

        <div class="form-section">
            <h4> Dados Profissionais e Musicais</h4>
            <div class="form-row">
                <div class="form-group"><label for="formacao">Formação Acadêmica:</label><input type="text" id="formacao" name="formacao" placeholder="Ex: Bacharel em Música" value="<?php echo htmlspecialchars($_POST['formacao'] ?? ''); ?>"></div>
                <div class="form-group">
                    <label for="data_contratacao">Data da Contratação: <span style="color: red;">*</span></label>
                    <input type="text" id="data_contratacao" name="data_contratacao" required 
                           placeholder="Selecione uma data"
                           value="<?php echo htmlspecialchars($_POST['data_contratacao'] ?? date('Y-m-d')); ?>">
                </div>
            </div>
            <div class="form-row">
               <div class="form-group">
                    <label for="instrumentos_leciona">Instrumentos que Leciona:</label>
                    <select id="instrumentos_leciona" name="instrumentos_leciona" required>
                        <option value="" disabled <?php echo empty($_POST['instrumentos_leciona']) ? 'selected' : ''; ?>>-- Selecione um instrumento --</option>
                        <option value="Violão" <?php echo ($_POST['instrumentos_leciona'] ?? '') == 'Violão' ? 'selected' : ''; ?>>Violão</option>
                        <option value="Guitarra" <?php echo ($_POST['instrumentos_leciona'] ?? '') == 'Guitarra' ? 'selected' : ''; ?>>Guitarra</option>
                        <option value="Baixo" <?php echo ($_POST['instrumentos_leciona'] ?? '') == 'Baixo' ? 'selected' : ''; ?>>Baixo</option>
                        <option value="Bateria" <?php echo ($_POST['instrumentos_leciona'] ?? '') == 'Bateria' ? 'selected' : ''; ?>>Bateria</option>
                        <option value="Teclado" <?php echo ($_POST['instrumentos_leciona'] ?? '') == 'Teclado' ? 'selected' : ''; ?>>Teclado</option>
                        <option value="Piano" <?php echo ($_POST['instrumentos_leciona'] ?? '') == 'Piano' ? 'selected' : ''; ?>>Piano</option>
                        <option value="Canto" <?php echo ($_POST['instrumentos_leciona'] ?? '') == 'Canto' ? 'selected' : ''; ?>>Canto</option>
                        <option value="Ukulele" <?php echo ($_POST['instrumentos_leciona'] ?? '') == 'Ukulele' ? 'selected' : ''; ?>>Ukulele</option>
                        <option value="Outro" <?php echo ($_POST['instrumentos_leciona'] ?? '') == 'Outro' ? 'selected' : ''; ?>>Outro (especificar na biografia)</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="biografia">Biografia (opcional):</label>
                <textarea id="biografia" name="biografia" rows="3"><?php echo htmlspecialchars($_POST['biografia'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary"> Cadastrar Professor</button>
    </form>
    
</div> <div class="card">
    <h3> Lista de Professores (<?php echo count($professores); ?>)</h3>
    <?php if(empty($professores)): ?>
        <div class="empty-state"><p>Nenhum professor cadastrado ainda.</p></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table" id="tabelaProfessores">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Instrumentos</th>
                    <th>Status</th>
                    <th>Cadastrado em</th>
                    <?php if (isAdmin()): ?><th>Ações</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($professores as $professor): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($professor['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($professor ['email']); ?></td>
                        <td><?php echo htmlspecialchars($professor['instrumentos_leciona'] ?? '-'); ?></td>
                        <td>
                            <span class="status-badge <?php echo ($professor['ativo'] ?? 1) ? 'status-realizado' : 'status-cancelado'; ?>">
                                <?php echo ($professor['ativo'] ?? 1) ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($professor['created_at'])); ?></td>
                        <?php if (isAdmin()): ?>
                        <td>
                            <div class="flex gap-10">
                                <a href="dashboard.php?page=editar-prof&professor_id=<?php echo htmlspecialchars($professor['professor_id']); ?>" class="btn btn-sm btn-info">Editar</a>
                                
                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Tem certeza que deseja apagar este professor?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($professor['usuario_id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Apagar</button>
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

<script>
// JavaScript não foi alterado.
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
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
        const novaSenha = gerarSenhaJS(8);
        
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
        // Remove tudo que não for dígito
        let valor = input.value.replace(/\D/g, '');
        let tamanho = valor.length;

        // Adiciona a primeira barra (DD/)
        if (tamanho > 2) {
            valor = valor.substring(0, 2) + '/' + valor.substring(2);
        }
        // Adiciona a segunda barra (DD/MM/)
        if (tamanho > 4) {
            // Limita aos 4 dígitos do ano (total de 10 caracteres: DD/MM/AAAA)
            valor = valor.substring(0, 5) + '/' + valor.substring(5, 9); 
        }
        
        // Atualiza o valor no campo
        input.value = valor;
    }


document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Configura o seletor para DATA DE NASCIMENTO
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

    // 2. Configura o seletor para DATA DE CONTRATAÇÃO
    flatpickr("#data_contratacao", {
        locale: "pt",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        allowInput: true, // Permite digitação
        maxDate: "today",
        
        // Conecta a máscara e o maxlength
        onReady: function(selectedDates, dateStr, instance) {
            instance.altInput.setAttribute('maxlength', '10');
            instance.altInput.addEventListener('input', formatarDataInput);
        }
    });
    
});
</script>


<script>
// NOVO SCRIPT PARA DATATABLES
document.addEventListener('DOMContentLoaded', function() {
    // Usamos o jQuery, que foi carregado no dashboard.php
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            $('#tabelaProfessores').DataTable({
                "language": {
                    // Arquivo de tradução oficial do DataTables para PT-BR
                    "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json" 
                }
            });
        });
    }
});
</script>

<script>
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
    
    // Função para formatar Telefone ( (00) 00000-0000 )
    function formatarTelefone(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        input.setAttribute('maxlength', '15'); // (00) 00000-0000
        input.value = value;
    }

    const cpfInput = document.getElementById('cpf');
        if (cpfInput) {
            cpfInput.setAttribute('maxlength', '14'); // 000.000.000-00
            cpfInput.addEventListener('input', formatarCPF);
        }

        // 3. Adiciona a máscara de RG
        const rgInput = document.getElementById('rg');
        if (rgInput) {
            rgInput.setAttribute('maxlength', '12'); // 00.000.000-0
            rgInput.addEventListener('input', formatarRG);
        }
        
        // 4. Adiciona a máscara de Telefone (Aluno)
        const telInput = document.getElementById('telefone');
        if (telInput) {
            telInput.addEventListener('input', formatarTelefone);
        }

</script>
