<?php
// Inclui os arquivos de configuração e autenticação.
// A variável $conn com a conexão já está disponível aqui.
require_once 'config/database.php';
require_once 'includes/auth.php';

// Variáveis para guardar as mensagens de sucesso ou erro.
$message = '';
$error = '';


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

    // add aluno
   if ($action === 'add') {

        if (empty($_POST['cpf']) || !validarCPF($_POST['cpf'])) {
            $error = "O CPF informado é inválido.";

        } elseif (empty($_POST['senha']) || strlen($_POST['senha']) < 8) {
            $error = "A senha é obrigatória e deve ter no mínimo 8 caracteres.";


        } elseif (empty($_POST['data_nascimento'])) { 
            $error = "A Data de Nascimento é obrigatória.";
            
        } else {
            
            // idade minima
            $data_nascimento = $_POST['data_nascimento'];
            $idade_minima = 4;
        
            try {
                $data_nascimento_obj = new DateTime($data_nascimento);
                $hoje = new DateTime();
                $diferenca = $hoje->diff($data_nascimento_obj);
                $idade = $diferenca->y;

                if ($idade < $idade_minima) {
                    $error = "O aluno deve ter no mínimo {$idade_minima} anos para ser cadastrado. Idade atual: {$idade} anos.";
                }
            } catch (Exception $e) {
                $error = "Data de Nascimento inválida.";
            }

        }
        

        if (empty($error)) { 
            

            iniciar_transacao($conn);
        
            try {

                $hashedPassword = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    
                $sql_usuario = "INSERT INTO usuarios (nome, email, senha, tipo, cpf, rg, cidade, endereco, complemento, data_nascimento, telefone, forcar_troca_senha)
                VALUES (?, ?, ?, 'aluno', ?, ?, ?, ?, ?, ?, ?, 1)";
                $stmt_usuario = executar_consulta($conn, $sql_usuario, [
                    $_POST['nome'], $_POST['email'], $hashedPassword, $_POST['cpf'], $_POST['rg'], 
                    $_POST['cidade'], $_POST['endereco'], $_POST['complemento'], $_POST['data_nascimento'], $_POST['telefone']
                ]);


                $usuario_id = $conn->insert_id;


                $sql_aluno = "INSERT INTO alunos (usuario_id, matricula, nome_responsavel, telefone_responsavel, email_responsavel, instrumento, nivel_experiencia, preferencia_horario, possui_instrumento, objetivos)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_aluno = executar_consulta($conn, $sql_aluno, [
                    $usuario_id, date('Y') . $usuario_id, // Cria uma matrícula simples
                    $_POST['nome_responsavel'], $_POST['telefone_responsavel'], $_POST['email_responsavel'], $_POST['instrumento'],
                    $_POST['nivel_experiencia'], $_POST['preferencia_horario'],
                    isset($_POST['possui_instrumento']) ? 1 : 0, $_POST['objetivos']
                ]);


                confirmar_transacao($conn);
                $message = "Aluno cadastrado com sucesso!";

            } catch (Exception $e) {

                reverter_transacao($conn);
                $error = "Erro ao cadastrar aluno: " . $e->getMessage();
            }
        }
    }

    // apagar aluno
    elseif ($action === 'delete') {
        $usuario_id = $_POST['usuario_id'] ?? null;
        if ($usuario_id) {
            $sql = "DELETE FROM usuarios WHERE id = ?";
            $stmt = executar_consulta($conn, $sql, [$usuario_id]);
            

            $message = "Aluno excluído com sucesso!";
        } else {
            $error = "ID do usuário não fornecido para exclusão.";
        }
    }
}


// listar alunos na tabela


$sql_listar = "SELECT a.*, u.nome, u.email, u.created_at, u.id as usuario_id FROM alunos a JOIN usuarios u ON a.usuario_id = u.id ORDER BY u.nome ASC";

