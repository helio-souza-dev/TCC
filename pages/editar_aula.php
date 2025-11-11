<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Apenas admin e professor podem acessar esta página.
if (!isAdmin() && !isProfessor()) {
    header("Location: dashboard.php?page=chamadas");
    exit;
}

// Pega o ID da aula da URL.
$aula_id = $_GET['id'] ?? null;
$aula = null;
$error = '';

// Se não houver ID na URL, redireciona de volta com um erro.
if (!$aula_id) {
    $_SESSION['error'] = "ID da aula não fornecido.";
    header("Location: dashboard.php?page=chamadas");
    exit;
}

// --- LÓGICA 1: PROCESSAR AÇÕES ENVIADAS PELOS FORMULÁRIOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // Usa um switch para decidir o que fazer com base na ação.
        switch ($action) {
            case 'marcar_presenca':
                $sql = "UPDATE aulas_agendadas SET status = 'realizado', presenca = 'presente' WHERE id = ?";
                executar_consulta($conn, $sql, [$aula_id]);
                $_SESSION['message'] = 'Presença marcada com sucesso!';
                break;
            
            case 'marcar_falta':
                $sql = "UPDATE aulas_agendadas SET status = 'realizado', presenca = 'ausente' WHERE id = ?";
                executar_consulta($conn, $sql, [$aula_id]);
                $_SESSION['message'] = 'Falta registrada com sucesso!';
                break;

            case 'cancelar_aula':
                $motivo = $_POST['motivo_cancelamento'] ?? '';
                if (empty($motivo)) throw new Exception("O motivo do cancelamento é obrigatório.");
                
                $sql = "UPDATE aulas_agendadas SET status = 'cancelado', presenca = 'justificada', motivo_cancelamento = ?, data_cancelamento = NOW() WHERE id = ?";
                executar_consulta($conn, $sql, [$motivo, $aula_id]);
                $_SESSION['message'] = 'Aula cancelada com sucesso.';
                break;

            case 'reagendar_aula':
                // Pega os novos dados do formulário de reagendamento.
                $nova_data = $_POST['nova_data'] ?? '';
                $novo_horario_inicio = $_POST['novo_horario_inicio'] ?? '';
                $novo_horario_fim = $_POST['novo_horario_fim'] ?? '';
                $motivo_reagendamento = $_POST['motivo_reagendamento'] ?? '';

                // Busca o ID do aluno e do professor da aula que está sendo editada.
                $aula_atual = executar_consulta($conn, "SELECT aluno_id, professor_id FROM aulas_agendadas WHERE id = ?", [$aula_id])->get_result()->fetch_assoc();
                $aluno_id = $aula_atual['aluno_id'];
                $professor_id = $aula_atual['professor_id'];

                // VERIFICAÇÃO 1: CONFLITO PARA O PROFESSOR (ignorando a aula atual)
                $sql_conflito_prof = "SELECT id FROM aulas_agendadas WHERE id != ? AND professor_id = ? AND data_aula = ? AND status != 'cancelado' AND (? < horario_fim AND ? > horario_inicio)";
                $stmt_prof = executar_consulta($conn, $sql_conflito_prof, [$aula_id, $professor_id, $nova_data, $novo_horario_inicio, $novo_horario_fim]);
                if ($stmt_prof->get_result()->fetch_assoc()) {
                    throw new Exception("Conflito de horário! O professor já tem outra aula neste período.");
                }

                // VERIFICAÇÃO 2: CONFLITO PARA O ALUNO (ignorando a aula atual)
                $sql_conflito_aluno = "SELECT id FROM aulas_agendadas WHERE id != ? AND aluno_id = ? AND data_aula = ? AND status != 'cancelado' AND (? < horario_fim AND ? > horario_inicio)";
                $stmt_aluno = executar_consulta($conn, $sql_conflito_aluno, [$aula_id, $aluno_id, $nova_data, $novo_horario_inicio, $novo_horario_fim]);
                if ($stmt_aluno->get_result()->fetch_assoc()) {
                    throw new Exception("Conflito de horário! O aluno já tem outra aula neste período.");
                }

                // Se passou nas verificações, atualiza a aula.
                $sql_update = "UPDATE aulas_agendadas SET data_aula = ?, horario_inicio = ?, horario_fim = ?, motivo_reagendamento = ?, data_reagendamento = NOW() WHERE id = ?";
                executar_consulta($conn, $sql_update, [$nova_data, $novo_horario_inicio, $novo_horario_fim, $motivo_reagendamento, $aula_id]);
                
                $_SESSION['message'] = 'Aula reagendada com sucesso!';
                break;
        }

        // Se qualquer ação foi bem-sucedida, redireciona de volta para a lista de chamadas.
        header("Location: dashboard.php?page=chamadas");
        exit;

    } catch (Exception $e) {
        // Se deu algum erro (especialmente no reagendamento), a página não redireciona e mostra o erro.
        $error = $e->getMessage();
    }
}


// --- LÓGICA 2: CARREGAR DADOS DA AULA PARA MOSTRAR NA PÁGINA ---
// Este código roda sempre que a página é carregada.
try {
    $sql = "SELECT aa.*, u_aluno.nome as aluno_nome
            FROM aulas_agendadas aa
            JOIN alunos al ON aa.aluno_id = al.id
            JOIN usuarios u_aluno ON al.usuario_id = u_aluno.id
            WHERE aa.id = ? LIMIT 1";
            
    $stmt = executar_consulta($conn, $sql, [$aula_id]);
    $aula = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$aula) {
        throw new Exception("Aula não encontrada.");
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao carregar dados da aula: " . $e->getMessage();
    header("Location: dashboard.php?page=chamadas");
    exit;
}
?>

