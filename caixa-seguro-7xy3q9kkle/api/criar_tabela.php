<?php
// ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::includes('database.php');

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    echo "Conexão bem-sucedida!<br>";
    
    // SQL para criar a tabela comandas
    $sql = "
    CREATE TABLE IF NOT EXISTS comandas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero_mesa INT NOT NULL,
        items TEXT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        status VARCHAR(20) DEFAULT 'aberta',
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ";
    
    try {
        $conn->exec($sql);
        echo "Tabela 'comandas' criada com sucesso!<br>";
        
        // Verificar se a tabela foi criada
        $stmt = $conn->query("SHOW TABLES LIKE 'comandas'");
        if ($stmt->rowCount() > 0) {
            echo "Tabela verificada e existe no banco de dados!<br>";
        }
    } catch(PDOException $e) {
        echo "Erro ao criar tabela: " . $e->getMessage();
    }
} else {
    echo "Falha na conexão!";
}
?>