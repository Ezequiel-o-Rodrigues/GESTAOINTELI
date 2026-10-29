<?php
echo "=== PROCURANDO DATABASE.PHP ===<br><br>";

$locations = [
    __DIR__ . '/config/database.php',
    __DIR__ . '/includes/database.php', 
    __DIR__ . '/database.php',
    __DIR__ . '/../config/database.php',
    'C:/xampp/htdocs/gestaointeli-jnr/public_html/config/database.php'
];

foreach ($locations as $location) {
    echo "Verificando: <strong>$location</strong> → ";
    if (file_exists($location)) {
        echo "<span style='color: green;'>✅ ENCONTRADO!</span><br>";
        
        // Testar se funciona
        try {
            require_once $location;
            if (class_exists('Database')) {
                $database = new Database();
                $db = $database->getConnection();
                echo "&nbsp;&nbsp;📊 <span style='color: green;'>Classe Database funciona!</span><br>";
            } else {
                echo "&nbsp;&nbsp;❌ <span style='color: red;'>Classe Database NÃO existe</span><br>";
            }
        } catch (Exception $e) {
            echo "&nbsp;&nbsp;❌ <span style='color: red;'>Erro: " . $e->getMessage() . "</span><br>";
        }
    } else {
        echo "<span style='color: red;'>❌ Não encontrado</span><br>";
    }
    echo "<br>";
}

echo "=== ESTRUTURA DO PROJETO ===<br>";
function listDirectory($dir, $level = 0) {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $path = $dir . '/' . $item;
        $indent = str_repeat('&nbsp;&nbsp;', $level);
        
        if (is_dir($path)) {
            echo "$indent📁 $item/<br>";
            if ($level < 3) { // Limita a profundidade
                listDirectory($path, $level + 1);
            }
        } else {
            echo "$indent📄 $item<br>";
        }
    }
}

listDirectory(__DIR__);
?>