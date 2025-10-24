<?php
// Inclui os arquivos de configuração e autenticação.
require_once 'config/database.php';
require_once 'includes/auth.php';

// Variáveis para guardar mensagens e os resultados do banco.
$message = '';
$error = '';
$aulas = []; // Inicia como um array vazio.

try {
    // --- PASSO 1: Montar a base da consulta SQL ---
    // Esta é a parte principal do SELECT que será usada por todos.
    // Usamos 'aa.*' para pegar todos os campos da tabela 'aulas_agendadas'.
    // E usamos 'JOINs' para buscar nomes e matrículas de outras tabelas.
    $sql = "SELECT aa.*, 
                   u_aluno.nome as aluno_nome, 
                   al.matricula, 
                   u_prof.nome as professor_nome
            FROM aulas_agendadas aa
            LEFT JOIN alunos al ON aa.aluno_id = al.id
            LEFT JOIN usuarios u_aluno ON al.usuario_id = u_aluno.id
            LEFT JOIN professores p ON aa.professor_id = p.id
            LEFT JOIN usuarios u_prof ON p.usuario_id = u_prof.id";

    // --- PASSO 2: Adicionar filtros (WHERE) dinamicamente ---
    $where_clauses = []; // Array para guardar as condições (ex: status = 'agendado')
    $params = [];        // Array para guardar os valores que irão nos '?'
    
    // Pega o status da URL, se não houver, o padrão é 'todos'.
    $status_filter = $_GET['status'] ?? 'todos';

    // Se o filtro não for 'todos', adiciona a condição na consulta.
    if ($status_filter !== 'todos') {
        $where_clauses[] = "aa.status = ?";
        $params[] = $status_filter;
    }

    // Adiciona um filtro dependendo do tipo de usuário.
    if (isProfessor()) {
        // Se for professor, busca apenas as aulas dele.
        $stmt_prof_id = executar_consulta($conn, "SELECT id FROM professores WHERE usuario_id = ? LIMIT 1", [$_SESSION['user_id']]);
        $professor = $stmt_prof_id->get_result()->fetch_assoc();
        if ($professor) {
            $where_clauses[] = "aa.professor_id = ?";
            $params[] = $professor['id'];
        } else {
            $where_clauses[] = "1 = 0"; // Força a não retornar nada se o ID do prof não for achado.
        }
    } elseif (isAluno()) {
        // Se for aluno, busca apenas as aulas dele.
        $stmt_aluno_id = executar_consulta($conn, "SELECT id FROM alunos WHERE usuario_id = ? LIMIT 1", [$_SESSION['user_id']]);
        $aluno = $stmt_aluno_id->get_result()->fetch_assoc();
        if ($aluno) {
            $where_clauses[] = "aa.aluno_id = ?";
            $params[] = $aluno['id'];
        } else {
             $where_clauses[] = "1 = 0"; // Força a não retornar nada.
        }
    }
    // Se for admin, não adiciona nenhum filtro de usuário, pois ele pode ver tudo.

    // --- PASSO 3: Juntar tudo e executar a consulta ---
    
    // Se tivermos alguma condição no array $where_clauses...
    if (!empty($where_clauses)) {
        // ...adiciona a palavra 'WHERE' e junta todas as condições com 'AND'.
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Adiciona a ordenação no final da consulta.
    $sql .= " ORDER BY aa.data_aula DESC, aa.horario_inicio ASC";
    
    // Executa a consulta final com os parâmetros.
    $stmt = executar_consulta($conn, $sql, $params);
    
    if ($stmt) {
        // Pega todos os resultados e guarda no array $aulas.
        $aulas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
} catch (Exception $e) {
    $error = 'Erro ao carregar aulas: ' . $e->getMessage();
}
?>

<div class="card">
    <div class="flex justify-between align-center mb-20">
        <h3><?php echo isAluno() ? 'Minhas Aulas Agendadas' : 'Gerenciar Aulas e Frequência'; ?></h3>
        <?php if(isProfessor() || isAdmin()): ?>
            <a href="dashboard.php?page=nova-chamada" class="btn">Agendar Nova Aula</a>
        <?php endif; ?>
    </div>
    
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-success"> <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error"> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if(isProfessor() || isAdmin()): ?>
        <div class="mb-20">
            <label>Filtrar por status:</label>
            <div class="flex gap-10" style="margin-top: 10px;">
                <a href="dashboard.php?page=chamadas&status=todos" class="btn <?php echo $status_filter === 'todos' ? '' : 'btn-outline'; ?>">Todos</a>
                <a href="dashboard.php?page=chamadas&status=agendado" class="btn <?php echo $status_filter === 'agendado' ? '' : 'btn-outline'; ?>">Agendados</a>
                <a href="dashboard.php?page=chamadas&status=realizado" class="btn <?php echo $status_filter === 'realizado' ? '' : 'btn-outline'; ?>">Realizados</a>
                <a href="dashboard.php?page=chamadas&status=cancelado" class="btn <?php echo $status_filter === 'cancelado' ? '' : 'btn-outline'; ?>">Cancelados</a>
            </div>
        </div>
        
        <?php if(empty($aulas)): ?>
            <div class="alert alert-info"><p> Nenhuma aula encontrada para o filtro selecionado.</p></div>
        <?php else: ?>
            <table class="table" id="tabela_chamada">
                <thead>
                    <tr>
                        <th>Data/Horário</th>
                        <th>Aluno</th>
                        <th>Disciplina</th>
                        <?php if(isAdmin()): ?><th>Professor</th><?php endif; ?>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($aulas as $aula): ?>
                        <tr>
                            <td><strong><?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?></strong><br><small><?php echo date('H:i', strtotime($aula['horario_inicio'])); ?> - <?php echo date('H:i', strtotime($aula['horario_fim'])); ?></small></td>
                            <td><strong><?php echo htmlspecialchars($aula['aluno_nome'] ?? 'N/A'); ?></strong><br><small><?php echo htmlspecialchars($aula['matricula'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($aula['disciplina'] ?? 'N/A'); ?></td>
                            <?php if(isAdmin()): ?><td><?php echo htmlspecialchars($aula['professor_nome'] ?? 'N/A'); ?></td><?php endif; ?>
                            <td>
                                <span class="status-badge status-<?php echo $aula['status']; ?>"><?php echo ucfirst($aula['status']); ?></span>
                                <?php if($aula['status'] === 'realizado'): ?>
                                    <br><small class="presenca-<?php echo $aula['presenca']; ?>"><?php echo 'Presença: ' . ucfirst($aula['presenca'] ?? 'N/A'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($aula['status'] === 'agendado'): ?>
                                    <a href="dashboard.php?page=editar_aula&id=<?php echo $aula['id']; ?>" class="btn btn-primary btn-sm"> Gerenciar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php elseif(isAluno()): ?>
        <p>Tabela de aulas do aluno.</p>
    <?php endif; ?>
</div>

<style>
.presenca-presente { color: #2e7d32; font-weight: bold; }
.presenca-ausente { color: #c62828; font-weight: bold; }
.presenca-justificada { color: #6c757d; }
</style>

<script>
// NOVO SCRIPT PARA DATATABLES
document.addEventListener('DOMContentLoaded', function() {
    // Usamos o jQuery, que foi carregado no dashboard.php
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            $('#tabela_chamada').DataTable({
                "language": {
                    // Arquivo de tradução oficial do DataTables para PT-BR
                    "url": "//cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json" 
                }
            });
        });
    }
});
</script>

<script>
// ... (script existente de máscara de CPF e geração de senha)