<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->client;
$message = '';
$error = '';

if($_POST && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? ''; // Senha em texto plano

    // NOTA: A maneira correta é primeiro criar o usuário no sistema de Auth do Supabase,
    // e depois inserir o perfil na sua tabela 'usuarios'.
    // Uma Trigger no banco de dados é a melhor forma de automatizar a criação do perfil.
    try {
        // 1. Cria o usuário no Supabase Auth
        $userResponse = $db->auth->signUp([
            'email' => $email,
            'password' => $senha,
        ]);
        
        if ($userResponse->error) {
            throw new Exception($userResponse->error->message);
        }
        
        $user_id = $userResponse->data->user->id; // ID do usuário no sistema Auth

        // 2. Insere os dados do perfil na tabela 'usuarios'
        $profileData = [
            'id' => $user_id, // CORREÇÃO: Usa o mesmo ID do usuário autenticado
            'nome' => $nome,
            'email' => $email,
            'tipo' => 'professor',
            'cpf' => $_POST['cpf'] ?? "",
            'rg' => $_POST['rg'] ?? "",
            'cidade' => $_POST['cidade'] ?? "",
            'endereco' => $_POST['endereco'] ?? "",
            'complemento' => $_POST['complemento'] ?? "",
        ];

        $profileResponse = $db->from('usuarios')->insert($profileData)->execute();

        if ($profileResponse->error) {
            // Se falhar, idealmente você deveria deletar o usuário criado no Auth
            $db->auth->admin->deleteUser($user_id);
            throw new Exception($profileResponse->error->message);
        }

        $message = 'Professor cadastrado com sucesso!';

    } catch (Exception $e) {
        $error = 'Erro ao cadastrar professor: ' . $e->getMessage();
    }
}

// Lista de Professores (código existente estava correto)
$response = $db->from('usuarios')->select('*')->eq('tipo', 'professor')->order('nome')->execute();
$teachers = $response->error ? [] : $response->data;

if ($response->error && !$error) { // Não sobrescreve o erro do formulário
    $error = 'Erro ao listar professores: ' . $response->error->message;
}
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

        <div class="form-group">
            <label for="complemento">Complemento:</label>
            <input type="text" id="complemento" name="complemento">
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
                        <td><?php echo htmlspecialchars($teacher->nome); ?></td>
                        <td><?php echo htmlspecialchars($teacher->email); ?></td>
                        <td><?php echo htmlspecialchars($teacher->cpf ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($teacher->rg ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($teacher->cidade ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($teacher->endereco ?? ''); ?></td>
                        <td><?php if (!empty($teacher->complemento)) {
                            echo htmlspecialchars($teacher->complemento);
                            }?>
                        </td>
                        <td><?php echo $teacher->ativo ? 'Ativo' : 'Inativo'; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($teacher->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>