<?php
// v_bdlocal/pages/solicitacao_dados.php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin(); 

$message = '';
$error = '';
$usuario_id = $_SESSION['user_id'];
$tipo_usuario = $_SESSION['user_type'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campo_solicitado = $_POST['campo_solicitado'] ?? '';
    $valor_novo = $_POST['valor_novo'] ?? '';

    if (empty($campo_solicitado) || empty($valor_novo)) {
        $error = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Nenhuma alteração necessária aqui. O PHP funciona igual com <select>
            $sql = "INSERT INTO solicitacoes_alteracao (usuario_id, tipo_usuario, campo_solicitado, valor_novo)
                    VALUES (?, ?, ?, ?)";
            
            executar_consulta($conn, $sql, [
                $usuario_id, 
                $tipo_usuario, 
                $campo_solicitado, 
                $valor_novo
            ]);

            $message = "Sua solicitação foi enviada para o administrador e está pendente de aprovação.";

        } catch (Exception $e) {
            $error = 'Erro ao enviar a solicitação: ' . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <h3> Solicitar Alteração de Dados Pessoais</h3>
    
    <?php if($message): ?><div class="alert alert-success"> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <p style="margin-bottom: 25px; color: #6c757d;">
        Use este formulário para solicitar correções em seus dados críticos (Nome, Email, CPF, etc.). Um administrador analisará e aprovará a alteração.
    </p>

    <form method="POST">
        
        <div class="form-group">
            <label for="campo_solicitado">Dado a ser Editado:</label>
            <select id="campo_solicitado" name="campo_solicitado" required>
                <option value="" disabled <?php echo empty($_POST['campo_solicitado']) ? 'selected' : ''; ?>>-- Selecione o dado que deseja alterar --</option>
                <option value="Nome Completo" <?php echo ($_POST['campo_solicitado'] ?? '') == 'Nome Completo' ? 'selected' : ''; ?>>Nome Completo</option>
                <option value="Email" <?php echo ($_POST['campo_solicitado'] ?? '') == 'Email' ? 'selected' : ''; ?>>Email</option>
                <option value="CPF" <?php echo ($_POST['campo_solicitado'] ?? '') == 'CPF' ? 'selected' : ''; ?>>CPF</option>
                <option value="RG" <?php echo ($_POST['campo_solicitado'] ?? '') == 'RG' ? 'selected' : ''; ?>>RG</option>
                <option value="Data de Nascimento" <?php echo ($_POST['campo_solicitado'] ?? '') == 'Data de Nascimento' ? 'selected' : ''; ?>>Data de Nascimento</option>
                <option value="Outro" <?php echo ($_POST['campo_solicitado'] ?? '') == 'Outro' ? 'selected' : ''; ?>>Outro (Descrever abaixo)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="valor_novo">Correção / Novo Valor Desejado:</label>
            <textarea id="valor_novo" name="valor_novo" rows="3" required
                      placeholder="Digite aqui o valor correto. Se selecionou 'Outro', descreva a solicitação."><?php echo htmlspecialchars($_POST['valor_novo'] ?? ''); ?></textarea>
        </div>
        
        <div class="flex gap-10 mt-20">
            <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
            <a href="dashboard.php?page=meu_perfil" class="btn btn-outline">Voltar para Perfil</a>
        </div>
    </form>
</div>