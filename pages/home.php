<?php
require_once 'config/database.php';
require_once 'includes/auth.php'; // Incluído para usar a função isAdmin()

$database = new Database();
$db = $database->client;

$stats = [];
$error = '';

try {
    if(isAdmin()) {
        // CORREÇÃO: Total de professores usando o cliente Supabase
        $response = $db->from('usuarios')->select('*', ['count' => 'exact'])->eq('tipo', 'professor')->eq('ativo', true)->execute();
        $stats['professores'] = $response->count;
        
        // CORREÇÃO: Total de alunos usando o cliente Supabase
        $response = $db->from('alunos')->select('*', ['count' => 'exact'])->eq('ativo', true)->execute();
        $stats['alunos'] = $response->count;
    }

    // CORREÇÃO: Total de chamadas hoje
    $query_chamadas = $db->from('chamadas')->select('*', ['count' => 'exact'])->eq('data_chamada', date('Y-m-d'));
    if(!isAdmin()) {
        $query_chamadas = $query_chamadas->eq('professor_id', $_SESSION['user_id']);
    }
    $response = $query_chamadas->execute();
    $stats['chamadas_hoje'] = $response->count;

    // CORREÇÃO: Chamadas recentes
    $query_recentes = $db->from('chamadas')
        ->select('*, professor_nome:usuarios(nome)') // JOIN com a tabela usuarios
        ->order('created_at', ['ascending' => false])
        ->limit(5);

    if(!isAdmin()) {
        $query_recentes = $query_recentes->eq('professor_id', $_SESSION['user_id']);
    }
    $response = $query_recentes->execute();
    $recent_calls = $response->data;

} catch (Exception $e) {
    $error = 'Erro ao carregar estatísticas: ' . $e->getMessage();
}

?>

<div class="card">
    <h3>Bem-vindo ao Sistema de Chamadas</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
        <?php if(isAdmin()): ?>
            <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
                <h4 style="color: #1976d2; margin-bottom: 10px;">Professores</h4>
                <div style="font-size: 32px; font-weight: bold; color: #1976d2;"><?php echo $stats['professores']; ?></div>
            </div>
            <div style="background: #f3e5f5; padding: 20px; border-radius: 8px; text-align: center;">
                <h4 style="color: #7b1fa2; margin-bottom: 10px;">Alunos</h4>
                <div style="font-size: 32px; font-weight: bold; color: #7b1fa2;"><?php echo $stats['alunos']; ?></div>
            </div>
        <?php endif; ?>
        <div style="background: #e8f5e8; padding: 20px; border-radius: 8px; text-align: center;">
            <h4 style="color: #388e3c; margin-bottom: 10px;">Chamadas Hoje</h4>
            <div style="font-size: 32px; font-weight: bold; color: #388e3c;"><?php echo $stats['chamadas_hoje']; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <h3>Chamadas Recentes</h3>
    
    <?php if(empty($recent_calls)): ?>
        <p>Nenhuma chamada encontrada.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Turma</th>
                    <th>Disciplina</th>
                    <?php if(isAdmin()): ?>
                        <th>Professor</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_calls as $call): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($call['data_chamada'])); ?></td>
                        <td><?php echo htmlspecialchars($call['turma']); ?></td>
                        <td><?php echo htmlspecialchars($call['disciplina']); ?></td>
                        <?php if(isAdmin()): ?>
                            <td><?php echo htmlspecialchars($call['professor_nome']); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
