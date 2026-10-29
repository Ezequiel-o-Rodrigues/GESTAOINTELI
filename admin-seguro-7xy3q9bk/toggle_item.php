<?php
header('Content-Type: application/json');

// Conexão com o banco de dados
require_once __DIR__ . '/../includes/conexao.php';

// Desativa a exibição de erros HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Verifica se os parâmetros necessários foram enviados
    if (!isset($_POST['id'], $_POST['table'], $_POST['idColumn'])) {
        throw new Exception('Parâmetros inválidos');
    }

    $id = (int)$_POST['id'];
    $table = $_POST['table'];
    $idColumn = $_POST['idColumn'];

    // Lista de tabelas permitidas para alteração
    $allowedTables = ['espetos', 'porcoes', 'bebidas', 'cervejas', 'opcoes_buffet'];
    
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Tabela não permitida');
    }

    // Primeiro obtém o status atual
    $check = $conn->prepare("SELECT ativo FROM $table WHERE $idColumn = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->bind_result($currentStatus);
    $check->fetch();
    $check->close();

    if ($currentStatus === null) {
        throw new Exception('Item não encontrado');
    }

    // Inverte o status
    $newStatus = $currentStatus ? 0 : 1;
    
    // Atualiza o status
    $stmt = $conn->prepare("UPDATE $table SET ativo = ? WHERE $idColumn = ?");
    $stmt->bind_param("ii", $newStatus, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'newStatus' => $newStatus
        ]);
    } else {
        throw new Exception('Nenhum item foi alterado');
    }

    $stmt->close();
} catch (Exception $e) {
    // Retorna erro em formato JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>