<div class="card">
    <div class="flex justify-between align-center mb-20">
        <h3>Gerenciar Aula</h3>
        <a href="dashboard.php?page=chamadas" class="btn btn-outline">Voltar</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="mb-20" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
        <p><strong>Aluno:</strong> <?php echo htmlspecialchars($aula['aluno_nome']); ?></p>
        <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($aula['disciplina']); ?></p>
        <p><strong>Data Agendada:</strong> <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?> das <?php echo date('H:i', strtotime($aula['horario_inicio'])); ?> às <?php echo date('H:i', strtotime($aula['horario_fim'])); ?></p>
        <p><strong>Status Atual:</strong> <span class="status-badge status-<?php echo $aula['status']; ?>"><?php echo ucfirst($aula['status']); ?></span></p>
    </div>

    <div class="mb-20">
        <h4>Ações de Frequência</h4>
        <div class="flex gap-10 mt-10">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="marcar_presenca">
                <button type="submit" class="btn"> Marcar Presença</button>
            </form>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="marcar_falta">
                <button type="submit" class="btn btn-danger"> Marcar Falta</button>
            </form>
        </div>
    </div>
    <hr class="mb-20">

    <div class="mb-20">
        <h4>Reagendar Aula</h4>
        <form method="POST">
            <input type="hidden" name="action" value="reagendar_aula">
            <div class="form-row">
                <div class="form-group">
                    <label for="nova_data">Nova Data:</label>
                    <input type="text" name="nova_data" id="nova_data" value="<?php echo htmlspecialchars($aula['data_aula']); ?>" placeholder="Selecione a data" required>
                </div>
                <div class="form-group">
                    <label for="novo_horario_inicio">Novo Início:</label>
                    <input type="text" name="novo_horario_inicio" id="novo_horario_inicio" value="<?php echo htmlspecialchars($aula['horario_inicio']); ?>" placeholder="Selecione o horário" required>
                </div>
                <div class="form-group">
                    <label for="novo_horario_fim">Novo Fim:</label>
                    <input type="text" name="novo_horario_fim" id="novo_horario_fim" value="<?php echo htmlspecialchars($aula['horario_fim']); ?>" placeholder="Selecione o horário" required>
                </div>
            </div>
            <div class="form-group">
                <label for="motivo_reagendamento">Motivo do Reagendamento:</label>
                <textarea name="motivo_reagendamento" id="motivo_reagendamento" rows="3" required placeholder="Ex: Pedido do aluno, imprevisto, etc."></textarea>
            </div>
            <button type="submit" class="btn btn-primary"> Reagendar</button>
        </form>
    </div>
    <hr class="mb-20">
    
    <div>
        <h4>Cancelar Aula (com justificativa)</h4>
        <form method="POST" onsubmit="return confirm('Tem certeza que deseja cancelar esta aula? Esta ação não pode ser desfeita facilmente.');">
            <input type="hidden" name="action" value="cancelar_aula">
            <div class="form-group">
                <label for="motivo_cancelamento">Motivo do Cancelamento:</label>
                <textarea name="motivo_cancelamento" id="motivo_cancelamento" rows="3" required placeholder="Descreva o motivo obrigatório para o cancelamento."></textarea>
            </div>
            <button type="submit" class="btn btn-danger"> Cancelar Aula Permanentemente</button>
        </form>
    </div>
</div>


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

    // <-- NOVA FUNÇÃO para formatar hora (HH:MM)
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
        flatpickr("#nova_data", {
            locale: "pt", // Usa a tradução
            dateFormat: "Y-m-d", // Formato do banco
            altInput: true,
            altFormat: "d/m/Y", // Formato amigável
            minDate: "today",
            allowInput: true, // <-- CORRIGIDO
            
            // <-- CONECTA A FUNÇÃO DE FORMATAR DATA
            onReady: function(selectedDates, dateStr, instance) {
                // instance.altInput é o campo de data VISÍVEL (DD/MM/AAAA)
                instance.altInput.setAttribute('maxlength', '10');
                instance.altInput.addEventListener('input', formatarDataInput);
            }
        });

        // 2. Configura o seletor de HORÁRIO DE INÍCIO
        flatpickr("#novo_horario_inicio", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            allowInput: true, // <-- CORRIGIDO
            
            // <-- CONECTA A FUNÇÃO DE FORMATAR HORA
            onReady: function(selectedDates, dateStr, instance) {
                // instance.input é o campo de hora (não tem altInput)
                instance.input.setAttribute('maxlength', '5');
                instance.input.addEventListener('input', formatarHoraInput);
            }
        });
        
        // 3. Configura o seletor de HORÁRIO DE FIM
        flatpickr("#novo_horario_fim", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            allowInput: true, // <-- CORRIGIDO
            
            // <-- CONECTA A FUNÇÃO DE FORMATAR HORA
            onReady: function(selectedDates, dateStr, instance) {
                instance.input.setAttribute('maxlength', '5');
                instance.input.addEventListener('input', formatarHoraInput);
            }
        });
        
    });
</script>