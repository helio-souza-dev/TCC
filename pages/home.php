<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Variáveis para guardar os dados que virão do banco.
$stats = ['professores' => 0, 'alunos' => 0, 'aulas_hoje' => 0];
$recent_calls = [];
$error = '';

try {
    // Pega o ID do usuário logado e a data de hoje.
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');

    // --- LÓGICA PARA BUSCAR AS ESTATÍSTICAS (OS CARDS) ---
    if (isAdmin()) {
        // Se for admin, conta tudo.
        $stats['professores'] = $conn->query("SELECT COUNT(id) FROM usuarios WHERE tipo = 'professor' AND ativo = 1")->fetch_column();
        $stats['alunos'] = $conn->query("SELECT COUNT(id) FROM usuarios WHERE tipo = 'aluno' AND ativo = 1")->fetch_column();
        $stats['aulas_hoje'] = $conn->query("SELECT COUNT(id) FROM aulas_agendadas WHERE data_aula = '$today'")->fetch_column();
    } elseif (isProfessor()) {
        // Se for professor, conta apenas as suas aulas de hoje.
        $stmt = executar_consulta($conn, "SELECT COUNT(id) FROM aulas_agendadas WHERE data_aula = ? AND professor_id = (SELECT id FROM professores WHERE usuario_id = ?)", [$today, $user_id]);
        $stats['aulas_hoje'] = $stmt->get_result()->fetch_column();
    }
    // Alunos não veem os cards de estatísticas.


    // --- LÓGICA PARA BUSCAR AS ÚLTIMAS 5 AULAS ---
    $sql_recent = '';
    $params = [];

    // Monta a consulta SQL de acordo com o tipo de usuário.
    if (isAdmin()) {
        // Admin vê tudo.

        $sql_recent = "SELECT aa.data_aula, aa.horario_inicio, aa.disciplina, aa.status, u_aluno.nome AS aluno_nome, u_prof.nome AS professor_nome, aa.observacoes
                       FROM aulas_agendadas aa
                       LEFT JOIN alunos al ON aa.aluno_id = al.id
                       LEFT JOIN usuarios u_aluno ON al.usuario_id = u_aluno.id
                       LEFT JOIN professores p ON aa.professor_id = p.id
                       LEFT JOIN usuarios u_prof ON p.usuario_id = u_prof.id
                       ORDER BY aa.data_aula DESC, aa.horario_inicio DESC LIMIT 5";
    } elseif (isProfessor()) {
        // Professor vê as aulas dele.

        $sql_recent = "SELECT aa.data_aula, aa.horario_inicio, aa.disciplina, aa.status, u_aluno.nome AS aluno_nome, aa.observacoes
                       FROM aulas_agendadas aa
                       LEFT JOIN alunos al ON aa.aluno_id = al.id
                       LEFT JOIN usuarios u_aluno ON al.usuario_id = u_aluno.id
                       WHERE aa.professor_id = (SELECT id FROM professores WHERE usuario_id = ?)
                       ORDER BY aa.data_aula DESC, aa.horario_inicio DESC LIMIT 5";
        $params[] = $user_id;
    } elseif (isAluno()) {
        // Aluno vê as aulas dele.

        $sql_recent = "SELECT aa.data_aula, aa.horario_inicio, aa.disciplina, aa.status, u_prof.nome as professor_nome, aa.observacoes
                       FROM aulas_agendadas aa
                       JOIN alunos al ON aa.aluno_id = al.id
                       LEFT JOIN professores p ON aa.professor_id = p.id
                       LEFT JOIN usuarios u_prof ON p.usuario_id = u_prof.id
                       WHERE al.usuario_id = ?
                       ORDER BY aa.data_aula DESC, aa.horario_inicio DESC LIMIT 5";
        $params[] = $user_id;
    }
    
    // Se uma consulta foi definida, executa e busca os resultados.
    if (!empty($sql_recent)) {
        $stmt_recent = executar_consulta($conn, $sql_recent, $params);
        $recent_calls = $stmt_recent->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_recent->close();
    }

} catch (Exception $e) {
    $error = 'Erro ao carregar dados do painel: ' . $e->getMessage();
}
?>


<div class="card">
    <h3>Bem-vindo(a) ao Sistema de Agendamento, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h3>
    
    <?php if($error): ?>
        <div class="alert alert-error">
             <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <?php if(isAdmin()): ?>
            <div class="stat-card">
                <h4>Professores Ativos</h4>
                <div class="stat-number"><?php echo $stats['professores']; ?></div>
            </div>
            <div class="stat-card">
                <h4>Alunos Ativos</h4>
                <div class="stat-number"><?php echo $stats['alunos']; ?></div>
            </div>
        <?php endif; ?>

        <?php if(isAdmin() || isProfessor()): ?>
            <div class="stat-card">
                <h4>Aulas Hoje</h4>
                <div class="stat-number"><?php echo $stats['aulas_hoje']; ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Últimas Aulas Agendadas</h3>
    
    <?php if(empty($recent_calls)): ?>
        <div class="alert alert-info">
            <p>Nenhuma aula recente encontrada.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" id="tabela_aulas_home">
                <thead>
                    <tr>
                        <th>Data e Hora</th>
                        <?php if(!isAluno()): ?><th>Aluno</th><?php endif; ?>
                        <th>Disciplina</th>
                        <th>Status</th>
                        <?php if(!isProfessor()): ?><th>Professor</th><?php endif; ?>
                        <th>Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_calls as $aula): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($aula['data_aula'] . ' ' . $aula['horario_inicio'])); ?></td>
                            
                            <?php if(!isAluno()): ?>
                                <td><?php echo htmlspecialchars($aula['aluno_nome'] ?? 'N/A'); ?></td>
                            <?php endif; ?>

                            <td><?php echo htmlspecialchars($aula['disciplina'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $aula['status'] ?? 'agendado'; ?>">
                                    <?php echo ucfirst($aula['status'] ?? 'agendado'); ?>
                                </span>
                            </td>

                            <?php if(!isProfessor()): ?>
                                <td><?php echo htmlspecialchars($aula['professor_nome'] ?? 'N/A'); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($aula['observacoes'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}
.stat-card {
    background: #495057;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #dee2e6;
}
.stat-card h4 {
    color: #f8f9fa;
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
}
.stat-card .stat-number {
    font-size: 36px;
    font-weight: bold;
    color: #f8f9fa;
}
</style>

<script>
// NOVO SCRIPT PARA DATATABLES
document.addEventListener('DOMContentLoaded', function() {
    // Usamos o jQuery, que foi carregado no dashboard.php
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            $('#tabela_aulas_home').DataTable({
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
// ... (script existente de máscara de CPF e geração de senha)