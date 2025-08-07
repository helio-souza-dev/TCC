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
    <title>Dashboard - Sistema de Chamadas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dashboard">
    <header class="header">
        <div class="header-content">
            <h1>Sistema de Chamadas</h1>
            <div class="user-info">
                <span>Olá, <?php echo $_SESSION['user_name']; ?></span>
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
            <?php endif; ?>
            <li><a href="dashboard.php?page=chamadas" class="<?php echo $page === 'chamadas' ? 'active' : ''; ?>">Chamadas</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <?php
        switch($page) {
            case 'professores':
                if(isAdmin()) include 'pages/professores.php';
                break;
            case 'alunos':
                if(isAdmin()) include 'pages/alunos.php';
                break;
            case 'chamadas':
                include 'pages/chamadas.php';
                break;
            case 'nova-chamada':
                include 'pages/nova-chamada.php';
                break;
            default:
                include 'pages/home.php';
        }
        ?>
    </main>
</body>
</html>
