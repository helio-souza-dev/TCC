<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submission
if($_POST) {
    $action = $_POST['action'] ?? '';
    
    if($action === 'add') {
        $nome = $_POST['nome'] ?? '';
        $matricula = $_POST['matricula'] ?? '';
        $turma = $_POST['turma'] ?? '';
        
        try {
            $query = "INSERT INTO alunos (nome, matricula, turma) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $nome);
            $stmt->bindParam(2, $matricula);
            $stmt->bindParam(3, $turma);
            $stmt->execute();
            $message = 'Aluno cadastrado com sucesso!';
        } catch(PDOException $e) {
            $error = 'Erro ao cadastrar aluno: ' . $e->getMessage();
        }
    }
}

// Get all students
$query = "SELECT * FROM alunos ORDER BY turma, nome";
$stmt = $db->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <h3>Gerenciar Alunos</h3>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" style="margin-bottom: 30px;">
        <input type="hidden" name="action" value="add">
        
        <div class="form-row">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="matricula">Matrícula:</label>
                <input type="text" id="matricula" name="matricula" required>
            </div>
            <div class="form-group">
                <label for="turma">Turma:</label>
                <input type="text" id="turma" name="turma" required>
            </div>
        </div>
        
        <button type="submit" class="btn">Cadastrar Aluno</button>
    </form>
</div>

<div class="card">
    <h3>Lista de Alunos</h3>
    
    <?php if(empty($students)): ?>
        <p>Nenhum aluno cadastrado.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Matrícula</th>
                    <th>Turma</th>
                    <th>Status</th>
                    <th>Data Cadastro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['nome']); ?></td>
                        <td><?php echo htmlspecialchars($student['matricula']); ?></td>
                        <td><?php echo htmlspecialchars($student['turma']); ?></td>
                        <td><?php echo $student['ativo'] ? 'Ativo' : 'Inativo'; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($student['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
