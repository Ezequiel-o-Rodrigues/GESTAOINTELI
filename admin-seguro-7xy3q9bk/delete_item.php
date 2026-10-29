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

    // Lista de tabelas permitidas para exclusão
    $allowedTables = ['espetos', 'porcoes', 'bebidas', 'cervejas', 'opcoes_buffet'];
    
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Tabela não permitida');
    }

    // Prepara e executa a exclusão
    $stmt = $conn->prepare("DELETE FROM $table WHERE $idColumn = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
        exit; // Termina o script aqui
    } else {
        throw new Exception('Nenhum item foi excluído');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

// Não deve haver nenhum código ou espaço em branco após este ponto
?>