$resultado = $conn->query($sql_listar);


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

            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label for="senha">Senha (mín. 8 caracteres):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="password" id="senha" name="senha" required minlength="8" style="flex-grow: 1;" readonly placeholder="Clique em 'Gerar' para criar a senha">
                        <button type="button" id="btnGerarSenha" class="btn btn-secondary">Gerar</button>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" required value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="rg">RG:</label>
                    <input type="text" id="rg" name="rg" maxlength="12" required value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="data_nascimento">Data de Nascimento: <span style="color: red;">*</span></label>
                <input type="text" id="data_nascimento" name="data_nascimento" required 
                       placeholder="Selecione uma data"
                       value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>"> <!-- Corrigido: 'data_nascimento' e não 'data_contratacao' -->
            </div>

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
            
            <!-- Linha de Instrumento e Nível (CORRIGIDA) -->
            <div class="form-row">
                <div class="form-group">
                   <label for="instrumento">Instrumento Principal:</label>
                       <select id="instrumento" name="instrumento" required> <!-- Adicionado 'required' -->
                       <option value="" disabled <?php echo empty($_POST['instrumento']) ? 'selected' : ''; ?>>-- Selecione um instrumento --</option>
                       <option value="Violão" <?php echo ($_POST['instrumento'] ?? '') == 'Violão' ? 'selected' : ''; ?>>Violão</option>
                       <option value="Guitarra" <?php echo ($_POST['instrumento'] ?? '') == 'Guitarra' ? 'selected' : ''; ?>>Guitarra</option>
                       <option value="Baixo" <?php echo ($_POST['instrumento'] ?? '') == 'Baixo' ? 'selected' : ''; ?>>Baixo</option>
                       <option value="Bateria" <?php echo ($_POST['instrumento'] ?? '') == 'Bateria' ? 'selected' : ''; ?>>Bateria</option>
                       <option value="Teclado" <?php echo ($_POST['instrumento'] ?? '') == 'Teclado' ? 'selected' : ''; ?>>Teclado</option>
                       <option value="Piano" <?php echo ($_POST['instrumento'] ?? '') == 'Piano' ? 'selected' : ''; ?>>Piano</option>
                       <option value="Canto" <?php echo ($_POST['instrumento'] ?? '') == 'Canto' ? 'selected' : ''; ?>>Canto</option>
                       <option value="Ukulele" <?php echo ($_POST['instrumento'] ?? '') == 'Ukulele' ? 'selected' : ''; ?>>Ukulele</option>
                       <option value="Outro" <?php echo ($_POST['instrumento'] ?? '') == 'Outro' ? 'selected' : ''; ?>>Outro (especificar nos objetivos)</option>
                    </select>
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

            <!-- Linha de Tipo de Aula (CORRIGIDA) -->
            <div class="form-row">
                <div class="form-group">
                    <label for="preferencia_horario">Preferência de Horário:</label>
                    <!-- MODIFICADO DE INPUT PARA SELECT -->
                    <select id="preferencia_horario" name="preferencia_horario">
                        <option value="" disabled <?php echo empty($_POST['preferencia_horario']) ? 'selected' : ''; ?>>-- Selecione o tipo de aula --</option>
                        <option value="manha" <?php echo ($_POST['preferencia_horario'] ?? '') == 'manha' ? 'selected' : ''; ?>>Manhã</option>
                        <option value="tarde" <?php echo ($_POST['preferencia_horario'] ?? '') == 'tarde' ? 'selected' : ''; ?>>Tarde</option>
                        <option value="noite" <?php echo ($_POST['preferencia_horario'] ?? '') == 'noite' ? 'selected' : ''; ?>>Noite</option>
                    </select>
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
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefone_responsavel">Telefone do Responsável:</label>
                        <input type="text" id="telefone_responsavel" name="telefone_responsavel" value="<?php echo htmlspecialchars($_POST['telefone_responsavel'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email_responsavel">Email do Responsável:</label>
                        <input type="email" id="email_responsavel" name="email_responsavel" value="<?php echo htmlspecialchars($_POST['email_responsavel'] ?? ''); ?>" placeholder="email@responsavel.com">
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
                                    <?php if (!empty($aluno['email_responsavel'])): // ADICIONADO ?>
                                        <br><small> <?php echo htmlspecialchars($aluno['email_responsavel']); ?></small>
                                    <?php endif; // FIM ADICIONADO ?>
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



<script> 
document.addEventListener('DOMContentLoaded', function() {
    const dataNascimentoInput = document.getElementById('data_nascimento');
    const responsavelFields = document.getElementById('responsavel-fields');
    const nomeResponsavelInput = document.getElementById('nome_responsavel');

    function checarIdade() {
        if (!dataNascimentoInput.value) {
            responsavelFields.style.display = 'none';
            nomeResponsavelInput.removeAttribute('required');
            return;
        }


        if (dataNascimentoInput.value.includes('/')) {

            const partes = dataNascimentoInput.value.split('/');
            if (partes.length === 3) {
                 dataNasc = new Date(partes[2], partes[1] - 1, partes[0]);
            } else {
                 return; 
            }
        } else {
             dataNasc = new Date(dataNascimentoInput.value);
        }

        if (isNaN(dataNasc.getTime())) return; 

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


    dataNascimentoInput.addEventListener('change', checarIdade);
    

    
    checarIdade(); 
    
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

<script>

document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            $('#tabelaAlunos').DataTable({
                "language": {
                    
                    "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json" 
                }
            });
        });
    }
});
</script>

<script>

    const botaoGerar = document.getElementById('btnGerarSenha');
    const inputSenha = document.getElementById('senha');

t
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


    botaoGerar.addEventListener('click', function() {

        const novaSenha = gerarSenhaJS(8);
        
        inputSenha.value = novaSenha;
        
        inputSenha.type = 'text';

        botaoGerar.disabled = true;
        botaoGerar.textContent = 'Gerada!'; 
    });

</script>