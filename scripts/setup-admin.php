<?php
require_once '../config/database.php';

// Dados do administrador
$nome_admin = 'Administrador';
$email_admin = 'admin@escola.com';
$senha_admin = 'admin123';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar se o admin já existe
    $query = "SELECT id, nome FROM usuarios WHERE email = ? AND tipo = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email_admin);
    $stmt->execute();
    
    $senha_hash = password_hash($senha_admin, PASSWORD_DEFAULT);
    
    if($stmt->rowCount() > 0) {
        // Admin existe, apenas atualizar senha
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "UPDATE usuarios SET senha = ? WHERE email = ? AND tipo = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $senha_hash);
        $stmt->bindParam(2, $email_admin);
        $stmt->execute();
        
        echo "✅ Senha do administrador '{$admin['nome']}' foi atualizada!\n";
        
    } else {
        // Admin não existe, criar novo
        $query = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'admin')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $nome_admin);
        $stmt->bindParam(2, $email_admin);
        $stmt->bindParam(3, $senha_hash);
        $stmt->execute();
        
        echo "✅ Usuário administrador criado com sucesso!\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📋 DADOS DE ACESSO DO ADMINISTRADOR\n";
    echo str_repeat("=", 50) . "\n";
    echo "📧 Email: {$email_admin}\n";
    echo "🔑 Senha: {$senha_admin}\n";
    echo "👤 Tipo: Administrador\n";
    echo str_repeat("=", 50) . "\n";
    echo "\n✨ Agora você pode acessar o sistema!\n";
    
} catch(PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n\n";
    
    echo "🔧 SOLUÇÕES POSSÍVEIS:\n";
    echo "1. Verifique se o banco 'sistema_chamadas' existe\n";
    echo "2. Confirme as configurações em config/database.php:\n";
    echo "   - Host: localhost\n";
    echo "   - Usuário: root\n";
    echo "   - Senha: (sua senha do MySQL)\n";
    echo "   - Banco: sistema_chamadas\n";
    echo "3. Execute primeiro o script database.sql\n";
}
?>
