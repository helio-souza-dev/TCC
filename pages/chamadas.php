<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all calls for current user (or all if admin)
$query = "SELECT c.*, u.nome as professor_nome FROM chamadas c 
          LEFT JOIN usuarios u ON c.professor_id = u.id 
          ORDER BY c.data_chamada DESC, c.created_at DESC";

if(!isAdmin()) {
    $query = "SELECT c.*, u.nome as professor_nome FROM chamadas c 
              LEFT JOIN usuarios u ON c.professor_id = u.id 
              WHERE c.professor_id = " . $_SESSION['user_id'] . "
              ORDER BY c.data_chamada DESC, c.created_at DESC";
}

$stmt = $db->prepare($query);
$stmt->execute();
$calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Gerenciar Chamadas</h3>
        <a href="dashboard.php?page=nova-chamada" class="btn">Nova Chamada</a>
    </div>
    
    <?php if(empty($calls)): ?>
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
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($calls as $call): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($call['data_chamada'])); ?></td>
                        <td><?php echo htmlspecialchars($call['turma']); ?></td>
                        <td><?php echo htmlspecialchars($call['disciplina']); ?></td>
                        <?php if(isAdmin()): ?>
                            <td><?php echo htmlspecialchars($call['professor_nome']); ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="ver-chamada.php?id=<?php echo $call['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">Ver Detalhes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
