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
        $email = $_POST['email'] ?? '';
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $cpf = $_POST['cpf'] ?? "";
        $rg = $_POST['rg'] ?? "";
        $cidade = $_POST['cidade'] ?? "";
        $endereco = $_POST['endereco'] ?? "";
        $complemento = $_POST['complemento'] ?? "";


         try {

    $query = "INSERT INTO usuarios (nome, email, senha, cpf, rg, cidade, endereco, complemento, tipo, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'professor', NOW())";
            
            $stmt = $db->prepare($query);
            
            // ✅ 2. EXECUÇÃO OTIMIZADA: Passa um array com todas as variáveis para execute().
            // A ordem no array deve corresponder exatamente à ordem dos '?' na query.
            $params = [$nome, $email, $senha, $cpf, $rg, $cidade, $endereco, $complemento];
            $stmt->execute($params);
            
            $message = 'Professor cadastrado com sucesso!';

        } catch(PDOException $e) {
            // Se o e-mail for duplicado ou houver outro erro, ele será capturado aqui.
            $error = 'Erro ao cadastrar professor: ' . $e->getMessage();
        }
    }
}

// Get all teachers
$query = "SELECT * FROM usuarios WHERE tipo = 'professor' ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <h3>Gerenciar Professores</h3>
    
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
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>

                    <div class="form-group">
                <label for="cpf">Cpf:</label>
                <input type="text" id="cpf" name="cpf" required>
            </div>

                    <div class="form-group">
                <label for="rg">RG:</label>
                <input type="text" id="rg" name="rg" required>
            </div>

                                <div class="form-group">
                <label for="cidade">Cidade:</label>
                <input type="text" id="cidade" name="cidade" required>
            </div>
    </div>

                        <div class="form-group">
                <label for="endereco">Endereço:</label>
                <input type="text" id="endereco" name="endereco" required>
            </div>
    </div>
                            <div class="form-group">
                <label for="complemento">Complemento:</label>
                <input type="text" id="complemento" name="complemento">
            </div>
    </div>


    </div>


        
        <button type="submit" class="btn">Cadastrar Professor</button>
    </form>
</div>

<div class="card">
    <h3>Lista de Professores</h3>
    
    <?php if(empty($teachers)): ?>
        <p>Nenhum professor cadastrado.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Cpf</th>
                    <th>Rg</th>
                    <th>Cidade</th>
                    <th>Endereço</th>
                    <th>Complemento</th>
                    <th>Status</th>
                    <th>Data Cadastro</th>
                </tr>
            </thead>
<tbody>
    <?php foreach($teachers as $teacher): ?>
        <tr>
            <td><?php echo htmlspecialchars($teacher['nome']); ?></td>
            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
            <td><?php echo htmlspecialchars($teacher['cpf'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($teacher['rg'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($teacher['cidade'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($teacher['endereco'] ?? ''); ?></td>
            <td><?php if (!empty($teacher['complemento'])) {
                    echo htmlspecialchars($teacher['complemento']);
                     }?>
            <td><?php echo $teacher['ativo'] ? 'Ativo' : 'Inativo'; ?></td>
            <td><?php echo date('d/m/Y', strtotime($teacher['created_at'])); ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>

        </table>
    <?php endif; ?>
</div>
