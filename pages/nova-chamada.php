<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submission
if($_POST) {
    $turma = $_POST['turma'] ?? '';
    $disciplina = $_POST['disciplina'] ?? '';
    $data_chamada = $_POST['data_chamada'] ?? date('Y-m-d');
    $presencas = $_POST['presencas'] ?? [];
    
    try {
        $db->beginTransaction();
        
        // Insert call
        $query = "INSERT INTO chamadas (professor_id, turma, disciplina, data_chamada) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $_SESSION['user_id']);
        $stmt->bindParam(2, $turma);
        $stmt->bindParam(3, $disciplina);
        $stmt->bindParam(4, $data_chamada);
        $stmt->execute();
        
        $chamada_id = $db->lastInsertId();
        
        // Insert attendance records
        $query = "INSERT INTO presencas (chamada_id, aluno_id, presente) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        
        foreach($presencas as $aluno_id => $presente) {
            $stmt->bindParam(1, $chamada_id);
            $stmt->bindParam(2, $aluno_id);
            $stmt->bindParam(3, $presente);
            $stmt->execute();
        }
        
        $db->commit();
        $message = 'Chamada realizada com sucesso!';
        
    } catch(PDOException $e) {
        $db->rollback();
        $error = 'Erro ao realizar chamada: ' . $e->getMessage();
    }
}

// Get students for selected class
$students = [];
$selected_turma = $_GET['turma'] ?? '';

if($selected_turma) {
    $query = "SELECT * FROM alunos WHERE turma = ? AND ativo = 1 ORDER BY nome";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $selected_turma);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get available classes
$query = "SELECT DISTINCT turma FROM alunos WHERE ativo = 1 ORDER BY turma";
$stmt = $db->prepare($query);
$stmt->execute();
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <h3>Nova Chamada</h3>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if(!$selected_turma): ?>
        <form method="GET">
            <input type="hidden" name="page" value="nova-chamada">
            <div class="form-group">
                <label for="turma">Selecione a Turma:</label>
                <select id="turma" name="turma" required onchange="this.form.submit()">
                    <option value="">Escolha uma turma...</option>
                    <?php foreach($turmas as $turma): ?>
                        <option value="<?php echo $turma['turma']; ?>"><?php echo $turma['turma']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    <?php else: ?>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="turma">Turma:</label>
                    <input type="text" id="turma" name="turma" value="<?php echo htmlspecialchars($selected_turma); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="disciplina">Disciplina:</label>
                    <input type="text" id="disciplina" name="disciplina" required>
                </div>
                <div class="form-group">
                    <label for="data_chamada">Data:</label>
                    <input type="date" id="data_chamada" name="data_chamada" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <h4>Lista de Alunos</h4>
            
            <?php if(empty($students)): ?>
                <p>Nenhum aluno encontrado para esta turma.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Matrícula</th>
                            <th>Presente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['nome']); ?></td>
                                <td><?php echo htmlspecialchars($student['matricula']); ?></td>
                                <td>
                                    <input type="checkbox" name="presencas[<?php echo $student['id']; ?>]" value="1" checked>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn">Realizar Chamada</button>
                    <a href="dashboard.php?page=nova-chamada" class="btn btn-secondary">Voltar</a>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>
