<?php
// v_bdlocal/pages/gerenciar_solicitacoes.php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireAdmin(); 

$message = '';
$error = '';

// --- Lógica para processar aprovação/rejeição (SÓ ATUALIZA O STATUS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $solicitacao_id = $_POST['solicitacao_id'] ?? null;
    $admin_id = $_SESSION['user_id'];

    if ($solicitacao_id && ($action === 'approve' || $action === 'reject')) {
        $novo_status = $action === 'approve' ? 'aprovado' : 'rejeitado';
        $status_display = $action === 'approve' ? 'aprovada' : 'rejeitada';
        
        try {
            $sql = "UPDATE solicitacoes_alteracao SET status = ?, administrador_id = ?, data_resposta = NOW() WHERE id = ?";
            executar_consulta($conn, $sql, [$novo_status, $admin_id, $solicitacao_id]);
            $message = "Solicitação $solicitacao_id foi $status_display com sucesso!";

            // AVISO MANUAL: O administrador deve fazer a alteração na página de edição.
            if ($action === 'approve') {
                $message .= " Lembre-se de ir até a página de edição do usuário para aplicar a alteração no cadastro.";
            }

        } catch (Exception $e) {
            $error = 'Erro ao processar solicitação: ' . $e->getMessage();
        }
    }
}

// --- Lógica para listar solicitações pendentes ---
$sql_listar = "SELECT sa.*, u.nome, u.email, u.id AS usuario_id, u.tipo AS usuario_tipo
               FROM solicitacoes_alteracao sa
               JOIN usuarios u ON sa.usuario_id = u.id
               WHERE sa.status = 'pendente'
               ORDER BY sa.data_solicitacao  ASC";

$resultado = $conn->query($sql_listar);
$solicitacoes = $resultado->fetch_all(MYSQLI_ASSOC);

// --- Lógica para listar solicitações resolvidas (últimas 10) ---
$sql_resolvidas = "SELECT sa.*, u.nome, u.email
                   FROM solicitacoes_alteracao sa
                   JOIN usuarios u ON sa.usuario_id = u.id
                   WHERE sa.status IN ('aprovado', 'rejeitado')
                   ORDER BY sa.data_resposta  DESC
                   LIMIT 10";
$resultado_resolvidas = $conn->query($sql_resolvidas);
$resolvidas = $resultado_resolvidas->fetch_all(MYSQLI_ASSOC);
?>

<div class="card">
    <h3> Gerenciar Solicitações Pendentes (<?php echo count($solicitacoes); ?>)</h3>
    
    <?php if($message): ?><div class="alert alert-success"> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if(empty($solicitacoes)): ?>
        <div class="alert alert-info"><p>Nenhuma solicitação de alteração pendente.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" id="tabela_gerenciamento_solicitacao1">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Tipo</th>
                        <th>Data Pedido</th>
                        <th>Campo</th>
                        <th>Correção (Novo Valor)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($solicitacoes as $solicitacao): ?>
                        <tr>
                            <td><?php echo $solicitacao['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($solicitacao['nome']); ?></strong><br>
                                <small><?php echo htmlspecialchars($solicitacao['email']); ?></small>
                            </td>
                            <td><span class="status-badge status-<?php echo $solicitacao['tipo_usuario']; ?>"><?php echo ucfirst($solicitacao['tipo_usuario']); ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($solicitacao['campo_solicitado'] ?? ''); ?></strong></td>

                            <td><textarea readonly rows="2" style="width: 200px;"><?php echo htmlspecialchars($solicitacao['valor_novo']); ?></textarea></td>
                            <td>
                                <div class="flex gap-10">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('APROVAR: Você confirma que irá aplicar essa alteração no cadastro do usuário?')">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="solicitacao_id" value="<?php echo $solicitacao['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Aprovar</button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('REJEITAR: Tem certeza que deseja rejeitar esta solicitação?')">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="solicitacao_id" value="<?php echo $solicitacao['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Rejeitar</button>
                                    </form>
                                </div>
                                <div class="mt-10">
                                    <?php 
                                        $user_type = $solicitacao['usuario_tipo'];
                                        $edit_page = $user_type === 'aluno' ? 'editar-aluno' : 'editar-prof';
                                        $id_key = $user_type === 'aluno' ? 'aluno_id' : 'professor_id';
                                        
                                        // Busca o ID específico do Aluno/Professor (p.id ou a.id)
                                        $tabela = $user_type === 'aluno' ? 'alunos' : 'professores';
                                        $id_result = executar_consulta($conn, "SELECT id FROM {$tabela} WHERE usuario_id = ?", [$solicitacao['usuario_id']])->get_result()->fetch_assoc();
                                        $edit_id = $id_result['id'] ?? null;
                                        
                                        if ($edit_id):
                                    ?>
                                        <a href="dashboard.php?page=<?php echo $edit_page; ?>&<?php echo $id_key; ?>=<?php echo htmlspecialchars($edit_id); ?>" class="btn btn-info btn-sm">Editar Cadastro</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Últimas 10 Solicitações Resolvidas</h3>
    <?php if(empty($resolvidas)): ?>
        <div class="alert alert-info"><p>Nenhuma solicitação resolvida recentemente.</p></div>
    <?php else: ?>
        <div class="table-responsive" id="">
            <table class="table" id="tabela_gerenciamento_solicitacao2">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Data Resposta</th>
                        <th>Campo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($resolvidas as $res): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($res['nome']); ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($res['data_resposta'])); ?></td>
                            <td><?php echo htmlspecialchars($res['campo_solicitado']); ?></td>

                            <td>
                                <span class="status-badge status-<?php echo ($res['status'] === 'aprovado' ? 'realizado' : 'cancelado'); ?>">
                                    <?php echo ucfirst($res['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// NOVO SCRIPT PARA DATATABLES
document.addEventListener('DOMContentLoaded', function() {
    // Usamos o jQuery, que foi carregado no dashboard.php
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            $('#tabela_gerenciamento_solicitacao1').DataTable({
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
// NOVO SCRIPT PARA DATATABLES
document.addEventListener('DOMContentLoaded', function() {
    // Usamos o jQuery, que foi carregado no dashboard.php
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            $('#tabela_gerenciamento_solicitacao2').DataTable({
                "language": {
                    // Arquivo de tradução oficial do DataTables para PT-BR
                    "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json"
            
            
                }
            });
            
        });
        
    }
    
});

</script>