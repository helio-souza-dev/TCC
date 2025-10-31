<?php
require_once 'includes/auth.php';
requireLogin();

$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Agendamento de Aulas</title>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Agendamento de Aulas</title>
   

    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.default.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/style.css?v=1.1">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    
    </head>

<body class="dashboard">
    <header class="header">
        <div class="header-content">
            <h1>Sistema de Agendamento de Aulas</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <span>(<?php echo ucfirst($_SESSION['user_type']); ?>)</span>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            </div>
        </div>
    </header>

    <nav class="nav-menu">
        <ul>
            <li><a href="dashboard.php" class="<?php echo $page === 'home' ? 'active' : ''; ?>">Início</a></li>
            <?php if(isAdmin()): ?>
                <li><a href="dashboard.php?page=professores" class="<?php echo $page === 'professores' ? 'active' : ''; ?>">Professores</a></li>
                <li><a href="dashboard.php?page=alunos" class="<?php echo $page === 'alunos' ? 'active' : ''; ?>">Alunos</a></li>
                <li><a href="dashboard.php?page=solicitacoes" class="<?php echo $page === 'solicitacoes' ? 'active' : ''; ?>">Solicitações</a></li>
            <?php endif; ?>
            
            <?php if(isAdmin() || isProfessor()): ?>
            <li><a href="dashboard.php?page=chamadas" class="<?php echo $page === 'chamadas' ? 'active' : ''; ?>">Aulas</a></li>
            <?php endif; ?>

            <?php if(isAluno() || isProfessor()): ?>
            <li><a href="dashboard.php?page=perfil" class="<?php echo $page === 'perfil' ? 'active' : ''; ?>">Dados Pessoais</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="main-content">
        <?php
        switch($page) {
            case 'professores':
                if(isAdmin()) include 'pages/professores.php';
                break;

                case 'editar_aula':
                if(isAdmin() || isProfessor()) include 'pages/editar_aula.php';
                break;

            case 'editar-prof':
                if(isAdmin()) include 'pages/editar_prof.php';
                break;
            case 'alunos':
                if(isAdmin()) include 'pages/alunos.php';
                break;
            case 'editar-aluno':
                if(isAdmin()) include 'pages/editar_aluno.php';
                break;
            
            case 'chamadas':
                if(isAdmin() || isProfessor()) include 'pages/chamadas.php';
                break;
            case 'nova-chamada':
                if(isAdmin() || isProfessor()) include 'pages/nova-chamada.php';
                break;

             case 'perfil':
                if(isAluno() || isProfessor()) include 'pages/meu_perfil.php';
                break;

                case 'minhas_solicitacoes':
                if(isAluno() || isProfessor()) include 'pages/minhas_solicitacoes.php';
                break;

                case 'solicitacoes':
                if(isAdmin()) include 'pages/gerenciar_solicitacoes.php';
                break;

            case 'solicitar-dados':
                include 'pages/solicitacao_dados.php';
                break;

            
            default:
                include 'pages/home.php';
        }
        ?>
    </main>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
</body>
</html>