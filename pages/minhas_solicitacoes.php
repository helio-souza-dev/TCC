<?php
// v_bdlocal/pages/minhas_solicitacoes.php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin(); 

$usuario_id = $_SESSION['user_id'];
$message = '';
$error = '';
$solicitacoes = [];

try {
    // pega as solicitaçoes buscadas pelo id logado
    $sql_listar = "SELECT *
                   FROM solicitacoes_alteracao
                   WHERE usuario_id = ?
                   ORDER BY data_solicitacao DESC";

    $stmt = executar_consulta($conn, $sql_listar, [$usuario_id]);
    
    if ($stmt) {
        $solicitacoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
} catch (Exception $e) {
    $error = 'Erro ao carregar solicitações: ' . $e->getMessage();
}

// determinar status da solicitaçao
function getStatusClass($status) {
    switch ($status) {
        case 'aprovado':
            return 'status-realizado'; 
        case 'rejeitado':
            return 'status-cancelado'; 
        case 'pendente':
        default:
            return 'status-agendado';
    }
}
?>

<div class="card">
    <div class="flex justify-between align-center mb-20">
        <h3> Minhas Solicitações de Alteração</h3>
        <a href="dashboard.php?page=meu_perfil" class="btn btn-outline">Voltar para Perfil</a>
    </div>

    <?php if($error): ?><div class="alert alert-error"> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if(empty($solicitacoes)): ?>
        <div class="alert alert-info">
            <p>Você ainda não enviou nenhuma solicitação de alteração de dados.</p>
            <a href="dashboard.php?page=solicitar-dados" class="btn btn-primary mt-10 btn-sm">Criar Nova Solicitação</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" id="minhas_solicitacoes">
                <thead>
                    <tr>
                        <th>Data do Pedido</th>
                        <th>Campo Solicitado</th>
                        <th>Novo Valor Desejado</th>
                        <th>Status</th>
                        <th>Data da Resposta</th>


                    </tr>
                </thead>
                <tbody>
                    <?php foreach($solicitacoes as $solicitacao): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($solicitacao['campo_solicitado']); ?></strong></td>
                            <td><textarea readonly rows="2" style="width: 200px; border: none; background: transparent;"><?php echo htmlspecialchars($solicitacao['valor_novo']); ?></textarea></td>
                            <td>
                                <span class="status-badge <?php echo getStatusClass($solicitacao['status']); ?>">
                                    <?php echo ucfirst($solicitacao['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($solicitacao['status'] !== 'pendente'): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_resposta'])); ?>
                                <?php else: ?>
                                    Aguardando análise
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            $('#minhas_solicitacoes').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json" 
                }

            });
        });
    }
});
</script>