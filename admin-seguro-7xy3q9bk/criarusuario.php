<?php
require_once __DIR__ . '/../includes/conexao.php';

// Dados do novo usuário (MODIFIQUE AQUI)
$usuario = 'junior'; // Nome de usuário desejado
$senha = 'junior101010'; // Senha em texto puro (troque por uma senha segura)
$nome = 'Junior Cezar Rodrigues'; // Nome completo do usuário
$email = 'junior@example.com';

// Cria o hash da senha
$senha_hash = password_hash($senha, PASSWORD_BCRYPT);

// Prepara e executa a query
$stmt = $conn->prepare("INSERT INTO administradores (usuario, senha_hash, nome, email) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $usuario, $senha_hash, $nome, $email);

if ($stmt->execute()) {
    echo "<h2>Usuário criado com sucesso!</h2>";
    echo "<p><strong>Usuário:</strong> {$usuario}</p>";
    echo "<p><strong>Senha:</strong> {$senha}</p>";
    echo "<p style='color:red;font-weight:bold;'>IMPORTANTE: Remova este arquivo após usar!</p>";
} else {
    echo "Erro ao criar usuário: " . $conn->error;
}

$stmt->close();
$conn->close();
?>