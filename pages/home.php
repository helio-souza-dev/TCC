<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

if(isAdmin()) {
    // Total teachers
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'professor' AND ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['professores'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total students
    $query = "SELECT COUNT(*) as total FROM alunos WHERE ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['alunos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Total calls today
$query = "SELECT COUNT(*) as total FROM chamadas WHERE data_chamada = CURDATE()";
if(!isAdmin()) {
    $query .= " AND professor_id = " . $_SESSION['user_id'];
}
$stmt = $db->prepare($query);
$stmt->execute();
$stats['chamadas_hoje'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent calls
$query = "SELECT c.*, u.nome as professor_nome FROM chamadas c 
          LEFT JOIN usuarios u ON c.professor_id = u.id 
          ORDER BY c.created_at DESC LIMIT 5";
if(!isAdmin()) {
    $query = "SELECT c.*, u.nome as professor_nome FROM chamadas c 
              LEFT JOIN usuarios u ON c.professor_id = u.id 
              WHERE c.professor_id = " . $_SESSION['user_id'] . "
              ORDER BY c.created_at DESC LIMIT 5";
}
$stmt = $db->prepare($query);
$stmt->execute();
$recent_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
