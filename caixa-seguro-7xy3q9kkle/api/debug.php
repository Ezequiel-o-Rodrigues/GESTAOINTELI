<?php
// api/debug.php - ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';

echo "=== DEBUG API ===\n";
echo "PHP está funcionando: SIM\n";
echo "Diretório atual: " . __DIR__ . "\n";

// Testar conexão com database.php
$database_path = PathConfig::includes('database.php');
echo "Caminho do database: " . $database_path . "\n";
echo "Database existe: " . (file_exists($database_path) ? 'SIM' : 'NÃO') . "\n";

if (file_exists($database_path)) {
    require_once $database_path;
    try {
        $database = new Database();
        $db = $database->getConnection();
        echo "Conexão com banco: OK\n";
        
        // Testar se tabelas existem
        $tables = ['comandas', 'itens_comanda', 'produtos'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->fetch() ? 'SIM' : 'NÃO';
            echo "Tabela $table: $exists\n";
        }
    } catch (Exception $e) {
        echo "Erro na conexão: " . $e->getMessage() . "\n";
    }
}
?>