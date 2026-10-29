<?php
require_once __DIR__ . '/config/database.php';

echo "<h3>Criando usuário Junior</h3>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Não foi possível conectar ao banco de dados. Verifique se o MySQL está rodando.');
    }
    
    // Verificar se já existe
    $check = $db->prepare("SELECT id FROM usuarios WHERE email = 'junior@sistema.com'");
    $check->execute();
    
    if ($check->fetch()) {
        echo "⚠️ Usuário 'junior' já existe!<br>";
        echo "Login: junior<br>";
        echo "Senha: 101010<br>";
        echo "<a href='login.php'>Ir para Login</a>";
        exit;
    }
    
    // Criar usuário junior
    $nome = 'Junior';
    $usuario = 'junior';
    $email = 'junior@sistema.com';
    $senha = password_hash('101010', PASSWORD_DEFAULT);
    $perfil = 'admin';
    
    // Verificar se a tabela tem coluna 'usuario'
    $columns = $db->query("SHOW COLUMNS FROM usuarios LIKE 'usuario'");
    if ($columns->rowCount() == 0) {
        // Adicionar coluna usuario se não existir
        $db->exec("ALTER TABLE usuarios ADD COLUMN usuario VARCHAR(50) UNIQUE AFTER nome");
        echo "✅ Coluna 'usuario' adicionada à tabela<br>";
    }
    
    $query = "INSERT INTO usuarios (nome, usuario, email, senha, perfil, ativo) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $db->prepare($query);
    $stmt->execute([$nome, $usuario, $email, $senha, $perfil]);
    
    echo "✅ Usuário 'junior' criado com sucesso!<br>";
    echo "Login: <strong>junior</strong><br>";
    echo "Senha: <strong>101010</strong><br>";
    echo "<br><a href='login.php'>Ir para Login</a>";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "<br>Certifique-se de que:<br>";
    echo "1. O XAMPP está rodando<br>";
    echo "2. O MySQL está ativo<br>";
    echo "3. O banco de dados está configurado corretamente<br>";
}
?>