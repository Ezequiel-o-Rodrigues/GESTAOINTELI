<?php
// ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::config('database.php'); 

function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function getCategorias() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM categorias ORDER BY nome";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProdutosPorCategoria($categoria_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM produtos WHERE categoria_id = :categoria_id AND ativo = 1 ORDER BY nome";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':categoria_id', $categoria_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Adicionar esta função ao includes/functions.php se não existir
function getProdutosAtivos($db, $categoria_id = null) {
    $query = "SELECT p.*, c.nome as categoria_nome FROM produtos p 
              JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.ativo = 1";
    
    if ($categoria_id) {
        $query .= " AND p.categoria_id = :categoria_id";
    }
    
    $query .= " ORDER BY p.nome";
    
    $stmt = $db->prepare($query);
    if ($categoria_id) {
        $stmt->bindParam(':categoria_id', $categoria_id);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInitials($name) {
    if (empty($name)) return 'US';
    
    $names = explode(' ', $name);
    $initials = '';
    
    if (count($names) >= 2) {
        // Primeira letra do primeiro nome + primeira letra do último nome
        $initials = strtoupper(substr($names[0], 0, 1) . substr($names[count($names)-1], 0, 1));
    } else {
        // Se só tem um nome, pega as duas primeiras letras
        $initials = strtoupper(substr($name, 0, 2));
    }
    
    return $initials;
}
?>