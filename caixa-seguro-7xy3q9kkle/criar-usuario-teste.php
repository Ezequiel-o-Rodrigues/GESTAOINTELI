<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Criar usuário de teste
    $nome = "Junior";
    $email = " espetinhojunior2@gmail.com";
    $senha = "123456";
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, perfil, ativo) VALUES (?, ?, ?, 'caixa', 1)");
    $stmt->execute([$nome, $email, $hash]);
    
    echo "Usuário criado com sucesso!<br>";
    echo "Email: $email<br>";
    echo "Senha: $senha<br>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>