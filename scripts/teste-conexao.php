<?php
require_once '../config/database.php';

echo "🔍 TESTANDO CONEXÃO COM O BANCO DE DADOS\n";
echo str_repeat("=", 50) . "\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "✅ Conexão estabelecida com sucesso!\n\n";
        
        // Testar se as tabelas existem
        $tables = ['usuarios', 'alunos', 'chamadas', 'presencas'];
        
        echo "📋 Verificando tabelas:\n";
        foreach($tables as $table) {
            try {
                $query = "SELECT COUNT(*) FROM {$table}";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $count = $stmt->fetchColumn();
                echo "  ✅ {$table}: {$count} registros\n";
            } catch(PDOException $e) {
                echo "  ❌ {$table}: Tabela não encontrada\n";
            }
        }
        
        // Verificar usuário admin
        echo "\n👤 Verificando usuário administrador:\n";
        try {
            $query = "SELECT nome, email, tipo FROM usuarios WHERE tipo = 'admin'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "  ✅ Admin encontrado: {$admin['nome']} ({$admin['email']})\n";
            } else {
                echo "  ⚠️  Nenhum administrador encontrado\n";
                echo "  💡 Execute o script setup-admin.php\n";
            }
        } catch(PDOException $e) {
            echo "  ❌ Erro ao verificar admin: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Falha na conexão!\n";
    }
    
} catch(PDOException $e) {
    echo "❌ ERRO DE CONEXÃO: " . $e->getMessage() . "\n\n";
    
    echo "🔧 CONFIGURAÇÕES ATUAIS (config/database.php):\n";
    echo "  Host: localhost\n";
    echo "  Banco: sistema_chamadas\n";
    echo "  Usuário: root\n";
    echo "  Senha: (oculta)\n\n";
    
    echo "💡 SOLUÇÕES:\n";
    echo "1. Verifique se o MySQL está rodando\n";
    echo "2. Confirme se o banco 'sistema_chamadas' existe\n";
    echo "3. Verifique usuário e senha do MySQL\n";
    echo "4. Execute: CREATE DATABASE sistema_chamadas;\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
?>
