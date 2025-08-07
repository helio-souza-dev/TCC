<?php
require_once '../config/database.php';

// Nova senha do administrador
$nova_senha = 'admin123';
$email_admin = 'admin@escola.com';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Gerar hash da nova senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    
    // Atualizar a senha no banco
    $query = "UPDATE usuarios SET senha = ? WHERE email = ? AND tipo = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $senha_hash);
    $stmt->bindParam(2, $email_admin);
    
    if($stmt->execute()) {
        if($stmt->rowCount() > 0) {
            echo "✅ Senha do administrador alterada com sucesso!\n";
            echo "📧 Email: {$email_admin}\n";
            echo "🔑 Nova senha: {$nova_senha}\n";
            echo "\nAgora você pode fazer login no sistema.\n";
        } else {
            echo "❌ Usuário administrador não encontrado!\n";
            echo "Verifique se o usuário admin foi criado corretamente.\n";
        }
    } else {
        echo "❌ Erro ao atualizar a senha!\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    echo "\nVerifique se:\n";
    echo "1. O banco de dados 'sistema_chamadas' existe\n";
    echo "2. As configurações de conexão estão corretas\n";
    echo "3. O usuário administrador foi criado\n";
}
?>
