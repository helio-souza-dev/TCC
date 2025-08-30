<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->client;

$message = '';
$error = '';

// Handle form submission
if($_POST) {
    $turma = $_POST['turma'] ?? '';
    $disciplina = $_POST['disciplina'] ?? '';
    $data_chamada = $_POST['data_chamada'] ?? date('Y-m-d');
    $presencas_checkbox = $_POST['presencas'] ?? [];
    $todos_alunos = $_POST['alunos_ids'] ?? []; // Campo hidden com todos os IDs

    // NOTA: A API REST do Supabase não suporta transações multi-request como o PDO.
    // A melhor forma de garantir a atomicidade (tudo ou nada) é usando uma
    // Função de Banco de Dados (RPC) no Supabase.
    // A seguir, uma implementação que faz as duas inserções em sequência.

    try {
        // 1. Insere o registro da chamada
        $chamadaResponse = $db->from('chamadas')->insert([
            'professor_id' => $_SESSION['user_id'],
            'turma' => $turma,
            'disciplina' => $disciplina,
            'data_chamada' => $data_chamada
        ])->select('id')->single()->execute();

        if ($chamadaResponse->error) {
            throw new Exception('Erro ao criar chamada: ' . $chamadaResponse->error->message);
        }
        $chamada_id = $chamadaResponse->data->id;
        
        // 2. Prepara os dados de presença para todos os alunos da turma
        $presencasData = [];
        foreach ($todos_alunos as $aluno_id) {
            $presencasData[] = [
                'chamada_id' => $chamada_id,
                'aluno_id' => $aluno_id,
                // Verifica se o aluno estava no array de checkboxes marcados
                'presente' => isset($presencas_checkbox[$aluno_id]) ? true : false
            ];
        }

        // 3. Insere todos os registros de presença de uma só vez
        if (!empty($presencasData)) {
            $presencasResponse = $db->from('presencas')->insert($presencasData)->execute();
            if ($presencasResponse->error) {
                // Idealmente, aqui você deletaria o registro da chamada que foi criado.
                $db->from('chamadas')->delete()->eq('id', $chamada_id)->execute();
                throw new Exception('Erro ao salvar presenças: ' . $presencasResponse->error->message);
            }
        }
        
        $message = 'Chamada realizada com sucesso!';
        
    } catch(Exception $e) {
        $error = 'Erro ao realizar chamada: ' . $e->getMessage();
    }
}

// Get students for selected class
$students = [];
$selected_turma = $_GET['turma'] ?? '';

if($selected_turma) {
    $response = $db->from('alunos')->select('*')->eq('turma', $selected_turma)->eq('ativo', true)->order('nome')->execute();
    $students = $response->error ? [] : $response->data;
}

// Get available classes
$responseTurmas = $db->from('alunos')->select('turma')->eq('ativo', true)->execute();
// CORREÇÃO: Processa o resultado para obter um array único de turmas
$turmas = [];
if (!$responseTurmas->error) {
    $turmas = array_unique(array_column($responseTurmas->data, 'turma'));
    sort($turmas);
}

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
                        <td><?php echo date('d/m/Y', strtotime($call->data_chamada)); ?></td>
                        <td><?php echo htmlspecialchars($call->turma); ?></td>
                        <td><?php echo htmlspecialchars($call->disciplina); ?></td>
                        <?php if(isAdmin()): ?>
                            <td><?php echo htmlspecialchars($call->usuarios->nome); ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="ver-chamada.php?id=<?php echo $call->id; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">Ver Detalhes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>