<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Variáveis para mensagens e para carregar as listas de alunos/professores.
$message = '';
$error = '';
$students = [];
$professors = [];

// --- LÓGICA 1: PROCESSAR O AGENDAMENTO QUANDO O FORMULÁRIO É ENVIADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'agendar_aula') {
    
    // Pega os dados do formulário.
    $aluno_id = $_POST['aluno_id'] ?? '';
    $disciplina = $_POST['disciplina'] ?? '';
    $data_aula = $_POST['data_aula'] ?? '';
    $horario_inicio = $_POST['horario_inicio'] ?? '';
    $horario_fim = $_POST['horario_fim'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    
    // Validações básicas antes de ir para o banco.
    if (empty($aluno_id) || empty($disciplina) || empty($data_aula) || empty($horario_inicio) || empty($horario_fim)) {
        $error = "Todos os campos com * são obrigatórios.";
    } else {
        try {
            // Define o ID do professor.
            $professor_id = null;
            if (isAdmin()) {
                $professor_id = $_POST['professor_id'] ?? '';
            } else {
                $sql_prof = "SELECT id FROM professores WHERE usuario_id = ? LIMIT 1";
                $stmt_prof = executar_consulta($conn, $sql_prof, [$_SESSION['user_id']]);
                $professor_data = $stmt_prof->get_result()->fetch_assoc();
                $professor_id = $professor_data['id'] ?? null;
            }

            if (!$professor_id) {
                throw new Exception("ID do professor não foi encontrado.");
            }

            // --- INÍCIO DA NOVA LÓGICA DE VERIFICAÇÃO DUPLA ---

            // VERIFICAÇÃO 1: CONFLITO DE HORÁRIO PARA O PROFESSOR
            $sql_conflito_prof = "SELECT id FROM aulas_agendadas
                                  WHERE professor_id = ? AND data_aula = ? AND status != 'cancelado'
                                  AND (? < horario_fim AND ? > horario_inicio)";
            $stmt_conflito_prof = executar_consulta($conn, $sql_conflito_prof, [
                $professor_id, $data_aula, $horario_inicio, $horario_fim
            ]);
            
            if ($stmt_conflito_prof->get_result()->fetch_assoc()) {
                throw new Exception("Conflito de horário! O professor já tem uma aula neste período.");
            }
            $stmt_conflito_prof->close(); // Fecha a consulta

            // VERIFICAÇÃO 2: CONFLITO DE HORÁRIO PARA O ALUNO
            $sql_conflito_aluno = "SELECT id FROM aulas_agendadas
                                   WHERE aluno_id = ? AND data_aula = ? AND status != 'cancelado'
                                   AND (? < horario_fim AND ? > horario_inicio)";
            $stmt_conflito_aluno = executar_consulta($conn, $sql_conflito_aluno, [
                $aluno_id, $data_aula, $horario_inicio, $horario_fim
            ]);

            if ($stmt_conflito_aluno->get_result()->fetch_assoc()) {
                throw new Exception("Conflito de horário! O aluno já tem uma aula neste período.");
            }
            $stmt_conflito_aluno->close(); // Fecha a consulta

            // --- FIM DA NOVA LÓGICA DE VERIFICAÇÃO DUPLA ---
            
            // Se passou pelas duas verificações, INSERE a nova aula no banco.
            $sql_insert = "INSERT INTO aulas_agendadas (professor_id, aluno_id, disciplina, data_aula, horario_inicio, horario_fim, observacoes, status)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'agendado')";
            executar_consulta($conn, $sql_insert, [
                $professor_id, $aluno_id, $disciplina, $data_aula, 
                $horario_inicio, $horario_fim, $observacoes
            ]);
            
            $message = 'Aula agendada com sucesso!';
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// --- LÓGICA 2: BUSCAR ALUNOS E PROFESSORES PARA PREENCHER O FORMULÁRIO ---
try {
    // Busca todos os alunos ativos.
    $sql_alunos = "SELECT a.id, a.matricula, u.nome 
                   FROM alunos a JOIN usuarios u ON a.usuario_id = u.id 
                   WHERE u.ativo = 1 ORDER BY u.nome ASC";
    $resultado_alunos = $conn->query($sql_alunos);
    $students = $resultado_alunos->fetch_all(MYSQLI_ASSOC);
    
    // Busca todos os professores ativos.
    $sql_professores = "SELECT p.id, u.nome 
                        FROM professores p JOIN usuarios u ON p.usuario_id = u.id 
                        WHERE u.ativo = 1 ORDER BY u.nome ASC";
    $resultado_professores = $conn->query($sql_professores);
    $professors = $resultado_professores->fetch_all(MYSQLI_ASSOC);
    
} catch(Exception $e) {
    $error = 'Erro ao carregar listas de alunos e professores: ' . $e->getMessage();
}
?>


<div class="card">
    <h3>Agendar Nova Aula</h3>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" id="formAgendamento">
        <input type="hidden" name="action" value="agendar_aula">
        
        


        <div class="form-group">
            

            <label for="aluno_id">Selecionar Aluno: <span style="color: red;">*</span></label>
            <select id="aluno_id" name="aluno_id" required>
                <option value="">Escolha um aluno...</option>
                <?php foreach($students as $student): ?>
                    <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo (isset($_POST['aluno_id']) && $_POST['aluno_id'] == $student['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($student['nome']); ?> - <?php echo htmlspecialchars($student['matricula'] ?? 'S/N'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (isAdmin()): ?>
            <div class="form-group">
                <label for="professor_id">Agendar para o Professor: <span style="color: red;">*</span></label>
                <select id="professor_id" name="professor_id" required>
                    <option value="">Escolha um professor...</option>
                    <?php foreach($professors as $prof): ?>
                        <option value="<?php echo htmlspecialchars($prof['id']); ?>" <?php echo (isset($_POST['professor_id']) && $_POST['professor_id'] == $prof['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prof['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
            
        <div class="form-group">
            <label for="disciplina">Disciplina: <span style="color: red;">*</span></label>
            <input type="text" id="disciplina" name="disciplina" required 
                   value="<?php echo htmlspecialchars($_POST['disciplina'] ?? ''); ?>"
                   placeholder="Ex: Violão, Flauta">
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="data_aula">Data da Aula: <span style="color: red;">*</span></label>
                <input type="text" id="data_aula" name="data_aula" required 
                       placeholder="Selecione uma data"
                       value="<?php echo htmlspecialchars($_POST['data_aula'] ?? date('Y-m-d')); ?>">
            </div>
            
            <div class="form-group">
                <label for="horario_inicio">Horário de Início: <span style="color: red;">*</span></label>
                <input type="text" id="horario_inicio" name="horario_inicio" required
                       placeholder="Selecione um horário"
                       value="<?php echo htmlspecialchars($_POST['horario_inicio'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="horario_fim">Horário de Término: <span style="color: red;">*</span></label>
                <input type="text" id="horario_fim" name="horario_fim" required
                       placeholder="Selecione um horário"
                       value="<?php echo htmlspecialchars($_POST['horario_fim'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="observacoes">Observações (opcional):</label>
            <textarea id="observacoes" name="observacoes" rows="3" 
                      placeholder="Conteúdo da aula, materiais necessários, objetivos, etc."><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
        </div>
        
        <div class="flex gap-10 mt-20">
            <button type="submit" class="btn btn-primary">Agendar Aula</button>
            <a href="dashboard.php?page=chamadas" class="btn btn-outline">Voltar para Aulas</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Ativa o Tom-Select para o dropdown de Alunos
    new TomSelect('#aluno_id', {
        create: false, // Impede que o usuário crie novos alunos por aqui
        sortField: {
            field: "text",
            direction: "asc"
        },
        placeholder: "Digite para buscar um aluno..."
    });

    // Verifica se o dropdown de professor existe na página (para não dar erro)
    if (document.getElementById('professor_id')) {
        // Ativa o Tom-Select para o dropdown de Professores
        new TomSelect('#professor_id', {
            create: false, // Impede que o usuário crie novos professores
            sortField: {
                field: "text",
                direction: "asc"
            },
            placeholder: "Digite para buscar um professor..."
        });
    }
});
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

    // Função pura de JS para formatar hora (HH:MM)
    function formatarHoraInput(event) {
        let input = event.target;
        // Remove tudo que não for dígito
        let valor = input.value.replace(/\D/g, '');
        let tamanho = valor.length;

        // Adiciona os dois pontos (HH:)
        if (tamanho > 2) {
            // Limita aos 4 dígitos (HH:MM)
            valor = valor.substring(0, 2) + ':' + valor.substring(2, 4);
        }
        
        // Atualiza o valor no campo
        input.value = valor;
    }


document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Configura o seletor de DATA (em Português)
    flatpickr("#data_aula", {
        locale: "pt", // Usa a tradução que carregamos
        dateFormat: "Y-m-d", // Formato que o banco de dados entende
        altInput: true, // Mostra um formato amigável para o usuário
        altFormat: "d/m/Y", // Formato amigável
        minDate: "today", // Impede de agendar aulas no passado
        allowInput: true, // Permite digitação
        
        // Conecta a máscara e o maxlength
        onReady: function(selectedDates, dateStr, instance) {
            instance.altInput.setAttribute('maxlength', '10');
            instance.altInput.addEventListener('input', formatarDataInput);
        }
    });

    // 2. Configura o seletor de HORÁRIO DE INÍCIO
    flatpickr("#horario_inicio", {
        enableTime: true, // Ativa o modo de hora
        noCalendar: true, // Desativa o calendário
        dateFormat: "H:i", // Formato 24h
        time_24hr: true,
        allowInput: true, // Permite digitação

        // Conecta a máscara e o maxlength
        onReady: function(selectedDates, dateStr, instance) {
            instance.input.setAttribute('maxlength', '5');
            instance.input.addEventListener('input', formatarHoraInput);
        }
    });
    
    // 3. Configura o seletor de HORÁRIO DE FIM
    flatpickr("#horario_fim", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        allowInput: true, // Permite digitação

        // Conecta a máscara e o maxlength
        onReady: function(selectedDates, dateStr, instance) {
            instance.input.setAttribute('maxlength', '5');
            instance.input.addEventListener('input', formatarHoraInput);
        }
    });
    
});
</script>