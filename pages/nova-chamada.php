<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

.
$message = '';
$error = '';
$estudantes = [];
$professors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'agendar_aula') {
    
   
    $aluno_id = $_POST['aluno_id'] ?? '';
    $disciplina = $_POST['disciplina'] ?? '';
    $data_aula = $_POST['data_aula'] ?? '';
    $horario_inicio = $_POST['horario_inicio'] ?? '';
    $horario_fim = $_POST['horario_fim'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    $instrumento = $_POST['instrumento'] ?? '';
    $instrumento_leciona = $_POST['instrumentos_leciona'] ?? '';
    

    if (empty($aluno_id) || empty($disciplina) || empty($data_aula) || empty($horario_inicio) || empty($horario_fim)) {
        $error = "Todos os campos com * são obrigatórios.";

    } elseif ($horario_fim <= $horario_inicio) {
        $error = "O horário de término não pode ser igual ou anterior ao horário de início.";

    } else {
        try {
           
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


            // validacoes de conflitos horarios prof
            $sql_conflito_prof = "SELECT id FROM aulas_agendadas
                                  WHERE professor_id = ? AND data_aula = ? AND status != 'cancelado'
                                  AND (? < horario_fim AND ? > horario_inicio)";
            $stmt_conflito_prof = executar_consulta($conn, $sql_conflito_prof, [
                $professor_id, $data_aula, $horario_inicio, $horario_fim
            ]);
            
            if ($stmt_conflito_prof->get_result()->fetch_assoc()) {
                throw new Exception("Conflito de horário! O professor já tem uma aula neste período.");
            }
            $stmt_conflito_prof->close(); 

            // validaçao de conflito de horarios aluno
            $sql_conflito_aluno = "SELECT id FROM aulas_agendadas
                                   WHERE aluno_id = ? AND data_aula = ? AND status != 'cancelado'
                                   AND (? < horario_fim AND ? > horario_inicio)";
            $stmt_conflito_aluno = executar_consulta($conn, $sql_conflito_aluno, [
                $aluno_id, $data_aula, $horario_inicio, $horario_fim
            ]);

            if ($stmt_conflito_aluno->get_result()->fetch_assoc()) {
                throw new Exception("Conflito de horário! O aluno já tem uma aula neste período.");
            }
            $stmt_conflito_aluno->close(); 


            
            
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


try {
    // busca aluno
    $sql_alunos = "SELECT a.id, a.matricula, u.nome, a.instrumento
                   FROM alunos a JOIN usuarios u ON a.usuario_id = u.id 
                   WHERE u.ativo = 1 ORDER BY u.nome ASC";
    $resultado_alunos = $conn->query($sql_alunos);
    $estudantes = $resultado_alunos->fetch_all(MYSQLI_ASSOC);
    
    // busca prof
    $sql_professores = "SELECT p.id, u.nome, p.instrumentos_leciona
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
                <?php foreach($estudantes as $estudante): ?>
                    <option value="<?php echo htmlspecialchars($estudante['id']); ?>" <?php echo (isset($_POST['aluno_id']) && $_POST['aluno_id'] == $estudante['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($estudante['nome']); ?> - <?php echo htmlspecialchars($estudante['matricula'] ?? 'S/N'); ?>
                        - <?php echo htmlspecialchars($estudante['instrumento'] ?? 'S/N'); ?>
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
                            <?php echo htmlspecialchars($prof['nome']); ?> - <?php echo htmlspecialchars($prof['instrumentos_leciona']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
            
        <div class="form-group">
    <label for="disciplina">Disciplina: <span style="color: red;">*</span></label>
    <select id="disciplina" name="disciplina" required>
        <option value="" disabled <?php echo empty($_POST['disciplina']) ? 'selected' : ''; ?>>-- Selecione uma disciplina --</option>
        <option value="Violão" <?php echo ($_POST['disciplina'] ?? '') == 'Violão' ? 'selected' : ''; ?>>Violão</option>
        <option value="Guitarra" <?php echo ($_POST['disciplina'] ?? '') == 'Guitarra' ? 'selected' : ''; ?>>Guitarra</option>
        <option value="Baixo" <?php echo ($_POST['disciplina'] ?? '') == 'Baixo' ? 'selected' : ''; ?>>Baixo</option>
        <option value="Bateria" <?php echo ($_POST['disciplina'] ?? '') == 'Bateria' ? 'selected' : ''; ?>>Bateria</option>
        <option value="Teclado" <?php echo ($_POST['disciplina'] ?? '') == 'Teclado' ? 'selected' : ''; ?>>Teclado</option>
        <option value="Piano" <?php echo ($_POST['disciplina'] ?? '') == 'Piano' ? 'selected' : ''; ?>>Piano</option>
        <option value="Canto" <?php echo ($_POST['disciplina'] ?? '') == 'Canto' ? 'selected' : ''; ?>>Canto</option>
        <option value="Ukulele" <?php echo ($_POST['disciplina'] ?? '') == 'Ukulele' ? 'selected' : ''; ?>>Ukulele</option>
        <option value="Outro" <?php echo ($_POST['disciplina'] ?? '') == 'Outro' ? 'selected' : ''; ?>>Outro (especificar nas observações)</option>
    </select>
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
    
    //tomselect
    if (document.getElementById('aluno_id')) {
        new TomSelect('#aluno_id', {
            create: false, 
            sortField: {
                field: "text",
                direction: "asc"
            },
            placeholder: "Digite para buscar um aluno..."
        });
    }

    //tomselect
    if (document.getElementById('professor_id')) {
        new TomSelect('#professor_id', {
            create: false, 
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


    function formatarHoraInput(event) {
        let input = event.target;

        let valor = input.value.replace(/\D/g, '');
        let tamanho = valor.length;


        if (tamanho > 2) {

            valor = valor.substring(0, 2) + ':' + valor.substring(2, 4);
        }
        

        input.value = valor;
    }


document.addEventListener('DOMContentLoaded', function() {
    

    flatpickr("#data_aula", {
        locale: "pt", 
        dateFormat: "Y-m-d", 
        altInput: true, 
        altFormat: "d/m/Y", 
        minDate: "today", 
        allowInput: true,
        
        
        onReady: function(selectedDates, dateStr, instance) {
            instance.altInput.setAttribute('maxlength', '10');
            instance.altInput.addEventListener('input', formatarDataInput);
        }
    });

    /
    flatpickr("#horario_inicio", {
        enableTime: true, 
        noCalendar: true, 
        dateFormat: "H:i", 
        time_24hr: true,
        allowInput: true, 

        
        onReady: function(selectedDates, dateStr, instance) {
            instance.input.setAttribute('maxlength', '5');
            instance.input.addEventListener('input', formatarHoraInput);
        }
    });
    

    flatpickr("#horario_fim", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        allowInput: true, 


        onReady: function(selectedDates, dateStr, instance) {
            instance.input.setAttribute('maxlength', '5');
            instance.input.addEventListener('input', formatarHoraInput);
        }
    });
    
});
</script>